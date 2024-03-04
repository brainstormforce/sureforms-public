<?php
/**
 * Sureforms Global Settings.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc\Global_Settings;

use SRFM\Inc\Global_Settings\SRFM_Email_Summary;
use SRFM\Inc\Traits\SRFM_Get_Instance;
use SRFM\Inc\SRFM_Helper;
use WP_REST_Server;
use WP_REST_Response;
use WP_REST_Request;
use WP_Error;

/**
 * Sureforms Global Settings - General Tab.
 *
 * @since 0.0.1
 */
class SRFM_Global_Settings {
	use SRFM_Get_Instance;

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
		$sureforms_helper = new SRFM_Helper();
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
	 * Handle Settings Form Submission
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return void
	 *
	 * @since 0.0.1
	 */
	public static function srfm_save_global_settings( $request ) {

		$nonce = SRFM_Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			wp_send_json_error(
				[
					'data'   => __( 'Nonce verification failed.', 'sureforms' ),
					'status' => false,
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

		if ( $is_option_saved ) {
			wp_send_json_success(
				[
					'data'   => __( 'Settings saved successfully.', 'sureforms' ),
					'status' => true,
				]
			);
		} else {
			wp_send_json_error(
				[
					'data'   => __( 'Failed to save settings.', 'sureforms' ),
					'status' => false,
				]
			);
		}
	}

	/**
	 * Save General Settings
	 *
	 * @param array<mixed> $setting_options Setting options.
	 * @return bool
	 * @since 0.0.1
	 */
	public static function srfm_save_general_settings( $setting_options ) {

		$srfm_ip_log         = isset( $setting_options['srfm_ip_log'] ) ? $setting_options['srfm_ip_log'] : false;
		$srfm_honeypot       = isset( $setting_options['srfm_honeypot'] ) ? $setting_options['srfm_honeypot'] : false;
		$srfm_form_analytics = isset( $setting_options['srfm_form_analytics'] ) ? $setting_options['srfm_form_analytics'] : false;
		$srfm_gdpr           = isset( $setting_options['srfm_gdpr'] ) ? $setting_options['srfm_gdpr'] : false;

		$is_option_saved = update_option(
			'srfm_general_settings_options',
			[
				'srfm_ip_log'         => $srfm_ip_log,
				'srfm_honeypot'       => $srfm_honeypot,
				'srfm_form_analytics' => $srfm_form_analytics,
				'srfm_gdpr'           => $srfm_gdpr,
			]
		);

		return $is_option_saved;
	}

	/**
	 * Save General Settings Dynamic Options
	 *
	 * @param array<mixed> $setting_options Setting options.
	 * @return bool
	 * @since 0.0.1
	 */
	public static function srfm_save_general_settings_dynamic_opt( $setting_options ) {

		$default_dynamic_block_option = [
			'srfm_url_block_required_text'          => $setting_options['srfm_url_block_required_text'],
			'srfm_input_block_required_text'        => $setting_options['srfm_input_block_required_text'],
			'srfm_input_block_unique_text'          => $setting_options['srfm_input_block_unique_text'],
			'srfm_address_block_required_text'      => $setting_options['srfm_address_block_required_text'],
			'srfm_phone_block_required_text'        => $setting_options['srfm_phone_block_required_text'],
			'srfm_phone_block_unique_text'          => $setting_options['srfm_phone_block_unique_text'],
			'srfm_number_block_required_text'       => $setting_options['srfm_number_block_required_text'],
			'srfm_textarea_block_required_text'     => $setting_options['srfm_textarea_block_required_text'],
			'srfm_multi_choice_block_required_text' => $setting_options['srfm_multi_choice_block_required_text'],
			'srfm_checkbox_block_required_text'     => $setting_options['srfm_checkbox_block_required_text'],
			'srfm_email_block_required_text'        => $setting_options['srfm_email_block_required_text'],
			'srfm_email_block_unique_text'          => $setting_options['srfm_email_block_unique_text'],
			'srfm_dropdown_block_required_text'     => $setting_options['srfm_dropdown_block_required_text'],

		];

		$is_option_saved = update_option( 'get_default_dynamic_block_option', $default_dynamic_block_option );

		return $is_option_saved;
	}

	/**
	 * Save Email Summary Settings
	 *
	 * @param array<mixed> $setting_options Setting options.
	 * @return bool
	 * @since 0.0.1
	 */
	public static function srfm_save_email_summary_settings( $setting_options ) {

		$srfm_email_summary   = isset( $setting_options['srfm_email_summary'] ) ? $setting_options['srfm_email_summary'] : false;
		$srfm_email_sent_to   = isset( $setting_options['srfm_email_sent_to'] ) ? $setting_options['srfm_email_sent_to'] : get_option( 'admin_email' );
		$srfm_schedule_report = isset( $setting_options['srfm_schedule_report'] ) ? $setting_options['srfm_schedule_report'] : 'Monday';

		$is_option_saved = update_option(
			'srfm_email_summary_settings_options',
			[
				'srfm_email_summary'   => $srfm_email_summary,
				'srfm_email_sent_to'   => $srfm_email_sent_to,
				'srfm_schedule_report' => $srfm_schedule_report,
			]
		);

		$email_summary = new SRFM_Email_Summary();

		$email_summary->unschedule_events( 'srfm_weekly_scheduled_events' );

		if ( $srfm_email_summary ) {
			$email_summary->schedule_weekly_entries_email();
		}

		return $is_option_saved;
	}

	/**
	 * Save Security Settings
	 *
	 * @param array<mixed> $setting_options Setting options.
	 * @return bool
	 * @since 0.0.1
	 */
	public static function srfm_save_security_settings( $setting_options ) {

		$srfm_v2_checkbox_site_key    = isset( $setting_options['srfm_v2_checkbox_site_key'] ) ? $setting_options['srfm_v2_checkbox_site_key'] : '';
		$srfm_v2_checkbox_secret_key  = isset( $setting_options['srfm_v2_checkbox_secret_key'] ) ? $setting_options['srfm_v2_checkbox_secret_key'] : '';
		$srfm_v2_invisible_site_key   = isset( $setting_options['srfm_v2_invisible_site_key'] ) ? $setting_options['srfm_v2_invisible_site_key'] : '';
		$srfm_v2_invisible_secret_key = isset( $setting_options['srfm_v2_invisible_secret_key'] ) ? $setting_options['srfm_v2_invisible_secret_key'] : '';
		$srfm_v3_site_key             = isset( $setting_options['srfm_v3_site_key'] ) ? $setting_options['srfm_v3_site_key'] : '';
		$srfm_v3_secret_key           = isset( $setting_options['srfm_v3_secret_key'] ) ? $setting_options['srfm_v3_secret_key'] : '';

		$is_option_saved = update_option(
			'srfm_security_settings_options',
			[
				'srfm_v2_checkbox_site_key'    => $srfm_v2_checkbox_site_key,
				'srfm_v2_checkbox_secret_key'  => $srfm_v2_checkbox_secret_key,
				'srfm_v2_invisible_site_key'   => $srfm_v2_invisible_site_key,
				'srfm_v2_invisible_secret_key' => $srfm_v2_invisible_secret_key,
				'srfm_v3_site_key'             => $srfm_v3_site_key,
				'srfm_v3_secret_key'           => $srfm_v3_secret_key,
			]
		);

		return $is_option_saved;
	}

	/**
	 * Get Settings Form Data
	 *
	 * @param \WP_REST_Request $request Request object or array containing form data.
	 * @return void
	 * @since 0.0.1
	 */
	public static function srfm_get_general_settings( $request ) {

		$nonce = SRFM_Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			wp_send_json_error(
				[
					'data'   => __( 'Nonce verification failed.', 'sureforms' ),
					'status' => false,
				]
			);
		}

		$options_to_get = $request->get_param( 'options_to_fetch' );

		$options_to_get = SRFM_Helper::get_string_value( $options_to_get );

		$options_to_get = explode( ',', $options_to_get );

		$global_setting_options = get_options( $options_to_get );

		if ( empty( $global_setting_options['srfm_general_settings_options'] ) ) {
			$global_setting_options['srfm_general_settings_options'] = [
				'srfm_ip_log'         => false,
				'srfm_honeypot'       => false,
				'srfm_form_analytics' => false,
				'srfm_gdpr'           => false,
			];
		}
		if ( empty( $global_setting_options['get_default_dynamic_block_option'] ) ) {
			$global_setting_options['get_default_dynamic_block_option'] = SRFM_Helper::default_dynamic_block_option();
		}
		if ( empty( $global_setting_options['srfm_email_summary_settings_options'] ) ) {
			$global_setting_options['srfm_email_summary_settings_options'] = [
				'srfm_email_summary'   => false,
				'srfm_email_sent_to'   => get_option( 'admin_email' ),
				'srfm_schedule_report' => 'Monday',
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
			];
		}

		wp_send_json( $global_setting_options );
	}

}
