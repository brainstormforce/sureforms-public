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
use SRFM\Inc\Payments\Payment_Helper;
use SRFM\Inc\Traits\Get_Instance;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		register_rest_route(
			$this->namespace,
			'/srfm-global-settings',
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'srfm_save_global_settings' ],
				'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
			]
		);
		register_rest_route(
			$this->namespace,
			'/srfm-global-settings',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'srfm_get_general_settings' ],
				'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
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
			return new WP_Error( 'rest_nonce_invalid', __( 'Nonce verification failed.', 'sureforms' ), [ 'status' => 403 ] );
		}

		$setting_options = $request->get_params();

		$tab = $setting_options['srfm_tab'] ?? '';

		unset( $setting_options['srfm_tab'] );

		switch ( $tab ) {
			case 'general-settings':
				self::srfm_save_general_settings( $setting_options );
				break;
			case 'general-settings-dynamic-opt':
				self::srfm_save_general_settings_dynamic_opt( $setting_options );
				break;
			case 'email-settings':
				self::srfm_save_email_summary_settings( $setting_options );
				break;
			case 'security-settings':
				self::srfm_save_security_settings( $setting_options );
				break;
			case 'payments-settings':
				self::srfm_save_payments_settings( $setting_options );
				break;
			case 'mcp-settings':
				self::srfm_save_mcp_settings( $setting_options );
				break;
			case 'form-restriction-settings':
				self::srfm_save_form_restriction_settings( $setting_options );
				break;
			case 'compliance-settings':
				self::srfm_save_compliance_settings( $setting_options );
				break;
			case 'form-confirmation-settings':
				self::srfm_save_form_confirmation_settings( $setting_options );
				break;
			case 'email-notification-settings':
				self::srfm_save_email_notification_settings( $setting_options );
				break;
			default:
				return new WP_Error( 'srfm_invalid_tab', __( 'Invalid settings tab.', 'sureforms' ), [ 'status' => 400 ] );
		}

		// update_option() returns false when the stored value already matches
		// the new value — that is not an error, so we only flag truly invalid
		// tabs (handled in default above) and always return success here.
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

		$srfm_ip_log             = $setting_options['srfm_ip_log'] ?? false;
		$srfm_form_analytics     = $setting_options['srfm_form_analytics'] ?? false;
		$srfm_bsf_analytics      = $setting_options['srfm_bsf_analytics'] ?? false;
		$srfm_admin_notification = isset( $setting_options['srfm_admin_notification'] ) ? (bool) $setting_options['srfm_admin_notification'] : true;

		$settings = [
			'srfm_ip_log'             => $srfm_ip_log,
			'srfm_form_analytics'     => $srfm_form_analytics,
			'srfm_admin_notification' => $srfm_admin_notification,
		];

		/**
		 * We are updating sureforms_analytics_optin option from the general settings as it has been introduced
		 * as part of general settings. Since the option sureforms_analytics_optin is already available from BSF analytics library
		 * We are updating this independently.
		 *
		 * @since 1.7.0
		 *
		 * @since 2.5.1 - Renamed sureforms_analytics_optin to sureforms_usage_optin.
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

		return update_option( 'sureforms_usage_optin', $enable_tracking );
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
			'srfm_textarea_min_chars',
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
				$value                 = $setting_options[ $key ];
				$options_names[ $key ] = sanitize_text_field( is_scalar( $value ) ? (string) $value : '' );
			}
		}

		// Re-sanitize after filter so Pro-injected keys are also covered.
		$options_to_save = apply_filters( 'srfm_general_dynamic_options_to_save', $options_names, $setting_options );
		$options_to_save = array_map( 'sanitize_text_field', $options_to_save );
		return update_option( 'srfm_default_dynamic_block_option', $options_to_save );
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
		$srfm_email_sent_to   = sanitize_email( $setting_options['srfm_email_sent_to'] ?? get_option( 'admin_email' ) );
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

		$srfm_v2_checkbox_site_key    = sanitize_text_field( Helper::get_string_value( $setting_options['srfm_v2_checkbox_site_key'] ?? '' ) );
		$srfm_v2_checkbox_secret_key  = sanitize_text_field( Helper::get_string_value( $setting_options['srfm_v2_checkbox_secret_key'] ?? '' ) );
		$srfm_v2_invisible_site_key   = sanitize_text_field( Helper::get_string_value( $setting_options['srfm_v2_invisible_site_key'] ?? '' ) );
		$srfm_v2_invisible_secret_key = sanitize_text_field( Helper::get_string_value( $setting_options['srfm_v2_invisible_secret_key'] ?? '' ) );
		$srfm_v3_site_key             = sanitize_text_field( Helper::get_string_value( $setting_options['srfm_v3_site_key'] ?? '' ) );
		$srfm_v3_secret_key           = sanitize_text_field( Helper::get_string_value( $setting_options['srfm_v3_secret_key'] ?? '' ) );
		$srfm_cf_appearance_mode      = sanitize_text_field( Helper::get_string_value( $setting_options['srfm_cf_appearance_mode'] ?? 'auto' ) );
		$srfm_cf_turnstile_site_key   = sanitize_text_field( Helper::get_string_value( $setting_options['srfm_cf_turnstile_site_key'] ?? '' ) );
		$srfm_cf_turnstile_secret_key = sanitize_text_field( Helper::get_string_value( $setting_options['srfm_cf_turnstile_secret_key'] ?? '' ) );
		$srfm_hcaptcha_site_key       = sanitize_text_field( Helper::get_string_value( $setting_options['srfm_hcaptcha_site_key'] ?? '' ) );
		$srfm_hcaptcha_secret_key     = sanitize_text_field( Helper::get_string_value( $setting_options['srfm_hcaptcha_secret_key'] ?? '' ) );
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
	 * Save Payments Settings
	 *
	 * Handles saving of both global payment settings (currency, payment_mode) and
	 * gateway-specific settings based on the gateway parameter.
	 *
	 * @param array<mixed> $setting_options Setting options.
	 * @return bool
	 * @since 2.0.0
	 */
	public static function srfm_save_payments_settings( $setting_options ) {
		$gateway = isset( $setting_options['gateway'] ) && is_string( $setting_options['gateway'] )
			? sanitize_text_field( $setting_options['gateway'] )
			: 'stripe';

		// Handle global settings (currency, payment_mode).
		if ( isset( $setting_options['currency'] ) && ! empty( $setting_options['currency'] ) && is_string( $setting_options['currency'] ) ) {
			$currency = sanitize_text_field( $setting_options['currency'] );
			Payment_Helper::update_global_setting( 'currency', $currency );
		}

		$payment_mode = null;
		if ( isset( $setting_options['payment_mode'] ) && ! empty( $setting_options['payment_mode'] ) && is_string( $setting_options['payment_mode'] ) ) {
			$payment_mode = sanitize_text_field( $setting_options['payment_mode'] );
			Payment_Helper::update_global_setting( 'payment_mode', $payment_mode );
		}

		// Save currency sign position.
		if ( isset( $setting_options['currency_sign_position'] ) && ! empty( $setting_options['currency_sign_position'] ) && is_string( $setting_options['currency_sign_position'] ) ) {
			$currency_sign_position = sanitize_text_field( $setting_options['currency_sign_position'] );
			Payment_Helper::update_global_setting( 'currency_sign_position', $currency_sign_position );
		}

		// Handle gateway-specific settings.
		if ( 'stripe' === $gateway ) {
			$current_stripe_settings = Payment_Helper::get_gateway_settings( 'stripe' );

			// Update payment_mode in stripe settings as well (if provided).
			if ( null !== $payment_mode ) {
				$current_stripe_settings['payment_mode'] = $payment_mode;
			}

			// Connection data (keys, account info) is managed separately via OAuth.
			return Payment_Helper::update_gateway_settings( 'stripe', $current_stripe_settings );
		}

		return true;
	}

	/**
	 * Save MCP Settings
	 *
	 * @param array<mixed> $setting_options Setting options.
	 * @return bool
	 * @since 2.6.0
	 */
	public static function srfm_save_mcp_settings( $setting_options ) {
		$srfm_abilities_api        = ! empty( $setting_options['srfm_abilities_api'] );
		$srfm_abilities_api_edit   = ! empty( $setting_options['srfm_abilities_api_edit'] );
		$srfm_abilities_api_delete = ! empty( $setting_options['srfm_abilities_api_delete'] );
		$srfm_mcp_server           = ! empty( $setting_options['srfm_mcp_server'] );

		// Save as individual options for the Abilities API permission_callback.
		update_option( 'srfm_abilities_api', $srfm_abilities_api );
		update_option( 'srfm_abilities_api_edit', $srfm_abilities_api_edit );
		update_option( 'srfm_abilities_api_delete', $srfm_abilities_api_delete );
		update_option( 'srfm_mcp_server', $srfm_mcp_server );

		// Save grouped option for the settings UI fetch.
		return update_option(
			'srfm_mcp_settings_options',
			[
				'srfm_abilities_api'        => $srfm_abilities_api,
				'srfm_abilities_api_edit'   => $srfm_abilities_api_edit,
				'srfm_abilities_api_delete' => $srfm_abilities_api_delete,
				'srfm_mcp_server'           => $srfm_mcp_server,
			]
		);
	}

	/**
	 * Save Form Restriction Settings
	 *
	 * Handles saving of the free Maximum Entries subsection. Pro-only
	 * subsections (IP, Country, Keyword) are persisted via the Pro plugin's
	 * own REST endpoint and option key, so the existing values for those
	 * sub-keys are preserved here via array merge.
	 *
	 * @param array<mixed> $setting_options Setting options.
	 * @return bool
	 * @since 2.9.0
	 */
	public static function srfm_save_form_restriction_settings( $setting_options ) {
		$max_entries = isset( $setting_options['max_entries'] ) && is_array( $setting_options['max_entries'] ) ? $setting_options['max_entries'] : [];

		$existing = get_option( 'srfm_form_restriction_settings_options', [] );
		if ( ! is_array( $existing ) ) {
			$existing = [];
		}

		$settings                = $existing;
		$settings['max_entries'] = [
			'status'     => isset( $max_entries['status'] ) ? (bool) $max_entries['status'] : false,
			'maxEntries' => isset( $max_entries['maxEntries'] ) ? absint( $max_entries['maxEntries'] ) : 0,
			'message'    => isset( $max_entries['message'] ) ? sanitize_textarea_field( (string) $max_entries['message'] ) : __( "This form is now closed as we've received all the entries.", 'sureforms' ),
		];

		return update_option( 'srfm_form_restriction_settings_options', $settings );
	}

	/**
	 * Save Compliance Settings
	 *
	 * Handles saving of global compliance settings that serve as defaults
	 * for newly created forms.
	 *
	 * @param array<mixed> $setting_options Setting options.
	 * @return bool
	 * @since 2.9.0
	 */
	public static function srfm_save_compliance_settings( $setting_options ) {
		$settings = [
			'gdpr'                 => isset( $setting_options['gdpr'] ) ? (bool) $setting_options['gdpr'] : false,
			'do_not_store_entries' => isset( $setting_options['do_not_store_entries'] ) ? (bool) $setting_options['do_not_store_entries'] : false,
			'auto_delete_entries'  => isset( $setting_options['auto_delete_entries'] ) ? (bool) $setting_options['auto_delete_entries'] : false,
			'auto_delete_days'     => isset( $setting_options['auto_delete_days'] ) ? absint( $setting_options['auto_delete_days'] ) : 30,
		];

		return update_option( 'srfm_compliance_settings_options', $settings );
	}

	/**
	 * Get default compliance settings.
	 *
	 * @return array<string, mixed>
	 * @since 2.9.0
	 */
	public static function get_default_compliance_settings() {
		return [
			'gdpr'                 => false,
			'do_not_store_entries' => false,
			'auto_delete_entries'  => false,
			'auto_delete_days'     => 30,
		];
	}

	/**
	 * Save Form Confirmation Settings
	 *
	 * Handles saving of global form confirmation settings that serve as defaults
	 * for newly created forms.
	 *
	 * @param array<mixed> $setting_options Setting options.
	 * @return bool
	 * @since 2.9.0
	 */
	public static function srfm_save_form_confirmation_settings( $setting_options ) {
		$valid_confirmation_types = [ 'same page', 'different page', 'custom url' ];
		$valid_submission_actions = [ 'hide form', 'reset form' ];

		$message = isset( $setting_options['message'] ) ? wp_kses_post( Helper::get_string_value( $setting_options['message'] ) ) : __( 'Thank you for contacting us! We will be in touch with you shortly.', 'sureforms' );

		// Sanitize query parameters — each item is a key-value object.
		$query_params = [];
		if ( isset( $setting_options['query_params'] ) && is_array( $setting_options['query_params'] ) ) {
			foreach ( $setting_options['query_params'] as $param ) {
				if ( is_array( $param ) ) {
					$sanitized_param = [];
					foreach ( $param as $key => $value ) {
						$sanitized_param[ sanitize_text_field( $key ) ] = sanitize_text_field( $value );
					}
					$query_params[] = $sanitized_param;
				}
			}
		}

		$settings = [
			'confirmation_type'   => isset( $setting_options['confirmation_type'] ) && in_array( $setting_options['confirmation_type'], $valid_confirmation_types, true )
				? sanitize_text_field( $setting_options['confirmation_type'] )
				: 'same page',
			'message'             => $message,
			'submission_action'   => isset( $setting_options['submission_action'] ) && in_array( $setting_options['submission_action'], $valid_submission_actions, true )
				? sanitize_text_field( $setting_options['submission_action'] )
				: 'hide form',
			'page_url'            => isset( $setting_options['page_url'] ) ? esc_url_raw( $setting_options['page_url'] ) : '',
			'custom_url'          => isset( $setting_options['custom_url'] ) ? esc_url_raw( $setting_options['custom_url'] ) : '',
			'enable_query_params' => ! empty( $setting_options['enable_query_params'] ),
			'query_params'        => $query_params,
		];

		return update_option( 'srfm_form_confirmation_settings_options', $settings );
	}

	/**
	 * Get the default confirmation success message HTML.
	 *
	 * Builds the rich HTML block (icon + heading + description) used as the
	 * initial value for the form confirmation message field. Centralised here
	 * so both the settings GET endpoint and the form-defaults applier share
	 * exactly the same value without duplication.
	 *
	 * @return string
	 * @since 2.9.0
	 */
	public static function get_default_confirmation_message() {
		$check_icon = esc_url( plugins_url( 'images/check-icon.svg', SRFM_FILE ) );
		return '<p style="text-align: center;"><img src="' . $check_icon . '" alt="" aria-hidden="true" /></p><h2 style="text-align: center;">'
			. esc_html__( 'Thank you', 'sureforms' ) . '</h2><p style="text-align: center;">'
			. esc_html__( 'Your form has been submitted successfully. We\'ll review your details and get back to you soon.', 'sureforms' ) . '</p>';
	}

	/**
	 * Get default form confirmation settings.
	 *
	 * @return array<string, mixed>
	 * @since 2.9.0
	 */
	public static function get_default_form_confirmation_settings() {
		return [
			'confirmation_type'   => 'same page',
			'message'             => self::get_default_confirmation_message(),
			'submission_action'   => 'hide form',
			'page_url'            => '',
			'custom_url'          => '',
			'enable_query_params' => false,
			'query_params'        => [],
		];
	}

	/**
	 * Save Email Notification Settings
	 *
	 * Handles saving of global email notification settings that serve as defaults
	 * for newly created forms.
	 *
	 * @param array<mixed> $setting_options Setting options.
	 * @return bool
	 * @since 2.9.0
	 */
	public static function srfm_save_email_notification_settings( $setting_options ) {
		$settings = [
			'email_to'       => isset( $setting_options['email_to'] ) ? sanitize_text_field( Helper::get_string_value( $setting_options['email_to'] ) ) : '',
			'subject'        => isset( $setting_options['subject'] ) ? sanitize_text_field( Helper::get_string_value( $setting_options['subject'] ) ) : '',
			'email_body'     => isset( $setting_options['email_body'] ) ? wp_kses_post( Helper::get_string_value( $setting_options['email_body'] ) ) : '',
			'from_name'      => isset( $setting_options['from_name'] ) ? sanitize_text_field( Helper::get_string_value( $setting_options['from_name'] ) ) : '{site_title}',
			'from_email'     => isset( $setting_options['from_email'] ) ? sanitize_text_field( Helper::get_string_value( $setting_options['from_email'] ) ) : '{admin_email}',
			'email_cc'       => isset( $setting_options['email_cc'] ) ? sanitize_text_field( Helper::get_string_value( $setting_options['email_cc'] ) ) : '',
			'email_bcc'      => isset( $setting_options['email_bcc'] ) ? sanitize_text_field( Helper::get_string_value( $setting_options['email_bcc'] ) ) : '',
			'email_reply_to' => isset( $setting_options['email_reply_to'] ) ? sanitize_text_field( Helper::get_string_value( $setting_options['email_reply_to'] ) ) : '',
		];

		return update_option( 'srfm_email_notification_settings_options', $settings );
	}

	/**
	 * Get default email notification settings.
	 *
	 * @return array<string, mixed>
	 * @since 2.9.0
	 */
	public static function get_default_email_notification_settings() {
		return [
			'email_to'       => '{admin_email}',
			'subject'        => sprintf(
				/* translators: %s: {form_title} smart tag placeholder. */
				__( 'New Form Submission - %s', 'sureforms' ),
				'{form_title}'
			),
			'email_body'     => '{all_data}',
			'from_name'      => '{site_title}',
			'from_email'     => '{admin_email}',
			'email_cc'       => '{admin_email}',
			'email_bcc'      => '{admin_email}',
			'email_reply_to' => '{admin_email}',
		];
	}

	/**
	 * Get default form restriction settings.
	 *
	 * Free only ships defaults for the Maximum Entries subsection. Pro-only
	 * subsections (IP, Country, Keyword) ship their own defaults from the
	 * Pro plugin.
	 *
	 * @return array<string, array<string, mixed>>
	 * @since 2.9.0
	 */
	public static function get_default_form_restriction_settings() {
		return [
			'max_entries' => [
				'status'     => false,
				'maxEntries' => 0,
				'message'    => __( "This form is now closed as we've received all the entries.", 'sureforms' ),
			],
		];
	}

	/**
	 * Get Settings Form Data
	 *
	 * @param \WP_REST_Request $request Request object or array containing form data.
	 * @return WP_REST_Response|WP_Error
	 * @since 0.0.1
	 */
	public static function srfm_get_general_settings( $request ) {

		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			return new WP_Error( 'rest_nonce_invalid', __( 'Nonce verification failed.', 'sureforms' ), [ 'status' => 403 ] );
		}

		$options_to_get = $request->get_param( 'options_to_fetch' );

		$options_to_get = Helper::get_string_value( $options_to_get );

		$options_to_get = explode( ',', $options_to_get );

		// Restrict fetched keys to known plugin options to prevent reading
		// arbitrary wp_options values (even though manage_options is required).
		$allowed_options = [
			'srfm_general_settings_options',
			'srfm_email_summary_settings_options',
			'srfm_security_settings_options',
			'srfm_default_dynamic_block_option',
			'srfm_mcp_settings_options',
			'srfm_form_restriction_settings_options',
			'srfm_compliance_settings_options',
			'srfm_form_confirmation_settings_options',
			'srfm_email_notification_settings_options',
		];
		$options_to_get  = array_values( array_intersect( array_map( 'sanitize_text_field', $options_to_get ), $allowed_options ) );

		$global_setting_options = [];
		foreach ( $options_to_get as $option_name ) {
			$global_setting_options[ $option_name ] = get_option( $option_name, [] );
		}

		if ( empty( $global_setting_options['srfm_general_settings_options'] ) || ! is_array( $global_setting_options['srfm_general_settings_options'] ) ) {
				$global_setting_options['srfm_general_settings_options'] = [
					'srfm_ip_log'             => false,
					'srfm_form_analytics'     => false,
					'srfm_admin_notification' => true,
				];
		}

		if ( ! isset( $global_setting_options['srfm_general_settings_options']['srfm_admin_notification'] ) ) {
				$global_setting_options['srfm_general_settings_options']['srfm_admin_notification'] = true;
		}

		/**
		 * We have introduced toggle for analytics optin in the general settings.
		 * Hence retrieving the option sureforms_analytics_optin to get current status.
		 *
		 * @since 1.7.0
		 *
		 * @since 2.5.1 - Renamed sureforms_analytics_optin to sureforms_usage_optin.
		 */
		$srfm_bsf_analytics = get_option( 'sureforms_usage_optin', false ) === 'yes' ? true : false;
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

		if ( empty( $global_setting_options['srfm_mcp_settings_options'] ) ) {
			$global_setting_options['srfm_mcp_settings_options'] = [
				'srfm_abilities_api'        => (bool) get_option( 'srfm_abilities_api', false ),
				'srfm_abilities_api_edit'   => (bool) get_option( 'srfm_abilities_api_edit', false ),
				'srfm_abilities_api_delete' => (bool) get_option( 'srfm_abilities_api_delete', false ),
				'srfm_mcp_server'           => (bool) get_option( 'srfm_mcp_server', false ),
			];
		}

		// Get form restriction settings with defaults.
		$form_restriction_settings = get_option( 'srfm_form_restriction_settings_options', [] );
		if ( empty( $form_restriction_settings ) || ! is_array( $form_restriction_settings ) ) {
			$form_restriction_settings = self::get_default_form_restriction_settings();
		} else {
			// Merge with defaults to ensure all keys exist.
			$form_restriction_settings = array_replace_recursive(
				self::get_default_form_restriction_settings(),
				$form_restriction_settings
			);
		}
		$global_setting_options['srfm_form_restriction_settings_options'] = $form_restriction_settings;

		// Get compliance settings with defaults.
		$compliance_settings = get_option( 'srfm_compliance_settings_options', [] );
		if ( empty( $compliance_settings ) || ! is_array( $compliance_settings ) ) {
			$compliance_settings = self::get_default_compliance_settings();
		} else {
			// Merge with defaults to ensure all keys exist.
			$compliance_settings = array_merge(
				self::get_default_compliance_settings(),
				$compliance_settings
			);
		}
		$global_setting_options['srfm_compliance_settings_options'] = $compliance_settings;

		// Get form confirmation settings with defaults.
		$form_confirmation_settings = get_option( 'srfm_form_confirmation_settings_options', [] );
		if ( empty( $form_confirmation_settings ) || ! is_array( $form_confirmation_settings ) ) {
			$form_confirmation_settings = self::get_default_form_confirmation_settings();
		} else {
			// Merge with defaults to ensure all keys exist.
			$form_confirmation_settings = array_merge(
				self::get_default_form_confirmation_settings(),
				$form_confirmation_settings
			);
		}
		// Restore "data:" prefix stripped by wp_kses_post() in previous saves.
		if ( isset( $form_confirmation_settings['message'] ) && is_string( $form_confirmation_settings['message'] ) && false !== strpos( $form_confirmation_settings['message'], 'src="image/svg+xml;base64' ) ) {
			$normalized = preg_replace( '/src="image\/svg\+xml;base64/', 'src="data:image/svg+xml;base64', $form_confirmation_settings['message'] );
			if ( is_string( $normalized ) ) {
				$form_confirmation_settings['message'] = $normalized;
			}
		}
		$global_setting_options['srfm_form_confirmation_settings_options'] = $form_confirmation_settings;

		// Get email notification settings with defaults.
		$email_notification_settings = get_option( 'srfm_email_notification_settings_options', [] );
		if ( empty( $email_notification_settings ) || ! is_array( $email_notification_settings ) ) {
			$email_notification_settings = self::get_default_email_notification_settings();
		} else {
			// Merge with defaults to ensure all keys exist.
			$email_notification_settings = array_merge(
				self::get_default_email_notification_settings(),
				$email_notification_settings
			);
		}
		$global_setting_options['srfm_email_notification_settings_options'] = $email_notification_settings;

		// Apply filter to allow other modules to add their settings.
		$global_setting_options = apply_filters( 'srfm_global_settings_data', $global_setting_options );

		return new WP_REST_Response( $global_setting_options );
	}

}
