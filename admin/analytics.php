<?php
/**
 * Analytics class helps to connect BSFAnalytics.
 *
 * @package sureforms.
 */

namespace SRFM\Admin;

use SRFM\Inc\Analytics_Events;
use SRFM\Inc\Database\Tables\Entries;
use SRFM\Inc\Helper;
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

		if ( ! class_exists( 'Astra_Notices' ) ) {
			require_once SRFM_DIR . 'inc/lib/astra-notices/class-astra-notices.php';
		}

		add_filter(
			'uds_survey_allowed_screens',
			static function () {
				return [ 'plugins' ];
			}
		);

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
							],
						]
					),
					'hide_optin_checkbox' => true,
				],
			]
		);

		add_filter( 'bsf_core_stats', [ $this, 'add_srfm_analytics_data' ] );

		// Event tracking hooks.
		add_action( 'current_screen', [ $this, 'track_first_editor_open' ] );
		add_action( 'transition_post_status', [ $this, 'track_first_form_published' ], 10, 3 );
		add_action( 'save_post', [ $this, 'track_embed_styling_configured' ], 10, 2 );

		// Detect state-based events on admin load (dedup prevents repeat tracking).
		$this->detect_state_events();
	}

	/**
	 * Callback function to add SureForms specific analytics data.
	 *
	 * @param array $stats_data existing stats_data.
	 * @since 1.4.0
	 * @return array
	 */
	public function add_srfm_analytics_data( $stats_data ) {
		$stats_data['plugin_data']['sureforms']                   = [
			'free_version'          => SRFM_VER,
			'site_language'         => get_locale(),
			'most_used_anti_spam'   => $this->most_used_anti_spam(),
			'user_status'           => $this->user_status(),
			'pointer_popup_clicked' => $this->pointer_popup_clicked(),
		];
		$stats_data['plugin_data']['sureforms']['numeric_values'] = [
			'total_forms'                => wp_count_posts( SRFM_FORMS_POST_TYPE )->publish ?? 0,
			'instant_forms_enabled'      => $this->instant_forms_enabled(),
			'forms_using_custom_css'     => $this->forms_using_custom_css(),
			'ai_generated_forms'         => $this->ai_generated_forms(),
			'ai_generated_payment_forms' => $this->ai_generated_forms( 'payments' ),
			'payment_forms'              => $this->get_payment_forms_count(),
			'total_entries'              => Entries::get_total_entries_by_status(),
			'restricted_forms'           => $this->get_restricted_forms(),
			'embed_styling_gb_default'   => self::embed_styling_gutenberg_count( 'default' ),
			'embed_styling_el_default'   => self::embed_styling_elementor_count( 'default' ),
			'embed_styling_br_default'   => self::embed_styling_bricks_count( 'default' ),
		];

		$stats_data['plugin_data']['sureforms'] = array_merge_recursive( $stats_data['plugin_data']['sureforms'], $this->global_settings_data() );
		// Add onboarding analytics data.
		$stats_data['plugin_data']['sureforms'] = array_merge_recursive( $stats_data['plugin_data']['sureforms'], $this->onboarding_analytics_data() );

		// Add KPI tracking data.
		$kpi_data = $this->get_kpi_tracking_data();
		if ( ! empty( $kpi_data ) ) {
			$stats_data['plugin_data']['sureforms']['kpi_records'] = $kpi_data;
		}

		// Flush pending events into payload (only if any exist).
		$pending_events = Analytics_Events::flush_pending();
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
	 * @since x.x.x
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
	 * @since x.x.x
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
	 * @since x.x.x
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

		return $global_data;
	}

	/**
	 * Generates onboarding analytics data
	 *
	 * @since 1.9.1
	 * @return array
	 */
	public function onboarding_analytics_data() {
		$onboarding_data  = [];
		$analytics_option = Helper::get_srfm_option( 'onboarding_analytics', [] );

		if ( empty( $analytics_option ) ) {
			return $onboarding_data;
		}

		// Process skipped steps - store as an array.
		if ( ! empty( $analytics_option['skippedSteps'] ) && is_array( $analytics_option['skippedSteps'] ) ) {
			// Map step keys to more descriptive names.
			$step_mapping = [
				'welcome'         => 'Welcome',
				'connect'         => 'Connect',
				'emailDelivery'   => 'SureMail',
				'premiumFeatures' => 'Features',
				'done'            => 'Done',
			];

			// Transform the step keys to their descriptive names.
			$mapped_steps = array_map(
				static function( $step ) use ( $step_mapping ) {
					return $step_mapping[ $step ] ?? $step;
				},
				$analytics_option['skippedSteps']
			);

			// Store as an array.
			$onboarding_data['onboarding_skipped_steps'] = $mapped_steps;
		}

		// SureMail Installation Status.
		if ( isset( $analytics_option['suremailInstalled'] ) ) {
			$onboarding_data['boolean_values']['onboarding_suremail_installed'] = (bool) $analytics_option['suremailInstalled'];
		}

		// Account Connection Status.
		if ( isset( $analytics_option['accountConnected'] ) ) {
			$onboarding_data['boolean_values']['onboarding_account_connected'] = (bool) $analytics_option['accountConnected'];
		}

		// Onboarding Completion Status.
		if ( isset( $analytics_option['completed'] ) ) {
			$onboarding_data['boolean_values']['onboarding_completed'] = (bool) $analytics_option['completed'];
		}

		// Onboarding Early Exit Status.
		if ( isset( $analytics_option['exitedEarly'] ) ) {
			$onboarding_data['boolean_values']['onboarding_exited_early'] = (bool) $analytics_option['exitedEarly'];
		}

		// Onboarding Selected Premium Features.
		if ( ! empty( $analytics_option['premiumFeatures'] ) && ! empty( $analytics_option['premiumFeatures']['selectedFeatures'] ) ) {
			// Map feature IDs to more descriptive names - exclude free features.
			$feature_mapping = [
				// Starter features.
				'multi_step_form'      => 'Multi-step Forms',
				'conditional_logic'    => 'Conditional Fields',
				'webhooks'             => 'Webhooks',
				'advanced_fields'      => 'Advanced Fields',

				// Pro features.
				'conversational_forms' => 'Conversational Forms',
				'digital_signatures'   => 'Digital Signatures',

				// Business features.
				'calculations'         => 'Calculators',
				'user_registration'    => 'User Registration and Login',
				'custom_app'           => 'Custom App',
				'pdf_generation'       => 'PDF Generation',
			];

			// Filter out any free features that might have been included.
			$premium_features = array_filter(
				$analytics_option['premiumFeatures']['selectedFeatures'],
				static function( $feature ) {
					// Exclude free features (ai-form-generation and entries).
					return 'ai-form-generation' !== $feature && 'entries' !== $feature;
				}
			);

			// Transform the feature IDs to their descriptive names.
			$mapped_features = array_map(
				static function( $feature ) use ( $feature_mapping ) {
					return $feature_mapping[ $feature ] ?? $feature;
				},
				$premium_features
			);

			// Store as an array.
			$onboarding_data['onboarding_selected_premium_features'] = $mapped_features;
		}

		return $onboarding_data;
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
			Analytics_Events::track( 'first_form_editor_opened' );
		}
	}

	/**
	 * Track first time a form is published.
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

		Analytics_Events::track(
			'first_form_published',
			(string) $post->ID,
			[
				'is_ai_generated' => $is_ai,
				'block_count'     => $block_count,
			]
		);
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
	 * @since x.x.x
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
		Analytics_Events::flush_pushed( [ 'embed_styling_configured' ] );

		Analytics_Events::track(
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
	 * Extract non-inherit formTheme values from Bricks element data.
	 *
	 * Recursively walks the unserialized Bricks elements array looking for
	 * sureforms elements with custom formTheme settings.
	 *
	 * @param array<mixed> $elements Bricks elements array.
	 * @return array<string> List of formTheme values (non-inherit).
	 * @since x.x.x
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
	 * Uses dedup in Analytics_Events::track() — safe to call repeatedly.
	 *
	 * @since 2.5.1
	 * @return void
	 */
	private function detect_state_events() {
		// plugin_activated: dedup in Analytics_Events::track() ensures this fires only once.
		$bsf_referrers = get_option( 'bsf_product_referers', [] );
		$source        = ! empty( $bsf_referrers['sureforms'] ) ? $bsf_referrers['sureforms'] : 'self';
		Analytics_Events::track( 'plugin_activated', SRFM_VER, [ 'source' => $source ] );

		// onboarding_completed: detect completed state.
		if ( \SRFM\Inc\Onboarding::get_instance()->get_onboarding_status() ) {
			Analytics_Events::track( 'onboarding_completed' );
		}

		// stripe_connected: detect connection state.
		if ( class_exists( '\SRFM\Inc\Payments\Stripe\Stripe_Helper' )
			&& \SRFM\Inc\Payments\Stripe\Stripe_Helper::is_stripe_connected() ) {
			$mode = \SRFM\Inc\Payments\Stripe\Stripe_Helper::get_stripe_mode();
			Analytics_Events::track( 'stripe_connected', ! empty( $mode ) ? $mode : 'live' );
		}

		// MCP / Abilities API first-enable events.
		$mcp_settings = get_option( 'srfm_mcp_settings_options', [] );

		if ( ! empty( $mcp_settings['srfm_abilities_api'] ) ) {
			Analytics_Events::track( 'abilities_api_enabled' );
		}

		if ( ! empty( $mcp_settings['srfm_mcp_server'] ) ) {
			Analytics_Events::track( 'mcp_server_enabled' );
		}
	}

}
