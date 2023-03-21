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

    protected function fetchData(string $url, string $keySuffix = '')
    {
        $key  = $this->transientKey . $keySuffix;
        $data = get_transient($key);

        if (empty($data)) {
            $response = wp_remote_get($url);
            if (is_wp_error($response)) {
                ob_start(); ?>
                <div class="bfg-notice-wrap">
                    <div class="bfg-notice-inner">
                        <span class="bfg-info-emoji">🤷‍</span>
                        <h2><?php esc_html_e('WP Error', 'blocks-for-github'); ?></h2>
                        <p><?php esc_html_e('A WordPress error occurred when trying to remotely call the GitHub API.', 'blocks-for-github'); ?></p>
                    </div>
                </div
                <?php return ob_get_clean();
            }

            $http_code = wp_remote_retrieve_response_code($response);

            // Ensure no API errors.
            if ($http_code >= 400) {
                // Delete the transient if an error occurred.
                delete_transient($key);

                // Parse the error message from the response body.
                $response_body = wp_remote_retrieve_body($response);
                $error_message = __('Unknown error occurred', 'blocks-for-github');
                if ( ! empty($response_body)) {
                    $response_data = json_decode($response_body);
                    $error_message = $response_data->message ?? $response_body;
                }

                return "API error occurred: $error_message";
            } else {
                // API request went through, so we can save the data.
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body);
                set_transient($key, $data, 24 * HOUR_IN_SECONDS);
            }
        }

        return $data;
    }

    protected function fetchProfileRepos()
    {
        $reposUrl = add_query_arg([
            'q'        => 'user:' . $this->attributes['profileName'],
            'stars'    => '>=0',
            'type'     => 'Repositories',
            'per_page' => 5,
        ], 'https://api.github.com/search/repositories');

        return $this->fetchData($reposUrl, $this->attributes['profileName'] . '_repos');
    }

    /**
     * @throws \Exception
     */
    public function render()
    {
        if ($this->attributes['blockType'] === 'repository') {
            $data = $this->fetchData('https://api.github.com/repos/' . $this->attributes['repoUrl'], $this->attributes['repoUrl']);

            return $this->getOutputOrError($data, $this->renderRepo($data));
        }

        if ($this->attributes['blockType'] === 'profile') {
            $data = $this->fetchData("https://api.github.com/users/{$this->attributes['profileName']}", $this->attributes['profileName']);

            return $this->getOutputOrError($data, $this->renderProfile($data));
        }
    }

    protected function getOutputOrError($data, $output)
    {
        if (is_string($data) && strpos($data, 'error')) {
            // Output error message if an error occurred during the API request.
            ob_start(); ?>
            <div class="bfg-notice-wrap">
                <div class="bfg-notice-inner">
                    <span class="bfg-info-emoji">👾</span>
                    <h2><?php esc_html_e('GitHub API Error', 'blocks-for-github'); ?></h2>
                    <p><?php esc_html_e('Hmm, GitHub returned a not found error.', 'blocks-for-github'); ?></p>
                    <div class="bfg-error">
                        <?php esc_html_e($data); ?>
                    </div>
                </div>
            </div>

            <?php
            return ob_get_clean();
        }

        return $output;
    }

    /**
     * @throws \Exception
     */
    public function renderRepo($data)
    {
        ob_start(); ?>
        <div class="bfg-wrap bfg-repo" id="bfg-wrap-<?php esc_html_e($data->id); ?>">
            <div class="bfg-repo-header bfg-grid-container">
                <div class="bfg-repo-avatar-wrap">
                    <img class="bfg-avatar" src="<?php esc_html_e($data->owner->avatar_url); ?>" alt="<?php esc_html_e($data->name); ?>" />
                </div>
                <div class="bfg-repo-content">
                    <div class="bfg-repo-name-wrap">
                        <h3 class="bfg-repo-name">
                            <a href="<?php esc_html_e($data->html_url); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e($data->name); ?></a>
                        </h3>
                        <span class="bfg-repo-byline"><?php esc_html_e('By', 'blocks-for-github'); ?>
                            <a href="<?php esc_html_e($data->owner->html_url); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e($data->owner->login); ?></a>
                           </span>
                    </div>
                    <div class="bfg-repo-follow-wrap">
                        <a href="<?php esc_html_e($data->html_url); ?>" class="bfg-follow-me" target="_blank">
                            <span class="bfg-follow-me__inner">
                                    <span class="bfg-follow-me__inner--svg">
                                      <?php echo file_get_contents(BLOCKS_FOR_GITHUB_DIR . '/assets/images/star-filled.svg'); ?>
                                    </span>
                                <?php esc_html_e('Star', 'blocks-for-github'); ?>
                              </span>
                            <span class="bfg-follow-me__count"><?php esc_html_e(number_format_i18n($data->stargazers_count)); ?></span>
                        </a>
                    </div>
                </div>
            </div>

            <?php
            if ( ! empty($data->description)) : ?>
                <div class="bfg-repo-description-wrap">
                    <p class="bfg-repo-description"><?php esc_html_e($data->description); ?></p>
                </div>
            <?php endif; ?>

            <?php
            if ( ! empty($data->topics) && $this->attributes['showTags']): ?>
                <ul class="bfg-tag-list">
                    <?php
                    // loop through and output html
                    foreach ($data->topics as $topic) : ?>
                        <li class="bfg-tag-list--item bfg-top-repo-pill bfg-top-repo-pill--blue"><?php
                            esc_html_e($topic); ?></li>
                    <?php
                    endforeach;
                    ?>
                </ul>
            <?php
            endif; ?>

            <ul class="bfg-meta-list">
                <?php
                if (isset($data->updated_at) && $this->attributes['showLastUpdate']): ?>
                    <li class="bfg-meta-list--updated"><?php
                        // Last update:
                        $dateTime      = new DateTime($data->updated_at);
                        $formattedDate = $dateTime->format('m-d-Y');
                        echo file_get_contents(BLOCKS_FOR_GITHUB_DIR . '/assets/images/calendar.svg');
                        echo esc_html__('Last Update', 'blocks-for-github') . ' ' . $formattedDate; ?></li>
                <?php
                endif; ?>
                <?php
                if (isset($data->open_issues) && $this->attributes['showOpenIssues']): ?>
                    <li class="bfg-meta-list--issues"><?php
                        // Open issues:
                        echo file_get_contents(BLOCKS_FOR_GITHUB_DIR . '/assets/images/flag.svg');
                        echo esc_html__('Open Issues', 'blocks-for-github') . ' ' . esc_html__(number_format_i18n($data->open_issues)); ?></li>
                <?php
                endif; ?>
                <?php
                if (isset($data->subscribers_count) && $this->attributes['showSubscribers']): ?>
                    <li class="bfg-meta-list--subscribers"><?php
                        // Subscribers
                        echo file_get_contents(BLOCKS_FOR_GITHUB_DIR . '/assets/images/mark-github.svg');
                        echo esc_html__('Subscribers', 'blocks-for-github') . ' ' . esc_html__(number_format_i18n($data->subscribers_count)); ?></li>
                <?php
                endif; ?>
                <?php
                if (isset($data->forks) && $this->attributes['showForks']): ?>
                    <li class="bfg-meta-list--forks"><?php
                        echo file_get_contents(BLOCKS_FOR_GITHUB_DIR . '/assets/images/fork.svg');
                        echo esc_html__('Forks', 'blocks-for-github') . ' ' . esc_html__(number_format_i18n($data->forks)); ?></li>
                <?php
                endif; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the GitHub Profile output.
     *
     * @param $data
     *
     * @return false|string
     */
    public function renderProfile($data)
    {
        $reposData = $this->fetchProfileRepos();

        ob_start(); ?>

        <div class="bfg-wrap" id="bfg-wrap-<?php esc_html_e($data->id); ?>">
            <div class="bfg-header" style="<?php
            echo ! empty($this->attributes['mediaUrl']) ? 'background-image: url(' . $this->attributes['mediaUrl'] . ')' : 'background-image: url(' . BLOCKS_FOR_GITHUB_URL . 'assets/images/code-placeholder.jpg)'; ?>">
                <div class="bfg-avatar">
                    <img src="<?php esc_html_e($data->avatar_url); ?>" alt="<?php esc_html_e($data->name); ?>" class="bfg-avatar-url" />
                </div>
            </div>

            <div class="bfg-subheader-content">
                <h3 class="bfg-profile-name"><?php esc_html_e($data->name); ?></h3>
                <a href="<?php esc_html_e($data->html_url); ?>" class="bfg-follow-me" target="_blank">
                    <span class="bfg-follow-me__inner">
                            <span class="bfg-follow-me__inner--svg">
                              <?php echo file_get_contents(BLOCKS_FOR_GITHUB_DIR . '/assets/images/mark-github.svg'); ?>
                            </span>
                        <?php esc_html_e('Follow', 'blocks-for-github'); ?>
                        <?php esc_html_e($data->login); ?>
                      </span>
                    <span class="bfg-follow-me__count"><?php esc_html_e(number_format_i18n($data->followers)); ?></span>
                </a>
            </div>

            <?php if ( ! empty($data->bio) && $this->attributes['showBio']) : ?>
                <div class="bfg-bio-wrap">
                    <p><?php esc_html_e($data->bio); ?></p>
                </div>
            <?php endif; ?>

            <?php
            // 🙉 Show meta list only if one or more fields are selected.
            if ( ! empty($this->attributes['showOrg']) || ! empty($this->attributes['showLocation']) || ! empty($this->attributes['showWebsite']) || ! empty($this->attributes['showTwitter'])): ?>
                <ul class="bfg-meta-list">
                    <?php if ( ! empty($data->location) && $this->attributes['showLocation']) : ?>
                        <li class="bfg-meta-list--location">
                            <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($data->location); ?>" target="_blank"><?php echo file_get_contents(
                                    BLOCKS_FOR_GITHUB_DIR . '/assets/images/location.svg'
                                ); ?><?php esc_html_e($data->location); ?></a>
                        </li>
                    <?php endif; ?>
                    <?php if ( ! empty($data->company) && $this->attributes['showOrg']) : ?>
                        <li class="bfg-meta-list--company">
                            <?php echo file_get_contents(BLOCKS_FOR_GITHUB_DIR . '/assets/images/building.svg'); ?><?php esc_html_e($data->company); ?>
                        </li>
                    <?php endif; ?>
                    <?php if ( ! empty($data->blog) && $this->attributes['showWebsite']) : ?>
                        <li class="bfg-meta-list--website">
                            <a href="<?php esc_html_e($data->blog); ?>" target="_blank"><?php echo file_get_contents(BLOCKS_FOR_GITHUB_DIR . '/assets/images/link.svg'); ?>
                                <?php esc_html_e($data->blog); ?></a>
                        </li>
                    <?php endif; ?>
                    <?php if ( ! empty($data->twitter_username) && $this->attributes['showTwitter']) : ?>
                        <li class="bfg-meta-list--twitter">
                            <a href="https://twitter.com/<?php esc_html_e($data->twitter_username); ?>" target="_blank"><?php echo file_get_contents(
                                    BLOCKS_FOR_GITHUB_DIR . '/assets/images/twitter.svg'
                                ); ?><?php echo '@' . esc_html__($data->twitter_username); ?></a>
                        </li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>

            <div class="bfg-bottom-wrap">
                <?php if ($reposData->items) : ?>
                    <ol class="bfg-github-list">
                        <?php foreach ($reposData->items as $repo) : ?>
                            <li class="bgf-top-repo">
                                <div class="bfg-top-repo__top">
                                    <a href="<?php echo $repo->html_url; ?>" class="bfg-top-repo__link" target="_blank"><?php echo $repo->name; ?></a>
                                    <div class="bfg-top-repo-pill-wrap">
                                        <?php if ($repo->archived) : ?>
                                            <span class="bfg-top-repo-pill bfg-top-repo-pill--purple"><?php echo file_get_contents(
                                                    BLOCKS_FOR_GITHUB_DIR . '/assets/images/archive.svg'
                                                ); ?><?php esc_html_e('Archived', 'blocks-for-github'); ?></span>
                                        <?php endif; ?>
                                        <span class="bfg-top-repo-pill bfg-top-repo-pill--blue"><?php echo file_get_contents(
                                                BLOCKS_FOR_GITHUB_DIR . '/assets/images/fork.svg'
                                            ); ?><?php esc_html_e($repo->forks); ?></span>
                                        <span class="bfg-top-repo-pill bfg-top-repo-pill--gold"><?php echo file_get_contents(
                                                BLOCKS_FOR_GITHUB_DIR . '/assets/images/star.svg'
                                            ); ?><?php esc_html_e($repo->stargazers_count); ?></span>
                                    </div>
                                </div>

                                <?php if ($repo->description) : ?>
                                    <p class="bfg-top-repo__description">
                                        <?php echo $repo->description; ?>
                                    </p>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>
        </div>

        <?php
        // Return output
        return ob_get_clean();
    }

}
