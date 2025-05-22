<?php
/**
 * Sureforms Global Settings.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc\Global_Settings;

use SRFM\Inc\Events_Scheduler;
use SRFM\Inc\Helper;
use SRFM\Inc\Traits\Get_Instance;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Sureforms Global Settings.
 *
 * @since 0.0.1
 */
class Global_Settings {
	use Get_Instance;

	/**
	 * Namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'sureforms/v1';

	/**
	 * Constructor
	 *
	 * @since  0.0.1
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_custom_endpoint' ] );
	}

	/**
	 * Add custom API Route submit-form
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function register_custom_endpoint() {
		$sureforms_helper = new Helper();
		register_rest_route(
			$this->namespace,
			'/srfm-global-settings',
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'srfm_save_global_settings' ],
				'permission_callback' => [ $sureforms_helper, 'get_items_permissions_check' ],
			]
		);
		register_rest_route(
			$this->namespace,
			'/srfm-global-settings',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'srfm_get_general_settings' ],
				'permission_callback' => [ $sureforms_helper, 'get_items_permissions_check' ],
			]
		);
	}

	/**
	 * Save global settings options.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 *
	 * @since 0.0.1
	 */
	public static function srfm_save_global_settings( $request ) {

		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			wp_send_json_error(
				[
					'data' => __( 'Nonce verification failed.', 'sureforms' ),
				]
			);
		}

		if ( ! is_user_logged_in() ) {
			// Return error if user is not logged in.
			wp_send_json_error(
				[
					'data' => esc_html__( 'You must be logged in to make this request.', 'sureforms' ),
				]
			);
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			// Return error if user does not have permissions to manage options.
			wp_send_json_error(
				[
					'data' => esc_html__( 'You do not have permissions to manage options.', 'sureforms' ),
				]
			);
		}

		$setting_options = $request->get_params();

		$tab = $setting_options['srfm_tab'];

		unset( $setting_options['srfm_tab'] );

		switch ( $tab ) {
			case 'general-settings':
				$is_option_saved = self::srfm_save_general_settings( $setting_options );
				break;
			case 'general-settings-dynamic-opt':
				$is_option_saved = self::srfm_save_general_settings_dynamic_opt( $setting_options );
				break;
			case 'email-settings':
				$is_option_saved = self::srfm_save_email_summary_settings( $setting_options );
				break;
			case 'security-settings':
				$is_option_saved = self::srfm_save_security_settings( $setting_options );
				break;
			default:
				$is_option_saved = false;
				break;
		}

		if ( ! $is_option_saved ) {
			return new WP_Error( __( 'Error Saving Settings!', 'sureforms' ), __( 'Global Settings', 'sureforms' ) );
		}
		return new WP_REST_Response(
			[
				'data' => __( 'Settings Saved Successfully.', 'sureforms' ),
			]
		);
	}

	/**
	 * Save General Settings
	 *
	 * @param array<mixed> $setting_options Setting options.
	 * @return bool
	 * @since 0.0.1
	 */
	public static function srfm_save_general_settings( $setting_options ) {

		$srfm_ip_log         = $setting_options['srfm_ip_log'] ?? false;
		$srfm_form_analytics = $setting_options['srfm_form_analytics'] ?? false;
		$srfm_bsf_analytics  = $setting_options['srfm_bsf_analytics'] ?? false;

		$settings = [
			'srfm_ip_log'         => $srfm_ip_log,
			'srfm_form_analytics' => $srfm_form_analytics,
		];

		/**
		 * We are updating sureforms_analytics_optin option from the general settings as it has been introduced
		 * as part of general settings. Since the option sureforms_analytics_optin is already available from BSF analytics library
		 * We are updating this independently.
		 *
		 * @since 1.7.0
		 */
		$analytics_result = self::update_bsf_analytics( $srfm_bsf_analytics );

		$general_result = update_option( 'srfm_general_settings_options', $settings );

		/**
		 * Returns the output of update_bsf_analytics or srfm_general_settings_options option.
		 *
		 * @since 1.7.0
		 */
		return $analytics_result || $general_result;
	}

	/**
	 * Toggle BSF analytics usage tracking in WP general settings.
	 *
	 * @param array<mixed> $settings general settings array.
	 * @return bool
	 * @since 1.7.0
	 */
	public static function update_bsf_analytics( $settings ) {
		if ( true === $settings ) {
			$enable_tracking = 'yes';
		} else {
			$enable_tracking = '';
		}

		return update_option( 'sureforms_analytics_optin', $enable_tracking );
	}

	/**
	 * Save General Settings Dynamic Options
	 *
	 * @param array<mixed> $setting_options Setting options.
	 * @return bool
	 * @since 0.0.1
	 */
	public static function srfm_save_general_settings_dynamic_opt( $setting_options ) {
		$options_keys = [
			'srfm_url_block_required_text',
			'srfm_input_block_required_text',
			'srfm_input_block_unique_text',
			'srfm_address_block_required_text',
			'srfm_phone_block_required_text',
			'srfm_phone_block_unique_text',
			'srfm_number_block_required_text',
			'srfm_textarea_block_required_text',
			'srfm_multi_choice_block_required_text',
			'srfm_checkbox_block_required_text',
			'srfm_gdpr_block_required_text',
			'srfm_email_block_required_text',
			'srfm_email_block_unique_text',
			'srfm_dropdown_block_required_text',
			'srfm_valid_phone_number',
			'srfm_valid_url',
			'srfm_confirm_email_same',
			'srfm_valid_email',
			'srfm_input_min_value',
			'srfm_input_max_value',
			'srfm_dropdown_min_selections',
			'srfm_dropdown_max_selections',
			'srfm_multi_choice_min_selections',
			'srfm_multi_choice_max_selections',
		];

		$options_names = [];

		foreach ( $options_keys as $key ) {
			if ( isset( $setting_options[ $key ] ) ) {
				$options_names[ $key ] = $setting_options[ $key ];
			}
		}

		return update_option( 'srfm_default_dynamic_block_option', apply_filters( 'srfm_general_dynamic_options_to_save', $options_names, $setting_options ) );
	}

	/**
	 * Save Email Summary Settings
	 *
	 * @param array<mixed> $setting_options Setting options.
	 * @return bool
	 * @since 0.0.1
	 */
	public static function srfm_save_email_summary_settings( $setting_options ) {

		$srfm_email_summary   = $setting_options['srfm_email_summary'] ?? false;
		$srfm_email_sent_to   = $setting_options['srfm_email_sent_to'] ?? get_option( 'admin_email' );
		$srfm_schedule_report = $setting_options['srfm_schedule_report'] ?? __( 'Monday', 'sureforms' );

		Events_Scheduler::unschedule_events( 'srfm_weekly_scheduled_events' );

		if ( $srfm_email_summary ) {
			Email_Summary::schedule_weekly_entries_email();
		}

		return update_option(
			'srfm_email_summary_settings_options',
			[
				'srfm_email_summary'   => $srfm_email_summary,
				'srfm_email_sent_to'   => $srfm_email_sent_to,
				'srfm_schedule_report' => $srfm_schedule_report,
			]
		);
	}

	/**
	 * Save Security Settings
	 *
	 * @param array<mixed> $setting_options Setting options.
	 * @return bool
	 * @since 0.0.1
	 */
	public static function srfm_save_security_settings( $setting_options ) {

		$srfm_v2_checkbox_site_key    = $setting_options['srfm_v2_checkbox_site_key'] ?? '';
		$srfm_v2_checkbox_secret_key  = $setting_options['srfm_v2_checkbox_secret_key'] ?? '';
		$srfm_v2_invisible_site_key   = $setting_options['srfm_v2_invisible_site_key'] ?? '';
		$srfm_v2_invisible_secret_key = $setting_options['srfm_v2_invisible_secret_key'] ?? '';
		$srfm_v3_site_key             = $setting_options['srfm_v3_site_key'] ?? '';
		$srfm_v3_secret_key           = $setting_options['srfm_v3_secret_key'] ?? '';
		$srfm_cf_appearance_mode      = $setting_options['srfm_cf_appearance_mode'] ?? 'auto';
		$srfm_cf_turnstile_site_key   = $setting_options['srfm_cf_turnstile_site_key'] ?? '';
		$srfm_cf_turnstile_secret_key = $setting_options['srfm_cf_turnstile_secret_key'] ?? '';
		$srfm_hcaptcha_site_key       = ! empty( $setting_options['srfm_hcaptcha_site_key'] ) ? $setting_options['srfm_hcaptcha_site_key'] : '';
		$srfm_hcaptcha_secret_key     = ! empty( $setting_options['srfm_hcaptcha_secret_key'] ) ? $setting_options['srfm_hcaptcha_secret_key'] : '';
		$srfm_honeypot                = $setting_options['srfm_honeypot'] ?? false;

		return update_option(
			'srfm_security_settings_options',
			[
				'srfm_v2_checkbox_site_key'    => $srfm_v2_checkbox_site_key,
				'srfm_v2_checkbox_secret_key'  => $srfm_v2_checkbox_secret_key,
				'srfm_v2_invisible_site_key'   => $srfm_v2_invisible_site_key,
				'srfm_v2_invisible_secret_key' => $srfm_v2_invisible_secret_key,
				'srfm_v3_site_key'             => $srfm_v3_site_key,
				'srfm_v3_secret_key'           => $srfm_v3_secret_key,
				'srfm_cf_appearance_mode'      => $srfm_cf_appearance_mode,
				'srfm_cf_turnstile_site_key'   => $srfm_cf_turnstile_site_key,
				'srfm_cf_turnstile_secret_key' => $srfm_cf_turnstile_secret_key,
				'srfm_hcaptcha_site_key'       => $srfm_hcaptcha_site_key,
				'srfm_hcaptcha_secret_key'     => $srfm_hcaptcha_secret_key,
				'srfm_honeypot'                => $srfm_honeypot,
			]
		);
	}

	/**
	 * Get Settings Form Data
	 *
	 * @param \WP_REST_Request $request Request object or array containing form data.
	 * @return void
	 * @since 0.0.1
	 */
	public static function srfm_get_general_settings( $request ) {

		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			wp_send_json_error(
				[
					'data' => __( 'Nonce verification failed.', 'sureforms' ),
				]
			);
		}

		$options_to_get = $request->get_param( 'options_to_fetch' );

		$options_to_get = Helper::get_string_value( $options_to_get );

		$options_to_get = explode( ',', $options_to_get );

		$global_setting_options = get_options( $options_to_get );

		if ( empty( $global_setting_options['srfm_general_settings_options'] ) ) {
			$global_setting_options['srfm_general_settings_options'] = [
				'srfm_ip_log'         => false,
				'srfm_form_analytics' => false,
			];
		}

		/**
		 * We have introduced toggle for analytics optin in the general settings.
		 * Hence retrieving the option sureforms_analytics_optin to get current status.
		 *
		 * @since 1.7.0
		 */
		$srfm_bsf_analytics = get_option( 'sureforms_analytics_optin', false ) === 'yes' ? true : false;
		$global_setting_options['srfm_general_settings_options']['srfm_bsf_analytics'] = $srfm_bsf_analytics;

		if ( empty( $global_setting_options['srfm_default_dynamic_block_option'] ) ) {
			$global_setting_options['srfm_default_dynamic_block_option'] = Helper::default_dynamic_block_option();
		}
		if ( empty( $global_setting_options['srfm_email_summary_settings_options'] ) ) {
			$global_setting_options['srfm_email_summary_settings_options'] = [
				'srfm_email_summary'   => false,
				'srfm_email_sent_to'   => get_option( 'admin_email' ),
				'srfm_schedule_report' => __( 'Monday', 'sureforms' ),
			];
		}
		if ( empty( $global_setting_options['srfm_security_settings_options'] ) ) {
			$global_setting_options['srfm_security_settings_options'] = [
				'srfm_v2_checkbox_site_key'    => '',
				'srfm_v2_checkbox_secret_key'  => '',
				'srfm_v2_invisible_site_key'   => '',
				'srfm_v2_invisible_secret_key' => '',
				'srfm_v3_site_key'             => '',
				'srfm_v3_secret_key'           => '',
				'srfm_cf_appearance_mode'      => 'auto',
				'srfm_cf_turnstile_site_key'   => '',
				'srfm_cf_turnstile_secret_key' => '',
				'srfm_hcaptcha_site_key'       => '',
				'srfm_hcaptcha_secret_key'     => '',
				'srfm_honeypot'                => false,
			];
		}

		wp_send_json( $global_setting_options );
	}

}
