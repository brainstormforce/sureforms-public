<?php
/**
 * Sureforms Submit Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc;

use SRFM\Inc\Traits\Get_Instance;
use SRFM\Inc\Helper;
use SRFM\Inc\Email\Email_Template;
use SRFM\Inc\Smart_Tags;
use SRFM\Inc\Generate_Form_Markup;
use WP_REST_Server;
use SRFM\Inc\Lib\Browser\Browser;
use WP_Error;
use WP_REST_Request;

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
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'rest_forbidden', __( 'Sorry, you cannot access this route', 'sureforms' ), [ 'status' => rest_authorization_required_code() ] );
		}
		return true;
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

		$form_data = Helper::sanitize_recursively( 'sanitize_text_field', $request->get_params() );
		if ( empty( $form_data ) || ! is_array( $form_data ) ) {
			wp_send_json_error( __( 'Form data is not found.', 'sureforms' ) );
		}
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && ! empty( $_FILES ) ) {
			add_filter( 'upload_dir', [ $this, 'change_upload_dir' ] );
			foreach ( $_FILES as $field => $file ) {
				$filename  = $file['name'];
				$temp_path = $file['tmp_name'];
				$file_size = $file['size'];
				$file_type = $file['type'];

				if ( ! $filename && ! $temp_path && ! $file_size && ! $file_type ) {
					continue;
				}

				// Use wp_handle_upload instead of move_uploaded_file.
				$uploaded_file = [
					'name'     => $filename,
					'type'     => $file_type,
					'tmp_name' => $temp_path,
					'error'    => $file['error'],
					'size'     => $file_size,
				];

				$upload_overrides = [
					'test_form' => false,
				];
				$move_file        = wp_handle_upload( $uploaded_file, $upload_overrides );
				remove_filter( 'upload_dir', [ $this, 'change_upload_dir' ] );
				if ( $move_file && ! isset( $move_file['error'] ) ) {
					$form_data[ $field ] = $move_file['url'];
				} else {
					wp_send_json_error( __( 'File is not uploaded', 'sureforms' ) );
				}
			}
		}

		if ( ! $form_data['form-id'] ) {
			wp_send_json_error( __( 'Form Id is missing.', 'sureforms' ) );
		}
		$current_form_id       = $form_data['form-id'];
		$selected_captcha_type = get_post_meta( Helper::get_integer_value( $current_form_id ), '_srfm_form_recaptcha', true ) ? Helper::get_string_value( get_post_meta( Helper::get_integer_value( $current_form_id ), '_srfm_form_recaptcha', true ) ) : '';

		if ( 'none' !== $selected_captcha_type ) {
			$global_setting_options = get_option( 'srfm_security_settings_options' );
		} else {
			$global_setting_options = [];
		}

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
					return new \WP_Error( 'recaptcha_error', 'reCAPTCHA error.', [ 'status' => 403 ] );
				}
				if ( isset( $sureforms_captcha_data['success'] ) && true === $sureforms_captcha_data['success'] ) {
					return rest_ensure_response( $this->handle_form_entry( $form_data ) );
				} else {
					return new \WP_Error( 'recaptcha_error', 'reCAPTCHA error.', [ 'status' => 403 ] );
				}
			} else {
				return rest_ensure_response( $this->handle_form_entry( $form_data ) );
			}
		} elseif ( ! isset( $form_data['srfm-honeypot-field'] ) ) {
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
					return new \WP_Error( 'recaptcha_error', 'reCAPTCHA error.', [ 'status' => 403 ] );
				}
				if ( true === $sureforms_captcha_data['success'] ) {
					return rest_ensure_response( $this->handle_form_entry( $form_data ) );
				} else {
					return new \WP_Error( 'recaptcha_error', 'reCAPTCHA error.', [ 'status' => 403 ] );
				}
			} else {
				return rest_ensure_response( $this->handle_form_entry( $form_data ) );
			}
		} else {
			return new \WP_Error( 'spam_detected', 'Spam Detected', [ 'status' => 403 ] );
		}

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

		$global_setting_options = get_option( 'srfm_general_settings_options' );
		$srfm_ip_log            = is_array( $global_setting_options ) && isset( $global_setting_options['srfm_ip_log'] ) ? $global_setting_options['srfm_ip_log'] : '';

		$user_ip      = ( $srfm_ip_log && isset( $_SERVER['REMOTE_ADDR'] ) ) ? filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP ) : '';
		$browser      = new Browser();
		$browser_name = sanitize_text_field( $browser->getBrowser() );
		$device_name  = sanitize_text_field( $browser->getPlatform() );

		$id           = wp_kses_post( $form_data['form-id'] );
		$form_markup  = get_the_content( null, false, Helper::get_integer_value( $form_data['form-id'] ) );
		$sender_email = '';
		$pattern      = '/"label":"(.*?)"/';
		preg_match_all( $pattern, $form_markup, $matches );
		$labels = $matches[1];

		$submission_data = [];

		$form_data_keys  = array_keys( $form_data );
		$form_data_count = count( $form_data );
		for ( $i = 4; $i < $form_data_count; $i++ ) {
			$key   = strval( $form_data_keys[ $i ] );
			$value = $form_data[ $key ];

			$field_name = htmlspecialchars( str_replace( '_', ' ', $key ) );

			$submission_data[ $field_name ] = htmlspecialchars( $value );
		}

		$name = sanitize_text_field( get_the_title( intval( $id ) ) );

		$honeypot = is_array( $global_setting_options ) && isset( $global_setting_options['srfm_honeypot'] ) ? $global_setting_options['srfm_honeypot'] : '';

		if ( $honeypot ) {
			$key               = strval( $form_data_keys[5] );
			$first_field_value = $form_data[ $key ];
		} else {
			$key               = strval( $form_data_keys[4] );
			$first_field_value = $form_data[ $key ];
		}

		$new_post = [
			'post_status' => 'publish',
			'post_type'   => 'sureforms_entry',
		];

		$post_id = wp_insert_post( $new_post );

		if ( empty( $first_field_value ) && $post_id ) {
			$post_title = __( 'Entry #', 'sureforms' ) . $post_id;
		} else {
			$post_title = $first_field_value;
		}

		$post_args = [
			'ID'         => $post_id,
			'post_title' => $post_title,
		];

		wp_update_post( $post_args );

		update_post_meta( $post_id, 'srfm_entry_meta', $submission_data );
		add_post_meta( $post_id, 'srfm_entry_meta_form_id', $id, true );
		if ( $post_id ) {
			$srfm_submission_info[] = [
				'user_ip'      => $user_ip,
				'browser_name' => $browser_name,
				'device_name'  => $device_name,
			];
			update_post_meta( $post_id, '_srfm_submission_info', $srfm_submission_info );
			wp_set_object_terms( $post_id, $id, 'sureforms_tax' );
			$response           = [
				'success' => true,
				'message' => Generate_Form_Markup::get_confirmation_markup( $form_data, $submission_data ),
				'data'    => [
					'name' => $name,
				],
			];
			$email_notification = get_post_meta( intval( $id ), '_srfm_email_notification' );
			$smart_tags         = new Smart_Tags();
			$is_mail_sent       = false;
			$emails             = [];
			if ( is_iterable( $email_notification ) ) {
				foreach ( $email_notification as $notification ) {
					foreach ( $notification as $item ) {
						if ( true === $item['status'] ) {
							$to             = $item['email_to'];
							$to             = $smart_tags->process_smart_tags( $to );
							$subject        = $item['subject'];
							$subject        = $smart_tags->process_smart_tags( $subject );
							$email_body     = $item['email_body'];
							$email_template = new Email_Template();
							$message        = $email_template->render( $submission_data, $email_body );
							$headers        = "From: $to\r\n" .
							"Reply-To: $to\r\n" .
							'X-Mailer: PHP/' . phpversion() . "\r\n" .
							'Content-Type: text/html; charset=utf-8';
							$sent           = wp_mail( $to, $subject, $message, $headers );
							$is_mail_sent   = $sent;
							$emails[]       = $to;
						}
					}
				}
			}

			$modified_message = [];
			foreach ( $submission_data as $key => $value ) {
				$only_key = str_replace( ':', '', ucfirst( explode( 'SF', $key )[0] ) );
				$parts    = explode( '-lbl-', $only_key );

				if ( ! empty( $parts[1] ) ) {
					$tokens = explode( '-', $parts[1] );
					if ( count( $tokens ) > 1 ) {
						$only_key = implode( '-', array_slice( $tokens, 1 ) );
					}
				} else {
					$tokens = explode( '-', $parts[0] );
					if ( 'address' === $tokens[1] ) {
						$only_key = implode( '-', array_slice( $tokens, 3 ) );
					}
				}
				$modified_message[ $only_key ] = esc_attr( $value );
			}

			$form_submit_response = [
				'success'   => true,
				'form_id'   => $id ? intval( $id ) : '',
				'emails'    => $emails,
				'form_name' => $name ? esc_attr( $name ) : '',
				'message'   => __( 'Form submitted successfully', 'sureforms' ),
				'data'      => $modified_message,
			];

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
	 * Retrieve all entries data for a specific form ID to check for unique values.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function field_unique_validation() {
		if ( isset( $_POST['nonce'] ) && ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), 'unique_validation_nonce' ) ) {
			$error_message = 'Nonce verification failed.';
			$error_data    = [
				'error' => $error_message,
			];
			wp_send_json_error( $error_data );
		}

		global $wpdb;
		$id         = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		$meta_value = $id;

		if ( ! $meta_value ) {
			$error_message = 'Invalid form ID.';
			$error_data    = [
				'error' => $error_message,
			];
			wp_send_json_error( $error_data );
		}

		$_POST = array_map( 'wp_unslash', $_POST );

		$taxonomy = 'sureforms_tax';

		$args  = [
			'post_type' => SRFM_ENTRIES_POST_TYPE,
			'tax_query'  // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query. -- We require tax_query for this function to work.
			=> [
				[
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $id,
				],
			],
			'fields'    => 'ids',
		];
		$query = new \WP_Query( $args );

		$post_ids = $query->posts;

		wp_reset_postdata();

		$all_form_entries = [];
		$keys             = array_keys( $_POST );
		$length           = count( $keys );

		for ( $i = 3; $i < $length; $i++ ) {
			$key   = $keys[ $i ];
			$value = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
			$key   = str_replace( '_', ' ', $keys[ $i ] );

			foreach ( $post_ids as $post_id ) {
				$post_id     = Helper::get_integer_value( $post_id );
				$meta_values = get_post_meta( $post_id, 'srfm_entry_meta', true );
				if ( is_array( $meta_values ) && isset( $meta_values[ $key ] ) && $meta_values[ $key ] === $value ) {
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
