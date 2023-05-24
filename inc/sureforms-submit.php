<?php
/**
 * Sureforms Submit Class file.
 *
 * @package sureforms.
 * @since X.X.X
 */

namespace SureForms\Inc;

use SureForms\Inc\Traits\Get_Instance;

/**
 * Sureforms Submit Class.
 *
 * @since X.X.X
 */
class Sureforms_Submit {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since  X.X.X
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_custom_endpoint' ] );
	}

	/**
	 * Add custom API Route submit-form
	 *
	 * @return void
	 * @since X.X.X
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
	 * @param object $request Request object or array containing form data.
	 *
	 * @since X.X.X
	 * @return array Array containing the response data.
	 * @phpstan-ignore-next-line
	 */
	public function handle_form_submission( $request ) {

		// @phpstan-ignore-next-line
		$form_data = $request->get_params();

		$google_captcha_secret_key = get_option( 'recaptcha_secret_key' );

		if ( ! empty( $google_captcha_secret_key ) ) {
			if ( isset( $form_data['sureforms_form_submit'] ) ) {
				$secret_key       = $google_captcha_secret_key;
				$ipaddress        = $_SERVER['REMOTE_ADDR'];
				$captcha_response = $form_data['g-recaptcha-response'];
				$url              = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $captcha_response . '&ip=' . $ipaddress;

				$response = wp_remote_get( $url );

				if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
					$json_string = wp_remote_retrieve_body( $response );
					$data        = json_decode( $json_string, true );
				} else {
					$data = array();
				}
				$sureforms_captcha_data = $data;

			} else {
				// @phpstan-ignore-next-line
				return new WP_Error( 'recaptcha_error', 'ReCaptcha error.', array( 'status' => 403 ) );
			}
			// @phpstan-ignore-next-line
			if ( true === $sureforms_captcha_data['success'] ) {
				$id          = wp_kses_post( $form_data['form-id'] );
				$form_markup = get_the_content( null, false, $form_data['form-id'] );

				$pattern = '/"label":"(.*?)"/';
				preg_match_all( $pattern, $form_markup, $matches );
				$labels = $matches[1];

				$meta_data = array();

				$form_data_keys  = array_keys( $form_data );
				$form_data_count = count( $form_data );

				for ( $i = 3; $i < $form_data_count - 1; $i++ ) {
					$key   = strval( $form_data_keys[ $i ] );
					$value = $form_data[ $key ];

					$field_name = htmlspecialchars( str_replace( '_', ' ', $key ) );

					$meta_data[ $field_name ] = htmlspecialchars( $value );
				}

				$first_input = str_replace( ' ', '_', $labels[0] );
				$name        = isset( $form_data[ $first_input ] ) ? sanitize_text_field( $form_data[ $first_input ] ) : '';

				$new_post = array(
					'post_title'  => $name,
					'post_status' => 'unread',
					'post_type'   => 'sureforms_entry',
				);

				$post_id = wp_insert_post( $new_post );

				update_post_meta( $post_id, 'sureforms_entry_meta', $meta_data );

				if ( $post_id ) {
					wp_set_object_terms( $post_id, $id, 'sureforms_tax' );
					$response = array(
						'success' => true,
						'message' => __( 'Form submitted successfully', 'sureforms' ),
						'data'    => array(
							'name' => $name,
						),
					);

					$admin_email = strval( get_option( 'admin_email' ) );
					$subject     = __( 'Notification from Sureforms Form ID:', 'sureforms' ) . $id;

					$message = '';

					foreach ( $meta_data as $field_name => $value ) {
						$message .= '<b>' . $field_name . '</b>: ' . $value . "\n";
					}

					$sent = wp_mail( $admin_email, $subject, $message );

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

			} else {
				// @phpstan-ignore-next-line
				return new WP_Error( 'recaptcha_error', 'ReCaptcha error.', array( 'status' => 403 ) );
			}
		} else {
			$id          = wp_kses_post( $form_data['form-id'] );
			$form_markup = get_the_content( null, false, $form_data['form-id'] );

			$pattern = '/"label":"(.*?)"/';
			preg_match_all( $pattern, $form_markup, $matches );
			$labels = $matches[1];

			$meta_data = array();

			$form_data_keys  = array_keys( $form_data );
			$form_data_count = count( $form_data );

			for ( $i = 3; $i < $form_data_count - 1; $i++ ) {
				$key   = strval( $form_data_keys[ $i ] );
				$value = $form_data[ $key ];

				$field_name = htmlspecialchars( str_replace( '_', ' ', $key ) );

				$meta_data[ $field_name ] = htmlspecialchars( $value );
			}

			$first_input = str_replace( ' ', '_', $labels[0] );
			$name        = isset( $form_data[ $first_input ] ) ? sanitize_text_field( $form_data[ $first_input ] ) : '';

			$new_post = array(
				'post_title'  => $name,
				'post_status' => 'unread',
				'post_type'   => 'sureforms_entry',
			);

			$post_id = wp_insert_post( $new_post );

			update_post_meta( $post_id, 'sureforms_entry_meta', $meta_data );

			if ( $post_id ) {
				wp_set_object_terms( $post_id, $id, 'sureforms_tax' );
				$response = array(
					'success' => true,
					'message' => __( 'Form submitted successfully', 'sureforms' ),
					'data'    => array(
						'name' => $name,
					),
				);

				$admin_email = strval( get_option( 'admin_email' ) );
				$subject     = __( 'Notification from Sureforms Form ID:', 'sureforms' ) . $id;

				$message = '';

				foreach ( $meta_data as $field_name => $value ) {
					$message .= '<b>' . $field_name . '</b>: ' . $value . "\n";
				}

				$sent = wp_mail( $admin_email, $subject, $message );

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

	}

	/**
	 * Handle Settings Form Submission
	 *
	 * @param object $request Request object or array containing form data.
	 *
	 * @return void
	 * @since X.X.X
	 */
	public function handle_settings_form_submission( $request ) {
		// @phpstan-ignore-next-line
		$data = $request->get_json_params();
			update_option( 'recaptcha_secret_key', $data['google_captcha_secret_key'] );
			update_option( 'recaptcha_site_key', $data['google_captcha_site_key'] );
	}

	/**
	 * Get Settings Form Data
	 *
	 * @return void
	 * @since X.X.X
	 */
	public function get_settings_form_data() {
		$secret_key = ! empty( get_option( 'recaptcha_secret_key' ) ) ? get_option( 'recaptcha_secret_key' ) : '';
		$site_key   = ! empty( get_option( 'recaptcha_site_key' ) ) ? get_option( 'recaptcha_site_key' ) : '';

		$results = array(
			'sitekey'    => $site_key,
			'secret_key' => $secret_key,
		);

		wp_send_json( $results );
	}
}
