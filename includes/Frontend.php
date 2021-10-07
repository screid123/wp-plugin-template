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
		$handle 	  = PluginInfo::get_asset_handle( 'frontend' );
		$script_asset = file_exists( PluginInfo::get_assets_path_base() . 'frontend.asset.php' )
			? include( PluginInfo::get_assets_path_base() . 'frontend.asset.php' )
			: [ 'version' => microtime(), 'dependencies' => [ 'wp-polyfill' ] ];

		if ( file_exists( PluginInfo::get_assets_path_base() . 'frontend.css' ) ) {
			wp_enqueue_style(
				$handle,
				PluginInfo::get_assets_url_base() . 'frontend.css',
				[],
				$script_asset['version']
			);
		}

		if ( file_exists( PluginInfo::get_assets_path_base() . 'frontend.js' ) ) {
			wp_enqueue_script(
				$handle,
				PluginInfo::get_assets_url_base() . 'frontend.js',
				$script_asset['dependencies'],
				$script_asset['version'],
				true
			);
		}
	}

}
