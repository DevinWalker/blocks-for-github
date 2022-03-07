<?php

declare(strict_types=1);

namespace GitHubBlock;

class Bootstrap
{

    public function init(): void
    {
        $this->registerHooks();
    }

    private function registerHooks(): void
    {
        add_action('init', [$this, 'registerBlock']);
        add_action('init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'addAdminLocalizations']);
    }

    public function registerSettings(): void
    {
        register_setting(
            'blocks_for_github_plugin_settings',
            'blocks_for_github_plugin_personal_token',
            [
                'default' => '',
                'show_in_rest' => true,
                'type' => 'string',
            ]
        );
    }

    public function registerBlock(): void
    {

        wp_register_script(
            BLOCKS_FOR_GITHUB_SCRIPT_NAME,
            plugins_url('build/index.js', BLOCKS_FOR_GITHUB_FILE),
            BLOCKS_FOR_GITHUB_SCRIPT_ASSET['dependencies'],
            BLOCKS_FOR_GITHUB_SCRIPT_ASSET['version']
        );

        wp_set_script_translations(BLOCKS_FOR_GITHUB_SCRIPT_NAME, 'blocks-for-github');

        register_block_type(BLOCKS_FOR_GITHUB_DIR, [
                'render_callback' => [$this, 'blockRenderCallback'],
            ]
        );
    }

    public function blockRenderCallback($attributes)
    {
        $block = new ProfileBlock($attributes);
        return $block->render();
    }

    public function addAdminLocalizations(): void
    {
        wp_localize_script(
            'blocks-for-github-profile-editor-script',
            'bfgPreviews',
            array(
                'profile_preview' => BLOCKS_FOR_GITHUB_URL . 'assets/images/profile-block-preview.jpg',
            )
        );
    }

}
