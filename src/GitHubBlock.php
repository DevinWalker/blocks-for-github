<?php

declare( strict_types=1 );

namespace GitHubBlock;

class GitHubBlock {

  public $attributes;
  private $accessToken;
  private $transientKey;
  private $transient;

  /**
   * RenderBlocksForEventbriteCard constructor.
   *
   * @param array $attributes
   */
  public function __construct( array $attributes ) {
    $this->attributes   = $attributes;
    $this->accessToken  = $this->getAccessToken();
    $this->transientKey = $this->getTransientKey();
    $this->transient    = get_transient( $this->transientKey );
  }

  private function getAccessToken() {
    return $accessToken = get_option( 'blocks_for_github_plugin_personal_token', $this->attributes['apiKeyState'] );
  }


  /**
   * Enqueue scripts
   *
   * @return void
   */
  protected function enqueueScripts() {
  }

  /**
   * Set transient key based on the individual blocks
   *
   * @return string
   */
  protected function getTransientKey() {
    return "blocks_for_github_";
  }

  /**
   * Set transient
   *
   * @param mixed $transient
   *
   * @return void
   */
  protected function setTransient( $transient ) {
    $this->transient = $transient;
  }

  /**
   * Fetch events
   *
   * @return void
   */
  protected function fetchEvents() {
  }

  public function render() {
      ob_start();
      echo '<p>hello</p>';
      return ob_get_clean();

      if ( ! $this->accessToken ) :
      ob_start(); ?>
      <div id="bfg-info-wrap">
        <div class="bfg-info-wrap-inner">
          <span class="bfg-info-emoji">👋</span>
          <h2><?php esc_html_e( 'Welcome to Blocks for GitHub!', 'blocks-for-github' ); ?></h2>
          <p><?php esc_html_e( 'To begin, please enter your GitHub personal access token in the block\'s setting panel to the right. Don\'t worry, you\'ll only have to do this one time.',
              'blocks-for-github' ); ?></p>
        </div>
      </div>
      <?php
      return ob_get_clean();
    endif;

  }


}
