<?php
/**
 * Analytics class helps to connect BSFAnalytics.
 *
 * @package sureforms.
 */

namespace SRFM\Admin;

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
		];

		$stats_data['plugin_data']['sureforms'] = array_merge_recursive( $stats_data['plugin_data']['sureforms'], $this->global_settings_data() );
		// Add onboarding analytics data.
		$stats_data['plugin_data']['sureforms'] = array_merge_recursive( $stats_data['plugin_data']['sureforms'], $this->onboarding_analytics_data() );
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
}
