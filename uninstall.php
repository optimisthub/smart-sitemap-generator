<?php
/**
 * Uninstall routine.
 *
 * Removes the plugin option, the rebuild flag, the cron event and every
 * generated sitemap file.
 *
 * @package OptimistHub\SmartSitemap
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Perform the uninstall cleanup.
 *
 * Wrapped in a function so the local variables are not registered as globals.
 *
 * @return void
 */
function optimisthub_smart_sitemap_uninstall(): void {
	delete_option( 'smartsitemap__options' );
	delete_option( 'smartsitemap__last_run' );
	delete_option( 'smartsitemap__needs_rebuild' );

	wp_clear_scheduled_hook( 'smartsitemap_regenerate' );

	$uploads = wp_upload_dir();
	$base    = isset( $uploads['basedir'] ) ? (string) $uploads['basedir'] : WP_CONTENT_DIR . '/uploads';
	$dir     = trailingslashit( $base ) . 'sitemaps';

	if ( ! is_dir( $dir ) ) {
		return;
	}

	$files = glob( trailingslashit( $dir ) . '*' );

	if ( is_array( $files ) ) {
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				wp_delete_file( $file );
			}
		}
	}

	// Remove the now-empty directory.
	global $wp_filesystem;

	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	if ( WP_Filesystem() && $wp_filesystem instanceof WP_Filesystem_Base ) {
		$wp_filesystem->rmdir( $dir );
	}
}

optimisthub_smart_sitemap_uninstall();
