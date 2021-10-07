<?php
/**
 * Settings class.
 *
 * @package WP_Plugin_Template
 */
declare( strict_types=1 );

namespace WP_Plugin_Template;

use WP_Plugin_Template\Dependencies\Cedaro\WP\Plugin\AbstractHookProvider;

class Settings extends AbstractHookProvider {

    /**
     * @inheritDoc
     */
    public function register_hooks(): void {
		// Register settings with Settings API
		$this->add_action( 'admin_init', 'register_settings' );
		$this->add_action( 'rest_api_init', 'register_settings' );
    }

	/**
	 * Register plugin settings.
	 *
	 * @link https://developer.wordpress.org/reference/functions/register_setting/
	 * @link https://developer.wordpress.org/rest-api/reference/settings/
	 * @link https://make.wordpress.org/core/2016/10/26/registering-your-settings-in-wordpress-4-7/
	 * @link https://make.wordpress.org/core/2019/10/03/wp-5-3-supports-object-and-array-meta-types-in-the-rest-api/
	 */
	public function register_settings(): void {
		// Set up the settings.
	}

}
