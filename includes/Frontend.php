<?php
/**
 * Frontend class.
 *
 * @package WP_Plugin_Template
 */

declare( strict_types=1 );

namespace WP_Plugin_Template;

use WP_Plugin_Template\Dependencies\Cedaro\WP\Plugin\AbstractHookProvider;

/**
 * Frontend functionality.
 *
 * @package WP_Plugin_Template
 */
class Frontend extends AbstractHookProvider {

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * @inheritDoc
	 */
	public function register_hooks(): void {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		// Enqueue plugin's frontend assets
		$this->add_action( 'wp_enqueue_scripts', 'load_assets' );
	}

	/**
	 * Load custom scripts and styles.
	 *
	 * @link https://developer.wordpress.org/reference/hooks/wp_enqueue_scripts/
	 */
	public function load_assets(): void {
		$handle 	  = $this->plugin->get_slug() . '-frontend';
		$script_asset = file_exists( $this->plugin->get_path( '/assets/frontend.asset.php' ) )
			? include( $this->plugin->get_path( '/assets/frontend.asset.php' ) )
			: [ 'version' => microtime(), 'dependencies' => [ 'wp-polyfill' ] ];

		if ( file_exists( $this->plugin->get_path( '/assets/frontend.css' ) ) ) {
			wp_enqueue_style(
				$handle,
				$this->plugin->get_url( '/assets/frontend.css' ),
				[],
				$script_asset['version']
			);
		}

		if ( file_exists( $this->plugin->get_path( '/assets/frontend.js' ) ) ) {
			wp_enqueue_script(
				$handle,
				$this->plugin->get_url( '/assets/frontend.js' ),
				$script_asset['dependencies'],
				$script_asset['version'],
				true
			);
		}
	}

}
