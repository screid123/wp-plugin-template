<?php
/**
 * Admin class.
 *
 * @package WP_Plugin_Template
 */

declare( strict_types=1 );

namespace WP_Plugin_Template;

use WP_Plugin_Template\Dependencies\Cedaro\WP\Plugin\AbstractHookProvider;

/**
 * Admin functionality.
 *
 * @package WP_Plugin_Template
 */
class Admin extends AbstractHookProvider {

	/**
	 * @inheritDoc
	 */
	public function register_hooks(): void {
		// Don't load if not in the Admin.
		if ( ! is_admin() ) {
			return;
		}

		// Plugin action links.
		$this->add_filter( 'plugin_action_links_' . PluginInfo::plugin_basename(), 'customize_action_links' );

		// Admin menu.
		$this->add_action( 'admin_menu', 'add_plugin_admin_menu' );
	}

	/**
	 * Load custom scripts and styles.
	 *
	 * @link https://developer.wordpress.org/reference/hooks/admin_enqueue_scripts/
	 */
	public function load_assets(): void {
		$handle       = PluginInfo::get_asset_handle( 'admin' );
		$script_asset = file_exists( PluginInfo::get_assets_path_base() . 'admin.asset.php' )
			? include( PluginInfo::get_assets_path_base() . 'admin.asset.php' )
			: [ 'version' => microtime(), 'dependencies' => [ 'wp-polyfill' ] ];

		if ( file_exists( PluginInfo::get_assets_path_base() . 'admin.css' ) ) {
			wp_enqueue_style(
				$handle,
				PluginInfo::get_assets_url_base() . 'admin.css',
				[],
				$script_asset['version']
			);
		}

		if ( file_exists( PluginInfo::get_assets_path_base() . 'admin.js' ) ) {
			wp_enqueue_script(
				$handle,
				PluginInfo::get_assets_url_base() . 'admin.js',
				$script_asset['dependencies'],
				$script_asset['version'],
				true
			);
		}

		/**
		 * Use `wp_localize_script` to add info for REST calls, e.g. - base URL, nonce, environment, etc.
		 * This creates a global on the page using a camelCased version of the plugin slug prefixed with an underscore.
		 * Example: wp-plugin-template -> _wpPluginTemplate
		 */
		$object_name = '_' . lcfirst( str_replace( ' ', '', ucwords( strtr( PluginInfo::plugin_slug(), '_-', '  ') ) ) );
		wp_localize_script( $handle, $object_name, [
			'env'    	=> constant( 'PANTHEON_ENVIRONMENT' ) ?? 'live',
			'baseUrl'	=> esc_url( get_rest_url( get_current_network_id() ) ),
			'nonce'  	=> wp_create_nonce( 'wp_rest' ),
			// More?
		] );
	}

	/**
	 * Add Settings link within Plugins List page.
	 *
	 * @param array $links
	 *
	 * @return array
	 */
	public function customize_action_links( array $links ): array {
		$links[] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=' . $this->get_settings_page_slug() ) ),
			esc_html__( 'Settings', PluginInfo::plugin_text_domain() )
		);

		return $links;
	}

	/**
	 * Add the Settings page to the WP Admin menu.
	 */
	public function add_plugin_admin_menu(): void {
		$hook_suffix = add_options_page(
			PluginInfo::get_plugin_display_name(),
			PluginInfo::get_plugin_display_name(),
			$this->required_capability(),
			$this->get_settings_page_slug(),
			[ $this, 'settings_page' ]
		);

		// Empty if insufficient permissions. We don't want our data put to page source in that case (but the action
		// would not fire successfully anyway).
		if ( ! empty( $hook_suffix ) ) {
			add_action( "admin_print_scripts-{$hook_suffix}", [ $this, 'load_assets' ] );
		}
	}


	/**
	 * Outputs HTML for the plugin's Settings page.
	 */
	public function settings_page(): void {
		if ( ! current_user_can( $this->required_capability() ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', PluginInfo::plugin_text_domain() ) );
		}

		printf(
			'<div class="wrap" id="%s">Use the Settings API to add some settings!</div>',
			PluginInfo::plugin_text_domain()
		);
	}

	/**
	 * The plugin's Settings page slug
	 *
	 * @return string
	 */
	private function get_settings_page_slug(): string {
		return PluginInfo::plugin_text_domain() . '-settings';
	}

	/**
	 * Capability required to access the settings, be shown error messages, etc.
	 *
	 * By default, 'customize' is mapped to 'edit_theme_options' (Administrator).
	 *
	 * @link https://developer.wordpress.org/themes/customize-api/advanced-usage/
	 *
	 * @return string
	 */
	private function required_capability(): string {
		return apply_filters( PluginInfo::plugin_text_domain_underscores() . '/required_capability', 'customize' );
	}

}
