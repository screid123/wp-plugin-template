<?php
/**
 * Bootstrap class.
 *
 * @package WP_Plugin_Template
 */

// Cannot `declare( strict_types=1 );` to avoid fatal if prior to PHP 7.0.0, since we did not yet verify the PHP version.

namespace WP_Plugin_Template;

use WP_Plugin_Template\Dependencies\Cedaro\WP\Plugin\PluginFactory;
use WP_Plugin_Template\Dependencies\Micropackage\Requirements\Requirements;

/**
 * Class representing the plugin.
 *
 * @package WP_Plugin_Template
 */
final class Bootstrap {

	/**
	 * List of plugins required by this plugin.
	 *
	 * @var array
	 */
	private static $required_plugins = [
		[
			'file' => 'advanced-custom-fields-pro/acf.php',
			'name' => 'Advanced Custom Fields PRO',
			'version' => '5.8',
		],
	];

	/**
	 * @var Requirements;
	 */
	private $requirements;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Register with RV_Updater
		if ( function_exists( 'rv_register_plugin_update' ) ) {
			rv_register_plugin_update( [
				'id'       => PluginInfo::plugin_slug(),
				'manifest' => '{{DOWNLOAD_URI}}manifest.json',
				'entry'    => PluginInfo::main_plugin_file(),
			] );
		}

		// Run requirements check.
		$this->requirements = new Requirements( PluginInfo::get_plugin_display_name(), [
			'php'    	=> PluginInfo::required_min_php_version(),
			'wp'     	=> PluginInfo::required_min_wp_version(),
			'plugins'	=> self::$required_plugins,
		] );
    }

	/**
	 * Begins execution of the plugin.
	 *
	 * Since everything within the plugin is registered via hooks, then kicking off the plugin from this point in the file
	 * does not affect the page life cycle.
	 *
	 * Also returns copy of the app object so 3rd party developers can interact with the plugin's hooks contained within.
	 */
	public function init(): void {
		// If the requirements are not met, notify and exit.
		if ( ! $this->requirements->satisfied() ) {
			$this->requirements->print_notice();
			return;
		}

		$plugin = PluginFactory::create( PluginInfo::plugin_slug(), PluginInfo::main_plugin_file() );
	}

}
