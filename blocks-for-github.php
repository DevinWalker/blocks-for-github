<?php
/**
 * Plugin Name:       Blocks for GitHub
 * Description:       Display your GitHub profile, activity, gists, repos, and more within the WordPress Block Editor, aka Gutenberg.
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Version:           1.0.0
 * Author:            Devin Walker
 * Author URI:        https://devin.org
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       blocks-for-github
 */

use GitHubBlock\Bootstrap;

const BLOCKS_FOR_GITHUB_FILE = __FILE__;
define( 'BLOCKS_FOR_GITHUB_DIR', plugin_dir_path( BLOCKS_FOR_GITHUB_FILE ) );
define( 'BLOCKS_FOR_GITHUB_URL', plugin_dir_url( BLOCKS_FOR_GITHUB_FILE ) );
const BLOCKS_FOR_GITHUB_SCRIPT_ASSET_PATH = BLOCKS_FOR_GITHUB_DIR . '/build/index.asset.php';
define( 'BLOCKS_FOR_GITHUB_SCRIPT_ASSET', require BLOCKS_FOR_GITHUB_SCRIPT_ASSET_PATH );
const BLOCKS_FOR_GITHUB_SCRIPT_NAME = 'blocks-for-github-script';

/**
 * Require WP version 6.5+
 */
register_activation_hook(
    __FILE__,
    function () {
        if ( ! version_compare( $GLOBALS['wp_version'], '6.5', '>=' ) ) {
            wp_die(
                esc_html__( 'Blocks for GitHub requires WordPress version 6.5 or greater.', 'blocks-for-github' ),
                esc_html__( 'Error Activating', 'blocks-for-github' )
            );
        }
    }
);

require_once __DIR__ . '/vendor/autoload.php';

(new Bootstrap())->init();
