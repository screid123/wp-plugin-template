<?php
/**
 * __NAME__
 *
 * @package     Red_Ventures\Media_Credit
 * @author      __AUTHOR__
 * @copyright   2021 __AUTHOR__
 * @license     GPLv3-or-later
 * @link        https://github.com/CreditCardsCom/wp-media-credit
 *
 * @wordpress-plugin
 * Plugin Name:     WP Plugin Template
 * Plugin URI:      https://github.com/screid123/wp-plugin-template
 * Description:     __DESCRIPTION__
 * Version:         __VERSION__
 * Author:          __AUTHOR__
 * Author URI:      __AUTHOR_URI__
 * License:         GPLv3 or later
 * License URI:     https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:     wp-plugin-template
 */

// Abort if this file is called directly.
defined( 'WPINC' ) || die;

// Constants
const RV_MEDIA_CREDIT_VERSION = '__VERSION__';
if ( ! defined( 'WP_PLUGIN_TEMPLATE_FILE' ) ) {
	define( 'WP_PLUGIN_TEMPLATE_FILE', __FILE__ );
}
if ( ! defined( 'WP_PLUGIN_TEMPLATE_PATH' ) ) {
	define( 'WP_PLUGIN_TEMPLATE_PATH', __DIR__ );
}

// Load the Composer autoloader + dependencies.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once( __DIR__ . '/vendor/autoload.php' );
}

// Setup RV_Updater
if ( function_exists( 'rv_register_plugin_update' ) ) {
	rv_register_plugin_update( [
		'id'       => 'wp-plugin-template',
		'manifest' => 'https://cdn.ccstatic.com/wordpress-plugins/wp-plugin-template/manifest.json',
		'entry'    => __FILE__,
	] );
}

// Initialize and run the plugin.
add_action( 'plugins_loaded', static function() {
	// Run plugin setup.
} );
