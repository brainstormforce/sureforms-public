<?php
/**
 * Sureforms Submit Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc;

use SRFM\Inc\Compatibility\Multilingual\Multilingual_Manager;
use SRFM\Inc\Database\Tables\Entries;
use SRFM\Inc\Email\Email_Template;
use SRFM\Inc\Email_Delivery\Lost_Submissions;
use SRFM\Inc\Email_Delivery\Mailer;
use SRFM\Inc\Email_Delivery\Status as Email_Delivery_Status;
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
	 * Addresses.
	 *
	 * @var string
	 * @since 1.6.1
	 */
	private $addresses = '';

	/**
	 * Constructor
	 *
	 * @since  0.0.1
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_custom_endpoint' ] );
		// One submission getting through retires the failure notice. srfm_form_submit
		// fires only on the success path.
		add_action( 'srfm_form_submit', [ Client_Logger::class, 'reset_fault_streak' ] );

		/**
		 * Fired when an integration fails to receive a submission.
		 *
		 * Pro's webhooks and native integrations write their outcome to the entry's
		 * own log, which nobody reads until a ticket is already open. Firing this
		 * as well surfaces it on the dashboard.
		 *
		 * @since 2.12.6
		 *
		 * @param int    $form_id Form the submission belongs to.
		 * @param string $reason  Short description of what failed.
		 */
		add_action( 'srfm_integration_failed', [ $this, 'record_integration_failure' ], 10, 2 );
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
				'permission_callback' => [ $this, 'submit_form_permissions_check' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/log-client-error',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_client_error_log' ],
				'permission_callback' => [ $this, 'client_error_log_permissions_check' ],
			]
		);
	}

	/**
	 * Record an integration failure against the form it happened on.
	 *
	 * Hooked - srfm_integration_failed.
	 *
	 * @param int    $form_id Form the submission belongs to.
	 * @param string $reason  Short description of what failed.
	 * @since 2.12.6
	 * @return void
	 */
	public function record_integration_failure( $form_id = 0, $reason = '' ) {
		$form_id = absint( $form_id );

		Client_Logger::append(
			Client_Logger::sanitize_entry(
				[
					'type'       => 'message',
					'form_id'    => $form_id,
					'form_title' => $form_id ? Helper::get_string_value( get_the_title( $form_id ) ) : '',
					'message'    => 'Integration failed. ' . Helper::get_string_value( $reason ),
				]
			)
		);

		Client_Logger::record_failure(
			'integration',
			$form_id,
			$form_id ? Helper::get_string_value( get_the_title( $form_id ) ) : ''
		);
	}

	/**
	 * Gate the client error log route.
	 *
	 * Order matters. The enabled check runs first and returns 404 rather than 403,
	 * because it is the only thing that actually stops logging: the frontend flag
	 * is baked into cached HTML and can be a full cache TTL out of date, so
	 * switching the setting off does not stop already-cached pages from posting.
	 *
	 * The submit token is then required for consistency with /submit-form, but be
	 * clear about what it buys. It is per-form, not per-visitor, valid for up to
	 * 48 hours, and readable from one GET of any public page carrying the form. It
	 * filters undirected scanners and costs nothing; it is not visitor
	 * authentication. The controls that carry real weight here are the fixed
	 * payload schema in Client_Logger::sanitize_entry() and the rate limit below.
	 *
	 * @param \WP_REST_Request $request Incoming REST request.
	 * @since 2.12.6
	 * @return WP_Error|bool
	 */
	public function client_error_log_permissions_check( $request ) {
		if ( ! Client_Logger::is_enabled() ) {
			return new WP_Error(
				'srfm_rest_no_route',
				__( 'Not found.', 'sureforms' ),
				[ 'status' => 404 ]
			);
		}

		$token   = Helper::get_string_value( $request->get_header( 'X-WP-Submit-Token' ) );
		$form_id = absint( $request->get_param( 'form_id' ) );

		if ( ! Submit_Token::verify( $token, $form_id ) ) {
			return new WP_Error(
				'srfm_token_invalid',
				__( 'Security verification failed.', 'sureforms' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Record one client-reported form submission failure.
	 *
	 * Always answers 204, whether or not a line was written. The browser has
	 * nothing useful to do with a failure here, and a response that distinguishes
	 * "written" from "dropped" would report back whether logging is on, whether
	 * the log is full, and whether the caller is being throttled.
	 *
	 * @param \WP_REST_Request $request Incoming REST request.
	 * @since 2.12.6
	 * @return \WP_REST_Response
	 */
	public function handle_client_error_log( $request ) {
		$response = new \WP_REST_Response( null, 204 );

		$form_id = absint( $request->get_param( 'form_id' ) );

		if ( $this->is_rate_limited( 'srfm_cl_', $form_id ) ) {
			return $response;
		}

		$entries = $request->get_param( 'entries' );

		if ( ! is_array( $entries ) ) {
			return $response;
		}

		// Cap the batch as well as each entry: a single request must not be able to
		// consume the whole file and evict the failure someone is trying to capture.
		foreach ( array_slice( $entries, 0, 10 ) as $raw ) {
			if ( ! is_array( $raw ) ) {
				continue;
			}

			$raw['form_id'] = $form_id;

			// Resolved here rather than sent by the browser: the title is what makes
			// a log line identifiable at a glance, and taking it from the request
			// would let a caller label an entry as any form it liked.
			$raw['form_title'] = $form_id ? Helper::get_string_value( get_the_title( $form_id ) ) : '';

			Client_Logger::append( Client_Logger::sanitize_entry( $raw ) );
		}

		return $response;
	}

	/**
	 * Check whether a given request has permission to submit the form.
	 *
	 * Validates the HMAC-based submission token embedded in the page at render
	 * time. Tokens remain valid for up to 48 hours (four 12-hour windows), so
	 * they survive cached-page scenarios without any browser-side refresh call.
	 *
	 * @param \WP_REST_Request $request Incoming REST request.
	 * @since 2.6.0
	 * @return WP_Error|bool
	 */
	public function submit_form_permissions_check( $request ) {
		$token   = Helper::get_string_value( $request->get_header( 'X-WP-Submit-Token' ) );
		$form_id = absint( $request->get_param( 'form-id' ) );

		if ( ! Submit_Token::verify( $token, $form_id ) ) {
			return new WP_Error(
				'srfm_token_invalid',
				__( 'Security verification failed. Please refresh the page and try again.', 'sureforms' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Check whether a given request has permission access route.
	 *
	 * @since 0.0.1
	 * @return WP_Error|bool
	 */
	public function permissions_check() {
		if ( ! Helper::current_user_can() ) {
			return new WP_Error( 'rest_forbidden', __( 'Sorry, you do not have permission to access this resource.', 'sureforms' ), [ 'status' => rest_authorization_required_code() ] );
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
		$form_data = Helper::sanitize_by_field_type( $request->get_params() );

		if ( empty( $form_data ) || ! is_array( $form_data ) ) {
			wp_send_json_error( [ 'message' => __( 'Form data is not found.', 'sureforms' ) ] );
		}

		if ( empty( $form_data['form-id'] ) ) {
			wp_send_json_error(
				[
					'message'  => __( 'Form ID is missing.', 'sureforms' ),
					'position' => 'header',
				]
			);
		}

		$current_form_id = $form_data['form-id'];

		/**
		 * If someone tries to access the form submit endpoint directly, we need to check if the form is restricted.
		 * If a form is loaded in a browser window and the limit exceeds then the form will not be submitted.
		 */
		$form_id = Helper::get_integer_value( $current_form_id );
		if ( Form_Restriction::is_form_restricted( $form_id ) ) {
			$form_restriction = Form_Restriction::get_form_restriction_setting( $form_id );

			// Get the scheduling state and appropriate message.
			$scheduling_state         = Form_Restriction::get_form_scheduling_state( $form_restriction );
			$form_restriction_message = Form_Restriction::get_restriction_message_by_state( $scheduling_state, $form_restriction );

			$form_restriction_message = apply_filters( 'srfm_form_restriction_message', $form_restriction_message, $form_id, $form_restriction );

			wp_send_json_error(
				[
					'message' => $form_restriction_message,
				]
			);
		}

		if ( apply_filters( 'srfm_additional_restriction_check', false, $form_id, $form_data ) ) {
			wp_send_json_error(
				[
					'message' => apply_filters( 'srfm_additional_restriction_message', __( 'You do not have permission to submit this form.', 'sureforms' ), $form_id, $form_data ),
				]
			);
		}

		// Check whether the form is valid.
		if ( ! Helper::is_valid_form( $current_form_id ) ) {
			wp_send_json_error(
				[
					'code'    => 'srfm_invalid_form_id',
					'message' => __( 'This form is no longer available.', 'sureforms' ),
				]
			);
		}

		// Drop submitted keys this form does not define before anything consumes them.
		// Runs on SUBMISSION only, so historical entries whose keys no longer match a
		// rebuilt form (see #2665) stay fully readable on the read/export paths.
		$form_data = Field_Validation::strip_unknown_field_keys( $form_data, $current_form_id );

		$validated_form_data = Field_Validation::validate_form_data( $form_data, $current_form_id );

		if ( ! empty( $validated_form_data ) ) {
			// Get the first error message to display as the main message.
			$first_error = reset( $validated_form_data );

			wp_send_json_error(
				[
					'message'      => $first_error ?? __( 'Please check the form for errors.', 'sureforms' ),
					'field_errors' => $validated_form_data,
				]
			);
		}

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
			$cf_response                  = ! empty( $form_data['cf-turnstile-response'] ) && is_string( $form_data['cf-turnstile-response'] ) ? $form_data['cf-turnstile-response'] : '';

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
				$this->recaptcha_error_response( 'cf-turnstile', $turnstile_validation_result );
			}
		}

		if ( 'hcaptcha' === $security_type ) {
			$srfm_hcaptcha_secret_key = is_array( $global_setting_options ) && isset( $global_setting_options['srfm_hcaptcha_secret_key'] ) ? Helper::get_string_value( $global_setting_options['srfm_hcaptcha_secret_key'] ) : '';
			$hcaptcha_response        = ! empty( $form_data['h-captcha-response'] ) && is_string( $form_data['h-captcha-response'] ) ? $form_data['h-captcha-response'] : '';

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
				$this->recaptcha_error_response( 'hcaptcha', $hcaptcha_validation_result );
			}
		}

		if ( isset( $form_data['srfm-honeypot-field'] ) && empty( $form_data['srfm-honeypot-field'] ) ) {
			if ( ! empty( $google_captcha_secret_key ) ) {
				if ( ! empty( $form_data['form-id'] ) ) {
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
					wp_send_json_error(
						[
							'message' => __( 'Security verification failed. Please refresh the page and try again.', 'sureforms' ),
						]
					);
				}
				if ( isset( $sureforms_captcha_data['success'] ) && true === $sureforms_captcha_data['success'] ) {
					return rest_ensure_response( $this->handle_form_entry( $form_data ) );
				}

				$this->recaptcha_error_response( 'g-recaptcha', $sureforms_captcha_data );
			}

			return rest_ensure_response( $this->handle_form_entry( $form_data ) );
		}

		if ( ! isset( $form_data['srfm-honeypot-field'] ) ) {
			// If honeypot is enabled globally, the missing field means a bot stripped it.
			$srfm_security_options = get_option( 'srfm_security_settings_options' );
			if ( is_array( $srfm_security_options ) && ! empty( $srfm_security_options['srfm_honeypot'] ) ) {
				wp_send_json_error(
					[
						'message' => __( 'Your submission was flagged as spam. Please try again.', 'sureforms' ),
					]
				);
			}

			if ( ! empty( $google_captcha_secret_key ) ) {
				if ( ! empty( $form_data['form-id'] ) ) {
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
					wp_send_json_error(
						[
							'message' => __( 'Security verification failed. Please refresh the page and try again.', 'sureforms' ),
						]
					);
				}
				if ( true === $sureforms_captcha_data['success'] ) {
					return rest_ensure_response( $this->handle_form_entry( $form_data ) );
				}

				$this->recaptcha_error_response( 'g-recaptcha', $sureforms_captcha_data );
			}

			return rest_ensure_response( $this->handle_form_entry( $form_data ) );
		}

		wp_send_json_error(
			[
				'message' => __( 'Your submission was flagged as spam. Please try again.', 'sureforms' ),
			]
		);
	}

	/**
	 * Send Email and Create Entry.
	 *
	 * @param array<string> $form_data Request object or array containing form data.
	 * @since 0.0.1
	 * @return array<mixed> Array containing the response data.
	 */
	public function handle_form_entry( $form_data ) {
		// Filter the form data.
		$form_data = apply_filters( 'srfm_form_submit_data', $form_data );
		if ( empty( $form_data ) || ! is_array( $form_data ) ) {
			wp_send_json_error(
				[
					'message'  => __( 'Form data was not found.', 'sureforms' ),
					'position' => 'header',
				]
			);
		} elseif ( isset( $form_data['error'] ) ) {
			wp_send_json_error(
				[
					'message'  => is_string( $form_data['error'] ) ? $form_data['error'] : __( 'Form data is not found.', 'sureforms' ),
					'position' => 'header',
				]
			);
		}

		$id = sanitize_text_field( $form_data['form-id'] );

		// Get the compliance settings.
		$compliance           = get_post_meta( Helper::get_integer_value( $id ), '_srfm_compliance', true );
		$gdpr                 = '';
		$do_not_store_entries = '';

		if ( is_array( $compliance ) && is_array( $compliance[0] ) ) {
			$gdpr                 = $compliance[0]['gdpr'] ?? '';
			$do_not_store_entries = $compliance[0]['do_not_store_entries'] ?? '';
		}

		// Check if the form data contains 'srfm_addresses' and is not empty.
		if ( ! empty( $form_data['srfm_addresses'] ) ) {
			// Assign the addresses to the class property for further processing.
			$this->addresses = $form_data['srfm_addresses'];
			// Remove the address data from the form data to avoid redundancy.
			unset( $form_data['srfm_addresses'] );
		}

		// A browser posts nothing for an unticked checkbox, so those keys never arrive
		// and the field is missing from the entry and from notification emails while
		// every other empty field is stored as ''. Restore them from the form's own
		// blocks before anything reads the submission. Runs after validation, so no
		// validation outcome depends on it, and it never replaces a submitted value.
		$form_data = Field_Validation::backfill_unsubmitted_fields( $form_data, $id );

		$form_data = apply_filters( 'srfm_before_fields_processing', $form_data );

		$submission_data = $this->process_form_fields( $form_data );

		$modified_message = $this->prepare_submission_data( $submission_data );

		$form_before_submission_data = [
			'form_id' => $id ? intval( $id ) : '',
			'data'    => $modified_message,
		];

		/**
		 * Fires before submission process starts.
		 */
		do_action( 'srfm_before_submission', $form_before_submission_data );

		$name   = sanitize_text_field( get_the_title( intval( $id ) ) );
		$emails = [];

		// Check if GDPR is enabled and do not store entries is enabled.
		// If so, send email and do not store entries.
		if ( $gdpr && $do_not_store_entries ) {
			/** This action is documented below, where the stored-entry path fires it. */
			do_action( 'srfm_submission_accepted', 0, Helper::get_integer_value( $id ), $form_data );

			// Send email before early return. No entry is created in this path so {entry_id} will be empty — that is expected.
			// The email is the only copy of this submission, so a failure is recorded as such.
			$send_email = $this->send_email( $id, $submission_data, $form_data, [ 'entry_saved' => false ] );
			if ( $send_email ) {
				$emails = $send_email['emails'];
			}

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

		// If GDPR is enabled, do not store IP, browser, device, and submission URL.
		// If not, store all of them.
		$user_ip        = '';
		$browser_name   = '';
		$device_name    = '';
		$submission_url = '';
		if ( ! $gdpr ) {
			$srfm_ip_log = is_array( $global_setting_options ) && isset( $global_setting_options['srfm_ip_log'] ) ? $global_setting_options['srfm_ip_log'] : '';

			$user_ip      = $srfm_ip_log && isset( $_SERVER['REMOTE_ADDR'] ) ? filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP ) : '';
			$browser      = new Browser();
			$browser_name = sanitize_text_field( $browser->getBrowser() );
			$device_name  = sanitize_text_field( $browser->getPlatform() );

			// Capture submission page URL server-side from the Referer header.
			// esc_url_raw() (not sanitize_text_field) preserves percent-encoded
			// non-ASCII slugs; normalize_submission_url() then validates same-origin.
			$referer        = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
			$submission_url = $this->normalize_submission_url( $referer );
		}

		$form_markup = get_the_content( null, false, Helper::get_integer_value( $form_data['form-id'] ) );
		$pattern     = '/"label":"(.*?)"/';
		preg_match_all( $pattern, $form_markup, $matches );
		$submission_info = [
			'user_ip'        => $user_ip,
			'browser_name'   => $browser_name,
			'device_name'    => $device_name,
			'submission_url' => $submission_url,
		];
		// Resolve the language the visitor saw at form-render time (captured in a
		// hidden srfm-form-language input) so the confirmation message and email
		// notifications below can be rendered in it — WPML's language detection on
		// the REST submit endpoint frequently falls back to the default. This value
		// is used only to switch_language() at submit time; it is not persisted. The
		// hidden input is client-supplied, so:
		// 1. Validate shape with a BCP-47 regex.
		// 2. Cross-check against the active multilingual provider's known
		// languages (active + default) so a crafted request can't switch rendering
		// to a code the site doesn't support.
		// 3. Fall back to the provider's current_language() on either failure.
		$entry_language     = Multilingual_Manager::get_instance()->provider()->current_language();
		$submitted_language = isset( $form_data['srfm-form-language'] ) ? sanitize_text_field( Helper::get_string_value( $form_data['srfm-form-language'] ) ) : '';
		if ( '' !== $submitted_language && preg_match( '/^[a-z]{2,3}([_-][A-Za-z0-9]{2,8})?$/', $submitted_language ) === 1 && $this->is_known_language( $submitted_language ) ) {
			$entry_language = $submitted_language;
		}

		$entries_data = [
			'form_id'         => $id,
			'form_data'       => $submission_data,
			'submission_info' => $submission_info,
			'created_at'      => current_time( 'mysql' ),
		];
		// Resolved via Helper rather than get_current_user_id() directly: this runs on
		// a REST request that carries no nonce, which core de-authenticates before
		// dispatch, so the plain call returns 0 even for a signed-in submitter and the
		// entry would lose its attribution. Returns 0 when genuinely anonymous.
		$submitting_user_id = Helper::get_submitting_user_id();
		if ( $submitting_user_id ) {
			$entries_data['user_id'] = $submitting_user_id;
		}

		$entries_data = apply_filters(
			'srfm_before_entry_data',
			$entries_data,
			[
				'form_data'       => $form_data,
				'submission_data' => $submission_data,
			]
		);

		$entry_id = Entries::add( $entries_data );
		if ( $entry_id ) {
			// Inject entry_id so {entry_id} smart tag resolves in confirmation message, redirect URL, email notifications, and downstream integrations.
			$form_data['entry_id'] = intval( $entry_id );

			/**
			 * Fires once a submission has been accepted, before notification email is sent.
			 *
			 * Runs as early as the accepted state is known, so listeners that must react to
			 * "this form was completed" (for example, stopping follow-up work queued against
			 * an unfinished copy of it) are not held behind a slow mail transport. The
			 * `srfm_form_submit` action still fires after mail, unchanged.
			 *
			 * Also fires when the form's compliance settings accept a submission without
			 * storing it; `$entry_id` is 0 on that path.
			 *
			 * @param int          $entry_id  Stored entry ID, or 0 when the entry is not stored.
			 * @param int          $form_id   Form ID.
			 * @param array<mixed> $form_data Parsed request data for the submission (JSON or
			 *                                multipart), including any non-field keys a client
			 *                                sent alongside the fields.
			 * @since 2.12.9
			 */
			do_action( 'srfm_submission_accepted', intval( $entry_id ), Helper::get_integer_value( $id ), $form_data );

			// Switch the multilingual provider to the entry's language so the
			// confirmation message, redirect URL, and email notifications render
			// in the language the visitor saw at submit time. The REST submit
			// endpoint doesn't carry the ?lang= URL parameter, so without this
			// switch the provider would return strings in its default language
			// even though the visitor filled the form in another language.
			$provider = Multilingual_Manager::get_instance()->provider();
			if ( $provider->is_active() && '' !== $entry_language ) {
				$provider->switch_language( $entry_language );
			}

			// Entries::add() has stored the logs collected so far. Start the instance
			// empty so the update below writes only what send_email() records --
			// Entries::update() merges with the stored logs, so anything left here
			// would be written twice.
			$entries_db_instance = Entries::get_instance();
			$entries_db_instance->reset_logs();

			// Send email after entry creation so {entry_id} is available when smart tags are processed.
			$send_email = $this->send_email( $id, $submission_data, $form_data, [ 'entry_saved' => true ] );
			if ( $send_email ) {
				$emails = $send_email['emails'];
			}

			// send_email() logs to the in-memory instance; the entry already exists,
			// so the log only reaches it through an update.
			$notification_logs = $entries_db_instance->get_logs();
			if ( ! empty( $notification_logs ) ) {
				Entries::update( Helper::get_integer_value( $entry_id ), [ 'logs' => $notification_logs ] );
			}

			$confirmation_message = Generate_Form_Markup::get_confirmation_markup( $form_data, $submission_data );
			$redirect_url         = Generate_Form_Markup::get_redirect_url( $form_data, $submission_data );

			if ( $provider->is_active() && '' !== $entry_language ) {
				$provider->restore_language();
			}

			$after_submit_nonce = wp_create_nonce( 'srfm_after_submission_' . Helper::get_string_value( $entry_id ) );

			$response = [
				'success'      => true,
				'message'      => $confirmation_message,
				'data'         => [
					'name'               => $name,
					'submission_id'      => $entry_id,
					'after_submit'       => true,
					'after_submit_nonce' => $after_submit_nonce,
					// Built here rather than assembled in JS. rest_url() already knows
					// whether the route is a path or a `?rest_route=` query arg, and
					// add_query_arg() knows whether the nonce needs `?` or `&` — the
					// client has no way to get either right without reimplementing
					// both, and concatenating produced a URL that did not route at all
					// on plain-permalink sites.
					'after_submit_url'   => add_query_arg(
						'after_submit_nonce',
						$after_submit_nonce,
						rest_url( 'sureforms/v1/after-submission/' . Helper::get_integer_value( $entry_id ) )
					),
				],
				'redirect_url' => $redirect_url,
			];

			$form_submit_response = apply_filters(
				'srfm_form_submit_response',
				[
					'success'   => true,
					'form_id'   => $id ? intval( $id ) : '',
					'entry_id'  => intval( $entry_id ),
					'to_emails' => $emails,
					'form_name' => $name ? esc_attr( $name ) : '',
					'message'   => $confirmation_message,
					'data'      => $modified_message,
				]
			);

			do_action( 'srfm_form_submit', $form_submit_response );
		} else {
			$response = [
				'success' => false,
				'message' => __( 'Unable to submit form. Please try again.', 'sureforms' ),
			];
		}

		/**
		 * Filter the form submission response.
		 *
		 * @param array<mixed> $response The response data.
		 * @param array<string> $form_data The original form data.
		 * @param array<mixed> $submission_data The processed submission data.
		 * @since 2.4.0
		 */
		return apply_filters( 'srfm_form_submission_response', $response, $form_data, $submission_data );
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

			/**
			 * Filters submission data for field processing.
			 *
			 * This filter allows customization of how individual fields are processed
			 * during submission data preparation. Plugins can modify field values,
			 * labels, or exclude specific fields from the final submission data.
			 *
			 * @since 1.11.0
			 *
			 * @param array $field_data {
			 *     Field data for processing.
			 *
			 *     @type array  $block_parts  The field key split by '-lbl-' delimiter.
			 *     @type string $field_key    The original field key from submission data.
			 *     @type mixed  $field_value  The field value from submission data.
			 * }
			 */
			$should_add_field_row = apply_filters(
				'srfm_prepare_submission_data',
				[
					'block_parts' => $parts,
					'field_key'   => $key,
					'field_value' => $value,
				]
			);

			// If we get the label and value from the filter, then use it.
			if ( ! empty( $should_add_field_row['label'] ) && ! empty( $should_add_field_row['value'] ) ) {
				$modified_message[ $should_add_field_row['label'] ] = $should_add_field_row['value'];
				continue;
			}

			if ( ! empty( $parts[1] ) ) {
				$tokens = explode( '-', $parts[1] );
				if ( count( $tokens ) > 1 ) {
					$label = implode( '-', array_slice( $tokens, 1 ) );
				}

				$fields = explode( '-', $parts[0] );

				// Since the upload field returns an array of file URLs, we need to implode them with a comma.
				if ( 'upload' === $fields[1] && ! empty( $value ) && is_array( $value ) ) {
					$modified_message[ $label ] = implode( ', ', array_map( 'rawurldecode', $value ) );
				} else {
					$modified_message[ $label ] = html_entity_decode( esc_attr( Helper::get_string_value( $value ) ) );
				}
			}
		}

		// If the address is not empty, add it to the submission data.
		// We are providing this for third-party integrations like Ottokit.
		// They can use compact addresses such as permanent address, temporary address, etc.
		// The address will be structured as field 1, field 2, and so on.
		if ( ! empty( $this->addresses ) ) {
			// Address will be JSON stringified, so decode it.
			$address = json_decode( wp_unslash( $this->addresses ), true );
			if ( ! empty( $address ) && is_array( $address ) ) {
				$modified_message = array_merge( $modified_message, $address );
			}
		}

		return apply_filters( 'srfm_update_prepared_submission_data', $modified_message );
	}

	/**
	 * Parse an email notification template and generate the necessary components for sending an email.
	 *
	 * @param array<mixed>         $submission_data An associative array containing submission data to be used in the email template.
	 * @param array<string,string> $item An associative array containing email settings, such as 'email_to', 'subject', 'email_body', and optional headers like 'email_reply_to', 'email_cc', and 'email_bcc'.
	 * @param array<string>        $form_data Request object or array containing form data.
	 * @since 1.3.0
	 * @return array<string,string> An associative array containing 'to', 'subject', 'message', and 'headers' for the email.
	 */
	public static function parse_email_notification_template( $submission_data, $item, $form_data = [] ) {
		$smart_tags = Smart_Tags::get_instance();

		$to            = Helper::get_string_value( $smart_tags->process_smart_tags( $item['email_to'], $submission_data ) );
		$subject       = Helper::get_string_value( $smart_tags->process_smart_tags( $item['subject'], $submission_data, $form_data ) );
		$email_body    = Helper::get_string_value( $smart_tags->process_smart_tags( $item['email_body'], $submission_data, $form_data ) );
		$is_raw_format = isset( $item['is_raw_format'] ) && true === $item['is_raw_format'];

		/**
		 * Sanitize the email body after smart tag substitution to prevent XSS.
		 *
		 * After process_smart_tags() resolves {form:slug} placeholders, the body may contain
		 * raw user-submitted values that must not render as executable HTML in email clients.
		 * wp_kses_post() strips dangerous markup (script, on* handlers, javascript: URIs)
		 * while preserving all legitimate email formatting (tables, links, bold, etc.).
		 *
		 * Note: {all_data} is not a recognised smart tag and remains a literal placeholder
		 * at this point; it is substituted later by process_all_data_tag() which applies
		 * its own per-field escaping, so this call does not interfere with that path.
		 *
		 * @since 2.5.2
		 */
		$email_body = wp_kses_post( $email_body );

		$email_template = new Email_Template();
		$message        = $is_raw_format
			? $email_template->render_raw( $submission_data, $email_body )
			: $email_template->render( $submission_data, $email_body );
		$headers        = 'X-Mailer: PHP/' . phpversion() . "\r\n";
		$headers       .= "Content-Type: text/html; charset=utf-8\r\n";

		// Add the From: to the headers.
		$headers .= self::add_from_data_in_header( $submission_data, $item, $smart_tags );

		// Handle Reply-To with proper sanitization.
		if ( isset( $item['email_reply_to'] ) && ! empty( $item['email_reply_to'] ) ) {
			$headers .= 'Reply-To: ' . Helper::sanitize_email_header( Helper::get_string_value( $smart_tags->process_smart_tags( $item['email_reply_to'], $submission_data ) ) ) . "\r\n";
		}

		// Handle CC with proper sanitization.
		if ( isset( $item['email_cc'] ) && ! empty( $item['email_cc'] ) ) {
			$headers .= 'Cc: ' . Helper::sanitize_email_header( Helper::get_string_value( $smart_tags->process_smart_tags( $item['email_cc'], $submission_data ) ) ) . "\r\n";
		}

		// Handle BCC with proper sanitization.
		if ( isset( $item['email_bcc'] ) && ! empty( $item['email_bcc'] ) ) {
			$headers .= 'Bcc: ' . Helper::sanitize_email_header( Helper::get_string_value( $smart_tags->process_smart_tags( $item['email_bcc'], $submission_data ) ) ) . "\r\n";
		}

		return compact( 'to', 'subject', 'message', 'headers' );
	}

	/**
	 * Send Email.
	 *
	 * @param string        $id Form ID.
	 * @param array<mixed>  $submission_data Submission data.
	 * @param array<string> $form_data Request object or array containing form data.
	 * @param array<mixed>  $context   Optional. 'entry_saved' => false when the form does not store entries,
	 *                                 so a failed notification is the loss of the submission itself.
	 * @since 0.0.1
	 * @since 2.12.9 Added $context.
	 * @return array<mixed> Array containing the response data.
	 */
	public static function send_email( $id, $submission_data, $form_data = [], $context = [] ) {
		// Set by the caller, which knows whether the entry was stored -- never
		// read from the request ($form_data is the visitor's POST on the
		// no-store path, so an entry_id there proves nothing).
		$entry_saved        = ! is_array( $context ) || ! isset( $context['entry_saved'] ) || (bool) $context['entry_saved'];
		$email_notification = get_post_meta( intval( $id ), '_srfm_email_notification' );
		$is_mail_sent       = false;
		// Whether any notification was accepted: on a form that stores no entries,
		// none at all means the submission is gone.
		$any_accepted = false;
		// Any recipient failing counts as a failure for the whole submission, so
		// these are set inside the loop and only read after it.
		$notification_failed = false;
		// Whether a failure was the site's, not only a visitor's own address
		// being refused. Only then is a never-stored submission lost: the owner
		// would not have received a copy sent to the visitor anyway.
		$site_failed = false;
		// Whether any recipient's "success" came from the opt-in mail() fallback,
		// which reports true for a message the local MTA accepted and may bounce.
		$used_mail_fallback = false;
		$emails             = [];

		// Filter to determine whether the email notification should be sent.
		$email_notification = apply_filters( 'srfm_email_notification_should_send', $email_notification, $submission_data, $form_data );

		if ( is_iterable( $email_notification ) ) {
			$entries_db_instance = Entries::get_instance();
			$log_key             = $entries_db_instance->add_log( __( 'Email notification passed to the sending server', 'sureforms' ) );

			foreach ( $email_notification as $notification ) {
				foreach ( $notification as $item ) {
					if ( true === $item['status'] ) {

						$parsed = self::parse_email_notification_template( $submission_data, $item, $form_data );

						// Allow filtering of the email data before it is sent.
						$parsed = apply_filters( 'srfm_email_notification', $parsed, $submission_data, $item, $form_data );

						// Trigger an action before sending the email, allowing additional processing or logging.
						do_action( 'srfm_before_email_send', $parsed, $submission_data, $item, $form_data );

						$notification_id = isset( $item['id'] ) ? intval( $item['id'] ) : 0;

						/**
						 * Filter to determine whether the email should be sent.
						 *
						 * @since 1.10.1
						 */
						$should_send_email = apply_filters(
							'srfm_should_send_email',
							true,
							$notification_id,
							$id,
							$form_data,
						);

						if ( ! wp_validate_boolean( $should_send_email ) ) {
								continue;
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
						$dispatch     = self::dispatch_notification( $parsed, intval( $id ) );
						$sent         = $dispatch['sent'];
						$email_report = $dispatch['error'];

						if ( $dispatch['via_php_mail'] ) {
							// Accepted by the local MTA, not delivered. Good enough
							// to avoid recording a fault, not good enough to retire one.
							$used_mail_fallback = true;
						}

						if ( true !== $sent ) {
							$notification_failed = true;
							$rejection           = Mailer::get_rejection_reason();

							// Outside the logging guard below: this is the site's
							// current email state, which the owner needs to see
							// even with debug logging switched off. A rejected
							// recipient address (often what a visitor typed) is
							// logged on the entry but says nothing about the site.
							$recipients = self::get_visitor_recipients( $item, $submission_data );
							if ( ! Mailer::is_visitor_recipient_failure( $email_report, $recipients['addresses'], $recipients['only_visitor'] ) ) {
								$site_failed = true;
								Email_Delivery_Status::record_outcome( false, intval( $id ), '' !== $email_report ? 'mailer_error' : $rejection['code'], $entry_saved );
							}
						}

						if ( is_int( $log_key ) ) {
							if ( true === $sent ) {
								$entries_db_instance->update_log(
									$log_key,
									null,
									[
										/* translators: Here, %s is the comma separated emails list. */
										sprintf( __( 'Email notification recipient: %s', 'sureforms' ), esc_html( $parsed['to'] ) ),
									]
								);
							} else {
								$reason = '' !== $email_report
									? esc_html( $email_report )
									: esc_html( Mailer::get_rejection_reason()['message'] );

								$entries_db_instance->update_log(
									$log_key,
									null,
									[
										sprintf(
										/* translators: Here, %1$s is the comma separated emails list and %2$s is error report ( if any ). */
											__(
												'Email server was unable to send the email notification. Recipient: %1$s. Reason: %2$s',
												'sureforms'
											),
											esc_html( $parsed['to'] ),
											$reason
										),
									]
								);

								// Also record it in the debug log. The submission itself
								// succeeded, so the visitor saw nothing wrong and nobody
								// looks at the entry's own log until a ticket is already
								// open. The recipient address is not included -- the log
								// is downloadable and must not carry personal data.
								Client_Logger::append(
									Client_Logger::sanitize_entry(
										[
											'type'       => 'message',
											'form_id'    => intval( $id ),
											'form_title' => Helper::get_string_value( get_the_title( intval( $id ) ) ),
											'message'    => 'Email notification failed to send. ' . $reason . ( $entry_saved ? '' : ' The form does not store entries, so this submission was not saved.' ),
										]
									)
								);

								// Its own category: the submission reached PHP, so this is
								// not a submission failure. The site owner is not being
								// told about entries they received -- or, on a form that
								// does not store entries, has lost this one outright,
								// which the notice has to say.
								Client_Logger::record_failure(
									'notification',
									intval( $id ),
									Helper::get_string_value( get_the_title( intval( $id ) ) ),
									[ 'entry_saved' => $entry_saved ]
								);
							}
						}

						// Trigger an action after the email is sent, allowing additional processing or logging.
						do_action(
							'srfm_after_email_send',
							$parsed,
							$submission_data,
							$item,
							$form_data
						);

						$is_mail_sent = $sent;
						$any_accepted = $any_accepted || true === $sent;
						$emails[]     = $parsed['to'];
					}
				}
			}

			if ( empty( $emails ) ) {
				$entries_db_instance->reset_logs();
				$entries_db_instance->add_log( __( 'No emails were sent.', 'sureforms' ) );
			}

			// The notification fault clears when notifications work again. Nothing
			// else retired it: Client_Logger::clear_category() had a single caller
			// hardcoded to 'submission', and the notice is deliberately not
			// dismissible, so a site that had fixed its SMTP kept an undismissable
			// banner on every admin page until somebody opened a support ticket.
			// Held until the loop is done because one recipient succeeding while
			// another fails is still a failure.
			//
			// Scoped to the form the fault was recorded against. send_email() runs
			// on the public submit path and the counter is per category, not per
			// form, so without this an anonymous submission of a working form
			// wipes a different form's standing fault -- once per admin page load,
			// by anyone. The notice names a form, so the granularity is visible
			// now that this clears as well as records.
			//
			// wp_mail() only. The opt-in mail() fallback returns true when the local
			// MTA merely accepts a message it will later bounce, which is the
			// broken configuration rather than the fixed one.
			//
			// is_int( $log_key ) mirrors the recording guard: record_failure() sits
			// inside it, so without it an install where add_log() returns a
			// non-int would never record a notification fault but would still
			// clear one.
			if ( ! empty( $emails ) && ! $notification_failed && ! $used_mail_fallback ) {
				Email_Delivery_Status::record_outcome( true, intval( $id ) );
			}

			// Kept apart from the notification fault, which the next success
			// clears, and recorded with logging off: until the owner has seen it,
			// a lost submission must not disappear.
			if ( ! $entry_saved && $site_failed && ! $any_accepted ) {
				Lost_Submissions::record( intval( $id ) );
			}

			$open_failures = Client_Logger::get_failures();

			if ( ! empty( $emails ) && ! $notification_failed && is_int( $log_key )
				&& ! $used_mail_fallback
				&& intval( $id ) === Helper::get_integer_value( $open_failures['notification']['form_id'] ?? 0 ) ) {
				Client_Logger::clear_category( 'notification' );
			}
		}

		return [
			'success' => $is_mail_sent,
			'emails'  => $emails,
		];
	}

	/**
	 * Validate unique field values for a specific form via AJAX.
	 *
	 * Checks submitted field values against existing entries to determine
	 * if duplicates exist. Rate-limited to prevent data enumeration.
	 *
	 * @since 0.0.1
	 * @since 2.7.0 Added rate limiting, form validation, and optimized query.
	 * @return void
	 */
	public function field_unique_validation() {
		$token   = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- HMAC token verification replaces nonce.
		$form_id = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( ! Submit_Token::verify( $token, $form_id ) ) {
			wp_send_json_error( [ 'error' => __( 'Security verification failed. Please refresh the page and try again.', 'sureforms' ) ] );
		}

		if ( ! $form_id ) {
			wp_send_json_error( [ 'error' => __( 'Invalid form ID.', 'sureforms' ) ] );
		}

		// Validate the form exists and is published to prevent cross-form probing.
		if ( 'publish' !== get_post_status( $form_id ) || 'sureforms_form' !== get_post_type( $form_id ) ) {
			wp_send_json_error( [ 'error' => __( 'Invalid form.', 'sureforms' ) ] );
		}

		// Rate limit: 10 requests per minute per IP per form.
		if ( $this->is_unique_validation_rate_limited( $form_id ) ) {
			wp_send_json_error( [ 'error' => __( 'Too many requests. Please try again shortly.', 'sureforms' ) ], 429 );
		}

		// SECURITY INVARIANT — only the fields the form itself marks unique may be
		// probed through this unauthenticated handler. The allowlist is what keeps the
		// lookup scoped to values a site owner opted into checking, rather than to
		// stored submission data generally. A form with no unique fields therefore
		// matches nothing and always answers with an empty set.
		$unique_block_ids = $this->get_unique_field_block_ids( $form_id );

		// Extract and validate field values from POST data.
		$skip_keys  = [ 'action', 'token', 'id' ];
		$duplicates = [];

		foreach ( $_POST as $raw_key => $raw_value ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- HMAC token verified above.
			if ( in_array( $raw_key, $skip_keys, true ) ) {
				continue;
			}

			$field_key = str_replace( '_', ' ', sanitize_text_field( $raw_key ) );
			$value     = sanitize_text_field( wp_unslash( $raw_value ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- HMAC token verified above.

			// Only process SureForms field keys (they contain -lbl- in the name).
			if ( false === strpos( $field_key, '-lbl-' ) ) {
				continue;
			}

			if ( '' === $value ) {
				continue;
			}

			// The key must resolve to a block this form configured as unique.
			$block_id = Helper::get_block_id_from_key( $field_key );

			if ( '' === $block_id || ! isset( $unique_block_ids[ $block_id ] ) ) {
				continue;
			}

			// Single optimized query per field instead of loading all entries.
			if ( Entries::has_duplicate_field_value( $form_id, $field_key, $value ) ) {
				$duplicates[] = [ $field_key => 'not unique' ];
			}
		}

		wp_send_json( [ 'data' => $duplicates ] );
	}

	/**
	 * Function to save allowed block data.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function srfm_global_update_allowed_block() {
		if ( ! Helper::current_user_can() ) {
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
		if ( ! Helper::current_user_can() ) {
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

	/**
	 * Send error response for reCAPTCHA validation failure.
	 *
	 * @param string       $type         The type of CAPTCHA used. Accepted values: 'g-recaptcha', 'hcaptcha', 'cf-turnstile'.
	 * @param array<mixed> $api_response The response returned from the CAPTCHA validation API.
	 * @since 1.7.0
	 * @return void
	 */
	public function recaptcha_error_response( $type, $api_response ) {
		$error_message = $this->recaptcha_error_message( $type, $api_response );
		$response      = array_merge(
			[
				'api_response' => $api_response,
			],
			$error_message
		);

		wp_send_json_error( $response );
	}

	/**
	 * Get the error message for a CAPTCHA validation failure based on the service type and API response.
	 *
	 * @param string       $type         The type of CAPTCHA used. Accepted values: 'g-recaptcha', 'hcaptcha', 'cf-turnstile'.
	 * @param array<mixed> $api_response The response returned from the CAPTCHA validation API.
	 * @since 1.7.0
	 * @return array<string,string> An associative array containing the error message and a detailed message.
	 */
	public function recaptcha_error_message( $type, $api_response ) {

		if ( empty( $api_response['error-codes'] ) || ! is_array( $api_response['error-codes'] ) ) {
			return [
				'detail_message' => __( 'Captcha validation failed. No error code provided.', 'sureforms' ),
				'message'        => __( 'Captcha validation failed.', 'sureforms' ),
			];
		}

		/**
		 * Note: The error codes are not translated because these messages are intended for debugging purposes.
		 * Translating them would make debugging difficult. These error messages are primarily for developers or administrators.
		 * A generic message will be displayed to the user, while detailed error information will be logged or shown in the console.
		 */

		// Google reCAPTCHA error codes.
		// Reference: (https://developers.google.com/recaptcha/docs/verify#error-code-reference).
		$google_recaptcha_error = [
			'missing-input-secret'   => 'The secret parameter is missing.',
			'invalid-input-secret'   => 'The secret parameter is invalid or malformed.',
			'missing-input-response' => 'The response parameter is missing.',
			'invalid-input-response' => 'The response parameter is invalid or malformed.',
			'bad-request'            => 'The request is invalid or malformed.',
			'timeout-or-duplicate'   => 'The response is no longer valid: either is too old or has been used previously.',
		];

		// hCaptcha error codes.
		// Reference: (https://docs.hcaptcha.com/#siteverify-error-codes).
		$hcaptcha_errors = [
			'missing-input-secret'     => 'Your secret key is missing.',
			'invalid-input-secret'     => 'Your secret key is invalid or malformed.',
			'missing-input-response'   => 'The response parameter (verification token) is missing.',
			'invalid-input-response'   => 'The response parameter (verification token) is invalid or malformed.',
			'expired-input-response'   => 'The response parameter (verification token) is expired. (120s default)',
			'already-seen-response'    => 'The response parameter (verification token) was already verified once.',
			'bad-request'              => 'The request is invalid or malformed.',
			'missing-remoteip'         => 'The remoteip parameter is missing.',
			'invalid-remoteip'         => 'The remoteip parameter is not a valid IP address or blinded value.',
			'not-using-dummy-passcode' => 'You have used a testing sitekey but have not used its matching secret.',
			'sitekey-secret-mismatch'  => 'The sitekey is not registered with the provided secret.',
		];

		// Cloudflare Turnstile error codes.
		// Reference: (https://developers.cloudflare.com/turnstile/get-started/server-side-validation/).
		$cf_turnstile_errors = [
			'missing-input-secret'   => 'The secret parameter was not passed.',
			'invalid-input-secret'   => 'The secret parameter was invalid, did not exist, or is a testing secret key with a non-testing response.',
			'missing-input-response' => 'The response parameter (token) was not passed.',
			'invalid-input-response' => 'The response parameter (token) is invalid or has expired. Most of the time, this means a fake token has been used. If the error persists, contact customer support.',
			'bad-request'            => 'The request was rejected because it was malformed.',
			'timeout-or-duplicate'   => 'The response parameter (token) has already been validated before. This means that the token was issued five minutes ago and is no longer valid, or it was already redeemed.',
			'internal-error'         => 'An internal error happened while validating the response. The request can be retried.',
		];

		$error_code = $api_response['error-codes'][0] ?? 'no-error-code';

		$captcha_title   = '';
		$captcha_message = '';
		switch ( $type ) {
			case 'g-recaptcha':
				$captcha_title   = __( 'Google reCAPTCHA', 'sureforms' );
				$captcha_message = $google_recaptcha_error[ $error_code ];
				break;
			case 'hcaptcha':
				$captcha_title   = __( 'hCaptcha', 'sureforms' );
				$captcha_message = $hcaptcha_errors[ $error_code ];
				break;
			case 'cf-turnstile':
				$captcha_title   = __( 'Cloudflare Turnstile', 'sureforms' );
				$captcha_message = $cf_turnstile_errors[ $error_code ];
				break;
			default:
				$captcha_title   = __( 'Unknown Captcha', 'sureforms' );
				$captcha_message = __( 'Invalid captcha type.', 'sureforms' );
				break;
		}

		$detail_message = sprintf(
			'%s: %s <br> Error Code: %s',
			$captcha_title,
			$captcha_message ?? 'Unknown error occurred.',
			$error_code
		);

		$message = sprintf(
			/* translators: %s is the captcha title. */
			__( '%s verification failed. Please contact your site administrator.', 'sureforms' ),
			$captcha_title
		);

		return [
			'log_message' => $detail_message, // This variable is used for logging purposes, such as displaying detailed error information in the console on the front end.
			'message'     => $message,
		];
	}

	/**
	 * Sanitise and validate a Referer into a storable submission URL.
	 *
	 * The value is rebuilt from parsed components so a non-browser client cannot
	 * inject bits a real browser would never send (userinfo, fragment) or mismatch
	 * the legitimate origin's port. Anything that is not a same-origin http(s) URL,
	 * or is longer than 2048 chars, is rejected and returns an empty string.
	 *
	 * Uses esc_url_raw() rather than sanitize_text_field(): the latter strips
	 * percent-encoded octets (`%E0%A4...`), which mangles the URLs of translated
	 * pages whose slugs contain non-ASCII characters (e.g. WPML Hindi/Arabic
	 * permalinks) down to bare hyphens. esc_url_raw() preserves the percent-encoding
	 * so the recorded submission URL stays accurate.
	 *
	 * @param string $referer Raw (unslashed) Referer header value.
	 * @since 2.11.0
	 * @return string Same-origin http(s) URL, or empty string when invalid.
	 */
	protected function normalize_submission_url( string $referer ): string {
		$referer = esc_url_raw( $referer );

		if ( '' === $referer || strlen( $referer ) > 2048 ) {
			return '';
		}

		$parts      = wp_parse_url( $referer );
		$home_parts = wp_parse_url( home_url() );

		if (
			! is_array( $parts )
			|| ! is_array( $home_parts )
			|| ! isset( $parts['scheme'], $parts['host'], $home_parts['host'] )
			|| ! in_array( strtolower( $parts['scheme'] ), [ 'http', 'https' ], true )
			|| 0 !== strcasecmp( (string) $parts['host'], (string) $home_parts['host'] )
			|| ( $parts['port'] ?? null ) !== ( $home_parts['port'] ?? null )
		) {
			return '';
		}

		$clean = $parts['scheme'] . '://' . $parts['host']
			. ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' )
			. ( $parts['path'] ?? '' )
			. ( isset( $parts['query'] ) ? '?' . $parts['query'] : '' );

		return esc_url_raw( $clean, [ 'http', 'https' ] );
	}

	/**
	 * Check whether the given language code is known to the active multilingual
	 * provider (i.e. in its active-languages set or matches the default language).
	 *
	 * Used to reject crafted srfm-form-language hidden-input values that pass
	 * the BCP-47 shape regex but reference languages the site doesn't actually
	 * support.
	 *
	 * @param string $language Language code to check (e.g. 'hi', 'de-AT').
	 * @since 2.11.0
	 * @return bool True when the code is known, false otherwise.
	 */
	protected function is_known_language( string $language ): bool {
		if ( '' === $language ) {
			return false;
		}

		$provider = Multilingual_Manager::get_instance()->provider();

		// When no provider is active there's no authoritative set to check
		// against. Accept whatever the visitor sent (shape-validated) so the
		// column still reflects the visitor's intent on non-WPML sites.
		if ( ! $provider->is_active() ) {
			return true;
		}

		// Default language is always considered known.
		if ( $language === $provider->default_language() ) {
			return true;
		}

		// Use WPML's filter when available — works regardless of which
		// multilingual plugin is the active provider, as Polylang implements
		// the same filter for compatibility.
		$active = apply_filters( 'wpml_active_languages', null, 'skip_missing=0' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML's own filter; the name must match WPML/Polylang exactly to integrate.
		if ( is_array( $active ) && ! empty( $active ) ) {
			return array_key_exists( $language, $active );
		}

		// A provider IS active but its language list is unavailable. Rather than
		// fail open and trust an arbitrary client-supplied code, accept it only when
		// it matches the server-resolved current language. The caller already
		// defaults $entry_language to current_language(), so this keeps mis-tagging
		// to the server's own determination instead of the (cacheable) client value.
		return $language === $provider->current_language();
	}

	/**
	 * Recipients of a notification that came from the visitor's own fields.
	 *
	 * A recipient template part with a {form:*} tag is filled in by the
	 * visitor; anything else ({admin_email}, a typed address) is the owner's.
	 *
	 * @param array<mixed> $item            Notification settings.
	 * @param array<mixed> $submission_data Submission data.
	 * @since 2.12.9
	 * @return array{addresses:array<int,string>,only_visitor:bool}
	 */
	private static function get_visitor_recipients( $item, $submission_data ) {
		$smart_tags   = Smart_Tags::get_instance();
		$addresses    = [];
		$owner        = [];
		$only_visitor = true;

		foreach ( [ 'email_to', 'email_cc', 'email_bcc' ] as $key ) {
			$template = Helper::get_string_value( $item[ $key ] ?? '' );
			foreach ( explode( ',', $template ) as $part ) {
				$part = trim( $part );
				if ( '' === $part ) {
					continue;
				}
				$resolved = array_map( 'trim', explode( ',', Helper::get_string_value( $smart_tags->process_smart_tags( $part, $submission_data ) ) ) );
				if ( false === strpos( $part, '{form:' ) ) {
					$only_visitor = false;
					$owner        = array_merge( $owner, $resolved );
					continue;
				}
				$addresses = array_merge( $addresses, $resolved );
			}
		}

		// An address the owner set is theirs even when a visitor also typed it.
		$owner     = array_map( 'strtolower', array_filter( $owner ) );
		$addresses = array_filter(
			$addresses,
			static function ( $address ) use ( $owner ) {
				return '' !== $address && ! in_array( strtolower( $address ), $owner, true );
			}
		);

		return [
			'addresses'    => array_values( $addresses ),
			'only_visitor' => $only_visitor,
		];
	}

	/**
	 * Hand one notification to wp_mail().
	 *
	 * Output is buffered because some mail plugins (Zoho Mail, for one) echo
	 * notices or errors instead of throwing, which would corrupt the JSON
	 * response. The wp_mail_failed listener is attached for this call only, so
	 * the reason recorded is the mailer's own rather than a guess.
	 *
	 * Raw PHP mail() is not tried unless Mailer::allow_php_mail_fallback() says
	 * so; see there for why.
	 *
	 * @param array<string,mixed> $parsed  Parsed notification: to, subject, message, headers.
	 * @param int                 $form_id Form the notification belongs to.
	 * @since 2.12.9
	 * @return array{sent:bool,via_php_mail:bool,error:string}
	 */
	private static function dispatch_notification( $parsed, $form_id ) {
		$to      = Helper::get_string_value( $parsed['to'] ?? '' );
		$subject = Helper::get_string_value( $parsed['subject'] ?? '' );
		$message = Helper::get_string_value( $parsed['message'] ?? '' );
		$headers = $parsed['headers'] ?? '';
		$headers = is_array( $headers ) ? $headers : Helper::get_string_value( $headers );

		$error   = '';
		$capture = static function ( $wp_error ) use ( &$error ) {
			if ( $wp_error instanceof \WP_Error ) {
				$error = $wp_error->get_error_message();
			}
		};

		add_action( 'wp_mail_failed', $capture );
		ob_start();
		$output = '';

		// try/finally: a mailer that throws must not leave the listener on for
		// the next send, or the output buffer open on the submit response.
		try {
			$sent         = (bool) wp_mail( $to, $subject, $message, $headers );
			$via_php_mail = false;

			if ( ! $sent && Mailer::allow_php_mail_fallback( $form_id, 'notification' ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions -- Owner opted in via srfm_allow_php_mail_fallback.
				$sent         = mail( $to, $subject, $message, is_array( $headers ) ? implode( "\r\n", $headers ) : $headers );
				$via_php_mail = $sent;
			}
		} finally {
			$output = Helper::get_string_value( ob_get_clean() );
			remove_action( 'wp_mail_failed', $capture );
		}

		return [
			'sent'         => $sent,
			'via_php_mail' => $via_php_mail,
			'error'        => '' !== $error ? $error : trim( $output ),
		];
	}

	/**
	 * Collect the block IDs of the fields a form configures as unique.
	 *
	 * Derived from the stored form, never from the request — the whole point is that
	 * the client cannot nominate which fields are probeable. The frontend already
	 * sends only inputs rendered with data-unique="true", which comes from the same
	 * isUnique attribute, so this is the server-side mirror of what the client does.
	 *
	 * @param int $form_id Form ID.
	 *
	 * @since 2.12.3
	 * @return array<string,true> Unique field block IDs, keyed by block ID.
	 */
	private function get_unique_field_block_ids( $form_id ) {
		$form = get_post( $form_id );

		if ( ! $form instanceof \WP_Post || '' === $form->post_content ) {
			return [];
		}

		$visited_refs = [];
		$block_ids    = $this->collect_unique_field_block_ids( parse_blocks( $form->post_content ), $visited_refs );

		/**
		 * Filters the block IDs treated as unique fields for the AJAX uniqueness check.
		 *
		 * Lets add-ons whose fields a static parse of the form cannot see contribute
		 * their own unique fields.
		 *
		 * @since 2.12.3
		 *
		 * @param array<string,true> $block_ids Unique field block IDs, keyed by block ID.
		 *                                     A plain list of IDs is accepted too and is
		 *                                     normalised to this shape.
		 * @param int                $form_id   Form ID.
		 */
		$filtered = apply_filters( 'srfm_unique_field_block_ids', $block_ids, $form_id );

		// Normalise rather than trust: the lookup is isset( $set[ $block_id ] ), so an
		// add-on returning a plain list would silently disable uniqueness for the form
		// instead of adding to it. A non-array return keeps the derived set.
		return is_array( $filtered ) ? self::normalize_block_id_set( $filtered ) : $block_ids;
	}

	/**
	 * Normalise a block-ID collection to a block ID => true map.
	 *
	 * Accepts both the documented map shape and a plain list of IDs.
	 *
	 * @param array<mixed> $block_ids Block IDs as a map or a list.
	 *
	 * @since 2.12.3
	 * @return array<string,true> Block IDs keyed by block ID.
	 */
	private static function normalize_block_id_set( $block_ids ) {
		$normalized = [];

		foreach ( $block_ids as $key => $value ) {
			// List entry: the ID is the value. Map entry: the ID is the key.
			$block_id = is_int( $key ) ? $value : $key;

			if ( is_string( $block_id ) && '' !== $block_id ) {
				$normalized[ $block_id ] = true;
			}
		}

		return $normalized;
	}

	/**
	 * Recursively collect block IDs of blocks whose isUnique attribute is enabled.
	 *
	 * Recurses into innerBlocks (repeater/container children) and expands
	 * reusable/synced patterns, mirroring Form_Styling::collect_form_block_ids().
	 *
	 * Note: parse_blocks() does NOT apply block.json defaults, unlike the render path.
	 * Every field block therefore has to keep isUnique defaulting to false — a block
	 * that defaults it to true would be serialised without the attribute and would be
	 * missed here while still rendering data-unique="true".
	 *
	 * @param array<mixed>     $blocks       Parsed blocks from parse_blocks().
	 * @param array<int, true> $visited_refs Reusable-block post IDs already expanded,
	 *                                       keyed by ID — guards against reference cycles.
	 *
	 * @since 2.12.3
	 * @return array<string,true> Unique field block IDs, keyed by block ID.
	 */
	private function collect_unique_field_block_ids( $blocks, &$visited_refs = [] ) {
		$block_ids = [];

		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : [];

			if ( ! empty( $attrs['isUnique'] ) && ! empty( $attrs['block_id'] ) && is_scalar( $attrs['block_id'] ) ) {
				$block_ids[ Helper::get_string_value( $attrs['block_id'] ) ] = true;
			}

			// Reusable/synced pattern: expand the referenced wp_block post so a field
			// living inside a pattern is seen like an inline block.
			if ( isset( $block['blockName'] ) && 'core/block' === $block['blockName'] && ! empty( $attrs['ref'] ) && is_scalar( $attrs['ref'] ) ) {
				$ref = absint( $attrs['ref'] );

				if ( $ref && ! isset( $visited_refs[ $ref ] ) ) {
					$visited_refs[ $ref ] = true;
					$ref_post             = get_post( $ref );

					if ( $ref_post instanceof \WP_Post && 'wp_block' === $ref_post->post_type && 'publish' === $ref_post->post_status && '' !== $ref_post->post_content ) {
						$block_ids += $this->collect_unique_field_block_ids( parse_blocks( $ref_post->post_content ), $visited_refs );
					}
				}
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$block_ids += $this->collect_unique_field_block_ids( $block['innerBlocks'], $visited_refs );
			}
		}

		return $block_ids;
	}

	/**
	 * Check if the current request is rate-limited for unique validation.
	 *
	 * Uses transients keyed by IP + form ID to throttle requests.
	 * Allows 10 requests per 60-second window per IP per form.
	 *
	 * @param int $form_id The form ID being validated.
	 * @since 2.7.0
	 * @return bool True if rate-limited (should block), false if allowed.
	 */
	private function is_unique_validation_rate_limited( $form_id ) {
		return $this->is_rate_limited( 'srfm_uv_', $form_id );
	}

	/**
	 * Throttle a public endpoint to 10 requests per minute per IP per form.
	 *
	 * Shared by the uniqueness check and the client log route rather than
	 * duplicated, so a change to the window applies to both.
	 *
	 * @param string $prefix  Transient key prefix, unique per endpoint.
	 * @param int    $form_id The form ID the request relates to.
	 * @since 2.12.6
	 * @return bool True if rate-limited (should block), false if allowed.
	 */
	private function is_rate_limited( $prefix, $form_id ) {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		if ( empty( $ip ) || ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return true; // Fail closed if IP cannot be determined.
		}

		$transient_key = $prefix . md5( $ip . '_' . $form_id );
		$attempts      = get_transient( $transient_key );

		if ( false === $attempts ) {
			set_transient( $transient_key, 1, MINUTE_IN_SECONDS );
			return false;
		}

		$attempts_count = Helper::get_integer_value( $attempts );

		if ( $attempts_count >= 10 ) {
			return true;
		}

		set_transient( $transient_key, $attempts_count + 1, MINUTE_IN_SECONDS );
		return false;
	}

	/**
	 * Process and sanitize SureForms field data from submitted form data.
	 *
	 * @param array<mixed> $form_data Raw form data from submission.
	 *
	 * @since 1.11.0
	 * @return array Processed and sanitized submission data.
	 */
	private function process_form_fields( $form_data ) {
		$form_id = isset( $form_data['form-id'] ) && is_numeric( $form_data['form-id'] ) ? absint( $form_data['form-id'] ) : 0;

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

			$field_block_name = Helper::get_block_name_from_field( $field_name );

			/**
			 * Filters the field value during form submission processing.
			 *
			 * This filter allows the Pro plugin to process and modify field values before they are saved.
			 * The Pro plugin can implement custom sanitization, validation and escaping logic for its
			 * specialized field types. When this filter is used by Pro, the core plugin will skip its
			 * default validation.
			 *
			 * @since 1.11.0
			 *
			 * @param mixed $value            The raw field value from form submission.
			 * @param array $field_data       Field information array containing:
			 *                                - 'field_name': The field name/key
			 *                                - 'field_block_name': The block type identifier
			 * @return array {
			 *     Processed field value data
			 *
			 *     @type bool   $is_processed Whether the value was processed by Pro plugin
			 *     @type mixed  $value        The processed and sanitized field value
			 * }
			 */
			$process_field_value = apply_filters(
				'srfm_process_field_value',
				$value,
				[
					'field_name'       => $field_name,
					'field_block_name' => $field_block_name,
				]
			);

			if ( is_array( $process_field_value ) && ! empty( $process_field_value['is_processed'] ) && ! empty( $process_field_value['value'] ) ) {
				$submission_data[ $field_name ] = $process_field_value['value'];
				continue;
			}

			/**
			 * Need to remove this refactor array value handling.
			 *
			 * The current array-based value handling needs to be replaced with:
			 * 1. Block-specific value processing based on block type.
			 * 2. Move premium features to pro version.
			 * 3. Implement value processing through filters for extensibility.
			 *
			 * This will improve code organization and maintainability while properly
			 * separating free/pro functionality.
			 */

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
				$submission_data[ $field_name ] = is_string( $value ) ? htmlspecialchars( $value ) : $value;
			}
		}

		/**
		 * Filters the submission data before preparing it for storage.
		 *
		 * The second parameter is a context array containing additional metadata
		 * about the submission. This array is extensible — new keys may be added
		 * in future versions without changing the filter signature.
		 *
		 * @since 2.6.0
		 *
		 * @param array<string,mixed> $submission_data Processed form submission data.
		 * @param array<string,mixed> $context {
		 *     Additional context for the submission.
		 *
		 *     @type int $form_id The ID of the form being submitted.
		 * }
		 */
		return apply_filters(
			'srfm_before_prepare_submission_data',
			$submission_data,
			[
				'form_id' => $form_id,
			]
		);
	}

	/**
	 * Add From email and name in the header.
	 *
	 * @param array<mixed>  $submission_data Submission data.
	 * @param array<string> $item An associative array containing email settings, such as 'email_to', 'subject', 'email_body', and optional headers like 'email_reply_to', 'email_cc', and 'email_bcc'.
	 * @param Smart_Tags    $smart_tags Smart Tags instance.
	 * @since 1.6.1
	 * @return string The formatted "From" email header.
	 */
	private static function add_from_data_in_header( $submission_data, $item, $smart_tags ) {
		$from_name  = is_array( $item ) && ! empty( $item['from_name'] ) ? sanitize_text_field( Helper::get_string_value( $item['from_name'] ) ) : '{site_title}';
		$from_email = is_array( $item ) && ! empty( $item['from_email'] ) ? Helper::get_string_value( $item['from_email'] ) : '{admin_email}';

		// Check if the email contains smart tags. If not, validate the email.
		$is_valid_email = true;
		if ( ! str_contains( $from_email, '{' ) && ! str_contains( $from_email, '}' ) ) {
			$is_valid_email = filter_var( $from_email, FILTER_VALIDATE_EMAIL );
		}
		// if the email is not valid, set it to the admin email.
		if ( ! $is_valid_email ) {
			$from_email = Helper::get_string_value( get_option( 'admin_email' ) );
		}

		return 'From: ' . esc_html( Helper::get_string_value( $smart_tags->process_smart_tags( $from_name, $submission_data ) ) ) . ' <' . esc_html( Helper::get_string_value( $smart_tags->process_smart_tags( $from_email, $submission_data ) ) ) . '>' . "\r\n";
	}
}
