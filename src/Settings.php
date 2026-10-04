<?php
/**
 * Settings storage and admin UI.
 *
 * @package OptimistHub\SmartSitemap
 */

declare( strict_types = 1 );

namespace OptimistHub\SmartSitemap;

defined( 'ABSPATH' ) || exit;

/**
 * Reads, validates and renders the plugin settings.
 */
final class Settings {

	/**
	 * Option name. Unchanged from 1.x so existing settings survive upgrade.
	 */
	public const OPTION = 'smartsitemap__options';

	/**
	 * Settings group used by the Settings API.
	 */
	public const GROUP = 'smartsitemap-setting';

	/**
	 * Page slug.
	 */
	public const PAGE = 'smart-sitemap-generator';

	/**
	 * Default values.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'is_active'    => 'yes',
			'auto_trigger' => 'yes',
			'ttl'          => '-1 days',
			'posttypes'    => array( 'post', 'page' ),
		);
	}

	/**
	 * All settings, merged over defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		$stored = get_option( self::OPTION, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$options = wp_parse_args( $stored, self::defaults() );

		$options['is_active']    = self::sanitize_yes_no( $options['is_active'] );
		$options['auto_trigger'] = self::sanitize_yes_no( $options['auto_trigger'] );
		$options['ttl']          = self::sanitize_ttl( $options['ttl'] );
		$options['posttypes']    = self::sanitize_post_types( $options['posttypes'] );

		return $options;
	}

	/**
	 * A single setting.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $fallback Fallback.
	 * @return mixed
	 */
	public static function get( string $key, $fallback = null ) {
		$options = self::all();

		return array_key_exists( $key, $options ) ? $options[ $key ] : $fallback;
	}

	/**
	 * Whether sitemap generation is switched on.
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return 'yes' === self::get( 'is_active' );
	}

	/**
	 * Whether publishing a post should rebuild the sitemaps.
	 *
	 * @return bool
	 */
	public static function auto_trigger(): bool {
		return 'yes' === self::get( 'auto_trigger' );
	}

	/**
	 * Allowed TTL values mapped to seconds.
	 *
	 * @return array<string, int>
	 */
	public static function ttl_choices(): array {
		return array(
			'-1 days'  => DAY_IN_SECONDS,
			'-7 days'  => WEEK_IN_SECONDS,
			'-15 days' => 15 * DAY_IN_SECONDS,
			'-30 days' => 30 * DAY_IN_SECONDS,
		);
	}

	/**
	 * Normalise a yes/no value.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_yes_no( $value ): string {
		return 'yes' === $value ? 'yes' : 'no';
	}

	/**
	 * Normalise a TTL value.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_ttl( $value ): string {
		$choices = self::ttl_choices();

		if ( is_string( $value ) && array_key_exists( $value, $choices ) ) {
			return $value;
		}

		return '-1 days';
	}

	/**
	 * Normalise a post-type list, keeping only public, queryable types.
	 *
	 * @param mixed $value Raw value.
	 * @return array<int, string>
	 */
	public static function sanitize_post_types( $value ): array {
		if ( ! is_array( $value ) ) {
			$value = array();
		}

		$value = array_values( array_filter( array_map( 'sanitize_key', $value ) ) );

		return array_values( array_filter( $value, 'post_type_exists' ) );
	}

	/**
	 * Register the settings page and fields.
	 *
	 * @return void
	 */
	public function register(): void {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);

		add_settings_section(
			'smartsitemap_section',
			__( 'Smart Sitemaps Options', 'smart-sitemap-generator' ),
			array( $this, 'section_intro' ),
			self::GROUP
		);

		add_settings_field(
			'is_active',
			__( 'Generate sitemaps smartly?', 'smart-sitemap-generator' ),
			array( $this, 'render_select' ),
			self::GROUP,
			'smartsitemap_section',
			array(
				'id'      => 'is_active',
				'options' => array(
					'yes' => __( 'Yes', 'smart-sitemap-generator' ),
					'no'  => __( 'No', 'smart-sitemap-generator' ),
				),
			)
		);

		add_settings_field(
			'auto_trigger',
			__( 'Regenerate sitemaps after updating/publishing posts?', 'smart-sitemap-generator' ),
			array( $this, 'render_select' ),
			self::GROUP,
			'smartsitemap_section',
			array(
				'id'      => 'auto_trigger',
				'options' => array(
					'yes' => __( 'Yes', 'smart-sitemap-generator' ),
					'no'  => __( 'No', 'smart-sitemap-generator' ),
				),
			)
		);

		add_settings_field(
			'ttl',
			__( 'Sitemap regeneration interval?', 'smart-sitemap-generator' ),
			array( $this, 'render_select' ),
			self::GROUP,
			'smartsitemap_section',
			array(
				'id'      => 'ttl',
				'options' => array(
					'-1 days'  => __( '24 Hours', 'smart-sitemap-generator' ),
					'-7 days'  => __( '1 Week', 'smart-sitemap-generator' ),
					'-15 days' => __( '15 Days', 'smart-sitemap-generator' ),
					'-30 days' => __( '30 Days', 'smart-sitemap-generator' ),
				),
			)
		);

		add_settings_field(
			'posttypes',
			__( 'Select post types for sitemaps', 'smart-sitemap-generator' ),
			array( $this, 'render_checkboxes' ),
			self::GROUP,
			'smartsitemap_section',
			array( 'id' => 'posttypes' )
		);
	}

	/**
	 * Add the options page.
	 *
	 * @return void
	 */
	public function add_page(): void {
		add_options_page(
			__( 'Smart Sitemap Options', 'smart-sitemap-generator' ),
			__( 'Smart Sitemap', 'smart-sitemap-generator' ),
			'manage_options',
			self::PAGE,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Sanitize the whole option array.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ): array {
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		return array(
			'is_active'    => self::sanitize_yes_no( $input['is_active'] ?? 'no' ),
			'auto_trigger' => self::sanitize_yes_no( $input['auto_trigger'] ?? 'no' ),
			'ttl'          => self::sanitize_ttl( $input['ttl'] ?? '-1 days' ),
			'posttypes'    => self::sanitize_post_types( $input['posttypes'] ?? array() ),
		);
	}

	/**
	 * Section description.
	 *
	 * @return void
	 */
	public function section_intro(): void {
		$index = Generator::index_url();

		printf(
			'<p>%s</p><p><code>%s</code></p>',
			esc_html__( 'Your sitemap index is available at the URL below. Submit it to Google, Bing and Yandex.', 'smart-sitemap-generator' ),
			esc_html( $index )
		);
	}

	/**
	 * Render a select control.
	 *
	 * @param array<string, mixed> $args Field args.
	 * @return void
	 */
	public function render_select( $args ): void {
		$id      = isset( $args['id'] ) ? (string) $args['id'] : '';
		$choices = isset( $args['options'] ) && is_array( $args['options'] ) ? $args['options'] : array();
		$current = (string) self::get( $id, '' );

		printf( '<select name="%s" id="%s">', esc_attr( self::OPTION . '[' . $id . ']' ), esc_attr( $id ) );

		foreach ( $choices as $value => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( (string) $value ),
				selected( $current, (string) $value, false ),
				esc_html( (string) $label )
			);
		}

		echo '</select>';
	}

	/**
	 * Render checkbox controls for post types.
	 *
	 * @param array<string, mixed> $args Field args.
	 * @return void
	 */
	public function render_checkboxes( $args ): void {
		unset( $args );

		$selected = (array) self::get( 'posttypes', array() );
		$types    = get_post_types( array( 'public' => true ), 'objects' );

		if ( empty( $types ) ) {
			esc_html_e( 'No public post types found.', 'smart-sitemap-generator' );

			return;
		}

		echo '<fieldset>';

		foreach ( $types as $type ) {
			// Attachments are media files, not addressable content pages.
			if ( 'attachment' === $type->name ) {
				continue;
			}

			printf(
				'<label style="display:block;margin-bottom:4px;"><input type="checkbox" name="%s" value="%s" %s /> %s <code>%s</code></label>',
				esc_attr( self::OPTION . '[posttypes][]' ),
				esc_attr( $type->name ),
				checked( in_array( $type->name, $selected, true ), true, false ),
				esc_html( $type->labels->singular_name ),
				esc_html( $type->name )
			);
		}

		echo '</fieldset>';
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::GROUP );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
