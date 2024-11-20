<?php
/**
 * SureForms - AI Auth.
 *
 * @package sureforms
 * @since 0.0.8
 */

namespace SRFM\Inc\AI_Form_Builder;

use SRFM\Inc\Helper;
use SRFM\Inc\Traits\Get_Instance;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SureForms AI Form Builder Class.
 */
class AI_Auth {
	use Get_Instance;

	/**
	 * The key for encryption and decryption.
	 *
	 * @since 0.0.8
	 * @var string
	 */
	private $key = '';

	/**
	 * Initiates the auth process.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @since 0.0.8
	 * @return void
	 */
	public function get_auth_url( $request ) {

		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			wp_send_json_error( __( 'Nonce verification failed.', 'sureforms' ) );
		}

		// Generate a random key of 16 characters.
		$this->key = wp_generate_password( 16, false );

		// Prepare the token data.
		$token_data = [
			'redirect-back' => site_url() . '/wp-admin/admin.php?page=add-new-form&method=ai',
			'key'           => $this->key,
			'site-url'      => site_url(),
			'nonce'         => wp_create_nonce( 'ai_auth_nonce' ),
		];

		$encoded_token_data = wp_json_encode( $token_data );

		if ( empty( $encoded_token_data ) ) {
			wp_send_json_error( [ 'message' => __( 'Failed to encode the token data.', 'sureforms' ) ] );
		}

		// Send the token data to the frontend for redirection.
		wp_send_json_success( SRFM_BILLING_PORTAL . 'auth/?token=' . base64_encode( $encoded_token_data ) );  // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Handles the access key.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @since 0.0.8
	 * @return void
	 */
	public function handle_access_key( $request ) {

		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );

		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			wp_send_json_error( __( 'Nonce verification failed.', 'sureforms' ) );
		}

		// get body data.
		$body = json_decode( $request->get_body(), true );

		if ( empty( $body ) ) {
			wp_send_json_error( [ 'message' => __( 'Error processing Access Key.', 'sureforms' ) ] );
		}

		// get access key.
		$access_key = is_array( $body ) && ! empty(
			$body['accessKey']
		) ? Helper::get_string_value( $body['accessKey'] ) : '';

		// decrypt the access key.
		if ( ! empty( $access_key ) ) {
			$this->decrypt_access_key(
				$access_key,
				$this->key
			);
		} else {
			wp_send_json_error( [ 'message' => __( 'No access key provided.', 'sureforms' ) ] );
		}
	}

	/**
	 * Decrypts a string using OpenSSL decryption.
	 *
	 * @param string $data The data to decrypt.
	 * @param string $key The encryption key.
	 * @param string $method The encryption method (e.g., AES-256-CBC).
	 * @since 0.0.8
	 * @return string|false The decrypted string or false on failure.
	 */
	public function decrypt_access_key( $data, $key, $method = 'AES-256-CBC' ) {
		// Decode the data and split IV and encrypted data.
		$decoded_data = base64_decode( $data ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		// if the data is not base64 encoded then return false.
		if ( empty( $decoded_data ) ) {
			return false;
		}

		// split the key and encrypted data.
		[$key, $encrypted] = explode( '::', $decoded_data, 2 );

		// Decrypt the data using the key.
		$decrypted = openssl_decrypt( $encrypted, $method, $key, 0, $key );

		// if the decryption returns false then send error.
		if ( empty( $decrypted ) ) {
			wp_send_json_error( [ 'message' => __( 'Failed to decrypt the access key.', 'sureforms' ) ] );
		}

		// json decode the decrypted data.
		$decrypted_data_array = json_decode( $decrypted, true );

		if ( ! is_array( $decrypted_data_array ) || empty( $decrypted_data_array ) ) {
			wp_send_json_error( [ 'message' => __( 'Failed to json decode the decrypted data.', 'sureforms' ) ] );
		}

		// verify the nonce that comes in $encrypted_email_array.
		if ( ! empty( $decrypted_data_array['nonce'] ) && ! wp_verify_nonce( $decrypted_data_array['nonce'], 'ai_auth_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Nonce verification failed.', 'sureforms' ) ] );
		}

		// check if the user email is present in the decrypted data.
		if ( empty( $decrypted_data_array['user_email'] ) ) {
			wp_send_json_error( [ 'message' => __( 'No user email found in the decrypted data.', 'sureforms' ) ] );
		}

		// remove the nonce from the decrypted data before saving it to the options.
		unset( $decrypted_data_array['nonce'] );

		// save the user email to the options.
		update_option( 'srfm_ai_auth_user_email', $decrypted_data_array );

		wp_send_json_success();
	}

}
