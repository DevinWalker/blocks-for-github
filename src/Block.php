<?php

declare(strict_types=1);

namespace GitHubBlock;

use DateTime;

class Block
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
        return $this->accessToken = get_option('blocks_for_github_plugin_personal_token', $this->attributes['apiKey']);
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


    protected function fetchProfileRepos()
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
            include 'views/error-wp.php';

            return ob_get_clean();
        endif;

        $body = wp_remote_retrieve_body($reposRequest);

        return json_decode($body);
    }

    protected function fetchRepo()
    {
        $reposRequest = wp_remote_get('https://api.github.com/repos/' . $this->attributes['repoUrl']);

        if (is_wp_error($reposRequest)) :
            ob_start();
            include 'views/error-wp.php';

            return ob_get_clean();
        endif;

        $body = wp_remote_retrieve_body($reposRequest);

        return json_decode($body);
    }

    /**
     * 🎆 Render the block.
     *
     * @return false|string
     * @throws \Exception
     */
    public function render()
    {
        if ($this->attributes['blockType'] === 'repository') {
            $data = $this->fetchRepo();

            return $this->renderRepo($data);
        }


        $data = get_transient($this->transientKey . '_' . $this->attributes['profileName']);

        if (empty($data)) {
            $data = $this->fetchProfile();

            // Set transient for caching purposes.
            set_transient($this->transientKey . '_' . $this->attributes['profileName'], $this->transient, HOUR_IN_SECONDS);
        }

        // 🔥 Get the profile info set.
        $company       = esc_html($data->company);
        $twitterHandle = esc_html($data->twitter_username);
        $location      = esc_html($data->location);
        $website       = esc_html($data->blog);

        $reposData = $this->fetchProfileRepos();

        ob_start(); ?>

        <div class="bfg-wrap" id="bfg-profile-wrap-<?php
        esc_html_e($data->id); ?>">
            <div class="bfg-header" style="<?php
            echo ! empty($this->attributes['mediaUrl']) ? 'background-image: url(' . $this->attributes['mediaUrl'] . ')' : 'background-image: url(' . BLOCKS_FOR_GITHUB_URL . 'assets/images/code-placeholder.jpg)'; ?>">
                <div class="bfg-avatar">
                    <img src="<?php
                    esc_html_e($data->avatar_url); ?>" alt="<?php
                    esc_html_e($data->name); ?>" class="bfg-avatar-url" />
                </div>
            </div>

            <div class="bfg-subheader-content">
                <h3 class="bfg-profile-name"><?php
                    esc_html_e($data->name); ?></h3>
                <a href="<?php
                esc_html_e($data->html_url); ?>" class="bfg-follow-me" target="_blank">
                    <span class="bfg-follow-me__inner">
                            <span class="bfg-follow-me__inner--svg">
                              <?php
                              echo file_get_contents(BLOCKS_FOR_GITHUB_DIR . '/assets/images/mark-github.svg'); ?>
                            </span>
                        <?php
                        esc_html_e('Follow', 'blocks-for-github'); ?>
                        <?php
                        esc_html_e($data->login); ?>
                      </span>
                    <span class="bfg-follow-me__count"><?php
                        esc_html_e(number_format_i18n($data->followers)); ?></span>
                </a>
            </div>

            <?php
            if ( ! empty($data->bio) && $this->attributes['showBio']) : ?>
                <div class="bfg-bio-wrap">
                    <p><?php
                        esc_html_e($data->bio); ?></p>
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

    public function renderRepo($data)
    {
        ob_start(); ?>
        <div class="bfg-wrap">

            <div class="bfg-repo-header">
                <div class="bfg-repo-avatar-wrap">
                    <img class="bfg-avatar" src="<?php
                    esc_html_e($data->owner->avatar_url); ?>" alt="<?php
                    esc_html_e($data->name); ?>" class="bfg-avatar-url" />
                </div>

                <div class="bfg-repo-content">
                    <div class="bfg-repo-header">
                        <div class="bfg-repo-name-wrap">
                            <h3 class="bfg-repo-name">
                                <a href="<?php
                                esc_html_e($data->html_url); ?>" target="_blank" rel="noopener noreferrer"><?php
                                    esc_html_e($data->name); ?></a>
                            </h3>
                            <span class="bfg-repo-byline"><?php
                                echo esc_html__('By', 'blocks-for-github') . ' ' . esc_html__($data->organization->login); ?></span>
                        </div>
                        <a href="<?php
                        esc_html_e($data->html_url); ?>" class="bfg-follow-me" target="_blank">
                            <span class="bfg-follow-me__inner">
                                    <span class="bfg-follow-me__inner--svg">
                                      <?php
                                      echo file_get_contents(BLOCKS_FOR_GITHUB_DIR . '/assets/images/star-filled.svg'); ?>
                                    </span>
                                <?php
                                esc_html_e('Star', 'blocks-for-github'); ?>
                              </span>
                            <span class="bfg-follow-me__count"><?php
                                esc_html_e(number_format_i18n($data->stargazers_count)); ?></span>
                        </a>
                    </div>

                    <p class="bfg-repo-description"><?php
                        esc_html_e($data->description); ?></p>
                </div>

            </div>

            <ul class="bfg-meta-list">
                <li><?php
                    // Last update:
                    $dateTime      = new DateTime($data->updated_at);
                    $formattedDate = $dateTime->format('m-d-Y');
                    echo file_get_contents(BLOCKS_FOR_GITHUB_DIR . '/assets/images/mark-github.svg');
                    echo esc_html__('Last Update', 'blocks-for-github') . ' ' . $formattedDate; ?></li>
                <li><?php
                    // Open issues:
                    echo file_get_contents(BLOCKS_FOR_GITHUB_DIR . '/assets/images/mark-github.svg');
                    echo esc_html__('Open Issues', 'blocks-for-github') . ' ' . esc_html__(number_format_i18n($data->open_issues)); ?></li>
                <li><?php
                    // Subscribers
                    echo file_get_contents(BLOCKS_FOR_GITHUB_DIR . '/assets/images/mark-github.svg');
                    echo esc_html__('Subscribers', 'blocks-for-github') . ' ' . esc_html__(number_format_i18n($data->subscribers_count)); ?></li>
                <li><?php
                    echo file_get_contents(BLOCKS_FOR_GITHUB_DIR . '/assets/images/fork.svg');
                    echo esc_html__('Forks', 'blocks-for-github') . ' ' . esc_html__(number_format_i18n($data->forks)); ?></li>
            </ul>

        </div>
        <?php
        return ob_get_clean();
    }


}
