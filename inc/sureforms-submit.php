<?php
/**
 * Sureforms Submit Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;
use SureForms\Inc\Email\Email_Template;

/**
 * Sureforms Submit Class.
 *
 * @since 0.0.1
 */
class Sureforms_Submit {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since  0.0.1
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_custom_endpoint' ] );
		add_action( 'wp_ajax_validation_ajax_action', [ $this, 'field_unique_validation' ] );
		add_action( 'wp_ajax_nopriv_validation_ajax_action', [ $this, 'field_unique_validation' ] );
	}

	/**
	 * Add custom API Route submit-form
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function register_custom_endpoint() {
		register_rest_route(
			'sureforms/v1',
			'/submit-form',
			array(
				'methods'             => 'POST',
				'callback'            => [ $this, 'handle_form_submission' ],
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'sureforms/v1',
			'/sureforms-settings',
			array(
				'methods'             => 'POST',
				'callback'            => [ $this, 'handle_settings_form_submission' ],
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'sureforms/v1',
			'/sureforms-settings',
			array(
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_settings_form_data' ],
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handle Form Submission
	 *
	 * @param \WP_REST_Request $request Request object or array containing form data.
	 *
	 * @since 0.0.1
	 * @return array<int, string>|\WP_Error Array containing the response data or error.
	 */
	public function handle_form_submission( $request ) {
		$form_data = Sureforms_Helper::sanitize_recursively( 'sanitize_text_field', $request->get_params() );
		if ( empty( $form_data ) || ! is_array( $form_data ) ) {
			return wp_send_json_error( __( 'Form data is not found.', 'sureforms' ) );
		}
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && ! empty( $_FILES ) ) {
			// Access the uploaded file.
			foreach ( $_FILES as $field => $file ) {
				// Access the uploaded file properties.
				$filename  = $file['name'];
				$temp_path = $file['tmp_name'];
				$file_size = $file['size'];
				$file_type = $file['type'];

				if ( ! $filename && ! $temp_path && ! $file_size && ! $file_type ) {
					continue;
				}
				// Handle each file.
				$upload_dir  = wp_upload_dir();
				$destination = $upload_dir['basedir'] . '/sure-forms/' . $filename;
				if ( ! is_dir( dirname( $destination ) ) ) {
					mkdir( dirname( $destination ), 0755, true );
				}
				move_uploaded_file( $temp_path, $destination );
				$file_url            = $upload_dir['baseurl'] . '/sure-forms/' . $filename;
				$form_data[ $field ] = $file_url;
			}
		}

		if ( ! $form_data['form-id'] ) {
			return wp_send_json_error( __( 'Form Id is missing.', 'sureforms' ) );
		}
		$current_form_id       = $form_data['form-id'];
		$selected_captcha_type = get_post_meta( Sureforms_Helper::get_integer_value( $current_form_id ), '_sureforms_form_recaptcha', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $current_form_id ), '_sureforms_form_recaptcha', true ) ) : '';

		if ( 'v2-checkbox' === $selected_captcha_type ) {
			$google_captcha_secret_key = get_option( 'sureforms_v2_checkbox_secret' );

		} elseif ( 'v2-invisible' === $selected_captcha_type ) {
			$google_captcha_secret_key = get_option( 'sureforms_v2_invisible_secret' );

		} elseif ( 'v3-reCAPTCHA' === $selected_captcha_type ) {
			$google_captcha_secret_key = get_option( 'sureforms_v3_secret' );
		}
		$honeypot_spam = get_option( 'honeypot' );
		if ( isset( $form_data['sureforms-honeypot-field'] ) && empty( $form_data['sureforms-honeypot-field'] ) ) {
			if ( ! empty( $google_captcha_secret_key ) ) {
				if ( isset( $form_data['sureforms_form_submit'] ) ) {
					$secret_key       = $google_captcha_secret_key;
					$ipaddress        = $_SERVER['REMOTE_ADDR'];
					$captcha_response = $form_data['g-recaptcha-response'];
					$url              = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $captcha_response . '&ip=' . $ipaddress;

					$response = wp_remote_get( $url );

					if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
						$json_string = wp_remote_retrieve_body( $response );
						$data        = (array) json_decode( $json_string, true );
					} else {
						$data = array();
					}
					$sureforms_captcha_data = $data;

				} else {
					return new \WP_Error( 'recaptcha_error', 'reCAPTCHA error.', array( 'status' => 403 ) );
				}
				if ( isset( $sureforms_captcha_data['success'] ) && true === $sureforms_captcha_data['success'] ) {
					return $this->handle_form_entry( $form_data );
				} else {
					return new \WP_Error( 'recaptcha_error', 'reCAPTCHA error.', array( 'status' => 403 ) );
				}
			} else {
				return $this->handle_form_entry( $form_data );
			}
		} elseif ( ! isset( $form_data['sureforms-honeypot-field'] ) ) {
			if ( ! empty( $google_captcha_secret_key ) ) {
				if ( isset( $form_data['sureforms_form_submit'] ) ) {
					$secret_key       = $google_captcha_secret_key;
					$ipaddress        = $_SERVER['REMOTE_ADDR'];
					$captcha_response = $form_data['g-recaptcha-response'];
					$url              = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $captcha_response . '&ip=' . $ipaddress;

					$response = wp_remote_get( $url );

					if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
						$json_string = wp_remote_retrieve_body( $response );
						$data        = (array) json_decode( $json_string, true );
					} else {
						$data = array();
					}
					$sureforms_captcha_data = $data;

				} else {
					return new \WP_Error( 'recaptcha_error', 'reCAPTCHA error.', array( 'status' => 403 ) );
				}
				if ( true === $sureforms_captcha_data['success'] ) {
					return $this->handle_form_entry( $form_data );
				} else {
					return new \WP_Error( 'recaptcha_error', 'reCAPTCHA error.', array( 'status' => 403 ) );
				}
			} else {
				return $this->handle_form_entry( $form_data );
			}
		} else {
			return new \WP_Error( 'spam_detected', 'Spam Detected', array( 'status' => 403 ) );
		}
	}

	/**
	 * Handle Settings Form Submission
	 *
	 * @param \WP_REST_Request $request Request object or array containing form data.
	 *
	 * @return void|\WP_Error Array containing the response data or error.
	 * @since 0.0.1
	 */
	public function handle_settings_form_submission( $request ) {
		$data = Sureforms_Helper::sanitize_recursively( 'sanitize_text_field', $request->get_json_params() );
		if ( empty( $data ) || ! is_array( $data ) ) {
			return wp_send_json_error( __( 'Data is not found.', 'sureforms' ) );
		}

		$options_to_update = array(
			'sureforms_v2_checkbox_secret',
			'sureforms_v2_checkbox_site',
			'sureforms_v2_invisible_secret',
			'sureforms_v2_invisible_site',
			'sureforms_v3_site',
			'sureforms_v3_secret',
			'honeypot_toggle',
		);

		foreach ( $options_to_update as $option_key ) {
			if ( isset( $data[ $option_key ] ) ) {
				if ( 'honeypot_toggle' === $option_key ) {
					update_option( 'honeypot', $data[ $option_key ] );
				} else {
					update_option( $option_key, $data[ $option_key ] );
				}
			}
		}
	}

	/**
	 * Get Settings Form Data
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function get_settings_form_data() {
		$sureforms_v2_checkbox_secret  = ! empty( get_option( 'sureforms_v2_checkbox_secret' ) ) ? get_option( 'sureforms_v2_checkbox_secret' ) : '';
		$sureforms_v2_checkbox_site    = ! empty( get_option( 'sureforms_v2_checkbox_site' ) ) ? get_option( 'sureforms_v2_checkbox_site' ) : '';
		$sureforms_v2_invisible_secret = ! empty( get_option( 'sureforms_v2_invisible_secret' ) ) ? get_option( 'sureforms_v2_invisible_secret' ) : '';
		$sureforms_v2_invisible_site   = ! empty( get_option( 'sureforms_v2_invisible_site' ) ) ? get_option( 'sureforms_v2_invisible_site' ) : '';
		$sureforms_v3_secret           = ! empty( get_option( 'sureforms_v3_secret' ) ) ? get_option( 'sureforms_v3_secret' ) : '';
		$sureforms_v3_site             = ! empty( get_option( 'sureforms_v3_site' ) ) ? get_option( 'sureforms_v3_site' ) : '';
		$honeypot                      = ! empty( get_option( 'honeypot' ) ) ? get_option( 'honeypot' ) : '';
		// TODO: We need to change it to array and serialize it.
		$results = array(
			'sureforms_v2_checkbox_site'    => $sureforms_v2_checkbox_site,
			'sureforms_v2_checkbox_secret'  => $sureforms_v2_checkbox_secret,
			'sureforms_v2_invisible_site'   => $sureforms_v2_invisible_site,
			'sureforms_v2_invisible_secret' => $sureforms_v2_invisible_secret,
			'sureforms_v3_secret'           => $sureforms_v3_secret,
			'sureforms_v3_site'             => $sureforms_v3_site,
			'honeypot'                      => $honeypot,
		);

		wp_send_json( $results );
	}

	/**
	 * Send Email and Create Entry.
	 *
	 * @param array $form_data Request object or array containing form data.
	 * @since 0.0.1
	 * @return array Array containing the response data.
	 * @phpstan-ignore-next-line
	 */
	public function handle_form_entry( $form_data ) {

		$id          = wp_kses_post( $form_data['form-id'] );
		$form_markup = get_the_content( null, false, $form_data['form-id'] );

		$pattern = '/"label":"(.*?)"/';
		preg_match_all( $pattern, $form_markup, $matches );
		$labels = $matches[1];

		$meta_data = array();

		$form_data_keys  = array_keys( $form_data );
		$form_data_count = count( $form_data );
		for ( $i = 3; $i < $form_data_count; $i++ ) {
			$key   = strval( $form_data_keys[ $i ] );
			$value = $form_data[ $key ];

			$field_name = htmlspecialchars( str_replace( '_', ' ', $key ) );

			$meta_data[ $field_name ] = htmlspecialchars( $value );
		}

		$first_input = str_replace( ' ', '_', $labels[0] );
		$name        = sanitize_text_field( get_the_title( intval( $id ) ) );

		$honeypot_value = get_option( 'honeypot' );

		$honeypot = ! empty( $honeypot_value ) ? $honeypot_value : '';

		if ( '1' === $honeypot ) {
			$key               = strval( $form_data_keys[5] );
			$first_field_value = $form_data[ $key ];
		} else {
			$key               = strval( $form_data_keys[4] );
			$first_field_value = $form_data[ $key ];
		}

		$new_post = array(
			'post_title'  => $first_field_value,
			'post_status' => 'publish',
			'post_type'   => 'sureforms_entry',
		);

		$post_id = wp_insert_post( $new_post );

		update_post_meta( $post_id, 'sureforms_entry_meta', $meta_data );
		add_post_meta( $post_id, 'sureforms_entry_meta_form_id', $id, true );
		if ( $post_id ) {
			wp_set_object_terms( $post_id, $id, 'sureforms_tax' );
			$response = array(
				'success' => true,
				'message' => __( 'Form submitted successfully', 'sureforms' ),
				'data'    => array(
					'name' => $name,
				),
			);

			$email       = Sureforms_Helper::get_string_value( get_post_meta( intval( $id ), '_sureforms_email', true ) );
			$admin_email = explode( ', ', $email );

			$subject = __( 'Notification from Sureforms Form ID:', 'sureforms' ) . $id;

			$email_template = new Email_Template();
			$message        = $email_template->render( $meta_data );

			$excluded_fields = [ 'sureforms-honeypot-field', 'g-recaptcha-response', 'sureforms-sender-email-field' ];
			foreach ( $meta_data as $field_name => $value ) {
				if ( in_array( $field_name, $excluded_fields, true ) || false !== strpos( $field_name, 'sf-radio' ) ) {
					continue;
				}
				if ( strpos( $field_name, 'SF-upload' ) !== false ) {
					$message .= strtoupper( explode( 'SF-upload', $field_name )[0] ) . ':';
					if ( ! empty( $value ) ) {
						$message .= ' <a href="' . esc_url( $value ) . '">' . __( 'View', 'sureforms' ) . '</a>';
					}
					$message .= '<br/>';
				} elseif ( strpos( $field_name, 'SF-url' ) !== false ) {
					$message .= strtoupper( explode( 'SF-url', $field_name )[0] ) . ':';
					if ( ! empty( $value ) ) {
						$message .= esc_url( $value );
					}
					$message .= '<br/>';
				} else {
					$message .= strtoupper( explode( 'SF-divider', $field_name )[0] ) . ': ' . $value . '<br/>';
				}
			}
			$headers = "From: $email\r\n" .
				"Reply-To: $email\r\n" .
				'X-Mailer: PHP/' . phpversion() . "\r\n" .
				'Content-Type: text/html; charset=utf-8';
			$sent    = wp_mail( $admin_email, $subject, $message, $headers );

			$sender_notification = get_post_meta( intval( $id ), '_sureforms_sender_notification', true );

			if ( 'on' === $sender_notification && isset( $form_data['sureforms-sender-email-field'] ) ) {
				$sender_email = strval( $form_data['sureforms-sender-email-field'] );
				wp_mail( $sender_email, $subject, $message, $headers );
			}

			if ( $sent ) {
				wp_send_json_success( __( 'Email sent successfully.', 'sureforms' ) );
			} else {
				wp_send_json_error( __( 'Failed to send form data.', 'sureforms' ) );
			}
		} else {
			$response = array(
				'success' => false,
				'message' => __( 'Error submitting form', 'sureforms' ),
			);
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

		if ( isset( $_POST['nonce'] ) && ! wp_verify_nonce( $_POST['nonce'], 'unique_validation_nonce' ) ) {
			$error_message = 'Nonce verification failed.';

			$error_data = array(
				'error' => $error_message,
			);
			wp_send_json_error( $error_data );
		}
		global $wpdb;
		$id         = isset( $_POST['id'] ) ? $_POST['id'] : '';
		$meta_value = $id;

		if ( ! $meta_value ) {
			$error_message = 'Invalid form ID.';
			$error_data    = array(
				'error' => $error_message,
			);
			wp_send_json_error( $error_data );
		}

		$post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_id
			FROM {$wpdb->postmeta}
			WHERE meta_value = %s",
				$meta_value
			)
		);

		$all_form_entries = array();
		$keys             = array_keys( $_POST );
		$length           = count( $keys );

		for ( $i = 3; $i < $length; $i++ ) {
			$key   = $keys[ $i ];
			$value = $_POST[ $key ];
			$key   = str_replace( '_', ' ', $keys[ $i ] );

			foreach ( $post_ids as $post_id ) {
				$meta_values = get_post_meta( $post_id, 'sureforms_entry_meta', true );
				if ( is_array( $meta_values ) && isset( $meta_values[ $key ] ) && $meta_values[ $key ] === $value ) {
					$obj = array( $key => 'not unique' );
					array_push( $all_form_entries, $obj );
					break;
				}
			}
		}

		$results = array(
			'data' => $all_form_entries,
		);

		wp_send_json( $results );
	}
}
