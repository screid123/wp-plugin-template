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
	 * @var Container;
	 */
	private $container;

	/**
	 * @var Plugin;
	 */
	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param string $entry Entry file path.
	 */
	public function __construct( string $entry ) {
		// Register with RV_Updater
		if ( function_exists( 'rv_register_plugin_update' ) ) {
			rv_register_plugin_update( [
				'id'       => '{{SLUG}}',
				'manifest' => '{{DOWNLOAD_URI}}manifest.json',
				'entry'    => $entry,
			] );
		}

		// Run requirements check.
		$requirements = new Requirements( '{{NAME}}', [
			'php'     => '{{REQUIRES_PHP}}',
			'wp'      => '{{REQUIRES}}',
			'plugins' => self::$required_plugins,
		] );

		// If the requirements are not met, notify and exit.
		if ( ! $requirements->satisfied() ) {
			$requirements->print_notice();
			return;
		}

		// Create the container and register the service provider.
		$this->container = new Container();
		$this->container->register( new Services() );

		// Initialize the plugin and inject the container.
		$this->plugin = ( new Plugin() )
			->set_basename( plugin_basename( $entry ) )
			->set_container( $this->container )
			->set_directory( plugin_dir_path( $entry ) )
			->set_file( $entry )
			->set_slug( '{{SLUG}}' )
			->set_url( plugin_dir_url( $entry ) );
    }

	/**
	 * Begins execution of the plugin.
	 */
	public function init(): void {
		if ( ! empty( $this->plugin ) ) {
			// Register hook providers.
			$this->plugin
				->register_hooks( $this->container->get( 'settings' ) )
				->register_hooks( $this->container->get( 'admin' ) )
				->register_hooks( $this->container->get( 'frontend' ) );
		}
	}

}
