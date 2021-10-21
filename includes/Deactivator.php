<?php
/**
 * Deactivator class.
 *
 * @package WP_Plugin_Template
 */

// Cannot `declare( strict_types=1 );` to avoid fatal if prior to PHP 7.0.0, since we did not yet verify the PHP version.

namespace WP_Plugin_Template;

/**
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @package WP_Plugin_Template
 */
class Deactivator {

    /**
     * Fired during plugin deactivation.
     */
    public static function deactivate(): void {
        // Do stuff on plugin deactivate...
    }

}
