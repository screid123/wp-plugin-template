<?php
/**
 * ServiceProvider class.
 *
 * @package WP_Plugin_Template
 */
declare( strict_types=1 );

namespace WP_Plugin_Template;

use WP_Plugin_Template\Dependencies\Pimple\Container as PimpleContainer;
use WP_Plugin_Template\Dependencies\Pimple\ServiceProviderInterface;

/**
 * Registers plugin classes with the DI container.
 *
 * @package WP_Plugin_Template
 */
class ServiceProvider implements ServiceProviderInterface {

	/**
	 * @inheritDoc
	 */
	public function register( PimpleContainer $pimple ): PimpleContainer {
		$pimple['settings'] = static function() {
			return new Settings();
		};

		$pimple['admin'] = static function( $container ) {
			return new Admin( $container['settings'] );
		};

		$pimple['frontend'] = static function( $container ) {
			return new Frontend( $container['settings'] );
		};

		return $pimple;
	}

}
