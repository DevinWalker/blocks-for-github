<?php

declare(strict_types=1);

namespace GitHubBlock;

class ProfileBlock
{

    public array $attributes;
    private string $accessToken;
    private string $transientKey;
    private $transient;

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes)
    {
        $this->attributes   = $attributes;
        $this->accessToken  = $this->getAccessToken();
        $this->transientKey = $this->getTransientKey();
        $this->transient    = get_transient($this->transientKey);
    }

    private function getAccessToken()
    {
        return $this->accessToken = get_option('blocks_for_github_plugin_personal_token', $this->attributes['apiKeyState']);
    }


    /**
     * Enqueue scripts
     *
     * @return void
     */
    protected function enqueueScripts()
    {
    }

    /**
     * Set transient key based on the individual blocks
     *
     * @return string
     */
    protected function getTransientKey()
    {
        return "blocks_for_github_";
    }

    protected function getHeaders(): array
    {
        error_log( print_r( $this->accessToken, true ), 3, __DIR__ . '/debug_custom.log' );
        return [
            'headers' =>
                [
                    'Authorization' => 'token ' . $this->accessToken,
                ],
        ];
    }

    /**
     * Set transient
     *
     * @param mixed $transient
     *
     * @return void
     */
    protected function setTransient($transient)
    {
        $this->transient = $transient;
    }

    /**
     * Fetch events
     */
    protected function fetchProfile()
    {
        $url     = "https://api.github.com/users/{$this->attributes['profileName']}";
        $request = wp_remote_get($url, $this->getHeaders());

        if (is_wp_error($request)) {
            ob_start();
            include 'src/views/error-wp.php';

            return ob_get_clean();
        }

        $body = wp_remote_retrieve_body($request);

        return json_decode($body);
    }


    protected function fetchRepos()
    {
        // 🔎 Get profile's repo data
        $reposUrl = add_query_arg([
            'q'        => 'user:' . $this->attributes['profileName'],
            'stars'    => '>0',
            'type'     => 'Repositories',
            'per_page' => 5,
        ], 'https://api.github.com/search/repositories');

        $reposRequest = wp_remote_get($reposUrl, $this->getHeaders());

        if (is_wp_error($reposRequest)) :
            ob_start();
            include 'src/template-parts/error-wp.php';

            return ob_get_clean();
        endif;

        $body = wp_remote_retrieve_body($reposRequest);

        return json_decode($body);
    }

    /**
     * 🎆 Render the block.
     *
     * @return false|string
     */
    public function render()
    {
        if (is_admin() && ! $this->accessToken) :
            ob_start();
            include BLOCKS_FOR_GITHUB_DIR . '/src/views/welcome.php';

            return ob_get_clean();
        endif;


        if ( ! $this->transient) {
            $data = $this->fetchProfile();

//            error_log( print_r( $data, true ), 3, __DIR__ . '/debug_custom.log' );
            if (isset($data->message)) {
                if ($data->message === 'Bad credentials') {
                    ob_start();
                    include BLOCKS_FOR_GITHUB_DIR . 'src/views/error-github-bad-creds.php';

                    return ob_get_clean();
                }

                if ($data->message === 'Not Found') {
                    ob_start();
                    include BLOCKS_FOR_GITHUB_DIR . 'src/views/error-github-profile-404.php';

                    return ob_get_clean();
                }
            }

            // TODO: Uncomment before release
            // set_transient($this->transientKey, $this->transient, HOUR_IN_SECONDS);
        }


        // 🔥 Get the profile info set.
        $username      = esc_html($data->login);
        $followerCount = esc_html($data->followers);
        $avatarUrl     = esc_html($data->avatar_url);
        $name          = esc_html($data->name);
        $profileUrl    = esc_html($data->html_url);
        $bio           = esc_html($data->bio);
        $company       = esc_html($data->company);
        $twitterHandle = esc_html($data->twitter_username);
        $location      = esc_html($data->location);
        $website       = esc_html($data->blog);

        $reposData = $this->fetchRepos();

        ob_start(); ?>

        <div class="bfg-profile-wrap" id="bfg-profile-wrap-<?php
        echo $data->id; ?>">
            <div class="bfg-header" style="<?php
            echo ! empty($this->attributes['mediaUrl']) ? 'background-image: url(' . $this->attributes['mediaUrl'] . ')' : 'background-image: url(' . BLOCKS_FOR_GITHUB_URL . 'assets/images/code-placeholder.jpg)'; ?>">
                <div class="bfg-avatar">
                    <img src="<?php
                    echo $avatarUrl; ?>" alt="<?php
                    echo $name; ?>" class="bfg-avatar-url" />
                </div>
            </div>

            <div class="bfg-subheader-content">
                <h3 class="bfg-profile-name"><?php
                    echo $name; ?></h3>
                <a href="<?php
                echo $profileUrl; ?>" class="bfg-follow-me" target="_blank">
                    <span class="bfg-follow-me__inner">
                            <span class="bfg-follow-me__inner--svg">
                              <?php
                              echo file_get_contents(BLOCKS_FOR_GITHUB_DIR . '/assets/images/mark-github.svg'); ?>
                            </span>
                        <?php
                        esc_html_e('Follow', 'blocks-for-github'); ?>
                        <?php
                        echo $username; ?>
                      </span>
                    <span class="bfg-follow-me__count"><?php
                        echo $followerCount; ?></span>
                </a>
            </div>

            <?php
            if ( ! empty($bio) && $this->attributes['showBio']) : ?>
                <div class="bfg-bio-wrap">
                    <p><?php
                        echo $bio; ?></p>
                </div>
            <?php
            endif; ?>

            <?php
            // 🙉 Show meta list only if one or more fields are selected.
            if ( ! empty($this->attributes['showOrg']) || ! empty($this->attributes['showLocation']) || ! empty($this->attributes['showWebsite']) || ! empty($this->attributes['showTwitter'])): ?>
                <ul class="bfg-meta-list">
                    <?php
                    if ( ! empty($location) && $this->attributes['showLocation']) : ?>
                        <li>
                            <a href="https://www.google.com/maps/search/?api=1&query=<?php
                            echo urlencode($location); ?>" target="_blank"><?php
                                echo file_get_contents(BLOCKS_FOR_GITHUB_DIR . '/assets/images/location.svg'); ?><?php
                                echo $location; ?></a>
                        </li>
                    <?php
                    endif; ?>
                    <?php
                    if ( ! empty($company) && $this->attributes['showOrg']) : ?>
                        <li>
                            <?php
                            echo file_get_contents(BLOCKS_FOR_GITHUB_DIR . '/assets/images/building.svg'); ?><?php
                            echo $company; ?>
                        </li>
                    <?php
                    endif; ?>
                    <?php
                    if ( ! empty($website) && $this->attributes['showWebsite']) : ?>
                        <li>
                            <a href="<?php
                            echo $website; ?>" target="_blank"><?php
                                echo file_get_contents(BLOCKS_FOR_GITHUB_DIR . '/assets/images/link.svg'); ?>
                                <?php
                                echo $website; ?></a>
                        </li>
                    <?php
                    endif; ?>
                    <?php
                    if ( ! empty($twitterHandle) && $this->attributes['showTwitter']) : ?>
                        <li>
                            <a href="https://twitter.com/<?php
                            echo $twitterHandle; ?>" target="_blank"><?php
                                echo file_get_contents(BLOCKS_FOR_GITHUB_DIR . '/assets/images/twitter.svg'); ?>
                                <?php
                                echo '@' . $twitterHandle;
                                ?></a>
                        </li>
                    <?php
                    endif; ?>
                </ul>
            <?php
            endif; ?>

            <div class="bfg-bottom-wrap">
                <?php
                if ($reposData->items) : ?>
                    <ol class="bfg-github-list">
                        <?php
                        foreach ($reposData->items as $repo) : ?>
                            <li class="bgf-top-repo">
                                <div class="bfg-top-repo__top">
                                    <a href="<?php
                                    echo $repo->html_url; ?>" class="bfg-top-repo__link" target="_blank"><?php
                                        echo $repo->name; ?></a>
                                    <div class="bfg-top-repo-pill-wrap">
                                        <?php
                                        if ($repo->archived) : ?>
                                            <span class="bfg-top-repo-pill bfg-top-repo-pill--purple"><?php
                                                echo file_get_contents(BLOCKS_FOR_GITHUB_DIR . '/assets/images/archive.svg'); ?><?php
                                                esc_html_e('Archived', 'blocks-for-github');
                                                ?></span>
                                        <?php
                                        endif; ?>
                                        <span class="bfg-top-repo-pill bfg-top-repo-pill--blue"><?php
                                            echo file_get_contents(BLOCKS_FOR_GITHUB_DIR . '/assets/images/fork.svg'); ?><?php
                                            echo $repo->forks; ?></span>
                                        <span class="bfg-top-repo-pill bfg-top-repo-pill--gold"><?php
                                            echo file_get_contents(BLOCKS_FOR_GITHUB_DIR . '/assets/images/star.svg'); ?><?php
                                            echo $repo->stargazers_count;
                                            ?></span>
                                    </div>
                                </div>

                                <?php
                                if ($repo->description) : ?>
                                    <p class="bfg-top-repo__description">
                                        <?php
                                        echo $repo->description; ?>
                                    </p>
                                <?php
                                endif; ?>
                            </li>
                        <?php
                        endforeach; ?>
                    </ol>
                <?php
                endif; ?>
            </div>


        </div>

        <?php
        // Return output
        return ob_get_clean();
    }


}
