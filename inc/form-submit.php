<?php
/**
 * Sureforms Submit Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc;

use SRFM\Inc\Database\Tables\Entries;
use SRFM\Inc\Email\Email_Template;
use SRFM\Inc\Lib\Browser\Browser;
use SRFM\Inc\Traits\Get_Instance;
use WP_Error;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'wp_handle_upload' ) ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
}

/**
 * Sureforms Submit Class.
 *
 * @since 0.0.1
 */
class Form_Submit {
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
		add_action( 'wp_ajax_validation_ajax_action', [ $this, 'field_unique_validation' ] );
		add_action( 'wp_ajax_nopriv_validation_ajax_action', [ $this, 'field_unique_validation' ] );
		// for quick action bar.
		add_action( 'wp_ajax_srfm_global_update_allowed_block', [ $this, 'srfm_global_update_allowed_block' ] );
		add_action( 'wp_ajax_srfm_global_sidebar_enabled', [ $this, 'srfm_global_sidebar_enabled' ] );
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
			'/submit-form',
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'handle_form_submission' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	/**
	 * Check whether a given request has permission access route.
	 *
	 * @since 0.0.1
	 * @return WP_Error|bool
	 */
	public function permissions_check() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'rest_forbidden', __( 'Sorry, you cannot access this route', 'sureforms' ), [ 'status' => rest_authorization_required_code() ] );
		}
		return true;
	}

	/**
	 * Validate Turnstile token
	 *
	 * @param string       $secret_key Turnstile token.
	 * @param string|false $response Response.
	 * @param string|false $remote_ip Remote IP.
	 * @return array<mixed>|mixed Result of the validation.
	 */
	public static function validate_turnstile_token( $secret_key, $response, $remote_ip ) {

		if ( empty( $secret_key ) || ! is_string( $secret_key ) ) {
			return [
				'success' => false,
				'error'   => __( 'Cloudflare Turnstile secret key is invalid.', 'sureforms' ),
			];
		}

		if ( empty( $response ) ) {
			return [
				'success' => false,
				'error'   => __( 'Cloudflare Turnstile response is missing.', 'sureforms' ),
			];
		}

		$body = [
			'secret'   => $secret_key,
			'response' => $response,
			'remoteip' => $remote_ip,
		];

		$url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

		$args = [
			'body'    => $body,
			'timeout' => 15,
		];

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			return [
				'success' => false,
				'error'   => $error_message,
			];
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * Validate hCaptcha token
	 *
	 * @param string       $secret_key hCaptcha token.
	 * @param string|false $response Response.
	 * @param string|false $remote_ip Remote IP.
	 * @since 0.0.5
	 * @return array<mixed>|mixed Result of the validation.
	 */
	public static function validate_hcaptcha_token( $secret_key, $response, $remote_ip ) {

		if ( empty( $secret_key ) || ! is_string( $secret_key ) ) {
			return [
				'success' => false,
				'error'   => __( 'hCaptcha secret key is invalid.', 'sureforms' ),
			];
		}

		if ( empty( $response ) ) {
			return [
				'success' => false,
				'error'   => __( 'hCaptcha response is missing.', 'sureforms' ),
			];
		}

		$body = [
			'secret'   => $secret_key,
			'response' => $response,
			'remoteip' => $remote_ip,
		];

		$url = 'https://api.hcaptcha.com/siteverify';

		$args = [
			'body'    => $body,
			'timeout' => 15,
		];

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			return [
				'success' => false,
				'error'   => $error_message,
			];
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * Handle Form Submission
	 *
	 * @param \WP_REST_Request $request Request object or array containing form data.
	 * @since 0.0.1
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function handle_form_submission( $request ) {

		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			wp_send_json_error(
				[
					'data'   => __( 'Nonce verification failed.', 'sureforms' ),
					'status' => false,
				]
			);
		}

		$form_data = Helper::sanitize_by_field_type( $request->get_params() );

		if ( empty( $form_data ) || ! is_array( $form_data ) ) {
			wp_send_json_error( __( 'Form data is not found.', 'sureforms' ) );
		}

		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && ! empty( $_FILES ) ) {
			add_filter( 'upload_dir', [ $this, 'change_upload_dir' ] );

			foreach ( $_FILES as $field => $file ) {
				if ( is_array( $file['name'] ) ) {
					foreach ( $file['name'] as $key => $filename ) {
						$temp_path  = $file['tmp_name'][ $key ];
						$file_size  = $file['size'][ $key ];
						$file_type  = $file['type'][ $key ];
						$file_error = $file['error'][ $key ];

						if ( ! $filename && ! $temp_path && ! $file_size && ! $file_type ) {
							$form_data[ $field ][] = '';
							continue;
						}

						$uploaded_file = [
							'name'     => $filename,
							'type'     => $file_type,
							'tmp_name' => $temp_path,
							'error'    => $file_error,
							'size'     => $file_size,
						];

						$upload_overrides = [
							'test_form' => false,
						];
						$move_file        = wp_handle_upload( $uploaded_file, $upload_overrides );
						remove_filter( 'upload_dir', [ $this, 'change_upload_dir' ] );

						if ( $move_file && ! isset( $move_file['error'] ) ) {
							$form_data[ $field ][] = $move_file['url'];
						} else {
							wp_send_json_error( __( 'File is not uploaded', 'sureforms' ) );
						}
					}
				} else {
					$form_data[ $field ][] = '';
				}
			}
		}

		if ( ! $form_data['form-id'] ) {
			wp_send_json_error( __( 'Form Id is missing.', 'sureforms' ) );
		}
		$current_form_id       = $form_data['form-id'];
		$security_type         = Helper::get_meta_value( Helper::get_integer_value( $current_form_id ), '_srfm_captcha_security_type' );
		$selected_captcha_type = get_post_meta( Helper::get_integer_value( $current_form_id ), '_srfm_form_recaptcha', true ) ? Helper::get_string_value( get_post_meta( Helper::get_integer_value( $current_form_id ), '_srfm_form_recaptcha', true ) ) : '';

		if ( 'none' !== $security_type ) {
			$global_setting_options = get_option( 'srfm_security_settings_options' );
		} else {
			$global_setting_options = [];
		}

		if ( 'g-recaptcha' === $security_type ) {
			switch ( $selected_captcha_type ) {
				case 'v2-checkbox':
					$key = 'srfm_v2_checkbox_secret_key';
					break;
				case 'v2-invisible':
					$key = 'srfm_v2_invisible_secret_key';
					break;
				case 'v3-reCAPTCHA':
					$key = 'srfm_v3_secret_key';
					break;
				default:
					$key = '';
					break;
			}

			$google_captcha_secret_key = is_array( $global_setting_options ) && isset( $global_setting_options[ $key ] ) ? $global_setting_options[ $key ] : '';
		}

		if ( 'cf-turnstile' === $security_type ) {
			// Turnstile validation.
			$srfm_cf_turnstile_secret_key = is_array( $global_setting_options ) && isset( $global_setting_options['srfm_cf_turnstile_secret_key'] ) ? Helper::get_string_value( $global_setting_options['srfm_cf_turnstile_secret_key'] ) : '';
			$cf_response                  = ! empty( $form_data['cf-turnstile-response'] ) ? $form_data['cf-turnstile-response'] : false;

			// if gdpr is enabled then set remote ip to empty.
			$compliance = get_post_meta( Helper::get_integer_value( $current_form_id ), '_srfm_compliance', true );
			$gdpr       = false;

			if ( is_array( $compliance ) && is_array( $compliance[0] ) ) {
				$gdpr = ! empty( $compliance[0]['gdpr'] ) ? $compliance[0]['gdpr'] : false;
			}

			// check if ip logging is disabled in global settings then set remote ip to empty.
			$gb_general_settinionsgs_opt = get_option( 'srfm_general_settings_options' );
			$srfm_ip_log                 = is_array( $gb_general_settinionsgs_opt ) && isset( $gb_general_settinionsgs_opt['srfm_ip_log'] ) ? $gb_general_settinionsgs_opt['srfm_ip_log'] : '';

			$remote_ip = $gdpr || ( ! $srfm_ip_log ) ? '' : ( isset( $_SERVER['REMOTE_ADDR'] ) ? filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP ) : '' );

			$turnstile_validation_result = self::validate_turnstile_token( $srfm_cf_turnstile_secret_key, $cf_response, $remote_ip );

			// If the cloudflare validation fails, return an error.
			if ( is_array( $turnstile_validation_result ) && isset( $turnstile_validation_result['success'] ) && false === $turnstile_validation_result['success'] ) {
				$error_message = $turnstile_validation_result['error'] ?? __( 'Cloudflare Turnstile validation failed.', 'sureforms' );
				return new \WP_Error( 'cf_turnstile_error', $error_message, [ 'status' => 403 ] );
			}
		}

		if ( 'hcaptcha' === $security_type ) {
			$srfm_hcaptcha_secret_key = is_array( $global_setting_options ) && isset( $global_setting_options['srfm_hcaptcha_secret_key'] ) ? Helper::get_string_value( $global_setting_options['srfm_hcaptcha_secret_key'] ) : '';
			$hcaptcha_response        = ! empty( $form_data['h-captcha-response'] ) ? $form_data['h-captcha-response'] : false;

			// if gdpr is enabled then set remote ip to empty.
			$compliance = get_post_meta( Helper::get_integer_value( $current_form_id ), '_srfm_compliance', true );
			$gdpr       = false;

			if ( is_array( $compliance ) && is_array( $compliance[0] ) ) {
				$gdpr = ! empty( $compliance[0]['gdpr'] ) ? $compliance[0]['gdpr'] : false;
			}

			// check if ip logging is disabled in global settings then set remote ip to empty.
			$gb_general_settings_options = get_option( 'srfm_general_settings_options' );
			$srfm_ip_log                 = is_array( $gb_general_settings_options ) && isset( $gb_general_settings_options['srfm_ip_log'] ) ? $gb_general_settings_options['srfm_ip_log'] : '';

			$remote_ip                  = $gdpr || ( ! $srfm_ip_log ) ? '' : ( isset( $_SERVER['REMOTE_ADDR'] ) ? filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP ) : '' );
			$hcaptcha_validation_result = self::validate_hcaptcha_token( $srfm_hcaptcha_secret_key, $hcaptcha_response, $remote_ip );

			// If the hcaptcha validation fails, return an error.
			if ( is_array( $hcaptcha_validation_result ) && isset( $hcaptcha_validation_result['success'] ) && false === $hcaptcha_validation_result['success'] ) {
				$error_message = $hcaptcha_validation_result['error'] ?? __( 'hCaptcha validation failed.', 'sureforms' );
				return new \WP_Error( 'hcaptcha_error', $error_message, [ 'status' => 403 ] );
			}
		}

		if ( isset( $form_data['srfm-honeypot-field'] ) && empty( $form_data['srfm-honeypot-field'] ) ) {
			if ( ! empty( $google_captcha_secret_key ) ) {
				if ( isset( $form_data['sureforms_form_submit'] ) ) {
					$secret_key       = $google_captcha_secret_key;
					$ipaddress        = isset( $_SERVER['REMOTE_ADDR'] ) ? filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP ) : '';
					$captcha_response = $form_data['g-recaptcha-response'];
					$url              = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $captcha_response . '&ip=' . $ipaddress;

					$response = wp_remote_get( $url );

					if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
						$json_string = wp_remote_retrieve_body( $response );
						$data        = (array) json_decode( $json_string, true );
					} else {
						$data = [];
					}
					$sureforms_captcha_data = $data;

				} else {
					return new \WP_Error( 'recaptcha_error', __( 'reCAPTCHA error.', 'sureforms' ), [ 'status' => 403 ] );
				}
				if ( isset( $sureforms_captcha_data['success'] ) && true === $sureforms_captcha_data['success'] ) {
					return rest_ensure_response( $this->handle_form_entry( $form_data ) );
				}
					return new \WP_Error( 'recaptcha_error', __( 'reCAPTCHA error.', 'sureforms' ), [ 'status' => 403 ] );

			}
				return rest_ensure_response( $this->handle_form_entry( $form_data ) );

		}
		if ( ! isset( $form_data['srfm-honeypot-field'] ) ) {
			if ( ! empty( $google_captcha_secret_key ) ) {
				if ( isset( $form_data['sureforms_form_submit'] ) ) {
					$secret_key       = $google_captcha_secret_key;
					$ipaddress        = isset( $_SERVER['REMOTE_ADDR'] ) ? filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP ) : '';
					$captcha_response = $form_data['g-recaptcha-response'];
					$url              = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $captcha_response . '&ip=' . $ipaddress;

					$response = wp_remote_get( $url );

					if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
						$json_string = wp_remote_retrieve_body( $response );
						$data        = (array) json_decode( $json_string, true );
					} else {
						$data = [];
					}
					$sureforms_captcha_data = $data;

				} else {
					return new \WP_Error( 'recaptcha_error', __( 'reCAPTCHA error.', 'sureforms' ), [ 'status' => 403 ] );
				}
				if ( true === $sureforms_captcha_data['success'] ) {
					return rest_ensure_response( $this->handle_form_entry( $form_data ) );
				}
					return new \WP_Error( 'recaptcha_error', __( 'reCAPTCHA error.', 'sureforms' ), [ 'status' => 403 ] );

			}
				return rest_ensure_response( $this->handle_form_entry( $form_data ) );

		}
			return new \WP_Error( 'spam_detected', __( 'Spam Detected', 'sureforms' ), [ 'status' => 403 ] );
	}

	/**
	 * Change the upload directory
	 *
	 * @param array<mixed> $dirs upload directory.
	 * @return array<mixed>
	 * @since 0.0.1
	 */
	public function change_upload_dir( $dirs ) {
		$dirs['subdir'] = '/sureforms';
		$dirs['path']   = $dirs['basedir'] . $dirs['subdir'];
		$dirs['url']    = $dirs['baseurl'] . $dirs['subdir'];
		return $dirs;
	}

	/**
	 * Send Email and Create Entry.
	 *
	 * @param array<string> $form_data Request object or array containing form data.
	 * @since 0.0.1
	 * @return array<mixed> Array containing the response data.
	 */
	public function handle_form_entry( $form_data ) {

		$id = sanitize_text_field( $form_data['form-id'] );

		// Get the compliance settings.
		$compliance           = get_post_meta( Helper::get_integer_value( $id ), '_srfm_compliance', true );
		$gdpr                 = '';
		$do_not_store_entries = '';

		if ( is_array( $compliance ) && is_array( $compliance[0] ) ) {
			$gdpr                 = $compliance[0]['gdpr'] ?? '';
			$do_not_store_entries = $compliance[0]['do_not_store_entries'] ?? '';
		}

		$submission_data = [];

		$form_data_keys  = array_keys( $form_data );
		$form_data_count = count( $form_data );

		for ( $i = 0; $i < $form_data_count; $i++ ) {
			$key = strval( $form_data_keys[ $i ] );

			/**
			 * This will allow to pass only sureforms fields
			 * checking -lbl- as thats mandatory for in key of sureforms fields.
			 */
			if ( false === str_contains( $key, '-lbl-' ) ) {
				continue;
			}

			$value = $form_data[ $key ];

			$field_name = htmlspecialchars( str_replace( '_', ' ', $key ) );

			// If the field is an array, encode the values. This is to add support for multi-upload field.
			if ( is_array( $value ) ) {
				$submission_data[ $field_name ] =
					array_map(
						static function ( $val ) {
							return rawurlencode( $val );
						},
						$value
					);
			} else {
				$submission_data[ $field_name ] = htmlspecialchars( $value );
			}
		}

		$modified_message = $this->prepare_submission_data( $submission_data );

		$form_before_submission_data = [
			'form_id' => $id ? intval( $id ) : '',
			'data'    => $modified_message,
		];

		/**
		 * Fires before submission process starts.
		 */
		do_action( 'srfm_before_submission', $form_before_submission_data );

		$name       = sanitize_text_field( get_the_title( intval( $id ) ) );
		$send_email = $this->send_email( $id, $submission_data, $form_data );
		$emails     = [];

		if ( $send_email ) {
			$emails = $send_email['emails'];
		}

		// Check if GDPR is enabled and do not store entries is enabled.
		// If so, send email and do not store entries.
		if ( $gdpr && $do_not_store_entries ) {

			$form_submit_response = [
				'success'   => true,
				'form_id'   => $id ? intval( $id ) : '',
				'to_emails' => $emails,
				'form_name' => $name ? esc_attr( $name ) : '',
				'message'   => Generate_Form_Markup::get_confirmation_markup( $form_data, $submission_data ),
				'data'      => $modified_message,
			];

			do_action( 'srfm_form_submit', $form_submit_response );

			/**
			 * Hook for enabling background processes.
			 *
			 * @param array $form_data form data related to submission.
			 */
			$form_data['form_id'] = $id ? intval( $id ) : '';
			do_action( 'srfm_after_submission_process', $form_data );

			return [
				'success'      => true,
				'message'      => Generate_Form_Markup::get_confirmation_markup( $form_data, $submission_data ),
				'data'         => [
					'name'         => $name,
					'after_submit' => false,
				],
				'redirect_url' => Generate_Form_Markup::get_redirect_url( $form_data, $submission_data ),
			];

		}

		$global_setting_options = get_option( 'srfm_general_settings_options' );

		// If GDPR is enabled, do not store IP, browser, and device info.
		// If not, store IP, browser, and device info.
		$user_ip      = '';
		$browser_name = '';
		$device_name  = '';
		if ( ! $gdpr ) {
			$srfm_ip_log = is_array( $global_setting_options ) && isset( $global_setting_options['srfm_ip_log'] ) ? $global_setting_options['srfm_ip_log'] : '';

			$user_ip      = $srfm_ip_log && isset( $_SERVER['REMOTE_ADDR'] ) ? filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP ) : '';
			$browser      = new Browser();
			$browser_name = sanitize_text_field( $browser->getBrowser() );
			$device_name  = sanitize_text_field( $browser->getPlatform() );
		}

		$form_markup = get_the_content( null, false, Helper::get_integer_value( $form_data['form-id'] ) );
		$pattern     = '/"label":"(.*?)"/';
		preg_match_all( $pattern, $form_markup, $matches );
		$submission_info = [
			'user_ip'      => $user_ip,
			'browser_name' => $browser_name,
			'device_name'  => $device_name,
		];
		$entries_data    = [
			'form_id'         => $id,
			'form_data'       => $submission_data,
			'submission_info' => $submission_info,
		];
		if ( is_user_logged_in() ) {
			// If user is logged in then save their user id.
			$entries_data['user_id'] = get_current_user_id();
		}
		$entry_id = Entries::add( $entries_data );
		if ( $entry_id ) {

			$response = [
				'success'      => true,
				'message'      => Generate_Form_Markup::get_confirmation_markup( $form_data, $submission_data ),
				'data'         => [
					'name'          => $name,
					'submission_id' => $entry_id,
					'after_submit'  => true,
				],
				'redirect_url' => Generate_Form_Markup::get_redirect_url( $form_data, $submission_data ),
			];

			$form_submit_response = apply_filters(
				'srfm_form_submit_response',
				[
					'success'   => true,
					'form_id'   => $id ? intval( $id ) : '',
					'entry_id'  => intval( $entry_id ),
					'to_emails' => $emails,
					'form_name' => $name ? esc_attr( $name ) : '',
					'message'   => Generate_Form_Markup::get_confirmation_markup( $form_data, $submission_data ),
					'data'      => $modified_message,
				]
			);

			do_action( 'srfm_form_submit', $form_submit_response );
		} else {
			$response = [
				'success' => false,
				'message' => __( 'Error submitting form', 'sureforms' ),
			];
		}

		return $response;
	}

	/**
	 * Prepare submission data.
	 *
	 * @param array<mixed> $submission_data Submission data.
	 * @since 0.0.7
	 * @return array<mixed> Modified submission data.
	 */
	public function prepare_submission_data( $submission_data ) {
		$modified_message = [];
		foreach ( $submission_data as $key => $value ) {
			$parts = explode( '-lbl-', $key );
			$label = '';

			if ( ! empty( $parts[1] ) ) {
				$tokens = explode( '-', $parts[1] );
				if ( count( $tokens ) > 1 ) {
					$label = implode( '-', array_slice( $tokens, 1 ) );
				}

				$fields = explode( '-', $parts[0] );

				// Since the upload field returns an array of file URLs, we need to implode them with a comma.
				if ( 'upload' === $fields[1] && ! empty( $value ) && is_array( $value ) ) {
					$modified_message[ $label ] = urldecode( implode( ', ', $value ) );
				} else {
					$modified_message[ $label ] = html_entity_decode( esc_attr( Helper::get_string_value( $value ) ) );
				}
			}
		}

		return $modified_message;
	}

	/**
	 * Send Email.
	 *
	 * @param string        $id Form ID.
	 * @param array<mixed>  $submission_data Submission data.
	 * @param array<string> $form_data Request object or array containing form data.
	 * @since 0.0.1
	 * @return array<mixed> Array containing the response data.
	 */
	public static function send_email( $id, $submission_data, $form_data = [] ) {
		$email_notification = get_post_meta( intval( $id ), '_srfm_email_notification' );
		$smart_tags         = new Smart_Tags();
		$is_mail_sent       = false;
		$emails             = [];

		if ( is_iterable( $email_notification ) ) {
			$entries_db_instance = Entries::get_instance();
			$log_key             = $entries_db_instance->add_log( __( 'Email Notification Initiated', 'sureforms' ) );

			foreach ( $email_notification as $notification ) {
				foreach ( $notification as $item ) {
					if ( true === $item['status'] ) {
						$from           = Helper::get_string_value( get_option( 'admin_email' ) );
						$to             = $smart_tags->process_smart_tags( $item['email_to'], $submission_data );
						$subject        = $smart_tags->process_smart_tags( $item['subject'], $submission_data, $form_data );
						$email_body     = $smart_tags->process_smart_tags( $item['email_body'], $submission_data, $form_data );
						$email_template = new Email_Template();
						$message        = $email_template->render( $submission_data, $email_body );
						$headers        = "From: {$from}\r\nX-Mailer: PHP/" . phpversion() . "\r\nContent-Type: text/html; charset=utf-8\r\n";
						if ( isset( $item['email_reply_to'] ) && ! empty( $item['email_reply_to'] ) ) {
							$headers .= 'Reply-To:' . $smart_tags->process_smart_tags( $item['email_reply_to'], $submission_data ) . "\r\n";
						} else {
							$headers .= "Reply-To: {$from}\r\n";
						}
						if ( isset( $item['email_cc'] ) && ! empty( $item['email_cc'] ) ) {
							$headers .= 'Cc:' . $smart_tags->process_smart_tags( $item['email_cc'], $submission_data ) . "\r\n";
						}
						if ( isset( $item['email_bcc'] ) && ! empty( $item['email_bcc'] ) ) {
							$headers .= 'Bcc:' . $smart_tags->process_smart_tags( $item['email_bcc'], $submission_data ) . "\r\n";
						}

						/**
						 * Temporary override the content type for wp_mail.
						 * This helps us from breaking of content type from other plugins.
						 *
						 * @since 1.2.2
						 */
						add_filter(
							'wp_mail_content_type',
							static function() {
								return 'text/html'; // We need "text/html" content type to render our emails.
							},
							99
						);

						/**
						 * Start sending email.
						 * Wrapping it in the buffer because when some plugin such as zoho mail, overrides the wp_mail
						 * function and any exception is thrown ( Or printed ) from that plugin side, it affects the JSON response.
						 * So, to make sure such exceptions doesn't affect our JSON response, we are wrapping it inside buffer.
						 *
						 * Try-Catch does not work because the notice or errors might be echoed by other plugins rather than thrown as an exception.
						 *
						 * @since 1.2.2
						 */
						$sent = false;
						ob_start();
						$sent = wp_mail( $to, $subject, $message, $headers );
						if ( ! $sent ) {
							// Fallback to default PHP mail if for some reasons wp_mail fails.
							$sent = mail( $to, $subject, $message, $headers );
						}
						$email_report = ob_get_clean(); // Catch any printed notice/errors/message for reports.

						if ( is_int( $log_key ) ) {
							if ( true === $sent ) {
								$entries_db_instance->update_log(
									$log_key,
									null,
									[
										/* translators: Here, %s is the comma separated emails list. */
										sprintf( __( 'Email notification sent to %s', 'sureforms' ), esc_html( $to ) ),
									]
								);
							} else {
								$entries_db_instance->update_log(
									$log_key,
									null,
									[
										sprintf(
											/* translators: Here, %1$s is the comma separated emails list and %2$s is error report ( if any ). */
											__( 'Failed sending email notification to %1$s. Reason: %2$s', 'sureforms' ),
											esc_html( $to ),
											! empty( $email_report ) ? esc_html( $email_report ) : esc_html__( 'Unknown', 'sureforms' )
										),
									]
								);
							}
						}

						$is_mail_sent = $sent;
						$emails[]     = $to;
					}
				}
			}
		}

		return [
			'success' => $is_mail_sent,
			'emails'  => $emails,
		];
	}

	/**
	 * Retrieve all entries data for a specific form ID to check for unique values.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function field_unique_validation() {
		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), 'unique_validation_nonce' ) ) {
			$error_message = __( 'Nonce verification failed.', 'sureforms' );
			$error_data    = [
				'error' => $error_message,
			];
			wp_send_json_error( $error_data );
		}

		global $wpdb;
		$id         = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		$meta_value = $id;

		if ( ! $meta_value ) {
			$error_message = __( 'Invalid form ID.', 'sureforms' );
			$error_data    = [
				'error' => $error_message,
			];
			wp_send_json_error( $error_data );
		}

		$_POST = array_map( 'wp_unslash', $_POST );

		// Get the entry IDs for the particualr form to perform unique field validation.
		$entry_ids = Entries::get_all_entry_ids_for_form( $id );

		$all_form_entries = [];
		$keys             = array_keys( $_POST );
		$length           = count( $keys );

		for ( $i = 3; $i < $length; $i++ ) {
			$key   = $keys[ $i ];
			$value = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
			$key   = str_replace( '_', ' ', $keys[ $i ] );

			foreach ( $entry_ids as $entry_id ) {
				$entry_id  = is_array( $entry_id ) ? Helper::get_integer_value( $entry_id['ID'] ) : 0;
				$form_data = Entries::get_form_data( $entry_id );
				if ( is_array( $form_data ) && isset( $form_data[ $key ] ) && $form_data[ $key ] === $value ) {
					$obj = [ $key => 'not unique' ];
					array_push( $all_form_entries, $obj );
					break;
				}
			}
		}

		$results = [
			'data' => $all_form_entries,
		];

		wp_send_json( $results );
	}

	/**
	 * Function to save allowed block data.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function srfm_global_update_allowed_block() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		if ( ! check_ajax_referer( 'srfm_ajax_nonce', 'security', false ) ) {
			wp_send_json_error();
		}

		if ( ! empty( $_POST['defaultAllowedQuickSidebarBlocks'] ) ) {
			$srfm_default_allowed_quick_sidebar_blocks = json_decode( sanitize_text_field( wp_unslash( $_POST['defaultAllowedQuickSidebarBlocks'] ) ), true );
			Helper::update_admin_settings_option( 'srfm_quick_sidebar_allowed_blocks', $srfm_default_allowed_quick_sidebar_blocks );
			wp_send_json_success();
		}
		wp_send_json_error();
	}

	/**
	 * Function to save enable/disable data.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function srfm_global_sidebar_enabled() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		if ( ! check_ajax_referer( 'srfm_ajax_nonce', 'security', false ) ) {
			wp_send_json_error();
		}

		if ( ! empty( $_POST['enableQuickActionSidebar'] ) ) {
			$srfm_enable_quick_action_sidebar = ( 'enabled' === $_POST['enableQuickActionSidebar'] ? 'enabled' : 'disabled' );
			Helper::update_admin_settings_option( 'srfm_enable_quick_action_sidebar', $srfm_enable_quick_action_sidebar );
			wp_send_json_success();
		}
		wp_send_json_error();
	}
}
