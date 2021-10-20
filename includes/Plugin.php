<?php
/**
 * Plugin class.
 *
 * @package WP_Plugin_Template;
 */
declare( strict_types=1 );

namespace WP_Plugin_Template;

use WP_Plugin_Template\Dependencies\Cedaro\WP\Plugin\Plugin as BasePlugin;

/**
 * Extend the default BasePlugin class with additional functionality.
 *
 * @package WP_Plugin_Template;
 */
class Plugin extends BasePlugin {

	/**
	 * Plugin prefix (safe string).
	 *
	 * Useful for filters and database keys, etc.
	 *
	 * @var string
	 */
	protected $prefix;

	/**
	 * Get the prefix.
	 *
	 * @return string
	 */
	public function get_prefix(): string {
		return $this->prefix;
	}

	/**
	 * Set the plugin prefix.
	 *
	 * Provided string will be sanitized, and all hyphens replaced with underscores.
	 *
	 * @param string $prefix
	 * @return $this
	 */
	public function set_prefix( string $prefix ): Plugin {
		$this->prefix = str_replace( '-', '_', sanitize_key( $prefix ) );
		return $this;
	}

}
