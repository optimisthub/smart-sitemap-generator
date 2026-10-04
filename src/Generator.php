<?php
/**
 * Sitemap generation.
 *
 * @package OptimistHub\SmartSitemap
 */

declare( strict_types = 1 );

namespace OptimistHub\SmartSitemap;

defined( 'ABSPATH' ) || exit;

/**
 * Builds XML sitemaps and the sitemap index.
 *
 * Sitemaps are written to the WordPress uploads directory
 * (wp-content/uploads/sitemaps/) instead of the web root, which is not
 * writable on many hosts and is not multisite-safe.
 */
final class Generator {

	/**
	 * Maximum URLs per sitemap file (Search Console limit is 50,000).
	 */
	public const MAX_URLS = 2000;

	/**
	 * Directory name inside uploads.
	 */
	public const DIR = 'sitemaps';

	/**
	 * Absolute path to the sitemap directory.
	 *
	 * @return string
	 */
	public static function path(): string {
		$uploads = wp_upload_dir();

		$base = isset( $uploads['basedir'] ) ? (string) $uploads['basedir'] : WP_CONTENT_DIR . '/uploads';

		return trailingslashit( $base ) . self::DIR;
	}

	/**
	 * Public base URL of the sitemap directory.
	 *
	 * @return string
	 */
	public static function url(): string {
		$uploads = wp_upload_dir();

		$base = isset( $uploads['baseurl'] ) ? (string) $uploads['baseurl'] : content_url( 'uploads' );

		return trailingslashit( $base ) . self::DIR;
	}

	/**
	 * Public URL of the sitemap index.
	 *
	 * @return string
	 */
	public static function index_url(): string {
		return trailingslashit( self::url() ) . 'sitemap-index.xml';
	}

	/**
	 * Create the sitemap directory, hardening it against directory listing.
	 *
	 * @return bool
	 */
	public function ensure_directory(): bool {
		$path = self::path();

		if ( ! wp_mkdir_p( $path ) ) {
			return false;
		}

		// Prevent directory listing on servers that honour .htaccess.
		$htaccess = trailingslashit( $path ) . '.htaccess';

		if ( ! file_exists( $htaccess ) ) {
			file_put_contents( $htaccess, "Options -Indexes\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Tiny static hardening file.
		}

		return true;
	}

	/**
	 * Generate every sitemap plus the index.
	 *
	 * @param array<int, string> $post_types Post types to include.
	 * @return array{files: int, urls: int} Generation summary.
	 */
	public function generate( array $post_types ): array {
		$summary = array(
			'files' => 0,
			'urls'  => 0,
		);

		if ( ! $this->ensure_directory() ) {
			return $summary;
		}

		$post_types = Settings::sanitize_post_types( $post_types );

		if ( empty( $post_types ) ) {
			$this->clean();

			return $summary;
		}

		// Start from a clean slate so removed content does not linger.
		$this->clean();

		$part_urls = array();

		foreach ( $post_types as $post_type ) {
			$files = $this->generate_for_type( $post_type );

			foreach ( $files as $file ) {
				$part_urls[] = $file;
			}
		}

		foreach ( $part_urls as $file ) {
			++$summary['files'];
			$summary['urls'] += $this->count_urls( $file );
		}

		if ( $this->write_index( $part_urls ) ) {
			++$summary['files'];
		}

		update_option( 'smartsitemap__last_run', time(), false );

		return $summary;
	}

	/**
	 * Generate the sitemap file(s) for one post type.
	 *
	 * @param string $post_type Post type slug.
	 * @return array<int, string> Absolute paths of the files written.
	 */
	private function generate_for_type( string $post_type ): array {
		$written = array();

		$posts = get_posts(
			array(
				'post_type'              => $post_type,
				'post_status'            => 'publish',
				'posts_per_page'         => self::MAX_URLS * 10,
				'orderby'                => 'modified',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				'suppress_filters'       => false,
			)
		);

		if ( empty( $posts ) ) {
			return $written;
		}

		$chunks = array_chunk( $posts, self::MAX_URLS );

		foreach ( $chunks as $index => $chunk ) {
			$part      = $index + 1;
			$filename  = $part > 1
				? sprintf( '%s-sitemap-%d.xml', $post_type, $part )
				: sprintf( '%s-sitemap.xml', $post_type );
			$file_path = trailingslashit( self::path() ) . $filename;

			if ( $this->write_sitemap( $file_path, $chunk ) ) {
				$written[] = $file_path;
			}
		}

		return $written;
	}

	/**
	 * Write one sitemap file.
	 *
	 * @param string               $file_path Absolute destination path.
	 * @param array<int, \WP_Post> $posts Posts to include.
	 * @return bool
	 */
	private function write_sitemap( string $file_path, array $posts ): bool {
		$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		$count = 0;

		foreach ( $posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$permalink = get_permalink( $post );

			if ( ! is_string( $permalink ) || '' === $permalink ) {
				continue;
			}

			$lastmod = get_post_modified_time( 'c', true, $post, false );

			if ( ! is_string( $lastmod ) || '' === $lastmod ) {
				$lastmod = gmdate( 'c' );
			}

			$xml .= "\t<url>\n";
			$xml .= "\t\t<loc>" . esc_url( $permalink ) . "</loc>\n";
			$xml .= "\t\t<lastmod>" . esc_html( $lastmod ) . "</lastmod>\n";
			$xml .= "\t\t<changefreq>" . esc_html( $this->changefreq( $post ) ) . "</changefreq>\n";
			$xml .= "\t\t<priority>" . esc_html( $this->priority( $post ) ) . "</priority>\n";
			$xml .= "\t</url>\n";

			++$count;
		}

		$xml .= '</urlset>' . "\n";

		if ( 0 === $count ) {
			return false;
		}

		return false !== file_put_contents( $file_path, $xml ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Writing generated static sitemap files.
	}

	/**
	 * Write the sitemap index referencing each part.
	 *
	 * @param array<int, string> $files Absolute file paths.
	 * @return bool
	 */
	private function write_index( array $files ): bool {
		if ( empty( $files ) ) {
			return false;
		}

		$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		foreach ( $files as $file ) {
			$name = wp_basename( $file );
			$url  = trailingslashit( self::url() ) . $name;

			$xml .= "\t<sitemap>\n";
			$xml .= "\t\t<loc>" . esc_url( $url ) . "</loc>\n";
			$xml .= "\t\t<lastmod>" . esc_html( gmdate( 'c' ) ) . "</lastmod>\n";
			$xml .= "\t</sitemap>\n";
		}

		$xml .= '</sitemapindex>' . "\n";

		$index_path = trailingslashit( self::path() ) . 'sitemap-index.xml';

		return false !== file_put_contents( $index_path, $xml ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Writing the generated sitemap index.
	}

	/**
	 * Count <loc> entries in a sitemap file.
	 *
	 * @param string $file Absolute path.
	 * @return int
	 */
	private function count_urls( string $file ): int {
		$contents = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a local generated file.

		if ( false === $contents ) {
			return 0;
		}

		return (int) substr_count( $contents, '<loc>' );
	}

	/**
	 * Delete previously generated sitemap files.
	 *
	 * @return void
	 */
	public function clean(): void {
		$path = self::path();

		if ( ! is_dir( $path ) ) {
			return;
		}

		$files = glob( trailingslashit( $path ) . '*.xml' );

		if ( ! is_array( $files ) ) {
			return;
		}

		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				wp_delete_file( $file );
			}
		}
	}

	/**
	 * Whether the sitemaps should be regenerated based on the configured TTL.
	 *
	 * @return bool
	 */
	public function is_stale(): bool {
		$path = trailingslashit( self::path() ) . 'sitemap-index.xml';

		if ( ! file_exists( $path ) ) {
			return true;
		}

		$choices = Settings::ttl_choices();
		$ttl_key = (string) Settings::get( 'ttl', '-1 days' );
		$ttl     = $choices[ $ttl_key ] ?? DAY_IN_SECONDS;

		$modified = filemtime( $path );

		if ( false === $modified ) {
			return true;
		}

		return ( time() - $modified ) > $ttl;
	}

	/**
	 * Suggest a changefreq value for a post.
	 *
	 * @param \WP_Post $post Post.
	 * @return string
	 */
	private function changefreq( \WP_Post $post ): string {
		$frequencies = array(
			'post' => 'weekly',
			'page' => 'monthly',
		);

		$value = $frequencies[ $post->post_type ] ?? 'monthly';

		/**
		 * Filter the changefreq value for a sitemap entry.
		 *
		 * @param string   $value Changefreq value.
		 * @param \WP_Post $post  Post.
		 */
		return (string) apply_filters( 'smartsitemap_changefreq', $value, $post );
	}

	/**
	 * Suggest a priority value for a post.
	 *
	 * @param \WP_Post $post Post.
	 * @return string
	 */
	private function priority( \WP_Post $post ): string {
		$priorities = array(
			'post' => '0.8',
			'page' => '0.6',
		);

		$value = $priorities[ $post->post_type ] ?? '0.5';

		/**
		 * Filter the priority value for a sitemap entry.
		 *
		 * @param string   $value Priority value.
		 * @param \WP_Post $post  Post.
		 */
		return (string) apply_filters( 'smartsitemap_priority', $value, $post );
	}
}
