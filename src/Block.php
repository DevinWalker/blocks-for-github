<?php
declare( strict_types=1 );

namespace GitHubBlock;

use DateTime;
use Exception;

class Block {

    public array $attributes;

    public function __construct( array $attributes ) {
        $this->attributes = self::sanitize_attributes( $attributes );
    }

    /**
     * Sanitize block attributes before render and GitHub API requests.
     *
     * @param array<string, mixed> $attributes Raw block attributes.
     * @return array<string, mixed>
     */
    public static function sanitize_attributes( array $attributes ): array {
        $block_type = $attributes['blockType'] ?? 'repository';
        $attributes['blockType'] = in_array( $block_type, [ 'repository', 'profile' ], true )
            ? $block_type
            : 'repository';

        $attributes['profileName'] = self::sanitize_github_username(
            (string) ( $attributes['profileName'] ?? 'Octocat' )
        );
        $attributes['repoUrl'] = self::sanitize_repo_path(
            (string) ( $attributes['repoUrl'] ?? 'DevinWalker/blocks-for-github' )
        );
        $attributes['customTitle'] = sanitize_text_field( (string) ( $attributes['customTitle'] ?? '' ) );
        $attributes['mediaUrl']    = esc_url_raw( (string) ( $attributes['mediaUrl'] ?? '' ) );
        $attributes['mediaId']     = absint( $attributes['mediaId'] ?? 0 );

        foreach (
            [
                'showTags',
                'showForks',
                'showSubscribers',
                'showOpenIssues',
                'showLastUpdate',
                'showBio',
                'showLocation',
                'showOrg',
                'showWebsite',
                'showTwitter',
                'preview',
            ] as $bool_key
        ) {
            $attributes[ $bool_key ] = ! empty( $attributes[ $bool_key ] );
        }

        return $attributes;
    }

    protected static function sanitize_github_username( string $username ): string {
        $username = sanitize_text_field( $username );
        if ( preg_match( '/^[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,37}[a-zA-Z0-9])?$/', $username ) ) {
            return $username;
        }

        return 'Octocat';
    }

    protected static function sanitize_repo_path( string $repo_path ): string {
        $repo_path = sanitize_text_field( $repo_path );
        if ( preg_match( '/^[a-zA-Z0-9_.-]+\/[a-zA-Z0-9_.-]+$/', $repo_path ) ) {
            return $repo_path;
        }

        return 'DevinWalker/blocks-for-github';
    }

    /**
     * @param string $suffix Transient key suffix.
     */
    protected function transient_key( string $suffix ): string {
        return 'blocks_for_github_' . md5( $suffix );
    }

    /**
     * @param mixed $data Fetch result.
     */
    protected function is_api_error( $data ): bool {
        return is_string( $data ) && str_starts_with( $data, 'API error occurred:' );
    }

    protected function fetchData( string $url, string $key_suffix = '' ) {
        $key  = $this->transient_key( $key_suffix );
        $data = get_transient( $key );

        if ( empty( $data ) ) {
            $response = wp_remote_get(
                $url,
                [
                    'timeout' => 10,
                    'headers' => [
                        'Accept'     => 'application/vnd.github+json',
                        'User-Agent' => 'WordPress Blocks-for-GitHub/1.0.0',
                    ],
                ]
            );

            if ( is_wp_error( $response ) ) {
                return 'API error occurred: ' . $response->get_error_message();
            }

            $http_code = wp_remote_retrieve_response_code( $response );

            if ( $http_code >= 400 ) {
                delete_transient( $key );

                $response_body = wp_remote_retrieve_body( $response );
                $error_message = __( 'Unknown error occurred', 'blocks-for-github' );
                if ( ! empty( $response_body ) ) {
                    $response_data = json_decode( $response_body );
                    $error_message = $response_data->message ?? $response_body;
                }

                return 'API error occurred: ' . sanitize_text_field( (string) $error_message );
            }

            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body );
            if ( ! is_object( $data ) ) {
                return 'API error occurred: ' . __( 'Invalid response from GitHub.', 'blocks-for-github' );
            }

            set_transient( $key, $data, 24 * HOUR_IN_SECONDS );
        }

        return $data;
    }

    protected function fetchProfileRepos() {
        $repos_url = add_query_arg(
            [
                'q'        => 'user:' . $this->attributes['profileName'],
                'stars'    => '>=0',
                'type'     => 'Repositories',
                'per_page' => 5,
            ],
            'https://api.github.com/search/repositories'
        );

        return $this->fetchData(
            $repos_url,
            $this->attributes['profileName'] . '_repos'
        );
    }

    /**
     * @throws Exception
     */
    public function render() {
        if ( $this->attributes['blockType'] === 'repository' ) {
            $data = $this->fetchData(
                'https://api.github.com/repos/' . $this->attributes['repoUrl'],
                $this->attributes['repoUrl']
            );

            return $this->get_output_or_error( $data, $this->renderRepo( $data ) );
        }

        if ( $this->attributes['blockType'] === 'profile' ) {
            $data = $this->fetchData(
                'https://api.github.com/users/' . $this->attributes['profileName'],
                $this->attributes['profileName']
            );

            return $this->get_output_or_error( $data, $this->renderProfile( $data ) );
        }

        return false;
    }

    /**
     * @param mixed  $data   API payload or error string.
     * @param string $output Rendered block HTML.
     */
    protected function get_output_or_error( $data, $output ) {
        if ( $this->is_api_error( $data ) ) {
            ob_start();
            ?>
            <div class="bfg-notice-wrap">
                <div class="bfg-notice-inner">
                    <span class="bfg-info-emoji">👾</span>
                    <h2><?php esc_html_e( 'GitHub API Error', 'blocks-for-github' ); ?></h2>
                    <p><?php esc_html_e( 'Hmm, GitHub returned a not found error.', 'blocks-for-github' ); ?></p>
                    <div class="bfg-error">
                        <?php echo esc_html( $data ); ?>
                    </div>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }

        return $output;
    }

    /**
     * @throws Exception
     */
    public function renderRepo( $data ) {
        ob_start();
        ?>
        <div class="bfg-wrap bfg-repo" id="bfg-wrap-<?php echo esc_attr( (string) $data->id ); ?>">
            <div class="bfg-repo-header bfg-grid-container">
                <div class="bfg-repo-avatar-wrap">
                    <img class="bfg-avatar" src="<?php echo esc_url( $data->owner->avatar_url ); ?>"
                         alt="<?php echo esc_attr( $data->name ); ?>" />
                </div>
                <div class="bfg-repo-content">
                    <div class="bfg-repo-name-wrap">
                        <h3 class="bfg-repo-name">
                            <a href="<?php echo esc_url( $data->html_url ); ?>" target="_blank"
                               rel="noopener noreferrer"><?php
                                if ( ! empty( $this->attributes['customTitle'] ) ) {
                                    echo esc_html( $this->attributes['customTitle'] );
                                } else {
                                    echo esc_html( $data->name );
                                }
                                ?></a>
                        </h3>
                        <span class="bfg-repo-byline"><?php esc_html_e( 'By', 'blocks-for-github' ); ?>
                            <a href="<?php echo esc_url( $data->owner->html_url ); ?>" target="_blank"
                               rel="noopener noreferrer"><?php echo esc_html( $data->owner->login ); ?></a>
                           </span>
                    </div>
                    <div class="bfg-repo-follow-wrap">
                        <a href="<?php echo esc_url( $data->html_url ); ?>" class="bfg-follow-me" target="_blank"
                           rel="noopener noreferrer">
                            <span class="bfg-follow-me__inner">
                                    <span class="bfg-follow-me__inner--svg">
                                      <?php echo file_get_contents( BLOCKS_FOR_GITHUB_DIR . '/assets/images/star-filled.svg' ); ?>
                                    </span>
                                <?php esc_html_e( 'Star', 'blocks-for-github' ); ?>
                              </span>
                            <span class="bfg-follow-me__count"><?php echo esc_html( number_format_i18n( (int) $data->stargazers_count ) ); ?></span>
                        </a>
                    </div>
                </div>
            </div>

            <?php if ( ! empty( $data->description ) ) : ?>
                <div class="bfg-repo-description-wrap">
                    <p class="bfg-repo-description"><?php echo esc_html( $data->description ); ?></p>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $data->topics ) && $this->attributes['showTags'] ) : ?>
                <ul class="bfg-tag-list">
                    <?php foreach ( $data->topics as $topic ) : ?>
                        <li class="bfg-tag-list--item bfg-top-repo-pill bfg-top-repo-pill--blue"><?php echo esc_html( $topic ); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <ul class="bfg-meta-list">
                <?php if ( isset( $data->updated_at ) && $this->attributes['showLastUpdate'] ) : ?>
                    <li class="bfg-meta-list--updated"><?php
                        $date_time      = new DateTime( $data->updated_at );
                        $formatted_date = $date_time->format( 'm-d-Y' );
                        echo file_get_contents( BLOCKS_FOR_GITHUB_DIR . '/assets/images/calendar.svg' );
                        echo esc_html__( 'Last Update', 'blocks-for-github' ) . ' ' . esc_html( $formatted_date );
                        ?></li>
                <?php endif; ?>
                <?php if ( isset( $data->open_issues ) && $this->attributes['showOpenIssues'] ) : ?>
                    <li class="bfg-meta-list--issues"><?php
                        echo file_get_contents( BLOCKS_FOR_GITHUB_DIR . '/assets/images/flag.svg' );
                        echo esc_html__( 'Open Issues', 'blocks-for-github' ) . ' ' . esc_html( number_format_i18n( (int) $data->open_issues ) );
                        ?></li>
                <?php endif; ?>
                <?php if ( isset( $data->subscribers_count ) && $this->attributes['showSubscribers'] ) : ?>
                    <li class="bfg-meta-list--subscribers"><?php
                        echo file_get_contents( BLOCKS_FOR_GITHUB_DIR . '/assets/images/mark-github.svg' );
                        echo esc_html__( 'Subscribers', 'blocks-for-github' ) . ' ' . esc_html( number_format_i18n( (int) $data->subscribers_count ) );
                        ?></li>
                <?php endif; ?>
                <?php if ( isset( $data->forks ) && $this->attributes['showForks'] ) : ?>
                    <li class="bfg-meta-list--forks"><?php
                        echo file_get_contents( BLOCKS_FOR_GITHUB_DIR . '/assets/images/fork.svg' );
                        echo esc_html__( 'Forks', 'blocks-for-github' ) . ' ' . esc_html( number_format_i18n( (int) $data->forks ) );
                        ?></li>
                <?php endif; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * @param object $data GitHub user payload.
     * @return false|string
     */
    public function renderProfile( $data ) {
        $repos_data = $this->fetchProfileRepos();

        $header_image = ! empty( $this->attributes['mediaUrl'] )
            ? esc_url( $this->attributes['mediaUrl'] )
            : esc_url( BLOCKS_FOR_GITHUB_URL . 'assets/images/code-placeholder.jpg' );

        ob_start();
        ?>

        <div class="bfg-wrap" id="bfg-wrap-<?php echo esc_attr( (string) $data->id ); ?>">
            <div class="bfg-header" style="<?php echo esc_attr( 'background-image: url(' . $header_image . ')' ); ?>">
                <div class="bfg-avatar">
                    <img src="<?php echo esc_url( $data->avatar_url ); ?>"
                         alt="<?php echo esc_attr( $data->login ); ?>"
                         class="bfg-avatar-url" />
                </div>
            </div>

            <div class="bfg-subheader-content">
                <h3 class="bfg-profile-name">
                    <?php
                    if ( ! empty( $this->attributes['customTitle'] ) ) {
                        echo esc_html( $this->attributes['customTitle'] );
                    } else {
                        echo esc_html( $data->name );
                    }
                    ?>
                </h3>
                <a href="<?php echo esc_url( $data->html_url ); ?>" class="bfg-follow-me" target="_blank"
                   rel="noopener noreferrer">
                    <span class="bfg-follow-me__inner">
                            <span class="bfg-follow-me__inner--svg">
                              <?php echo file_get_contents( BLOCKS_FOR_GITHUB_DIR . '/assets/images/mark-github.svg' ); ?>
                            </span>
                        <?php esc_html_e( 'Follow', 'blocks-for-github' ); ?>
                        <?php echo esc_html( $data->login ); ?>
                      </span>
                    <span class="bfg-follow-me__count"><?php echo esc_html( number_format_i18n( (int) $data->followers ) ); ?></span>
                </a>
            </div>

            <?php if ( ! empty( $data->bio ) && $this->attributes['showBio'] ) : ?>
                <div class="bfg-bio-wrap">
                    <p><?php echo esc_html( $data->bio ); ?></p>
                </div>
            <?php endif; ?>

            <?php
            if (
                ! empty( $this->attributes['showOrg'] )
                || ! empty( $this->attributes['showLocation'] )
                || ! empty( $this->attributes['showWebsite'] )
                || ! empty( $this->attributes['showTwitter'] )
            ) :
                ?>
                <ul class="bfg-meta-list">
                    <?php if ( ! empty( $data->location ) && $this->attributes['showLocation'] ) : ?>
                        <li class="bfg-meta-list--location">
                            <a href="<?php echo esc_url( 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( (string) $data->location ) ); ?>"
                               target="_blank" rel="noopener noreferrer"><?php
                                echo file_get_contents( BLOCKS_FOR_GITHUB_DIR . '/assets/images/location.svg' );
                                echo esc_html( $data->location );
                                ?></a>
                        </li>
                    <?php endif; ?>
                    <?php if ( ! empty( $data->company ) && $this->attributes['showOrg'] ) : ?>
                        <li class="bfg-meta-list--company">
                            <?php
                            echo file_get_contents( BLOCKS_FOR_GITHUB_DIR . '/assets/images/building.svg' );
                            echo esc_html( $data->company );
                            ?>
                        </li>
                    <?php endif; ?>
                    <?php if ( ! empty( $data->blog ) && $this->attributes['showWebsite'] ) : ?>
                        <li class="bfg-meta-list--website">
                            <a href="<?php echo esc_url( $data->blog ); ?>"
                               target="_blank" rel="noopener noreferrer"><?php
                                echo file_get_contents( BLOCKS_FOR_GITHUB_DIR . '/assets/images/link.svg' );
                                echo esc_html( $data->blog );
                                ?></a>
                        </li>
                    <?php endif; ?>
                    <?php if ( ! empty( $data->twitter_username ) && $this->attributes['showTwitter'] ) : ?>
                        <li class="bfg-meta-list--twitter">
                            <a href="<?php echo esc_url( 'https://twitter.com/' . $data->twitter_username ); ?>"
                               target="_blank" rel="noopener noreferrer"><?php
                                echo file_get_contents( BLOCKS_FOR_GITHUB_DIR . '/assets/images/twitter.svg' );
                                echo esc_html( '@' . $data->twitter_username );
                                ?></a>
                        </li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>

            <div class="bfg-bottom-wrap">
                <?php if ( is_object( $repos_data ) && ! empty( $repos_data->items ) ) : ?>
                    <ol class="bfg-github-list">
                        <?php foreach ( $repos_data->items as $repo ) : ?>
                            <li class="bgf-top-repo">
                                <div class="bfg-top-repo__top">
                                    <a href="<?php echo esc_url( $repo->html_url ); ?>" class="bfg-top-repo__link"
                                       target="_blank" rel="noopener noreferrer"><?php echo esc_html( $repo->name ); ?></a>
                                    <div class="bfg-top-repo-pill-wrap">
                                        <?php if ( $repo->archived ) : ?>
                                            <span class="bfg-top-repo-pill bfg-top-repo-pill--purple"><?php
                                                echo file_get_contents( BLOCKS_FOR_GITHUB_DIR . '/assets/images/archive.svg' );
                                                esc_html_e( 'Archived', 'blocks-for-github' );
                                                ?></span>
                                        <?php endif; ?>
                                        <span class="bfg-top-repo-pill bfg-top-repo-pill--blue"><?php
                                            echo file_get_contents( BLOCKS_FOR_GITHUB_DIR . '/assets/images/fork.svg' );
                                            echo esc_html( number_format_i18n( (int) $repo->forks ) );
                                            ?></span>
                                        <span class="bfg-top-repo-pill bfg-top-repo-pill--gold"><?php
                                            echo file_get_contents( BLOCKS_FOR_GITHUB_DIR . '/assets/images/star.svg' );
                                            echo esc_html( number_format_i18n( (int) $repo->stargazers_count ) );
                                            ?></span>
                                    </div>
                                </div>

                                <?php if ( $repo->description ) : ?>
                                    <p class="bfg-top-repo__description">
                                        <?php echo esc_html( $repo->description ); ?>
                                    </p>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>
        </div>

        <?php
        return ob_get_clean();
    }
}
