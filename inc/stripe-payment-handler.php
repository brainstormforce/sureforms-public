<?php
/**
 * Stripe Payment Handler
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc;

use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Stripe Payment Handler Class
 *
 * @since x.x.x
 */
class Stripe_Payment_Handler {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		add_action( 'wp_ajax_srfm_create_payment_intent', [ $this, 'create_payment_intent' ] );
		add_action( 'wp_ajax_nopriv_srfm_create_payment_intent', [ $this, 'create_payment_intent' ] ); // For non-logged-in users.
		add_filter( 'srfm_form_submit_data', [ $this, 'validate_payment_fields' ], 5, 1 );
		add_action( 'wp_head', [ $this, 'add_payment_styles' ] );
	}

	/**
	 * Add payment styles
	 *
	 * @return void
	 * @since x.x.x
	 */
	public function add_payment_styles() {
		$styles = '
		.srfm-payment-field-wrapper {
			margin: 10px 0;
		}
		.srfm-payment-amount {
			background: #f8f9fa;
			border: 1px solid #e1e5e9;
			border-radius: 4px;
			padding: 12px;
			margin-bottom: 16px;
		}
		.srfm-payment-amount-display {
			display: flex;
			align-items: center;
			gap: 8px;
			font-weight: 500;
		}
		.srfm-payment-icon {
			color: #0073aa;
		}
		.srfm-payment-value {
			color: #0073aa;
			font-weight: 600;
		}
		.srfm-payment-fee-info {
			margin-top: 4px;
			color: #666;
		}
		.srfm-stripe-payment-element {
			margin: 16px 0;
		}
		.srfm-payment-status {
			padding: 12px;
			border-radius: 4px;
			background: #f0f6fc;
			border: 1px solid #c3ddfd;
		}
		.srfm-payment-processing {
			display: flex;
			align-items: center;
			gap: 8px;
			color: #0969da;
		}
		.srfm-spinner {
			width: 16px;
			height: 16px;
			border: 2px solid #e1e5e9;
			border-top: 2px solid #0969da;
			border-radius: 50%;
			animation: srfm-spin 1s linear infinite;
		}
		@keyframes srfm-spin {
			0% { transform: rotate(0deg); }
			100% { transform: rotate(360deg); }
		}
		.srfm-payment-notice {
			padding: 12px;
			background: #fff3cd;
			border: 1px solid #ffecb5;
			border-radius: 4px;
			color: #664d03;
		}
		.srfm-stripe-elements-preview {
			margin: 16px 0;
		}
		.srfm-stripe-card-preview {
			border: 1px solid #e1e5e9;
			border-radius: 4px;
			padding: 16px;
			background: #ffffff;
		}
		.srfm-stripe-field-preview {
			border: 1px solid #e1e5e9;
			border-radius: 4px;
			padding: 12px;
			margin-bottom: 8px;
			background: #fafbfc;
		}
		.srfm-stripe-field-row {
			display: flex;
			gap: 8px;
		}
		.srfm-stripe-field-small {
			flex: 1;
		}
		.srfm-stripe-placeholder {
			color: #8b949e;
			font-size: 14px;
		}
		.srfm-stripe-preview-notice {
			text-align: center;
			margin-top: 8px;
			color: #656d76;
		}
		';

		wp_add_inline_style( 'srfm-frontend-css', $styles );
	}

	/**
	 * Create payment intent
	 *
	 * @return void
	 * @throws \Exception When Stripe configuration is invalid.
	 * @since x.x.x
	 */
	public function create_payment_intent() {
		// Verify nonce.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'srfm_stripe_payment_nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce.', 'sureforms' ) );
			return;
		}

		$amount          = intval( $_POST['amount'] ?? 0 );
		$currency        = sanitize_text_field( wp_unslash( $_POST['currency'] ?? 'usd' ) );
		$description     = sanitize_text_field( wp_unslash( $_POST['description'] ?? 'SureForms Payment' ) );
		$block_id        = sanitize_text_field( wp_unslash( $_POST['block_id'] ?? '' ) );

		if ( $amount <= 0 ) {
			wp_send_json_error( __( 'Invalid payment amount.', 'sureforms' ) );
			return;
		}

		try {
			// Get payment settings.
			$payment_settings = get_option( 'srfm_payments_settings', [] );

			if ( empty( $payment_settings['stripe_connected'] ) ) {
				throw new \Exception( __( 'Stripe is not connected.', 'sureforms' ) );
			}

			$payment_mode = $payment_settings['payment_mode'] ?? 'test';
			$secret_key   = 'live' === $payment_mode
				? $payment_settings['stripe_live_secret_key'] ?? ''
				: $payment_settings['stripe_test_secret_key'] ?? '';

			if ( empty( $secret_key ) ) {
				throw new \Exception( __( 'Stripe secret key not found.', 'sureforms' ) );
			}

			// Create payment intent.
			$payment_intent_data = apply_filters(
				'srfm_create_payment_intent_data',
				[
					'secret_key'                => $secret_key,
					'amount'                    => $amount,
					'currency'                  => strtolower( $currency ),
					'description'               => $description,
					'automatic_payment_methods' => [
						'enabled' => true,
					],
					'metadata'                  => [
						'source'          => 'SureForms',
						'block_id'        => $block_id,
						'original_amount' => $amount,
					],
			] );

			$payment_intent = wp_remote_post(
				'prod' === SRFM_PAYMENTS_ENV ? SRFM_PAYMENTS_PROD . 'payment-intent/create' : SRFM_PAYMENTS_LOCAL . 'payment-intent/create',
				[
					'body'     => base64_encode( wp_json_encode( $payment_intent_data ) ),
					'headers'  => [
						'Content-Type' => 'application/json',
					],
				]
			);

			if ( is_wp_error( $payment_intent ) ) {
				throw new \Exception( __( 'Failed to create payment intent.', 'sureforms' ) );
			}

			$payment_intent = json_decode( wp_remote_retrieve_body( $payment_intent ), true );

			wp_send_json_success(
				[
					'client_secret'     => $payment_intent['client_secret'],
					'payment_intent_id' => $payment_intent['id'],
				]
			);

		} catch ( \Exception $e ) {
			error_log( 'SureForms Stripe Error: ' . $e->getMessage() );
			wp_send_json_error( __( 'Failed to create payment intent. Please try again.', 'sureforms' ) );
		}
	}

	/**
	 * Validate payment fields before form submission
	 *
	 * @param array<mixed> $form_data Form data.
	 * @return array<mixed>
	 * @since x.x.x
	 */
	public function validate_payment_fields( $form_data ) {
		// Check if form has payment fields.
		$has_payment = false;
		foreach ( $form_data as $field_name => $field_value ) {
			if ( strpos( $field_name, '-lbl-' . Helper::encrypt( 'Payment' ) ) !== false ) {
				$has_payment = true;
				break;
			}
		}

		if ( ! $has_payment ) {
			return $form_data;
		}

		// Validate payment completion.
		foreach ( $form_data as $field_name => $field_value ) {
			if ( strpos( $field_name, '-lbl-' . Helper::encrypt( 'Payment' ) ) !== false ) {
				// Check if payment intent ID is present.
				if ( empty( $field_value ) || strpos( $field_value, 'pi_' ) !== 0 ) {
					// Payment not completed.
					wp_send_json_error(
						[
							'message' => __( 'Payment is required to submit this form.', 'sureforms' ),
						]
					);
					wp_die();
				}

				// Verify payment intent status.
				if ( ! $this->verify_payment_intent( $field_value ) ) {
					wp_send_json_error(
						[
							'message' => __( 'Payment verification failed. Please try again.', 'sureforms' ),
						]
					);
					wp_die();
				}
			}
		}

		return $form_data;
	}

	/**
	 * Verify payment intent status
	 *
	 * @param string $payment_intent_id Payment intent ID.
	 * @return bool
	 * @since x.x.x
	 */
	private function verify_payment_intent( $payment_intent_id ) {
		try {
			$payment_settings = get_option( 'srfm_payments_settings', [] );
			$payment_mode     = $payment_settings['payment_mode'] ?? 'test';
			$secret_key       = 'live' === $payment_mode
				? $payment_settings['stripe_live_secret_key'] ?? ''
				: $payment_settings['stripe_test_secret_key'] ?? '';

			if ( empty( $secret_key ) ) {
				return false;
			}

			$payment_intent = wp_remote_post(
				'prod' === SRFM_PAYMENTS_ENV ? SRFM_PAYMENTS_PROD . 'payment-intent/retrieve' : SRFM_PAYMENTS_LOCAL . 'payment-intent/retrieve',
				[
					'method'    => 'POST',
					'body'     => base64_encode( wp_json_encode( [ 'payment_intent_id' => $payment_intent_id, 'secret_key' => $secret_key ] ) ),
					'headers'  => [
						'Content-Type' => 'application/json',
					],
			] );

			if ( is_wp_error( $payment_intent ) ) {
				return false;
			}

			$payment_intent = json_decode( wp_remote_retrieve_body( $payment_intent ), true );

			return 'succeeded' === $payment_intent['status'];

		} catch ( \Exception $e ) {
			error_log( 'SureForms Payment Verification Error: ' . $e->getMessage() );
			return false;
		}
	}

}

