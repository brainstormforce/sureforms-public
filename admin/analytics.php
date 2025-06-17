<?php
/**
 * Analytics class helps to connect BSFAnalytics.
 *
 * @package sureforms.
 */

namespace SRFM\Admin;

use SRFM\Inc\Database\Tables\Entries;
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
			'free_version'        => SRFM_VER,
			'site_language'       => get_locale(),
			'most_used_anti_spam' => $this->most_used_anti_spam(),
		];
		$stats_data['plugin_data']['sureforms']['numeric_values'] = [
			'total_forms'            => wp_count_posts( SRFM_FORMS_POST_TYPE )->publish ?? 0,
			'instant_forms_enabled'  => $this->instant_forms_enabled(),
			'forms_using_custom_css' => $this->forms_using_custom_css(),
			'ai_generated_forms'     => $this->ai_generated_forms(),
			'total_entries'          => Entries::get_total_entries_by_status(),
		];

		$stats_data['plugin_data']['sureforms'] = array_merge_recursive( $stats_data['plugin_data']['sureforms'], $this->global_settings_data() );

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
	 * @since 1.4.0
	 * @return int
	 */
	public function ai_generated_forms() {
		$meta_query = [
			[
				'key'     => '_srfm_is_ai_generated',
				'value'   => '',
				'compare' => '!=', // Checks if the value is NOT empty.
			],
		];

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

		return $global_data;
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
