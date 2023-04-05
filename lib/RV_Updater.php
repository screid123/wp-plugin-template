<?php
/**
 * RV Updater - A custom plugin update manager for Red Ventures.
 *
 * Add this file to your plugin and include it as a dependency.
 * Register your plugin using the `rv_register_plugin_update` function with an array containing (at minimum):
 *   - "entry" => The filepath to your plugin's main PHP file (https://developer.wordpress.org/plugins/plugin-basics/#getting-started).
 *   - "manifest" => The location of a manifest file that contains information on the latest plugin version.
 * Once registered, this class will hook into the WordPress actions and filters to check for updates once per day (or
 * more if forced via "Updates" in the Admin dashboard) from the defined manifest source.
 *
 * @see https://github.com/RedVentures/wordpress-utilities/blob/master/self-hosted-plugins/
 * @author rlinker@redventures.com
 * @requires PHP 7.1
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'RV_Updater' ) ) :

	final class RV_Updater {

		/** @var string The current version of the updater */
		private static $version = '1.1.0';

		/** @var self The singleton instance of the updater */
		private static $instance;

		/** @var array The array of registered plugins */
		private $plugins = array();

		/** @var int Counts the number of plugin update checks */
		private $checked = 0;

		/**
		 * Prevent direct construction calls with the `new` operator.
		 *
		 * @uses RV_Updater::modify_plugin_details() in the "plugins_api" filter.
		 * @uses RV_Updater::modify_update_plugins_transient() in the "pre_set_site_transient_update_plugins" filter.
		 */
		private function __construct() {
			if ( function_exists( 'add_filter' ) ) {
				// Modify plugin data visible in the 'View details' popup.
				add_filter( 'plugins_api', array( $this, 'modify_plugin_details' ), 10, 3 );

				// Append update information to transients.
				add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'modify_update_plugins_transient' ), 10, 2 );
			}
		}

		/**
		 * Cloning is not permitted.
		 */
		public function __clone() {
			throw new \Exception( "Cannot clone a singleton." );
		}

		/**
		 * Unserialization is not permitted.
		 */
		public function __wakeup() {
			throw new \Exception( "Cannot unserialize a singleton." );
		}

		/**
		 * Create a single instance of the updater in case it is used by multiple plugins.
		 *
		 * @uses RV_Updater::$instance to create a reference to itself.
		 *
		 * @return self A reference to the singleton instance.
		 */
		public static function get_instance(): self {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new static;
			}
			return self::$instance;
		}

		/**
		 * Registers a plugin for updates.
		 *
		 * @used-by rv_register_plugin_update()
		 *
		 * @uses RV_Updater::get_plugin_slug() if the slug isn't manually provided.
		 * @uses RV_Updater::$plugins to store the plugin reference with the class.
		 * @uses RV_Updater::modify_plugin_update_message() in the "in_plugin_update_message-{$file}" filter.
		 *
		 * @param array $plugin Array containing the plugin information.
		 *
		 * @return void
		 */
		public function add_plugin( $plugin ): void {
			// Validate required params.
			$plugin = (array) wp_parse_args( $plugin, array(
				'manifest' => '', // Location of manifest file for updates.
				'entry' => '', // Plugin's entry path/file location.
			) );

			if ( empty( $plugin['manifest'] ) || empty( $plugin['entry'] ) ) {
				error_log( 'RV_Updater: Could not register plugin for updates. It is missing required parameters.' );
				return;
			}

			// Validate the plugin's metadata.
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			$plugin_data = get_plugin_data( $plugin['entry'] );

			if ( empty( $plugin_data['Name'] ) ) {
				error_log( 'RV_Updater: RV_Updater: Invalid plugin metadata. Missing required key: "Plugin Name".' );
				return;
			}

			if ( empty( $plugin_data['Version'] ) ) {
				error_log( 'RV_Updater: Invalid plugin metadata. Missing required key: "Version".' );
				return;
			}

			// Append inferred attributes if they are not set.
			$plugin['name'] = $plugin['name'] ?? $plugin_data['Name'];
			$plugin['version'] = $plugin['version'] ?? $plugin_data['Version'];
			$plugin['slug'] = $plugin['slug'] ?? $this->get_plugin_slug( $plugin_data );
			$plugin['basename'] = $plugin['basename'] ?? plugin_basename( $plugin['entry'] );

			// If any of the keys have empty values, log and abort.
			if ( count( $plugin ) !== count( array_filter( $plugin ) ) ) {
				error_log( 'RV_Updater: Could not register plugin due to incomplete plugin data.' );
				return;
			}

			// Check if is_plugin_active() function exists. This is required on the front end of the
			// site, since it is in a file that is normally only loaded in the admin.
			if( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			// Add if an active plugin (e.g. - not included in a theme).
			if ( is_plugin_active( $plugin['basename'] ) ) {
				$this->plugins[ $plugin['basename'] ] = $plugin;

				// If in the Admin, modify the update message for the plugin.
				if ( is_admin() ) {
					add_action( 'in_plugin_update_message-' . $plugin['basename'], array( $this, 'modify_plugin_update_message' ), 10, 2 );
				}
			}
		}

		/**
		 * Called when WP updates the 'update_plugins' site transient.
		 * @see https://developer.wordpress.org/reference/hooks/site_transient_transient/
		 * @see https://developer.wordpress.org/reference/hooks/pre_set_site_transient_transient/
		 *
		 * @uses RV_Updater::$checked to make sure force-check is only run once (use cache other times).
		 * @uses RV_Updater::get_plugin_updates() to check for plugin updates.
		 * @uses RV_Updater::get_plugin_slug() to create the slug from the update name.
		 * @uses RV_Updater::get_plugin_by() to look up a registered plugin by its slug.
		 *
		 * @param mixed $transient New value of site transient.
		 * @param string $transient_name Transient name.
		 *
		 * @return mixed The filtered transient value.
		 */
		public function modify_update_plugins_transient( $transient, $transient_name ) {
			// Bail early if no response (error).
			if ( ! isset( $transient->response ) ) {
				return $transient;
			}

			// Force-check (only once).
			$force_check = ( 0 === $this->checked ) ? ! empty( $_GET['force-check'] ) : false;

			// Fetch updates and add to transient cache (since this filter is called multiple times during a single page load).
			if ( is_array( $updates = $this->get_plugin_updates( $force_check ) ) ) {
				foreach( $updates['plugins'] as $update ) {
					// Create the slug from plugin name.
					$slug = $this->get_plugin_slug( $update );

					// Lookup the registered plugin. Abort if not found.
					if ( empty( $plugin = $this->get_plugin_by( 'slug', $slug ) ) ) {
						error_log( "RV_Updater: The plugin “{$update['name']}” was not found as a registered plugin." );
						break;
					}

					// Get current plugin data.
					$plugin_data = get_plugin_data( $plugin['entry'] );

					// Make sure the updated plugin version is greater than the current version, and
					// the required WordPress version is less or equal to the current WordPress version.
					if (
						version_compare( $plugin_data['Version'], $update['version'], '<' ) &&
						version_compare( $update['requires'] ?? $plugin_data['RequiresWP'], get_bloginfo( 'version' ), '<=' )
					) {
						// Construct the object.
						$res = new \stdClass();
						$res->plugin = $plugin['basename'];
						$res->slug = $plugin['slug'];
						$res->new_version = $update['version'];
						$res->package = $update['download_link'];

						// Add the tested WordPress version if defined.
						if ( isset( $update['tested'] ) ) {
							$res->tested = $update['tested'];
						}

						// Update the transient.
						$transient->response[ $plugin['basename'] ] = $res;
					}
				}
			}

			// Increase counter.
			$this->checked++;

			return $transient;
		}

		/**
		 * Modify the update message in the Plugins list.
		 * @see https://developer.wordpress.org/reference/hooks/in_plugin_update_message-file/
		 *
		 * @used-by RV_Updater::add_plugin()
		 *
		 * @param array $plugin_data An array of plugin metadata.
		 * @param array $response An array of metadata about the available plugin update.
		 */
		public function modify_plugin_update_message( $plugin_data, $response ): void {
			echo '<br /><b>NOTE:</b> ' . sprintf(
					'This is a custom plugin by %s and updates are hosted externally. You can view the plugin source <a href="%s" target="_blank">here</a>.',
					$plugin_data['Author'],
					$plugin_data['PluginURI']
				);
		}

		/**
		 * Returns the plugin data visible in the 'View details' popup.
		 * @see https://developer.wordpress.org/reference/hooks/plugins_api/
		 *
		 * @uses RV_Updater::get_plugin_by() to ensure the plugin is registered.
		 * @uses RV_Updater::get_plugin_info() to read the updated plugin information.
		 * @uses RV_Updater::get_plugin_details() to create the updated plugin details.
		 *
		 * @param bool|object $result The result object or array. Defaults to "false".
		 * @param string $action The type of information being requested from the Plugin Installation API.
		 * @param object $args Plugin API arguments.
		 *
		 * @return bool|object The filtered $result.
		 */
		public function modify_plugin_details( $result, $action, $args ) {
			// Do nothing if this is not about getting plugin information.
			if ( 'plugin_information' !== $action ) {
				return $result;
			}

			// Do nothing if this is not a registered plugin.
			if ( empty( $plugin = $this->get_plugin_by( 'slug', $args->slug ) ) ) {
				return $result;
			}

			// Force-check (only once).
			$force_check = ! empty( $_GET['force-check'] );

			// Get the info. Bail early if no response.
			if ( empty( $plugin_info = $this->get_plugin_info( $plugin, $force_check ) ) ) {
				error_log( "RV_Updater: Could not read plugin info from “{$plugin['manifest']}”." );
				return $result;
			}

			return $this->get_plugin_details( $plugin, $plugin_info );
		}

		/**
		 * Create plugin details from local and remote data.
		 *
		 * @uses RV_Updater::get_local_banners() to check for local banner assets
		 *
		 * @param array $plugin Plugin meta info.
		 * @param array $remote Plugin update information.
		 *
		 * @return object
		 */
		private function get_plugin_details( $plugin, $remote ): object {
			global $allowedposttags, $allowedtags;

			// Create a new generic object.
			$result = new \stdClass();
			$result->slug = $plugin['slug'];

			// Get the local plugin data.
			$plugin_data = get_plugin_data( $plugin['entry'] );

			// Set default values.
			$result->name = $plugin_data['Name'];
			$result->homepage = $plugin_data['PluginURI'];
			$result->version = $plugin_data['Version'];
			$result->requires = $plugin_data['RequiresWP'];
			$result->requires_php = $plugin_data['RequiresPHP'];
			$result->author = $plugin_data['Author'];
			$result->sections = array();
			$result->sections['description'] = $plugin_data['Description'];

			// Iterate over the plugin information and update the object accordingly.
			foreach ( $remote as $key => $value ) {
				switch ( $key ) {
					case 'name':
					case 'version':
					case 'requires':
					case 'requires_php':
					case 'tested':
					case 'last_updated':
					case 'homepage':
						$result->{$key} = $value;
						break;
					case 'download_link':
						$result->download_link = $value;
						$result->trunk = $value;
						break;
					case 'author':
						// Since this could potentially contain markup, make sure it's safe.
						if ( $remote['author_uri'] ) {
							$result->{$key} = sprintf(
								'<a href="%s" target="_blank">%s</a>',
								$remote['author_uri'],
								strip_tags( $value )
							);
						} else {
							$result->{$key} = wp_kses( $value, $allowedtags );
						}
						break;
				}
			}

			// Create sections (tabs).
			$sections = array(
				'description',
				'upgrade_notice',
				'changelog',
				'installation',
			);
			foreach( $sections as $k ) {
				if ( isset( $remote[$k] ) ) {
					// Since these sections allow markup, make sure they're safe.
					$result->sections[$k] = wp_kses( $remote[$k], $allowedposttags );
				}
			}

			// Create "Screenshots" section.
			if ( ! empty( $remote['screenshots'] ) ) {
				$screenshots = "<ol>";
				foreach ( $remote['screenshots'] as $screenshot ) {
					$url = $screenshot['url'];
					// Sanitize any potential markup in caption.
					$caption = wp_kses( $screenshot['caption'], $allowedtags );
					$screenshots .= sprintf(
						'<li><a href="%s" target="_blank"><img src="%s" alt="%s" /></a><p>%s</p></li>',
						$url,
						$url,
						strip_tags( $caption ),
						$caption
					);
				}
				$result->sections['screenshots'] = $screenshots . "</ol>";
			}

			// Add remote banners.
			if ( ! empty( $remote['banners'] ) ) {
				$result->banners = array(
					'low' => $remote['banners']['low'] ?? '',
					'high' => $remote['banners']['high'] ?? '',
				);
			} elseif ( ! empty( $local = $this->get_local_banners( $plugin['entry'] ) ) ) {
				$result->banners = array(
					'low' => $local['low'] ?? '',
					'high' => $local['high'] ?? '',
				);
			}

			return $result;
		}

		/**
		 * Check local plugin assets directory for banners.
		 *
		 * @used-by RV_Updater::get_plugin_details()
		 *
		 * @param string $entry File path to plugin's main PHP file
		 *
		 * @return array Array of local banner URLs found.
		 */
		private function get_local_banners( $entry ): array {
			$plugin_dir = plugin_dir_path( $entry );
			$banners = array();
			if ( file_exists( $low = ( $plugin_dir . 'assets/banner-772x250.jpg' ) ) ) {
				$banners['low'] = $low;
			}
			if ( file_exists( $low = ( $plugin_dir . 'assets/banner-772x250.png' ) ) ) {
				$banners['low'] = $low;
			}
			if ( file_exists( $high = ( $plugin_dir . 'assets/banner-1544x500.png' ) ) ) {
				$banners['high'] = $high;
			}
			if ( file_exists( $high = ( $plugin_dir . 'assets/banner-1544x500.png' ) ) ) {
				$banners['high'] = $high;
			}
			return $banners;
		}

		/**
		 * Check for plugin updates via manifest file.
		 *
		 * @used-by RV_Updater::get_plugin_info()
		 * @used-by RV_Updater::get_plugin_updates()
		 *
		 * @param array $plugin Array containing the plugin information.
		 *
		 * @return array|WP_Error Response of the fetch: either JSON data or a WordPress error object.
		 */
		private function get_plugin_manifest( $plugin ) {
			// Make sure there is a manifest file URL defined to fetch.
			if ( empty( $plugin['manifest'] ) ) {
				return new WP_Error( 'no_plugin_manifest', 'No manifest defined for plugin.', $plugin['slug'] );
			}

			// Determine if manifest is local or remote.
			if ( is_file( $plugin['manifest'] ) ) {
				// Local file, so use `file_get_contents`.
				$response = file_get_contents( $plugin['manifest'] );
				$json = is_string( $response ) ? json_decode( $response, true ) : array();
			} else {
				// Remote file, so use `wp_remote_get`.
				$response = wp_remote_get( $plugin['manifest'], array(
					'timeout' => 10,
					'headers' => array(
						'Accept' => 'application/json',
					),
				) );

				// Handle response error.
				if ( is_wp_error( $response ) ) {
					return $response;
				}

				// Handle HTTP error.
				if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
					return new WP_Error( 'server_error', wp_remote_retrieve_response_message( $response ) );
				}

				// Return JSON response.
				$json = json_decode( wp_remote_retrieve_body( $response ), true );
			}

			// Do not allow non-JSON value.
			if ( empty( $json ) ) {
				return new WP_Error( 'server_error', esc_html( $response ) );
			}

			return $json;
		}

		/**
		 * Returns update information for a plugin.
		 *
		 * @used-by RV_Updater::modify_plugin_details()
		 *
		 * @uses RV_Updater::get_plugin_manifest() to get the updated plugin information.
		 *
		 * @param array $plugin Array containing the plugin information.
		 * @param bool $force_check Bypass cache.
		 *
		 * @return array The response object.
		 */
		private function get_plugin_info( $plugin, $force_check = false ): array {
			// Create transient key for the plugin.
			$transient_name = 'rv_plugin_info_' . $plugin['slug'];

			// Check cache but allow for $force_check override.
			if ( ! $force_check ) {
				$transient = get_transient( $transient_name );

				if ( false !== $transient ) {
					return $transient;
				}
			}

			// Get external manifest for the plugin.
			// If an error is returned, log it and return an empty array.
			if ( is_wp_error( $response = $this->get_plugin_manifest( $plugin ) ) ) {
				error_log( $response->get_error_message() );
				return array();
			}

			// Otherwise update transient if valid response.
			if ( ! empty( $response ) ) {
				set_transient( $transient_name, $response, DAY_IN_SECONDS );
			}

			return $response;
		}

		/**
		 * Checks for plugin updates.
		 *
		 * @used-by RV_Updater::modify_update_plugins_transient()
		 *
		 * @uses RV_Updater::$plugins to iterate over the registered plugins.
		 * @uses RV_Updater::get_plugin_manifest() to get updated plugin information.
		 *
		 * @param boolean $force_check Bypass the cache and fetch directly from the manifest.
		 *
		 * @return array Lists of updated and checked plugins.
		 */
		private function get_plugin_updates( $force_check = false ): array {
			$transient_name = 'rv_plugin_updates';

			// Construct array of plugins to check and current versions.
			$checked = array();
			foreach( $this->plugins as $basename => $plugin ) {
				$checked[ $basename ] = $plugin['version'];
			}

			// Sort by key to avoid detecting change due to "include order".
			ksort( $checked );

			// $force_check prevents transient lookup.
			if ( ! $force_check ) {
				$transient = get_transient( $transient_name );

				// If cached response was found, compare $transient['checked'] against $checked
				// and ignore if they don't match (plugins/versions have changed).
				if ( is_array( $transient ) ) {
					$transient_checked = $transient['checked'] ?? array();

					if ( wp_json_encode( $checked ) !== wp_json_encode( $transient_checked ) ) {
						$transient = false;
					}
				}

				if ( false !== $transient ) {
					return $transient;
				}
			}

			// Construct array of plugins updated info.
			$updated = array();
			foreach( $this->plugins as $basename => $plugin ) {
				if ( ! is_wp_error( $update = $this->get_plugin_manifest( $plugin ) ) ) {
					$updated[] = $update;
				}
			}

			// Construct the response.
			$response = array(
				'plugins' => $updated,
				'checked' => $checked,
			);

			// Update transient.
			set_transient( $transient_name, $response, DAY_IN_SECONDS );

			return $response;
		}

		/**
		 * Lookup a plugin by a property.
		 *
		 * @used-by RV_Updater::modify_update_plugins_transient()
		 *
		 * @param string $key The key to lookup.
		 * @param string $value The value to match.
		 *
		 * @return array|bool The plugin if found, otherwise false.
		 */
		private function get_plugin_by( $key, $value ) {
			foreach( $this->plugins as $plugin ) {
				if( $plugin[$key] === $value ) {
					return $plugin;
				}
			}
			return false;
		}

		/**
		 * Infer the plugin's slug from its metadata.
		 *
		 * @used-by RV_Updater::add_plugin()
		 *
		 * @param array $plugin Plugin metadata/header information.
		 *
		 * @return string The slug.
		 */
		private function get_plugin_slug( $plugin ): string {
			// Try to get the name from the plugin metadata.
			if ( empty( $name = $plugin['Name'] ?? ( $plugin['name'] ?? '' ) ) ) {
				// If not, try to extract from the basename (filename without the extension).
				$name = explode( '/', $plugin['basename'] );
				$name = preg_replace( '/(\.php)$/i', '', $name[1] ?? $name[0] );
			}
			return sanitize_title_with_dashes( strtolower( $name ) );
		}
	}

	/**
	 * The main function responsible for returning the one true rv_updates instance to functions everywhere.
	 *
	 * Use this function like you would a global variable, except without needing to declare the global.
	 * Example: <?php $rv_updater = rv_updater(); ?>
	 *
	 * @used-by rv_register_plugin_update()
	 *
	 * @uses RV_Updater::get_instance() to get the singleton instance.
	 *
	 * @return RV_Updater An instance of RV_Updater
	 */
	function rv_updater(): RV_Updater {
		return RV_Updater::get_instance();
	}

	/**
	 * Alias of rv_updater()->add_plugin().
	 *
	 * Register a plugin with the class to enable automatic update checks from a custom host (non-WordPress.org).
	 * Example: <?php rv_register_plugin_update( array(
	 *     "entry" => __FILE__,
	 *     "manifest" => "https://some.domain.com/path/to/manifest.json"
	 * ) ); ?>
	 *
	 * @uses rv_updater()
	 * @uses RV_Updater::add_plugin()
	 *
	 * @param array $plugin Array containing the plugin information.
	 *
	 * @return void
	 */
	function rv_register_plugin_update( $plugin ) {
		rv_updater()->add_plugin( $plugin );
	}

endif; // class_exists check
