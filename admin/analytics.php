<?php
/**
 * Analytics class helps to connect BSFAnalytics.
 *
 * @package sureforms.
 */

namespace SRFM\Admin;

use SRFM\Inc\AI_Form_Builder\AI_Helper;
use SRFM\Inc\Database\Register;
use SRFM\Inc\Database\Tables\Entries;
use SRFM\Inc\Email_Delivery\Status as Email_Delivery_Status;
use SRFM\Inc\Form_Start;
use SRFM\Inc\Form_Templates;
use SRFM\Inc\Form_Views;
use SRFM\Inc\Helper;
use SRFM\Inc\Learn;
use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Analytics class.
 *
 * @since 1.4.0
 */
class Analytics {
	use Get_Instance;

	/**
	 * Option holding the Add New Form start funnel totals.
	 *
	 * @since 2.13.0
	 */
	public const FORM_START_FUNNEL_OPTION = 'srfm_form_start_funnel';

	/**
	 * Option holding the template create, swap, view and upgrade counts. Like
	 * the start funnel, it has its own row: counting rewrites the whole value,
	 * and in srfm_options that would also rewrite every setting stored there.
	 *
	 * @since 2.13.0
	 */
	public const FORM_TEMPLATE_USAGE_OPTION = 'srfm_form_template_usage';

	/**
	 * Option holding the template library's search and request counts, in its
	 * own row for the same reason.
	 *
	 * @since 2.13.0
	 */
	public const FORM_TEMPLATE_DEMAND_OPTION = 'srfm_form_template_demand';

	/**
	 * Ajax action backing the deactivation-survey checkbox.
	 *
	 * Shared between the survey config that declares it and the handler that
	 * serves it, so the two cannot drift.
	 *
	 * @since 2.13.0
	 */
	public const UNINSTALL_PREF_ACTION = 'srfm_set_uninstall_pref';

	/**
	 * Key inside srfm_options holding the uninstall opt-in.
	 *
	 * The uninstall script reads this key straight out of the row, since the
	 * plugin is not loaded by then and this class does not exist.
	 *
	 * @since 2.13.0
	 */
	public const UNINSTALL_PREF_KEY = 'remove_analytics_data_on_uninstall';

	/**
	 * BSF_Analytics_Events instance for one-time event tracking.
	 *
	 * @var \BSF_Analytics_Events|null
	 */
	private static $events = null;

	/**
	 * Class constructor.
	 *
	 * @return void
	 * @since 1.4.0
	 */
	public function __construct() {
		/*
		* BSF Analytics.
		*/
		if ( ! class_exists( 'BSF_Analytics_Loader' ) ) {
			require_once SRFM_DIR . 'inc/lib/bsf-analytics/class-bsf-analytics-loader.php';
		}

		if ( ! class_exists( 'BSF_Admin_Notices' ) ) {
			require_once SRFM_DIR . 'inc/lib/astra-notices/class-bsf-admin-notices.php';
		}

		add_filter(
			'uds_survey_allowed_screens',
			static function () {
				return [ 'plugins' ];
			}
		);

		add_action( 'init', [ $this, 'register_analytics_entity' ], 0 );

		// Saves the uninstall preference from the deactivation survey checkbox.
		add_action( 'wp_ajax_' . self::UNINSTALL_PREF_ACTION, [ $this, 'save_uninstall_preference' ] );

		add_filter( 'bsf_core_stats', [ $this, 'add_srfm_analytics_data' ] );

		// Event tracking hooks.
		add_action( 'current_screen', [ $this, 'track_first_editor_open' ] );
		add_action( 'transition_post_status', [ $this, 'track_first_form_published' ], 10, 3 );
		add_action( 'save_post', [ $this, 'track_embed_styling_configured' ], 10, 2 );
		add_action( 'admin_init', [ $this, 'track_email_delivery_status' ] );

		// Detect state-based events on admin load (dedup prevents repeat tracking).
		$this->detect_state_events();
	}

	/**
	 * Register the analytics entity, including the deactivation survey.
	 *
	 * On init at priority 0 rather than in the constructor, which runs while
	 * plugins are still being required - before wp-includes/pluggable.php
	 * exists. current_user_can() and wp_create_nonce() below both call the
	 * pluggable wp_get_current_user(): undefined that early, it fatals the whole
	 * site.
	 *
	 * Priority 0 is the latest safe point: the library reads its entities on
	 * init at the default priority 10 (BSF_Analytics_Loader::load_analytics). It
	 * is also late enough that an add-on filtering srfm_deactivation_survey_data
	 * from plugins_loaded is in time.
	 *
	 * @since 2.13.0
	 * @return void
	 */
	public function register_analytics_entity() {
		$srfm_bsf_analytics = \BSF_Analytics_Loader::get_instance();

		$srfm_bsf_analytics->set_entity(
			[
				'sureforms' => [
					'product_name'        => 'SureForms',
					'path'                => SRFM_DIR . 'inc/lib/bsf-analytics',
					'author'              => 'SureForms',
					'time_to_display'     => '+24 hours',
					'deactivation_survey' => apply_filters(
						'srfm_deactivation_survey_data',
						[
							[
								'id'                => 'deactivation-survey-sureforms',
								'popup_logo'        => SRFM_URL . 'admin/assets/sureforms-logo.png',
								'plugin_slug'       => 'sureforms',
								'popup_title'       => 'Quick Feedback',
								'support_url'       => 'https://sureforms.com/contact/',
								'popup_description' => 'If you have a moment, please share why you are deactivating SureForms:',
								'show_on_screens'   => [ 'plugins' ],
								'plugin_version'    => SRFM_VER,

								/*
								 * Offers the uninstall preference at the moment
								 * the decision is being made. This is the only
								 * surface for the flag, so the copy has to carry
								 * the whole promise on its own.
								 *
								 * Only offered to someone who could act on it, so
								 * the control is never rendered for a user the
								 * handler would reject.
								 *
								 * is_admin() first, and not merely as an
								 * optimisation: the popup renders only on the
								 * plugins screen (see uds_survey_allowed_screens
								 * above), while this runs on every request. Asking
								 * current_user_can() on a front-end view would
								 * resolve and pin the current user on every page
								 * load, ahead of anything that sets it on init.
								 */
								'extra_options'     => is_admin() && current_user_can( 'manage_options' ) ? [
									[
										'type'        => 'checkbox',
										'name'        => self::UNINSTALL_PREF_KEY,
										'label'       => __( 'Check this to remove the usage data SureForms has collected when you delete the plugin. Note: your forms, entries and settings are never deleted.', 'sureforms' ),
										'checked'     => true === Helper::get_srfm_option( self::UNINSTALL_PREF_KEY, false ),
										'ajax_action' => self::UNINSTALL_PREF_ACTION,
										'nonce'       => wp_create_nonce( self::UNINSTALL_PREF_ACTION ),
									],
								] : [],
							],
						]
					),
					'hide_optin_checkbox' => true,
				],
			]
		);
	}

	/**
	 * Save the uninstall preference set from the deactivation survey.
	 *
	 * Writing this neither deactivates nor deletes anything: it only records
	 * what should happen if the plugin is deleted later, and it stays
	 * reversible - the box reflects the stored value next time the popup opens.
	 *
	 * The library posts the value as the string '1' or '0' on change, so this
	 * compares strictly and stores a real boolean. A cast would read 'no' as
	 * consent, and uninstall.php's gate only accepts boolean true.
	 *
	 * @since 2.13.0
	 * @return void
	 */
	public function save_uninstall_preference() {

		if ( ! check_ajax_referer( self::UNINSTALL_PREF_ACTION, 'security', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid nonce.', 'sureforms' ) ], 403 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to change this setting.', 'sureforms' ) ], 403 );
		}

		$checked = isset( $_POST['checked'] ) ? sanitize_text_field( wp_unslash( $_POST['checked'] ) ) : '';

		Helper::update_srfm_option( self::UNINSTALL_PREF_KEY, '1' === $checked );

		wp_send_json_success();
	}

	/**
	 * Get the shared BSF_Analytics_Events instance.
	 *
	 * Uses SureForms' Helper option methods so data stays in the
	 * existing srfm_options row — zero migration required.
	 *
	 * @since 2.7.0
	 * @return \BSF_Analytics_Events
	 */
	public static function events() {
		if ( null === self::$events ) {
			if ( ! class_exists( 'BSF_Analytics_Events' ) ) {
				require_once SRFM_DIR . 'inc/lib/bsf-analytics/class-bsf-analytics-events.php';
			}

			self::$events = new \BSF_Analytics_Events(
				'sureforms',
				[
					'get'    => [ Helper::class, 'get_srfm_option' ],
					'update' => [ Helper::class, 'update_srfm_option' ],
				]
			);
		}
		return self::$events;
	}

	/**
	 * Callback function to add SureForms specific analytics data.
	 *
	 * @param array $stats_data existing stats_data.
	 * @since 1.4.0
	 * @return array
	 */
	public function add_srfm_analytics_data( $stats_data ) {
		$stats_data['plugin_data']['sureforms'] = [
			'free_version'          => SRFM_VER,
			'site_language'         => get_locale(),
			'most_used_anti_spam'   => $this->most_used_anti_spam(),
			'user_status'           => $this->user_status(),
			'pointer_popup_clicked' => $this->pointer_popup_clicked(),
		];
		// Every query against the entries table errors when the table is missing, so
		// resolve that once here rather than letting each caller below trip over it.
		$entries_table_missing = Register::is_entries_table_missing();

		$stats_data['plugin_data']['sureforms']['numeric_values'] = [
			'total_forms'                => wp_count_posts( SRFM_FORMS_POST_TYPE )->publish ?? 0,
			'instant_forms_enabled'      => $this->instant_forms_enabled(),
			'forms_using_custom_css'     => $this->forms_using_custom_css(),
			'ai_generated_forms'         => $this->ai_generated_forms(),
			'ai_generated_payment_forms' => $this->ai_generated_forms( 'payments' ),
			'payment_forms'              => $this->get_payment_forms_count(),
			'total_entries'              => $entries_table_missing ? 0 : Entries::get_total_entries_by_status(),
			'restricted_forms'           => $this->get_restricted_forms(),
			'embed_styling_gb_default'   => self::embed_styling_gutenberg_count( 'default' ),
			'embed_styling_el_default'   => self::embed_styling_elementor_count( 'default' ),
			'embed_styling_br_default'   => self::embed_styling_bricks_count( 'default' ),
			'total_form_views'           => $this->total_form_views(),
			'forms_with_views'           => $this->forms_with_views(),
		];

		// Whether the entries table is currently missing. An event fires once when a
		// site first sees the notice; this is what shows the state persisting, and
		// catches a recurrence that the event's one-time dedup would swallow.
		$stats_data['plugin_data']['sureforms']['boolean_values']['db_entries_table_missing'] = $entries_table_missing;

		$stats_data['plugin_data']['sureforms'] = array_merge_recursive( $stats_data['plugin_data']['sureforms'], $this->global_settings_data() );
		// Add KPI tracking data.
		$kpi_data = $this->get_kpi_tracking_data();
		if ( ! empty( $kpi_data ) ) {
			$stats_data['plugin_data']['sureforms']['kpi_records'] = $kpi_data;
		}

		// Build learn progress snapshot.
		$this->get_learn_tracking_data();

		// Recount published template forms so this report carries current numbers.
		self::refresh_form_template_usage();
		self::refresh_form_start_funnel();

		// Flush pending events into payload (only if any exist).
		$pending_events = self::events()->flush_pending();
		if ( ! empty( $pending_events ) ) {
			$stats_data['plugin_data']['sureforms']['events_record'] = $pending_events;
		}

		return $stats_data;
	}

	/**
	 * Return total number of forms using instant forms.
	 *
	 * @since 1.4.0
	 * @return int
	 */
	public function instant_forms_enabled() {
		$meta_query = [
			[
				'key'     => '_srfm_instant_form_settings',
				'value'   => '"enable_instant_form";b:1;',
				'compare' => 'LIKE',
			],
		];

		return $this->custom_wp_query_total_posts( $meta_query );
	}

	/**
	 * Return total number of ai generated forms.
	 *
	 * @param string $form_type Form type to check.
	 *
	 * @since 1.4.0
	 * @return int
	 */
	public function ai_generated_forms( $form_type = '' ) {
		$form_type  = empty( $form_type ) || ! is_string( $form_type ) ? '' : $form_type;
		$meta_query = [
			[
				'key'     => '_srfm_is_ai_generated',
				'value'   => '',
				'compare' => '!=', // Checks if the value is NOT empty.
			],
		];

		if ( 'payments' === $form_type ) {
			$search = 'wp:srfm/payment';
			return $this->custom_wp_query_total_posts_with_search( $meta_query, $search );
		}

		return $this->custom_wp_query_total_posts( $meta_query );
	}

	/**
	 * Return most used anti-spam type on this site.
	 *
	 * @since 1.4.4
	 * @return int
	 */
	public function most_used_anti_spam() {
		global $wpdb;

		// Attempt to get from cache first.
		$cache_key     = 'most_used_anti_spam';
		$cached_result = wp_cache_get( $cache_key, 'sureforms' );

		if ( false !== $cached_result ) {
			return $cached_result;
		}

		$meta_key = '_srfm_captcha_security_type';

		// Query to get the most used captcha type.
		// PHPCS: Ignore direct database query warning, as there is no built-in alternative.
    	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->get_row(
			$wpdb->prepare(
				"
			SELECT meta_value, COUNT(meta_value) as count
			FROM {$wpdb->postmeta}
			WHERE meta_key = %s
			AND meta_value != ''
			GROUP BY meta_value
			ORDER BY count DESC
			LIMIT 1
		",
				$meta_key
			),
			ARRAY_A
		);

		$output = '';
		if ( $result && ! empty( $result['meta_value'] ) ) {
			switch ( $result['meta_value'] ) {
				case 'g-recaptcha':
					$output = 'Google reCAPTCHA';
					break;

				case 'cf-turnstile':
					$output = 'CloudFlare Turnstile';
					break;

				case 'hcaptcha':
					$output = 'hCaptcha';
					break;

				default:
					$output = '';
					break;
			}
		}

		// Store result in cache for 1 hour.
		wp_cache_set( $cache_key, $output, 'sureforms', HOUR_IN_SECONDS );

		return $output;
	}

	/**
	 * Returns total number of forms using custom css.
	 *
	 * @since 1.4.0
	 * @return int
	 */
	public function forms_using_custom_css() {
		$meta_query = [
			[
				'key'     => '_srfm_form_custom_css',
				'value'   => '',
				'compare' => '!=', // Checks if the value is NOT empty.
			],
		];

		return $this->custom_wp_query_total_posts( $meta_query );
	}

	/**
	 * Count Gutenberg srfm/form embed blocks using a specific formTheme.
	 *
	 * When formTheme is 'inherit' (the block.json default), WordPress does not
	 * serialize it in the block comment. So only 'default' and 'custom' appear.
	 *
	 * Counts individual embed blocks (not pages), since a single page can
	 * contain multiple form embeds each with different styling.
	 *
	 * @param string $theme Theme slug to count ('default' or 'custom').
	 * @since 2.7.0
	 * @return int
	 */
	public static function embed_styling_gutenberg_count( $theme ) {
		global $wpdb;

		$cache_key     = 'embed_styling_gb_' . $theme;
		$cached_result = wp_cache_get( $cache_key, 'sureforms' );

		if ( false !== $cached_result ) {
			return (int) $cached_result;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- No WP_Query alternative for cross-post-type content search with multiple LIKE conditions.
		$posts = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_content FROM {$wpdb->posts}
				WHERE post_status = 'publish'
				AND post_content LIKE %s
				AND post_content LIKE %s",
				'%' . $wpdb->esc_like( 'wp:srfm/form' ) . '%',
				'%' . $wpdb->esc_like( '"formTheme":"' . $theme . '"' ) . '%'
			)
		);

		$count         = 0;
		$escaped_theme = preg_quote( $theme, '/' );
		foreach ( $posts as $content ) {
			$count += preg_match_all( '/<!--\s*wp:srfm\/form\s+\{[^}]*"formTheme"\s*:\s*"' . $escaped_theme . '"/', $content );
		}

		wp_cache_set( $cache_key, $count, 'sureforms', HOUR_IN_SECONDS );

		return $count;
	}

	/**
	 * Count Elementor sureforms_form widgets using a specific formTheme.
	 *
	 * Searches _elementor_data post meta for sureforms_form widgets with
	 * a non-inherit formTheme. Counts individual widgets (not pages).
	 *
	 * @param string $theme Theme slug to count ('default' or 'custom').
	 * @since 2.7.0
	 * @return int
	 */
	public static function embed_styling_elementor_count( $theme ) {
		global $wpdb;

		$cache_key     = 'embed_styling_el_' . $theme;
		$cached_result = wp_cache_get( $cache_key, 'sureforms' );

		if ( false !== $cached_result ) {
			return (int) $cached_result;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- No WP_Query alternative for post meta content search with multiple LIKE conditions.
		$meta_values = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT pm.meta_value FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE p.post_status = 'publish'
				AND pm.meta_key = '_elementor_data'
				AND pm.meta_value LIKE %s
				AND pm.meta_value LIKE %s",
				'%' . $wpdb->esc_like( 'sureforms_form' ) . '%',
				'%' . $wpdb->esc_like( '"formTheme":"' . $theme . '"' ) . '%'
			)
		);

		$count         = 0;
		$escaped_theme = preg_quote( $theme, '/' );
		foreach ( $meta_values as $json ) {
			// Count widget instances with the specific formTheme in the JSON.
			$count += preg_match_all( '/"widgetType"\s*:\s*"sureforms_form"[^}]*"formTheme"\s*:\s*"' . $escaped_theme . '"/', $json );
		}

		wp_cache_set( $cache_key, $count, 'sureforms', HOUR_IN_SECONDS );

		return $count;
	}

	/**
	 * Count Bricks sureforms elements using a specific formTheme.
	 *
	 * Searches _bricks_page_content_2 post meta (serialized PHP) for
	 * sureforms elements with a non-inherit formTheme. Counts individual elements.
	 *
	 * @param string $theme Theme slug to count ('default' or 'custom').
	 * @since 2.7.0
	 * @return int
	 */
	public static function embed_styling_bricks_count( $theme ) {
		global $wpdb;

		$cache_key     = 'embed_styling_br_' . $theme;
		$cached_result = wp_cache_get( $cache_key, 'sureforms' );

		if ( false !== $cached_result ) {
			return (int) $cached_result;
		}

		// Bricks stores element data as serialized PHP in _bricks_page_content_2.
		// In serialized format: s:9:"formTheme";s:7:"default" (for 'default' theme).
		$serialized_theme = sprintf( '"formTheme";s:%d:"%s"', strlen( $theme ), $theme );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- No WP_Query alternative for post meta content search with multiple LIKE conditions.
		$meta_values = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT pm.meta_value FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE p.post_status = 'publish'
				AND pm.meta_key = '_bricks_page_content_2'
				AND pm.meta_value LIKE %s
				AND pm.meta_value LIKE %s",
				'%' . $wpdb->esc_like( '"sureforms"' ) . '%',
				'%' . $wpdb->esc_like( $serialized_theme ) . '%'
			)
		);

		$count                = 0;
		$escaped_serial_theme = preg_quote( $serialized_theme, '/' );
		foreach ( $meta_values as $data ) {
			$count += preg_match_all( '/' . $escaped_serial_theme . '/', $data );
		}

		wp_cache_set( $cache_key, $count, 'sureforms', HOUR_IN_SECONDS );

		return $count;
	}

	/**
	 * Return total number of restricted forms.
	 *
	 * @since 1.10.1
	 * @return int
	 */
	public function get_restricted_forms() {
		$meta_query = [
			[
				'key'     => '_srfm_form_restriction',
				'value'   => '"status":true',
				'compare' => 'LIKE',
			],
		];

		return $this->custom_wp_query_total_posts( $meta_query );
	}

	/**
	 * Generates global setting data for analytics
	 *
	 * @since 1.4.0
	 * @return array
	 */
	public function global_settings_data() {
		$global_data = [];

		$security_settings                                 = get_option( 'srfm_security_settings_options', [] );
		$global_data['boolean_values']['honeypot_enabled'] = isset( $security_settings['srfm_honeypot'] ) && true === $security_settings['srfm_honeypot'];

		$email_summary_data                                     = get_option( 'srfm_email_summary_settings_options', [] );
		$global_data['boolean_values']['email_summary_enabled'] = isset( $email_summary_data['srfm_email_summary'] ) && true === $email_summary_data['srfm_email_summary'];

		$global_data['boolean_values']['suretriggers_active'] = is_plugin_active( 'suretriggers/suretriggers.php' );

		$bsf_internal_referrer = get_option( 'bsf_product_referers', [] );
		if ( ! empty( $bsf_internal_referrer['sureforms'] ) ) {
			$global_data['internal_referer'] = $bsf_internal_referrer['sureforms'];
		} else {
			$global_data['internal_referer'] = '';
		}

		$general_settings                                    = get_option( 'srfm_general_settings_options', [] );
		$global_data['boolean_values']['ip_logging_enabled'] = ! empty( $general_settings['srfm_ip_log'] );

		$validation_messages                                        = get_option( 'srfm_default_dynamic_block_option', [] );
		$global_data['boolean_values']['custom_validation_message'] = ! empty( $validation_messages ) && is_array( $validation_messages );

		// Payment analytics - check if any payment method is enabled.
		$global_data['boolean_values']['stripe_enabled'] = $this->is_stripe_enabled();

		// Delegated rather than read from $general_settings directly, so the telemetry
		// can never disagree with what the Forms list actually shows. That matters
		// because the default has already changed once: reimplementing the check here
		// means a future change has two places to be made and one to be forgotten.
		$global_data['boolean_values']['form_views_columns_enabled'] = Form_Views::get_instance()->is_tracking_enabled();

		return $global_data;
	}

	/**
	 * Total page views counted across all published forms.
	 *
	 * Read alongside `forms_with_views`, which is what makes this figure legible: a
	 * site with views but no forms recording any reads very differently from one
	 * with neither.
	 *
	 * Not a denominator for `total_entries`, and the two must not be divided. This
	 * total starts at the moment tracking opened and leaves out anyone who can edit
	 * the site, while `total_entries` is an unfiltered all-time count of every
	 * entry ever received. A rate built from the pair answers neither question and
	 * will not match the Conversion Rate column, which measures both halves over
	 * the same window with the same exclusion.
	 *
	 * Counts only published forms, so views left behind by a trashed or draft form
	 * do not inflate the total against a denominator that no longer includes them.
	 *
	 * @since 2.12.6
	 * @return int
	 */
	public function total_form_views() {
		global $wpdb;

		// Deliberately uncached: the only caller is the analytics payload builder,
		// which runs once per cron pass. A cache would never see a second read inside
		// a request, and any TTL would always have expired between passes — while
		// still going stale if a second caller ever appeared.
		// PHPCS: Ignore direct database query warning, as there is no built-in alternative.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total = $wpdb->get_var(
			$wpdb->prepare(
				"
			SELECT SUM( pm.meta_value )
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = %s
			AND p.post_type = %s
			AND p.post_status = 'publish'
		",
				Form_Views::META_KEY,
				SRFM_FORMS_POST_TYPE
			)
		);

		return Helper::get_integer_value( $total );
	}

	/**
	 * Number of published forms that have been viewed at least once.
	 *
	 * Distinguishes "the feature is on but nothing is embedded anywhere" from "the
	 * beacon is firing" — a total alone cannot, because one busy form looks the
	 * same as many quiet ones.
	 *
	 * @since 2.12.6
	 * @return int
	 */
	public function forms_with_views() {
		return $this->custom_wp_query_total_posts(
			[
				[
					'key'     => Form_Views::META_KEY,
					'value'   => 0,
					'compare' => '>',
					'type'    => 'NUMERIC',
				],
			]
		);
	}

	/**
	 * Returns user status.
	 *
	 * @since 1.8.0
	 * @return string
	 */
	public function user_status() {
		// First, check if user_active is already set in srfm_options.
		if ( Helper::get_srfm_option( 'user_active', false ) ) {
			return 'active';
		}
		// Get up to 10 published SureForms.
		$forms = get_posts(
			[
				'post_type'      => SRFM_FORMS_POST_TYPE,
				'posts_per_page' => 10,
				'post_status'    => 'publish',
			]
		);
		if ( empty( $forms ) ) {
			return 'inactive';
		}
		foreach ( $forms as $form ) {
			if ( ! get_post_meta( $form->ID, '_astra_sites_imported_post', true ) ) {
				// Mark user as active in srfm_options.
				Helper::update_srfm_option( 'user_active', true );
				return 'active';
			}
		}
		return 'inactive';
	}

	/**
	 * Return pointer popup clicked status.
	 *
	 * @return string pointer click status.
	 * @since 1.8.0
	 */
	public function pointer_popup_clicked() {
		// Get both values from srfm_options.
		$accepted  = Helper::get_srfm_option( 'pointer_popup_accepted', false );
		$dismissed = Helper::get_srfm_option( 'pointer_popup_dismissed', false );
		// If neither action has occurred.
		if ( ! $accepted && ! $dismissed ) {
			return '';
		}

		// If both are set, return the most recent one.
		if ( $accepted && $dismissed ) {
			return $accepted > $dismissed ? 'accepted' : 'dismissed';
		}

		// If only one is set, return it.
		return $accepted ? 'accepted' : 'dismissed';
	}

	/**
	 * Runs a custom WP_Query to fetch the total number of posts matching the given meta query and optional search string.
	 *
	 * This function is used to count SureForms posts based on specific meta query conditions.
	 * Optionally, a search string can be included to further filter results by keyword match.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $meta_query Meta query array for WP_Query.
	 * @param string $search     Optional. Search string for WP_Query. Default empty.
	 * @return int               The number of matching posts.
	 */
	public function custom_wp_query_total_posts_with_search( $meta_query = [], $search = '' ) {
		$args = [
			'post_type'      => SRFM_FORMS_POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		];

		if ( ! empty( $meta_query ) && is_array( $meta_query ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Meta query required as we need to fetch count of nested data.
			$args['meta_query'] = $meta_query;
		}

		// If search string is provided, add it to the query.
		if ( ! empty( $search ) ) {
			$args['s'] = sanitize_text_field( $search );
		}

		$query       = new \WP_Query( $args );
		$posts_count = $query->found_posts;

		wp_reset_postdata();

		return $posts_count;
	}

	/**
	 * Get the total number of forms that utilize payment blocks.
	 *
	 * This function searches for forms containing the payment block identifier
	 * ('wp:srfm/payment') to determine how many forms include payment capabilities.
	 *
	 * @since 2.0.0
	 * @return int The number of forms that contain payment blocks.
	 */
	public function get_payment_forms_count() {
		$search = 'wp:srfm/payment';
		// Runs a custom WP_Query to find the count of forms with payment block.
		return $this->custom_wp_query_total_posts_with_search( [], $search );
	}

	/**
	 * Track first time a user opens the form editor.
	 *
	 * @since 2.5.1
	 * @return void
	 */
	public function track_first_editor_open() {
		$screen = get_current_screen();
		if ( $screen && 'sureforms_form' === $screen->id ) {
			self::events()->track( 'first_form_editor_opened' );
		}
	}

	/**
	 * Track first time a form is published (activation event).
	 *
	 * @param string   $new_status New post status.
	 * @param string   $old_status Old post status.
	 * @param \WP_Post $post       Post object.
	 * @since 2.5.1
	 * @return void
	 */
	public function track_first_form_published( $new_status, $old_status, $post ) {
		if ( 'publish' !== $new_status || 'publish' === $old_status || SRFM_FORMS_POST_TYPE !== $post->post_type ) {
			return;
		}

		$is_ai       = ! empty( get_post_meta( $post->ID, '_srfm_is_ai_generated', true ) );
		$block_count = substr_count( $post->post_content, '<!-- wp:srfm/' );

		// Time-to-value: days between install and first form published.
		$install_time       = get_site_option( 'sureforms_usage_installed_time', 0 );
		$days_since_install = 0;
		if ( $install_time > 0 ) {
			$days_since_install = (int) floor( ( time() - $install_time ) / DAY_IN_SECONDS );
		}

		self::events()->track(
			'first_form_published',
			(string) $post->ID,
			[
				'is_ai_generated'    => (string) (int) $is_ai,
				'block_count'        => (string) $block_count,
				'days_since_install' => (string) $days_since_install,
				'created_via'        => Helper::get_string_value( get_post_meta( $post->ID, '_srfm_created_via', true ) ),
				'template_id'        => Helper::get_string_value( get_post_meta( $post->ID, '_srfm_template_id', true ) ),
				'start_choice'       => Helper::get_string_value( get_post_meta( $post->ID, Form_Start::START_CHOICE_META, true ) ),
			]
		);
	}

	/**
	 * Track AI form creation, broken down by the starter card used (if any).
	 *
	 * Keeps cumulative counters in srfm_options and queues the latest totals as
	 * the `ai_starter_usage` event each time an AI form is created, so the share
	 * of AI forms started from each card can be read from the warehouse.
	 *
	 * @param mixed $starter_id Starter card id (sanitized here), or empty string for a free-text prompt.
	 * @param bool  $edited     Whether the starter prompt was edited before generating.
	 * @since 2.12.9
	 * @return void
	 */
	public static function track_ai_starter_usage( $starter_id, $edited ) {
		// The id becomes an option key and an event property key, so clean it here
		// rather than trusting every caller.
		$starter_id = substr( sanitize_key( Helper::get_string_value( $starter_id ) ), 0, 40 );

		$usage = Helper::get_srfm_option( 'ai_starter_usage', [] );
		$usage = is_array( $usage ) ? $usage : [];

		$counts = isset( $usage['starters'] ) && is_array( $usage['starters'] ) ? array_map( 'absint', $usage['starters'] ) : [];

		// Counts AI forms created since this tracking shipped, deleted ones included
		// (unlike the `ai_generated_forms` stat, which counts forms that exist now).
		$usage['ai_forms_created'] = absint( $usage['ai_forms_created'] ?? 0 ) + 1;

		// Cap distinct keys so a filtered starter list can't grow the option unbounded.
		if ( '' !== $starter_id && ( isset( $counts[ $starter_id ] ) || count( $counts ) < 20 ) ) {
			$counts[ $starter_id ] = absint( $counts[ $starter_id ] ?? 0 ) + 1;

			if ( $edited ) {
				$usage['edited'] = absint( $usage['edited'] ?? 0 ) + 1;
			}
		}

		$usage['starters'] = $counts;
		Helper::update_srfm_option( 'ai_starter_usage', $usage );

		$properties = [
			'ai_forms_created' => (string) $usage['ai_forms_created'],
			'edited'           => (string) absint( $usage['edited'] ?? 0 ),
		];
		foreach ( $counts as $id => $count ) {
			$properties[ 'starter_' . $id ] = (string) $count;
		}

		// $force = true: skip the already-sent check and replace any queued copy, so
		// the next flush sends these totals. Nothing is re-sent until another AI form
		// is created.
		self::events()->track( 'ai_starter_usage', (string) array_sum( $counts ), $properties, true );
	}

	/**
	 * Track forms started from a prebuilt template.
	 *
	 * Keeps cumulative counters in FORM_TEMPLATE_USAGE_OPTION and re-sends the
	 * latest totals as the `form_template_usage` event, so template popularity and how often
	 * templates are swapped inside the editor can be read from the warehouse.
	 * Nothing is kept or queued unless the site shares usage data.
	 *
	 * @param mixed  $template_id Template id (sanitized here).
	 * @param string $action      `create` for a new form, `swap` for a template swapped into an open form.
	 * @since 2.13.0
	 * @return void
	 */
	public static function track_form_template_usage( $template_id, $action = 'create' ) {
		if ( ! Form_Templates::is_usage_sharing_on() ) {
			return;
		}

		// The id becomes an option key and an event property key, so clean it here
		// rather than trusting every caller.
		$template_id = substr( sanitize_key( Helper::get_string_value( $template_id ) ), 0, 40 );
		if ( '' === $template_id ) {
			return;
		}

		$usage = Helper::get_array_value( get_option( self::FORM_TEMPLATE_USAGE_OPTION, [] ) );
		$usage = is_array( $usage ) ? $usage : [];

		$counts = isset( $usage['templates'] ) && is_array( $usage['templates'] ) ? array_map( 'absint', $usage['templates'] ) : [];

		if ( 'swap' === $action ) {
			$usage['swaps'] = absint( $usage['swaps'] ?? 0 ) + 1;
		} else {
			$usage['created'] = absint( $usage['created'] ?? 0 ) + 1;
		}

		// Cap distinct keys so a filtered template list can't grow the option unbounded.
		if ( isset( $counts[ $template_id ] ) || count( $counts ) < 40 ) {
			$counts[ $template_id ] = absint( $counts[ $template_id ] ?? 0 ) + 1;
		}

		$usage['templates'] = $counts;
		update_option( self::FORM_TEMPLATE_USAGE_OPTION, $usage, false );

		self::send_form_template_usage( $usage );
	}

	/**
	 * Refresh the `form_template_usage` event with the current number of
	 * published template forms, just before an analytics report is built.
	 *
	 * Publishing happens long after a form is created, and forms get
	 * unpublished or deleted, so the published counts are recounted from the
	 * forms each cycle instead of being added up. Sites that never used a
	 * template send nothing.
	 *
	 * @since 2.13.0
	 * @return void
	 */
	public static function refresh_form_template_usage() {
		$usage = Helper::get_array_value( get_option( self::FORM_TEMPLATE_USAGE_OPTION, [] ) );
		$usage = is_array( $usage ) ? $usage : [];

		$used = 0;
		foreach ( [ 'created', 'swaps', 'library_opens', 'previews' ] as $key ) {
			$used += absint( $usage[ $key ] ?? 0 );
		}
		if ( 0 === $used && 0 === array_sum( self::published_template_forms() ) ) {
			return;
		}

		self::send_form_template_usage( $usage );
	}

	/**
	 * Count a step of the template journey that happens in the library: the
	 * library being opened, a template previewed, a locked Premium template
	 * previewed, or its "Upgrade to unlock" button clicked.
	 *
	 * Adds to the running totals in FORM_TEMPLATE_USAGE_OPTION and re-sends them
	 * with the `form_template_usage` event. Locked previews and upgrade clicks are also
	 * counted per template.
	 *
	 * @param string $action      `library_open`, `preview`, `locked_preview` or `upgrade_click`.
	 * @param string $template_id Template id, for every action but `library_open`.
	 * @since 2.13.0
	 * @return bool Whether the action was counted.
	 */
	public static function track_form_template_action( $action, $template_id = '' ) {
		$totals = [
			'library_open'   => 'library_opens',
			'preview'        => 'previews',
			'locked_preview' => 'locked_previews',
			'upgrade_click'  => 'upgrade_clicks',
		];
		if ( ! isset( $totals[ $action ] ) ) {
			return false;
		}

		$template_id = substr( sanitize_key( Helper::get_string_value( $template_id ) ), 0, 40 );
		if ( 'library_open' !== $action && '' === $template_id ) {
			return false;
		}

		$usage = Helper::get_array_value( get_option( self::FORM_TEMPLATE_USAGE_OPTION, [] ) );
		$usage = is_array( $usage ) ? $usage : [];

		$usage[ $totals[ $action ] ] = absint( $usage[ $totals[ $action ] ] ?? 0 ) + 1;

		$per_template = [
			'locked_preview' => 'locked_preview_counts',
			'upgrade_click'  => 'upgrade_click_counts',
		];
		if ( isset( $per_template[ $action ] ) ) {
			$counts = self::usage_counts( $usage, $per_template[ $action ] );
			// Cap distinct keys so the option and the event stay small.
			if ( isset( $counts[ $template_id ] ) || count( $counts ) < 40 ) {
				$counts[ $template_id ] = ( $counts[ $template_id ] ?? 0 ) + 1;
			}
			$usage[ $per_template[ $action ] ] = $counts;
		}

		update_option( self::FORM_TEMPLATE_USAGE_OPTION, $usage, false );
		self::send_form_template_usage( $usage );

		return true;
	}

	/**
	 * Number of forms per template id that have received at least one entry,
	 * the sign a template form is actually working on the site.
	 *
	 * @since 2.13.0
	 * @return array<string, int> Template id => forms with entries, at most 40 ids.
	 */
	public static function template_forms_with_entries() {
		global $wpdb;

		if ( Register::is_entries_table_missing() ) {
			return [];
		}

		$entries = Entries::get_instance()->get_tablename();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The table name comes from the Entries class, not from input.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One grouped count per analytics cycle; caching would only delay the numbers.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT tm.meta_value AS template_id, COUNT(DISTINCT p.ID) AS forms
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} cv ON cv.post_id = p.ID AND cv.meta_key = %s AND cv.meta_value = %s
				INNER JOIN {$wpdb->postmeta} tm ON tm.post_id = p.ID AND tm.meta_key = %s
				WHERE p.post_type = %s AND p.post_status <> %s
				AND EXISTS ( SELECT 1 FROM {$entries} e WHERE e.form_id = p.ID AND e.status <> %s )
				GROUP BY tm.meta_value
				ORDER BY forms DESC
				LIMIT 40",
				'_srfm_created_via',
				'template',
				'_srfm_template_id',
				SRFM_FORMS_POST_TYPE,
				'trash',
				'trash'
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$counts = [];
		foreach ( is_array( $rows ) ? $rows : [] as $row ) {
			$id = substr( sanitize_key( Helper::get_string_value( $row['template_id'] ?? '' ) ), 0, 40 );
			if ( '' !== $id ) {
				$counts[ $id ] = absint( $row['forms'] ?? 0 );
			}
		}

		return $counts;
	}

	/**
	 * Number of published forms per template id, counted from the forms
	 * themselves (forms created from a template carry `_srfm_created_via` =
	 * `template` and `_srfm_template_id`).
	 *
	 * @since 2.13.0
	 * @return array<string, int> Template id => published forms, at most 40 ids.
	 */
	public static function published_template_forms() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One grouped count per analytics cycle; caching would only delay the numbers.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT tm.meta_value AS template_id, COUNT(*) AS forms
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} cv ON cv.post_id = p.ID AND cv.meta_key = %s AND cv.meta_value = %s
				INNER JOIN {$wpdb->postmeta} tm ON tm.post_id = p.ID AND tm.meta_key = %s
				WHERE p.post_type = %s AND p.post_status = %s
				GROUP BY tm.meta_value
				ORDER BY forms DESC
				LIMIT 40",
				'_srfm_created_via',
				'template',
				'_srfm_template_id',
				SRFM_FORMS_POST_TYPE,
				'publish'
			),
			ARRAY_A
		);

		$counts = [];
		foreach ( is_array( $rows ) ? $rows : [] as $row ) {
			$id = substr( sanitize_key( Helper::get_string_value( $row['template_id'] ?? '' ) ), 0, 40 );
			if ( '' !== $id ) {
				$counts[ $id ] = absint( $row['forms'] ?? 0 );
			}
		}

		return $counts;
	}

	/**
	 * Count a step of the Add New Form start funnel: the start screen shown,
	 * a choice made on it (with the position its card was shown in, or `link`
	 * for the scratch link), or a step inside a path while a choice is in
	 * play. Adds to the running totals in FORM_START_FUNNEL_OPTION and re-sends
	 * them with the `form_start_funnel` event.
	 *
	 * @param string $action   `view`, `choose`, or one of Form_Start::STEPS.
	 * @param string $choice   Choice, required for `choose`.
	 * @param string $position Card position (`1`, `2`) or `link`, for `choose`.
	 * @since 2.13.0
	 * @return bool Whether the action was counted.
	 */
	public static function track_form_start_action( $action, $choice = '', $position = '' ) {
		if ( ! Form_Templates::is_usage_sharing_on() ) {
			return false;
		}

		$choice = Form_Start::sanitize_choice( $choice );
		$funnel = self::get_form_start_funnel();

		if ( 'view' === $action ) {
			$funnel['views'] = Helper::get_integer_value( $funnel['views'] ?? 0 ) + 1;
		} elseif ( 'choose' === $action ) {
			if ( '' === $choice ) {
				return false;
			}
			$funnel[ 'choice_' . $choice ] = Helper::get_integer_value( $funnel[ 'choice_' . $choice ] ?? 0 ) + 1;

			// Positions are a few known values; anything else is not counted per position.
			$position = sanitize_key( $position );
			if ( in_array( $position, [ '1', '2', '3', 'link' ], true ) ) {
				$key            = 'choice_' . $choice . '_pos_' . $position;
				$funnel[ $key ] = Helper::get_integer_value( $funnel[ $key ] ?? 0 ) + 1;
			}

			// The very first choice on a site is the cleanest read of what people want.
			if ( empty( $funnel['first_choice'] ) ) {
				$funnel['first_choice'] = $choice;
			}
		} elseif ( in_array( $action, Form_Start::STEPS, true ) ) {
			$funnel[ 'step_' . $action ] = Helper::get_integer_value( $funnel[ 'step_' . $action ] ?? 0 ) + 1;
		} else {
			return false;
		}

		self::update_form_start_funnel( $funnel );
		self::send_form_start_funnel( $funnel );

		return true;
	}

	/**
	 * Count a form created after a start screen choice. When it was created
	 * another way than the one picked (AI picked, template used), the switch
	 * is counted too.
	 *
	 * @param string $choice      Start screen choice.
	 * @param string $created_via How the form was created: `ai`, `template` or `scratch`.
	 * @since 2.13.0
	 * @return void
	 */
	public static function track_form_start_created( $choice, $created_via ) {
		$choice      = Form_Start::sanitize_choice( $choice );
		$created_via = Form_Start::sanitize_choice( $created_via );
		if ( '' === $choice || ! Form_Templates::is_usage_sharing_on() ) {
			return;
		}

		$funnel = self::get_form_start_funnel();

		$funnel[ 'created_' . $choice ] = Helper::get_integer_value( $funnel[ 'created_' . $choice ] ?? 0 ) + 1;
		if ( '' !== $created_via && $created_via !== $choice ) {
			$key            = 'switch_' . $choice . '_to_' . $created_via;
			$funnel[ $key ] = Helper::get_integer_value( $funnel[ $key ] ?? 0 ) + 1;
		}

		self::update_form_start_funnel( $funnel );
		self::send_form_start_funnel( $funnel );
	}

	/**
	 * Re-send the `form_start_funnel` event with current draft, published
	 * and with-entries counts, just before an analytics report is built.
	 * Those change long after a form is created, so they are recounted from
	 * the forms each cycle. Sites that never saw the start screen send nothing.
	 *
	 * A site that used the start screen while usage sharing was off has no
	 * stored totals, but its forms still carry the choice, so their outcomes
	 * are sent once it is turned on.
	 *
	 * @since 2.13.0
	 * @return void
	 */
	public static function refresh_form_start_funnel() {
		if ( ! Form_Templates::is_usage_sharing_on() ) {
			return;
		}

		$funnel = self::get_form_start_funnel();
		if ( empty( $funnel ) ) {
			$forms = 0;
			foreach ( self::form_start_outcomes() as $counts ) {
				$forms += array_sum( $counts );
			}
			if ( 0 === $forms ) {
				return;
			}
			// Views and choices can't be rebuilt from the forms, so mark these
			// outcomes for the warehouse to leave out of cross-site rates.
			$funnel = [ 'recovered' => true ];
		}

		self::send_form_start_funnel( $funnel );
	}

	/**
	 * Forms created after each start screen choice, by status, counted from
	 * the forms themselves.
	 *
	 * @since 2.13.0
	 * @return array<string, array<string, int>> Choice => [ `draft`, `published`, `with_entries` ].
	 */
	public static function form_start_outcomes() {
		global $wpdb;

		$outcomes = array_fill_keys(
			Form_Start::CHOICES,
			[
				'draft'        => 0,
				'published'    => 0,
				'with_entries' => 0,
			]
		);

		// Both counts read drafts and published forms only, so `with_entries`
		// never counts a form that is in neither (private, pending, scheduled).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One grouped count per analytics cycle; caching would only delay the numbers.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT sc.meta_value AS choice, p.post_status AS status, COUNT(DISTINCT p.ID) AS forms
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} sc ON sc.post_id = p.ID AND sc.meta_key = %s
				WHERE p.post_type = %s AND p.post_status IN ( %s, %s )
				GROUP BY sc.meta_value, p.post_status",
				Form_Start::START_CHOICE_META,
				SRFM_FORMS_POST_TYPE,
				'draft',
				'publish'
			),
			ARRAY_A
		);
		foreach ( is_array( $rows ) ? $rows : [] as $row ) {
			$choice = Form_Start::sanitize_choice( $row['choice'] ?? '' );
			if ( '' !== $choice ) {
				$status                         = 'publish' === ( $row['status'] ?? '' ) ? 'published' : 'draft';
				$outcomes[ $choice ][ $status ] = absint( $row['forms'] ?? 0 );
			}
		}

		if ( Register::is_entries_table_missing() ) {
			return $outcomes;
		}

		$entries = Entries::get_instance()->get_tablename();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The table name comes from the Entries class, not from input.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One grouped count per analytics cycle; caching would only delay the numbers.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT sc.meta_value AS choice, COUNT(DISTINCT p.ID) AS forms
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} sc ON sc.post_id = p.ID AND sc.meta_key = %s
				WHERE p.post_type = %s AND p.post_status IN ( %s, %s )
				AND EXISTS ( SELECT 1 FROM {$entries} e WHERE e.form_id = p.ID AND e.status <> %s )
				GROUP BY sc.meta_value",
				Form_Start::START_CHOICE_META,
				SRFM_FORMS_POST_TYPE,
				'draft',
				'publish',
				'trash'
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( is_array( $rows ) ? $rows : [] as $row ) {
			$choice = Form_Start::sanitize_choice( $row['choice'] ?? '' );
			if ( '' !== $choice ) {
				$outcomes[ $choice ]['with_entries'] = absint( $row['forms'] ?? 0 );
			}
		}

		return $outcomes;
	}

	/**
	 * Track demand for templates that do not exist yet: what people request
	 * from the template library, and how often a search finds nothing.
	 *
	 * Keeps cumulative counters in FORM_TEMPLATE_DEMAND_OPTION and re-sends the
	 * latest totals as the `form_template_demand` event, so the most wanted templates can be
	 * read from the warehouse. Only a request the user sends on purpose carries
	 * its text (already cleaned by Form_Templates::clean_demand_query()); a
	 * search with no results is only counted, never its text. Nothing is kept
	 * or queued unless the site shares usage data.
	 *
	 * @param string $query Cleaned request text. Ignored for `search`.
	 * @param string $type  `request` for an explicit request, `search` for a search with no results.
	 * @since 2.13.0
	 * @return void
	 */
	public static function track_form_template_demand( $query, $type = 'request' ) {
		if ( ! Form_Templates::is_usage_sharing_on() ) {
			return;
		}

		$demand = Helper::get_array_value( get_option( self::FORM_TEMPLATE_DEMAND_OPTION, [] ) );
		$demand = is_array( $demand ) ? $demand : [];

		if ( 'search' === $type ) {
			$demand['searches'] = absint( $demand['searches'] ?? 0 ) + 1;
		} else {
			// The query becomes an option key and an event property key.
			$key = substr( str_replace( '-', '_', sanitize_title( Helper::get_string_value( $query ) ) ), 0, 40 );
			if ( '' === $key ) {
				return;
			}

			$counts = isset( $demand['request'] ) && is_array( $demand['request'] ) ? array_map( 'absint', $demand['request'] ) : [];

			// Cap distinct keys so free text can't grow the option unbounded.
			if ( isset( $counts[ $key ] ) || count( $counts ) < 30 ) {
				$counts[ $key ] = absint( $counts[ $key ] ?? 0 ) + 1;
			}
			$demand['request'] = $counts;
		}
		update_option( self::FORM_TEMPLATE_DEMAND_OPTION, $demand, false );

		$requests   = isset( $demand['request'] ) && is_array( $demand['request'] ) ? array_map( 'absint', $demand['request'] ) : [];
		$properties = [
			'plan'     => Helper::has_pro() ? 'business' : 'free',
			'requests' => (string) array_sum( $requests ),
			'searches' => (string) absint( $demand['searches'] ?? 0 ),
		];
		foreach ( $requests as $id => $count ) {
			$properties[ 'request_' . $id ] = (string) $count;
		}

		// $force = true: cumulative counter, must re-send the latest totals each cycle.
		self::events()->track( 'form_template_demand', (string) array_sum( $requests ), $properties, true );
	}

	/**
	 * Track embed styling configuration when a post/page is saved.
	 *
	 * Detects custom formTheme in Gutenberg blocks (post_content),
	 * Elementor widgets (_elementor_data meta), and Bricks elements
	 * (_bricks_page_content_2 meta). Re-tracks on each save by flushing
	 * the dedup flag.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @since 2.7.0
	 * @return void
	 */
	public function track_embed_styling_configured( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( 'publish' !== $post->post_status ) {
			return;
		}

		$themes = [];
		$source = '';

		// Check Gutenberg blocks in post_content.
		if ( preg_match_all( '/<!--\s*wp:srfm\/form\s+\{[^}]*"formTheme"\s*:\s*"(?!inherit")([^"]*)"/', $post->post_content, $matches ) ) {
			$themes = array_count_values( $matches[1] );
			$source = 'gutenberg';
		}

		// Check Elementor widgets in _elementor_data meta.
		if ( empty( $themes ) ) {
			$elementor_data = get_post_meta( $post_id, '_elementor_data', true );
			if ( is_string( $elementor_data )
				&& preg_match_all( '/"widgetType"\s*:\s*"sureforms_form"[^}]*"formTheme"\s*:\s*"(?!inherit")([^"]*)"/', $elementor_data, $el_matches )
			) {
				$themes = array_count_values( $el_matches[1] );
				$source = 'elementor';
			}
		}

		// Check Bricks elements in _bricks_page_content_2 meta (serialized PHP).
		if ( empty( $themes ) ) {
			$bricks_data = get_post_meta( $post_id, '_bricks_page_content_2', true );
			if ( is_array( $bricks_data ) ) {
				$bricks_themes = self::extract_bricks_form_themes( $bricks_data );
				if ( ! empty( $bricks_themes ) ) {
					$themes = array_count_values( $bricks_themes );
					$source = 'bricks';
				}
			}
		}

		if ( empty( $themes ) ) {
			return;
		}

		// Flush dedup so event is re-tracked on each meaningful save.
		self::events()->flush_pushed( [ 'embed_styling_configured' ] );

		self::events()->track(
			'embed_styling_configured',
			(string) $post_id,
			[
				'themes'      => $themes,
				'block_count' => array_sum( $themes ),
				'source'      => $source,
			]
		);
	}

	/**
	 * Track the plugin_activated event with the correct install referer.
	 *
	 * Dedup in self::events()->track() ensures this fires only once. Runs on
	 * 'shutdown' (see detect_state_events()) so it reads bsf_product_referers
	 * after any late-writing referer call has had a chance to run.
	 *
	 * @since 2.12.6
	 * @return void
	 */
	public function track_plugin_activated_event() {
		$bsf_referrers = get_option( 'bsf_product_referers', [] );
		$source        = ! empty( $bsf_referrers['sureforms'] ) ? $bsf_referrers['sureforms'] : 'self';
		self::events()->track( 'plugin_activated', SRFM_VER, [ 'source' => $source ] );
	}

	/**
	 * Track the site's email delivery state.
	 *
	 * Whether form notifications have a real mailer behind them. A state, not a
	 * milestone, so it is re-sent (forced) whenever it changes -- and checked at
	 * most daily, because admin_init runs on every admin page load and the status
	 * can scan the plugins directory. Only fixed labels are sent: connection
	 * titles are owner-entered text.
	 *
	 * On admin_init rather than in detect_state_events(), which runs while
	 * plugins are still loading: pluggable functions do not exist yet, so a
	 * redefined wp_mail() would be missed and current_user_can() is undefined.
	 *
	 * @since 2.12.9
	 * @return void
	 */
	public function track_email_delivery_status() {
		if ( false !== get_transient( 'srfm_email_delivery_state_checked' ) ) {
			return;
		}

		set_transient( 'srfm_email_delivery_state_checked', 1, DAY_IN_SECONDS );

		$email_delivery = Email_Delivery_Status::get_status();
		$mailer_slug    = Helper::get_string_value( $email_delivery['mailer']['slug'] ?? '' );
		$verified       = $email_delivery['surecontact']['email_verified'] ?? null;
		$delivery_props = [
			'mailer'               => $mailer_slug,
			'mailer_plugin'        => 'other' === $mailer_slug ? Helper::get_string_value( $email_delivery['mailer']['label'] ?? '' ) : '',
			'surecontact'          => ! empty( $email_delivery['surecontact']['connected'] ) ? 'yes' : 'no',
			'surecontact_verified' => null === $verified ? 'unknown' : ( $verified ? 'yes' : 'no' ),
		];
		$delivery_state = Helper::get_string_value( $email_delivery['state'] ?? '' );
		$fingerprint    = $delivery_state . '|' . implode( '|', $delivery_props );

		if ( '' !== $delivery_state && Helper::get_srfm_option( 'email_delivery_state_tracked', '' ) !== $fingerprint ) {
			self::events()->track( 'email_delivery_status', $delivery_state, $delivery_props, true );
			Helper::update_srfm_option( 'email_delivery_state_tracked', $fingerprint );
		}
	}

	/**
	 * A per-template count map stored in the usage totals.
	 *
	 * @param array<string, mixed> $usage Stored usage totals.
	 * @param string               $key   Map key.
	 * @since 2.13.0
	 * @return array<string, int>
	 */
	private static function usage_counts( $usage, $key ) {
		if ( ! isset( $usage[ $key ] ) || ! is_array( $usage[ $key ] ) ) {
			return [];
		}

		$counts = [];
		foreach ( $usage[ $key ] as $id => $count ) {
			$counts[ (string) $id ] = Helper::get_integer_value( $count );
		}
		return $counts;
	}

	/**
	 * Queue the `form_template_usage` event: the stored creation and swap
	 * totals plus the forms published right now, per template.
	 *
	 * @param array<string, mixed> $usage Stored usage totals.
	 * @since 2.13.0
	 * @return void
	 */
	private static function send_form_template_usage( $usage ) {
		$counts    = isset( $usage['templates'] ) && is_array( $usage['templates'] ) ? array_map( 'absint', $usage['templates'] ) : [];
		$published = self::published_template_forms();

		$with_entries = self::template_forms_with_entries();
		$locked       = self::usage_counts( $usage, 'locked_preview_counts' );
		$upgrades     = self::usage_counts( $usage, 'upgrade_click_counts' );

		$properties   = [
			'plan'            => Helper::has_pro() ? 'business' : 'free',
			// From this version, a duplicated form no longer counts for its
			// template (`_srfm_created_via` = `duplicate`), so the series can be split here.
			'plugin_version'  => SRFM_VER,
			'library_opens'   => (string) Helper::get_integer_value( $usage['library_opens'] ?? 0 ),
			'previews'        => (string) Helper::get_integer_value( $usage['previews'] ?? 0 ),
			'created'         => (string) Helper::get_integer_value( $usage['created'] ?? 0 ),
			'swaps'           => (string) Helper::get_integer_value( $usage['swaps'] ?? 0 ),
			'published'       => (string) array_sum( $published ),
			'with_entries'    => (string) array_sum( $with_entries ),
			'locked_previews' => (string) Helper::get_integer_value( $usage['locked_previews'] ?? 0 ),
			'upgrade_clicks'  => (string) Helper::get_integer_value( $usage['upgrade_clicks'] ?? 0 ),
		];
		$per_template = [
			'template_'       => $counts,
			'published_'      => $published,
			'with_entries_'   => $with_entries,
			'locked_preview_' => $locked,
			'upgrade_'        => $upgrades,
		];
		foreach ( $per_template as $prefix => $values ) {
			foreach ( $values as $id => $count ) {
				$properties[ $prefix . $id ] = (string) $count;
			}
		}

		// $force = true: cumulative counter, must re-send the latest totals each cycle.
		self::events()->track( 'form_template_usage', (string) array_sum( $counts ), $properties, true );
	}

	/**
	 * Stored start funnel totals.
	 *
	 * @since 2.13.0
	 * @return array<string, mixed>
	 */
	private static function get_form_start_funnel() {
		$funnel = get_option( self::FORM_START_FUNNEL_OPTION, [] );
		return is_array( $funnel ) ? $funnel : [];
	}

	/**
	 * Save start funnel totals.
	 *
	 * Kept in their own option rather than srfm_options: the start screen, the
	 * template library and the AI screen send their counts at the same moment,
	 * and requests rewriting one shared row would drop each other's counts.
	 *
	 * @param array<string, mixed> $funnel Totals.
	 * @since 2.13.0
	 * @return void
	 */
	private static function update_form_start_funnel( $funnel ) {
		update_option( self::FORM_START_FUNNEL_OPTION, $funnel, false );
	}

	/**
	 * Queue the `form_start_funnel` event: the stored view, choice, step,
	 * creation and switch totals plus each choice's forms in draft, published
	 * and receiving entries right now. Shares and completion rates are worked
	 * out in the warehouse, e.g. published_ai / choice_ai. `views` counts the
	 * times the screen was shown, not unique visits: going back and picking
	 * the other card is two choices on one view, so choices can exceed views.
	 *
	 * @param array<string, mixed> $funnel Stored totals.
	 * @since 2.13.0
	 * @return void
	 */
	private static function send_form_start_funnel( $funnel ) {
		$properties = [
			'plan'           => Helper::has_pro() ? 'business' : 'free',
			'first_choice'   => Form_Start::sanitize_choice( $funnel['first_choice'] ?? '' ),
			// Lets a change in how something is counted be told apart in the series.
			'plugin_version' => SRFM_VER,
		];
		if ( ! empty( $funnel['recovered'] ) ) {
			$properties['recovered'] = '1';
		}

		// Counters are stored under known prefixes only, so they pass through as is.
		foreach ( $funnel as $key => $value ) {
			if ( is_string( $key ) && preg_match( '/^(views|choice_|step_|created_|switch_)/', $key ) ) {
				$properties[ $key ] = (string) Helper::get_integer_value( $value );
			}
		}

		$choices = 0;
		foreach ( self::form_start_outcomes() as $choice => $counts ) {
			$choices += Helper::get_integer_value( $funnel[ 'choice_' . $choice ] ?? 0 );
			foreach ( $counts as $status => $count ) {
				$properties[ $status . '_' . $choice ] = (string) $count;
			}
		}

		// $force = true: cumulative counter, must re-send the latest totals each cycle.
		self::events()->track( 'form_start_funnel', (string) $choices, $properties, true );
	}

	/**
	 * Extract non-inherit formTheme values from Bricks element data.
	 *
	 * Recursively walks the unserialized Bricks elements array looking for
	 * sureforms elements with custom formTheme settings.
	 *
	 * @param array<mixed> $elements Bricks elements array.
	 * @return array<string> List of formTheme values (non-inherit).
	 * @since 2.7.0
	 */
	private static function extract_bricks_form_themes( $elements ) {
		$themes = [];

		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			$name       = $element['name'] ?? '';
			$settings   = $element['settings'] ?? [];
			$form_theme = $settings['formTheme'] ?? '';

			if ( 'sureforms' === $name && ! empty( $form_theme ) && 'inherit' !== $form_theme ) {
				$themes[] = sanitize_text_field( $form_theme );
			}

			// Recurse into nested children.
			if ( ! empty( $element['children'] ) && is_array( $element['children'] ) ) {
				$themes = array_merge( $themes, self::extract_bricks_form_themes( $element['children'] ) );
			}
		}

		return $themes;
	}

	/**
	 * Check if any payment method is enabled.
	 *
	 * This function checks if any payment gateway is connected and enabled.
	 * Currently supports Stripe, but can be extended for other payment methods in the future.
	 *
	 * @since 2.0.0
	 * @return bool True if any payment method is enabled, false otherwise.
	 */
	private function is_stripe_enabled() {
		// Check if Stripe is connected.
		return class_exists( 'SRFM\Inc\Payments\Stripe\Stripe_Helper' ) && \SRFM\Inc\Payments\Stripe\Stripe_Helper::is_stripe_connected();
	}

	/**
	 * Runs custom WP_Query to fetch data as per requirement
	 *
	 * @param array $meta_query meta query array for WP_Query.
	 * @since 1.4.0
	 * @return int
	 */
	private function custom_wp_query_total_posts( $meta_query ) {

		$args = [
			'post_type'      => SRFM_FORMS_POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => $meta_query, //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Meta query required as we need to fetch count of nested data.
		];

		$query       = new \WP_Query( $args );
		$posts_count = $query->found_posts;

		wp_reset_postdata();

		return $posts_count;
	}

	/**
	 * Get KPI tracking data for the last 2 days (excluding today).
	 *
	 * @since 2.4.0
	 * @return array KPI data organized by date.
	 */
	private function get_kpi_tracking_data() {
		$kpi_data = [];
		$today    = current_time( 'Y-m-d' );

		// Get data for yesterday and day before yesterday.
		for ( $i = 1; $i <= 2; $i++ ) {
			$date = gmdate( 'Y-m-d', strtotime( $today . ' -' . $i . ' days' ) );

			$kpi_data[ $date ] = [
				'numeric_values' => [
					'submissions' => $this->get_daily_submissions_count( $date ),
				],
			];
		}

		return $kpi_data;
	}

	/**
	 * Get daily submissions count for a specific date.
	 *
	 * @param string $date Date in Y-m-d format.
	 * @since 2.4.0
	 * @return int Daily submissions count.
	 */
	private function get_daily_submissions_count( $date ) {
		// Guarded here rather than at the call site: this is the function that runs
		// the query, so a future caller is covered too. Every read against a missing
		// entries table raises a DB error, and the daily send would raise one per
		// day counted on exactly the sites whose breakage we most need reported.
		if ( Register::is_entries_table_missing() ) {
			return 0;
		}

		$start_date = $date . ' 00:00:00';
		$end_date   = $date . ' 23:59:59';

		$where_conditions = [
			[
				[
					'key'     => 'created_at',
					'compare' => '>=',
					'value'   => $start_date,
				],
				[
					'key'     => 'created_at',
					'compare' => '<=',
					'value'   => $end_date,
				],
			],
		];

		return Entries::get_instance()->get_total_count( $where_conditions );
	}

	/**
	 * Detect state-based events that can't use direct hooks.
	 * Uses dedup in self::events()->track() — safe to call repeatedly.
	 *
	 * @since 2.5.1
	 * @return void
	 */
	private function detect_state_events() {
		// plugin_activated: deferred to 'shutdown' so that a referring plugin/theme's
		// own BSF_UTM_Analytics::update_referer() call — which some products (incorrectly)
		// make only after their activate_plugin() call returns, in the same request — has
		// already run by the time we read bsf_product_referers. Reading this synchronously
		// here would race that write, since this constructor can execute mid-request while
		// SureForms itself is being activated by that other plugin.
		if ( did_action( 'shutdown' ) ) {
			$this->track_plugin_activated_event();
		} else {
			add_action( 'shutdown', [ $this, 'track_plugin_activated_event' ], PHP_INT_MAX );
		}

		// One-time: re-send onboarding_completed with full properties (v2).
		if ( ! Helper::get_srfm_option( 'onboarding_event_v2_flushed', false )
			&& \SRFM\Inc\Onboarding::get_instance()->get_onboarding_status() ) {
			self::events()->flush_pushed( [ 'onboarding_completed' ] );
			Helper::update_srfm_option( 'onboarding_event_v2_flushed', true );
		}

		// onboarding_completed: detect completed state with full onboarding details.
		if ( \SRFM\Inc\Onboarding::get_instance()->get_onboarding_status() ) {
			$onboarding_props     = [];
			$onboarding_analytics = Helper::get_srfm_option( 'onboarding_analytics', [] );

			// Which wizard produced the blob. Outside the non-empty guard and not
			// isset()-gated like the flags below, because absence IS the answer: a
			// blob from the old wizard, or no blob at all, must still report 'no'.
			// Without this the two wizards share one event name and can only be
			// told apart by which properties happen to be present -- and a v2 run
			// on Pro with no caching plugin emits none of the new ones.
			$onboarding_props['onboarding_v2'] = ! empty( $onboarding_analytics['onboardingV2'] ) ? 'yes' : 'no';

			if ( ! empty( $onboarding_analytics ) && is_array( $onboarding_analytics ) ) {
				if ( ! empty( $onboarding_analytics['skippedSteps'] ) && is_array( $onboarding_analytics['skippedSteps'] ) ) {
					$onboarding_props['skipped_steps'] = implode( ',', $onboarding_analytics['skippedSteps'] );
				}

				if ( isset( $onboarding_analytics['suremailInstalled'] ) ) {
					$onboarding_props['suremail_installed'] = (bool) $onboarding_analytics['suremailInstalled'] ? 'yes' : 'no';
				}

				// Email delivery step: the delivery state on arrival and on leaving,
				// and what the owner did. Closed sets, intersected like the premium
				// tabs below, because the blob is whatever the wizard POSTed.
				if ( ! empty( $onboarding_analytics['emailDelivery'] ) && is_array( $onboarding_analytics['emailDelivery'] ) ) {
					$email_delivery = $onboarding_analytics['emailDelivery'];
					$states         = [ 'attention', 'not_configured', 'suremail_no_connection', 'limited', 'other_mailer', 'ready' ];
					$step_actions   = [ 'skip', 'cannot_install', 'install_other_smtp', 'continue_limited', 'continue_other_mailer', 'continue_ready', 'continue_attention', 'continue_not_set_up' ];

					$state_props = [
						'stateAtStep' => 'email_delivery_state_at_step',
						'finalState'  => 'email_delivery_state_at_exit',
					];

					foreach ( $state_props as $key => $prop ) {
						if ( isset( $email_delivery[ $key ] ) && in_array( $email_delivery[ $key ], $states, true ) ) {
							$onboarding_props[ $prop ] = $email_delivery[ $key ];
						}
					}

					if ( isset( $email_delivery['action'] ) && in_array( $email_delivery['action'], $step_actions, true ) ) {
						$onboarding_props['email_delivery_action'] = $email_delivery['action'];
					}

					if ( isset( $email_delivery['suremailOpened'] ) ) {
						$onboarding_props['email_delivery_suremail_opened'] = (bool) $email_delivery['suremailOpened'] ? 'yes' : 'no';
					}

					if ( isset( $email_delivery['consented'] ) ) {
						$onboarding_props['email_delivery_consented'] = (bool) $email_delivery['consented'] ? 'yes' : 'no';
					}

					// The option card open when the step was left.
					if ( isset( $email_delivery['option'] ) && in_array( $email_delivery['option'], [ 'surecontact', 'other_smtp' ], true ) ) {
						$onboarding_props['email_delivery_option'] = $email_delivery['option'];
					}

					// Outcome of the one-click SureContact sign-up, when it was tried.
					$provision_outcomes = [ 'created', 'already_registered', 'unavailable', 'failed' ];
					if ( isset( $email_delivery['provision'] ) && in_array( $email_delivery['provision'], $provision_outcomes, true ) ) {
						$onboarding_props['email_delivery_provision'] = $email_delivery['provision'];
					}
				}

				if ( isset( $onboarding_analytics['accountConnected'] ) ) {
					$onboarding_props['account_connected'] = (bool) $onboarding_analytics['accountConnected'] ? 'yes' : 'no';
				}

				if ( isset( $onboarding_analytics['completed'] ) ) {
					$onboarding_props['completed'] = (bool) $onboarding_analytics['completed'] ? 'yes' : 'no';
				}

				if ( isset( $onboarding_analytics['exitedEarly'] ) ) {
					$onboarding_props['exited_early'] = (bool) $onboarding_analytics['exitedEarly'] ? 'yes' : 'no';
				}

				// Add-ons step: the wizard shows one feature tab at a time
				// instead of a checkbox list, so we report which tabs were opened and
				// whether Upgrade was clicked. Blobs written by older wizards carry
				// neither key and emit neither property.
				if ( ! empty( $onboarding_analytics['premiumFeatures']['viewedTabs'] ) && is_array( $onboarding_analytics['premiumFeatures']['viewedTabs'] ) ) {
					// The blob is whatever the wizard POSTed. The tabs are a closed
					// set of four, so intersect against it rather than filtering on
					// type: that drops anything unrecognised and caps both the
					// content and the length of the property in one step.
					$viewed_tabs                             = array_values(
						array_intersect(
							array_map( 'strval', array_filter( (array) $onboarding_analytics['premiumFeatures']['viewedTabs'], 'is_scalar' ) ),
							[ 'multistep', 'conditional', 'calculation', 'conversational' ]
						)
					);
					$onboarding_props['viewed_premium_tabs'] = implode( ',', $viewed_tabs );
				}

				if ( isset( $onboarding_analytics['premiumFeatures']['upgradeClicked'] ) ) {
					$onboarding_props['premium_upgrade_clicked'] = (bool) $onboarding_analytics['premiumFeatures']['upgradeClicked'] ? 'yes' : 'no';
				}

				// Cache-conflict step: shown only when a recognised caching plugin
				// is active; "yes" means the user pressed "I've fixed this".
				//
				// Read this as an abandonment signal, not as intent. Acknowledging
				// is the only way past the step, so everyone who completes the
				// wizard reports "yes" and "no" only ever appears next to
				// exited_early. It cannot answer "did people act on the warning" --
				// only "did they stop here".
				if ( isset( $onboarding_analytics['cacheConflictAcknowledged'] ) ) {
					$onboarding_props['cache_conflict_acknowledged'] = (bool) $onboarding_analytics['cacheConflictAcknowledged'] ? 'yes' : 'no';
				}
			}

			self::events()->track( 'onboarding_completed', '', $onboarding_props );
		}

		// stripe_connected: detect connection state.
		if ( class_exists( '\SRFM\Inc\Payments\Stripe\Stripe_Helper' )
			&& \SRFM\Inc\Payments\Stripe\Stripe_Helper::is_stripe_connected() ) {
			$mode = \SRFM\Inc\Payments\Stripe\Stripe_Helper::get_stripe_mode();
			self::events()->track( 'stripe_connected', ! empty( $mode ) ? $mode : 'live' );
		}

		// first_ai_form_generated: detect if any AI-generated form exists.
		// Guard with is_tracked() to skip the meta_query after the event is already tracked.
		if ( ! self::events()->is_tracked( 'first_ai_form_generated' ) && AI_Helper::has_ai_generated_forms() ) {
			self::events()->track( 'first_ai_form_generated' );
		}

		// MCP / Abilities API first-enable events.
		$mcp_settings = get_option( 'srfm_mcp_settings_options', [] );

		if ( ! empty( $mcp_settings['srfm_abilities_api'] ) ) {
			self::events()->track( 'abilities_api_enabled' );
		}

		if ( ! empty( $mcp_settings['srfm_mcp_server'] ) ) {
			self::events()->track( 'mcp_server_enabled' );
		}
	}

	/**
	 * Get learn section progress aggregated across all users.
	 *
	 * Queries all users with srfm_learn_progress meta, counts completions
	 * per step. Called at analytics send time (bsf_core_stats filter).
	 *
	 * @since 2.8.0
	 * @return void
	 */
	private function get_learn_tracking_data() {
		// Only send when progress changed or event has never been tracked.
		$has_changed = get_transient( 'srfm_learn_progress_changed' );
		if ( ! $has_changed && self::events()->is_tracked( 'learn' ) ) {
			return;
		}

		$chapters    = Learn::get_chapters_structure();
		$step_counts = [];
		$total_steps = 0;

		// Initialize step counters from canonical structure.
		foreach ( $chapters as $chapter ) {
			if ( ! isset( $chapter['id'], $chapter['steps'] ) || ! is_array( $chapter['steps'] ) ) {
				continue;
			}
			foreach ( $chapter['steps'] as $step ) {
				if ( ! isset( $step['id'] ) ) {
					continue;
				}
				$step_counts[ $chapter['id'] . '/' . $step['id'] ] = 0;
				++$total_steps;
			}
		}

		// Query all users who have learn progress.
		$users = get_users(
			[
				'meta_key' => 'srfm_learn_progress', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Runs once per analytics send cycle; bounded by admin user count.
				'fields'   => 'ID',
			]
		);

		$users_with_progress   = 0;
		$users_fully_completed = 0;
		$max_completed         = 0;

		foreach ( $users as $user_id ) {
			$progress = get_user_meta( $user_id, 'srfm_learn_progress', true );
			if ( ! is_array( $progress ) || empty( $progress ) ) {
				continue;
			}

			$user_completed = 0;

			foreach ( $step_counts as $key => $count ) {
				[ $chapter_id, $step_id ] = explode( '/', $key );
				if ( ! empty( $progress[ $chapter_id ][ $step_id ] ) ) {
					$step_counts[ $key ] = $count + 1;
					++$user_completed;
				}
			}

			if ( $user_completed > 0 ) {
				++$users_with_progress;
			}
			if ( $user_completed === $total_steps ) {
				++$users_fully_completed;
			}
			$max_completed = max( $max_completed, $user_completed );
		}

		// Build flat properties — step keys use snake_case (hyphens → underscores).
		$properties = [];
		foreach ( $step_counts as $key => $count ) {
			$step_id = explode( '/', $key )[1];
			$properties[ str_replace( '-', '_', $step_id ) ] = (string) $count;
		}

		$properties['users_with_progress']   = (string) $users_with_progress;
		$properties['users_fully_completed'] = (string) $users_fully_completed;
		$properties['total_steps']           = (string) $total_steps;

		// Flush dedup so event re-tracks with latest snapshot.
		self::events()->flush_pushed( [ 'learn' ] );
		self::events()->track( 'learn', (string) $max_completed, $properties );

		// Clear the change flag after tracking.
		delete_transient( 'srfm_learn_progress_changed' );
	}

}
