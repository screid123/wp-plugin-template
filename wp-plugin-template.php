<?php
/**
 * @package     WP_Plugin_Template
 * @author      {{AUTHOR}}
 * @copyright   2021 {{AUTHOR}}
 * @license     GPLv3-or-later
 * @link        {{URI}}
 *
 * @wordpress-plugin
 * Plugin Name:     {{NAME}}
 * Plugin URI:      {{URI}}
 * Description:     {{SHORT_DESCRIPTION}}
 * Version:         {{VERSION}}
 * Author:          {{AUTHOR}}
 * Author URI:      {{AUTHOR_URI}}
 * License:         GPLv3 or later
 * License URI:     https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:     {{TEXT_DOMAIN}}
 */

// Cannot `declare( strict_types=1 );` to avoid fatal if prior to PHP 7.0.0, since we did not yet verify the PHP version.

namespace WP_Plugin_Template;

// Abort if this file is called directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VERSION = '{{VERSION}}';

// Composer autoloader.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once( __DIR__ . '/vendor/autoload.php' );
}

// Register activation hook.
register_activation_hook( __FILE__, [ __NAMESPACE__ . '\Activator', 'activate' ] );

// Register deactivation hook.
register_deactivation_hook( __FILE__, [ __NAMESPACE__ . '\Deactivator', 'deactivate' ] );

// Initialize the plugin.
if ( function_exists( 'wp_installing' ) && ! wp_installing() && class_exists( __NAMESPACE__ . '\Bootstrap' ) ) {
	$plugin = new Bootstrap( __FILE__ );
	add_action( 'plugins_loaded', [ $plugin, 'init' ] );
}
