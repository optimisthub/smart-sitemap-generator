<?php
/**
 * Main plugin class.
 *
 * @package OptimistHub\SmartSitemap
 */

declare( strict_types = 1 );

namespace OptimistHub\SmartSitemap;

defined( 'ABSPATH' ) || exit;

/**
 * Wires sitemap generation into WordPress.
 */
final class Plugin {

	/**
	 * Cron hook that performs the regeneration.
	 */
	public const CRON_HOOK = 'smartsitemap_regenerate';

	/**
	 * Flag set when a post change should trigger a rebuild.
	 */
	public const FLAG = 'smartsitemap__needs_rebuild';

	/**
	 * Generator instance.
	 *
	 * @var Generator
	 */
	private Generator $generator;

	/**
	 * Settings instance.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Set up dependencies.
	 */
	public function __construct() {
		$this->generator = new Generator();
		$this->settings  = new Settings();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function boot(): void {
		add_action( 'admin_init', array( $this->settings, 'register' ) );
		add_action( 'admin_menu', array( $this->settings, 'add_page' ) );

		/*
		 * 1.x hooked this to save_post with a zero-argument callback while
		 * WordPress passes three arguments, which is a fatal TypeError. We
		 * accept the correct signature and only *flag* a rebuild, so a bulk
		 * edit does not regenerate the sitemaps dozens of times per request.
		 */
		add_action( 'save_post', array( $this, 'on_save_post' ), 99, 3 );
		add_action( 'deleted_post', array( $this, 'on_deleted_post' ) );
		add_action( 'trashed_post', array( $this, 'on_deleted_post' ) );

		add_action( self::CRON_HOOK, array( $this, 'run_scheduled_rebuild' ) );

		// Rebuild once, on shutdown, if a change was flagged during this request.
		add_action( 'shutdown', array( $this, 'maybe_rebuild_on_shutdown' ) );

		// Keep the cron event healthy on updated (not just freshly activated) sites.
		add_action( 'admin_init', array( $this, 'ensure_cron' ) );

		add_action( 'update_option_' . Settings::OPTION, array( $this, 'on_settings_change' ), 10, 2 );

		add_filter( 'plugin_action_links_' . plugin_basename( SMART_SITEMAP_FILE ), array( $this, 'action_links' ) );
	}

	/**
	 * Activation handler.
	 *
	 * @return void
	 */
	public static function activate(): void {
		$plugin = new self();
		$plugin->ensure_cron( true );
	}

	/**
	 * Deactivation handler.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		wp_clear_scheduled_hook( self::CRON_HOOK );
		delete_option( self::FLAG );
	}

	/**
	 * Schedule/repair the regeneration cron event.
	 *
	 * @param bool $force Force reschedule.
	 * @return void
	 */
	public function ensure_cron( bool $force = false ): void {
		if ( $force ) {
			wp_clear_scheduled_hook( self::CRON_HOOK );
		}

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Flag a rebuild when a published post changes.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @param bool     $update  Whether this is an update.
	 * @return void
	 */
	public function on_save_post( $post_id, $post, $update ): void {
		unset( $update );

		if ( ! Settings::is_active() || ! Settings::auto_trigger() ) {
			return;
		}

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( 'publish' !== $post->post_status ) {
			return;
		}

		$post_types = (array) Settings::get( 'posttypes', array() );

		if ( ! in_array( $post->post_type, $post_types, true ) ) {
			return;
		}

		update_option( self::FLAG, 1, false );
	}

	/**
	 * Flag a rebuild when a post is removed.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function on_deleted_post( $post_id ): void {
		if ( ! Settings::is_active() ) {
			return;
		}

		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$post_types = (array) Settings::get( 'posttypes', array() );

		if ( in_array( $post->post_type, $post_types, true ) ) {
			update_option( self::FLAG, 1, false );
		}
	}

	/**
	 * Rebuild at the end of the request when a change was flagged.
	 *
	 * @return void
	 */
	public function maybe_rebuild_on_shutdown(): void {
		if ( ! Settings::is_active() ) {
			return;
		}

		if ( ! get_option( self::FLAG ) ) {
			return;
		}

		delete_option( self::FLAG );

		$this->generator->generate( (array) Settings::get( 'posttypes', array() ) );
	}

	/**
	 * Cron callback: rebuild only when the TTL has elapsed.
	 *
	 * @return void
	 */
	public function run_scheduled_rebuild(): void {
		if ( ! Settings::is_active() ) {
			return;
		}

		if ( ! $this->generator->is_stale() ) {
			return;
		}

		$this->generator->generate( (array) Settings::get( 'posttypes', array() ) );
	}

	/**
	 * Rebuild immediately when the settings are saved.
	 *
	 * @param mixed $old_value Old value.
	 * @param mixed $value     New value.
	 * @return void
	 */
	public function on_settings_change( $old_value, $value ): void {
		unset( $old_value, $value );

		if ( ! Settings::is_active() ) {
			$this->generator->clean();

			return;
		}

		$this->generator->generate( (array) Settings::get( 'posttypes', array() ) );
	}

	/**
	 * Add a settings shortcut to the plugins list.
	 *
	 * @param array<int, string> $links Existing links.
	 * @return array<int, string>
	 */
	public function action_links( $links ) {
		if ( ! is_array( $links ) ) {
			return $links;
		}

		if ( current_user_can( 'manage_options' ) ) {
			array_unshift(
				$links,
				sprintf(
					'<a href="%s">%s</a>',
					esc_url( admin_url( 'options-general.php?page=' . Settings::PAGE ) ),
					esc_html__( 'Settings', 'smart-sitemap-generator' )
				)
			);
		}

		return $links;
	}
}
