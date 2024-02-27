<?php

/**
 * Sureforms Global Settings - General Tab.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Global_Settings;

use SureForms\Inc\Global_Settings\Email_Summary;
use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;
use WP_REST_Server;
use WP_REST_Response;
use WP_REST_Request;
use WP_Error;

/**
 * Sureforms Global Settings - General Tab.
 *
 * @since 0.0.1
 */
class SRFM_General_Settings {
	use Get_Instance;

	/**
	 * Namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'sureforms/v1';

	/**
	 * General Tab Options.
	 *
	 * @var array<string>
	 */
	private static $general_tab_options = array(
		'srfm_ip_log',
		'srfm_honeypot',
		'srfm_form_analytics',
		'srfm_gdpr',
	);

	/**
	 * Email Summary Tab Options.
	 *
	 * @var array<string>
	 */
	private static $email_summary_tab_options = array(
		'srfm_email_summary',
		'srfm_email_sent_to',
		'srfm_schedule_report',
	);

	/**
	 * Security Tab Options.
	 *
	 * @var array<string>
	 */
	private static $security_tab_options = array(
		'srfm_v2_checkbox_site_key',
		'srfm_v2_checkbox_secret_key',
		'srfm_v2_invisible_site_key',
		'srfm_v2_invisible_secret_key',
		'srfm_v3_site_key',
		'srfm_v3_secret_key',
	);

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
		$sureforms_helper = new Sureforms_Helper();
		register_rest_route(
			$this->namespace,
			'/srfm-global-settings',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'srfm_save_global_settings' ],
				'permission_callback' => [ $sureforms_helper, 'get_items_permissions_check' ],
			)
		);
		register_rest_route(
			$this->namespace,
			'/srfm-global-settings',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'srfm_get_general_settings' ],
				'permission_callback' => [ $sureforms_helper, 'get_items_permissions_check' ],
			)
		);
	}

	/**
	 * Handle Settings Form Submission
	 *
	 * @param \WP_REST_Request $request Request object or array containing form data.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 * @since 0.0.1
	 */
	public static function srfm_save_global_settings( $request ) {

		$nonce = Sureforms_Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

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

		// remove tab from the options
		unset( $setting_options['srfm_tab'] );

		// $is_option_saved = false;

		switch ( $tab ) {
			case 'general-settings':
				$is_option_saved = self::srfm_save_general_settings( $setting_options );
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

		// return new \WP_REST_Response( $is_option_saved, 200 );
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

	public static function srfm_save_general_settings( $setting_options ) {

		$srfm_ip_log         = isset( $setting_options['srfm_ip_log'] ) ? $setting_options['srfm_ip_log'] : false;
		$srfm_honeypot       = isset( $setting_options['srfm_honeypot'] ) ? $setting_options['srfm_honeypot'] : false;
		$srfm_form_analytics = isset( $setting_options['srfm_form_analytics'] ) ? $setting_options['srfm_form_analytics'] : false;
		$srfm_gdpr           = isset( $setting_options['srfm_gdpr'] ) ? $setting_options['srfm_gdpr'] : false;

		$is_option_saved = update_option(
			'srfm_general_settings_options',
			array(
				'srfm_ip_log'         => $srfm_ip_log,
				'srfm_honeypot'       => $srfm_honeypot,
				'srfm_form_analytics' => $srfm_form_analytics,
				'srfm_gdpr'           => $srfm_gdpr,
			)
		);

		return $is_option_saved;
	}

	public static function srfm_save_email_summary_settings( $setting_options ) {

		$srfm_email_summary = isset( $setting_options['srfm_email_summary'] ) ? $setting_options['srfm_email_summary'] : false;
		$srfm_email_sent_to = isset( $setting_options['srfm_email_sent_to'] ) ? $setting_options['srfm_email_sent_to'] : get_option( 'admin_email' );
		$srfm_schedule_report = isset( $setting_options['srfm_schedule_report'] ) ? $setting_options['srfm_schedule_report'] : 'Monday';

		$is_option_saved = update_option(
			'srfm_email_summary_settings_options',
			array(
				'srfm_email_summary' => $srfm_email_summary,
				'srfm_email_sent_to' => $srfm_email_sent_to,
				'srfm_schedule_report' => $srfm_schedule_report,
			)
		);

		$email_summary = new Email_Summary();

		$email_summary->unschedule_events( 'srfm_weekly_scheduled_events' );

		if ( $srfm_email_summary ) {
			$email_summary->schedule_weekly_entries_email();
		}

		return $is_option_saved;
	}

	// save security settings
	public static function srfm_save_security_settings( $setting_options ) {
		
		$srfm_v2_checkbox_site_key = isset( $setting_options['srfm_v2_checkbox_site_key'] ) ? $setting_options['srfm_v2_checkbox_site_key'] : '';
		$srfm_v2_checkbox_secret_key = isset( $setting_options['srfm_v2_checkbox_secret_key'] ) ? $setting_options['srfm_v2_checkbox_secret_key'] : '';
		$srfm_v2_invisible_site_key = isset( $setting_options['srfm_v2_invisible_site_key'] ) ? $setting_options['srfm_v2_invisible_site_key'] : '';
		$srfm_v2_invisible_secret_key = isset( $setting_options['srfm_v2_invisible_secret_key'] ) ? $setting_options['srfm_v2_invisible_secret_key'] : '';
		$srfm_v3_site_key = isset( $setting_options['srfm_v3_site_key'] ) ? $setting_options['srfm_v3_site_key'] : '';
		$srfm_v3_secret_key = isset( $setting_options['srfm_v3_secret_key'] ) ? $setting_options['srfm_v3_secret_key'] : '';

		$is_option_saved = update_option(
			'srfm_security_settings_options',
			array(
				'srfm_v2_checkbox_site_key' => $srfm_v2_checkbox_site_key,
				'srfm_v2_checkbox_secret_key' => $srfm_v2_checkbox_secret_key,
				'srfm_v2_invisible_site_key' => $srfm_v2_invisible_site_key,
				'srfm_v2_invisible_secret_key' => $srfm_v2_invisible_secret_key,
				'srfm_v3_site_key' => $srfm_v3_site_key,
				'srfm_v3_secret_key' => $srfm_v3_secret_key,
			)
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

		$nonce = Sureforms_Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			wp_send_json_error(
				[
					'data'   => __( 'Nonce verification failed.', 'sureforms' ),
					'status' => false,
				]
			);
		}

		$global_setting_options = get_options( [ 'srfm_general_settings_options', 'srfm_email_summary_settings_options', 'srfm_security_settings_options' ] );

		if ( ! is_array( $global_setting_options ) ) {
			$global_setting_options = array(
				'srfm_ip_log'          => false,
				'srfm_honeypot'        => false,
				'srfm_form_analytics'  => false,
				'srfm_gdpr'            => false,
				'srfm_email_summary'   => false,
				'srfm_email_sent_to'   => get_option( 'admin_email' ),
				'srfm_schedule_report' => 'Monday',
				'srfm_v2_checkbox_site_key' => '',
				'srfm_v2_checkbox_secret_key' => '',
				'srfm_v2_invisible_site_key' => '',
				'srfm_v2_invisible_secret_key' => '',
				'srfm_v3_site_key' => '',
				'srfm_v3_secret_key' => '',
			);
		}

		wp_send_json( $global_setting_options );
	}

}
