<?php
/**
 * PluginInfo class.
 *
 * @package WP_Plugin_Template
 */

declare( strict_types=1 );

namespace WP_Plugin_Template;

/**
 * The basic information about this plugin, like its texts (text domain and display name) and file locations.
 *
 * @package WP_Plugin_Template
 */
class PluginInfo {

	/**
	 * Get this plugin's slug.
	 *
	 * @return string
	 */
	public static function plugin_slug(): string {
		return '{{SLUG}}';
	}

	/**
	 * Get this plugin's version.
	 *
	 * @return string
	 */
	public static function plugin_version(): string {
		return '{{VERSION}}';
	}

	/**
	 * Get this plugin's required minimum version of WordPress.
	 *
	 * @return string
	 */
	public static function required_min_wp_version(): string {
		return '{{REQUIRES}}';
	}

	/**
	 * Get this plugin's required minimum version of PHP.
	 *
	 * Should match composer.json's `"require": { "php":...`
	 *
	 * @link https://wordpress.org/about/requirements/
	 * @link https://en.wikipedia.org/wiki/PHP#Release_history
	 *
	 * @return string
	 */
	public static function required_min_php_version(): string {
		return '{{REQUIRES_PHP}}';
	}

	/**
	 * Get this plugin's text domain.
	 *
	 * @return string
	 */
	public static function plugin_text_domain(): string {
		return '{{TEXT_DOMAIN}}';
	}

	/**
	 * Prefix a style/script handle with our text domain. (Make sure it's unique!)
	 *
	 * To be consistent while being unique. Note that we don't keep a list of each handle to prevent non-uniques,
	 * and we probably shouldn't, due to things like `wp_localize_script()` needing the same handle as the enqueue.
	 *
	 * @param string $handle
	 *
	 * @return string
	 */
	public static function get_asset_handle( string $handle ): string {
		return self::plugin_text_domain() . '-' . $handle;
	}

	/**
	 * Get this plugin's text domain with underscores instead of hyphens.
	 *
	 * Used for saving options, but also useful for building namespaced hook names, class names, URLs, etc.
	 *
	 * @return string
	 */
	public static function plugin_text_domain_underscores(): string {
		return str_replace( '-', '_', self::plugin_text_domain() );
	}

	/**
	 * Get the plugin's display name.
	 *
	 * Useful for headings, for example.
	 *
	 * @return string
	 */
	public static function get_plugin_display_name(): string {
		return esc_html_x('{{NAME}}', 'Plugin name for display', self::plugin_text_domain() );
	}

	/**
	 * Get this plugin's directory path, relative to this file's location.
	 *
	 * This file should be in `/src` and we want one level above.
	 * Example: /app/public/wp-content/plugins/wp-plugin-template
	 *
	 * @return string
	 */
	public static function plugin_dir_path(): string {
		return trailingslashit( dirname( __DIR__ ) );
	}

	/**
	 * Get this plugin's directory URL.
	 *
	 * Example: https://example.com/wp-content/plugins/wp-plugin-template/
	 *
	 * @return string
	 */
	public static function plugin_dir_url(): string {
		return plugin_dir_url( self::main_plugin_file() );
	}

	/**
	 * Get the base URL of the directory containing JS/CSS files.
	 *
	 * Example usage:
	 * PluginData::get_assets_url_base() . 'admin.css'
	 *
	 * @return string
	 */
	public static function get_assets_path_base(): string {
		return self::plugin_dir_path() . 'assets/';
	}

	/**
	 * Get the base URL of the directory containing JS/CSS files.
	 *
	 * Example usage:
	 * PluginData::get_assets_url_base() . 'admin.css'
	 *
	 * @return string
	 */
	public static function get_assets_url_base(): string {
		return self::plugin_dir_url() . 'assets/';
	}

	/**
	 * Get this plugin's basename.
	 *
	 * @return string 'cliff-wp-plugin-boilerplate/cliff-wp-plugin-boilerplate.php'
	 */
	public static function plugin_basename(): string {
		return plugin_basename( self::main_plugin_file() );
	}

	/**
	 * Get this plugin's directory relative to this file's location.
	 *
	 * This file should be in `/src` and we want one level above.
	 * Example: /app/public/wp-content/plugins/
	 *
	 * @return string
	 */
	public static function all_plugins_dir(): string {
		return trailingslashit( dirname( self::plugin_dir_path(), 2 ) );
	}

	/**
	 * Get this plugin's main plugin file.
	 *
	 * @return string
	 */
	public static function main_plugin_file(): string {
		return self::plugin_dir_path() . self::plugin_slug() . '.php';
	}

}
