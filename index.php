<?php
/**
 * Plugin Name:       Smart Sitemap Generator
 * Plugin URI:        https://github.com/optimisthub/smart-sitemap-generator
 * Description:       Automatically generate XML sitemaps and a sitemap index for your posts, pages and custom post types. Fast, cached and search-engine ready.
 * Version:           2.0.0
 * Requires at least: 6.0
 * Tested up to:      7.1
 * Requires PHP:      7.4
 * Author:            Optimist Hub
 * Author URI:        https://optimisthub.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       smart-sitemap-generator
 * Domain Path:       /languages
 *
 * @package OptimistHub\SmartSitemap
 */

declare( strict_types = 1 );

namespace OptimistHub\SmartSitemap;

defined( 'ABSPATH' ) || exit;

define( 'SMART_SITEMAP_VERSION', '2.0.0' );
define( 'SMART_SITEMAP_FILE', __FILE__ );
define( 'SMART_SITEMAP_DIR', plugin_dir_path( __FILE__ ) );

if ( is_readable( SMART_SITEMAP_DIR . 'vendor/autoload.php' ) ) {
	require_once SMART_SITEMAP_DIR . 'vendor/autoload.php';
} else {
	spl_autoload_register(
		static function ( $class ) {
			$prefix = __NAMESPACE__ . '\\';

			if ( 0 !== strpos( $class, $prefix ) ) {
				return;
			}

			$relative = substr( $class, strlen( $prefix ) );
			$path     = SMART_SITEMAP_DIR . 'src/' . str_replace( '\\', '/', $relative ) . '.php';

			if ( is_readable( $path ) ) {
				require_once $path;
			}
		}
	);
}

require_once SMART_SITEMAP_DIR . 'src/Settings.php';
require_once SMART_SITEMAP_DIR . 'src/Generator.php';
require_once SMART_SITEMAP_DIR . 'src/Plugin.php';

register_activation_hook( __FILE__, array( Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Plugin::class, 'deactivate' ) );

/**
 * Boot the plugin.
 *
 * @return Plugin
 */
function plugin() {
	static $instance = null;

	if ( null === $instance ) {
		$instance = new Plugin();
		$instance->boot();
	}

	return $instance;
}

plugin();
