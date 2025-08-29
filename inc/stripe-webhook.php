<?php
/**
 * SureForms Webhook Class
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc;

use SRFM\Inc\Database\Tables\Payments;
use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Webhook endpoints
 */
class Stripe_Webhook {
	use Get_Instance;
	public const SRFM_LIVE_BEGAN_AT        = 'srfm_live_webhook_began_at';
	public const SRFM_LIVE_LAST_SUCCESS_AT = 'srfm_live_webhook_last_success_at';
	public const SRFM_LIVE_LAST_FAILURE_AT = 'srfm_live_webhook_last_failure_at';
	public const SRFM_LIVE_LAST_ERROR      = 'srfm_live_webhook_last_error';

	public const SRFM_TEST_BEGAN_AT        = 'srfm_test_webhook_began_at';
	public const SRFM_TEST_LAST_SUCCESS_AT = 'srfm_test_webhook_last_success_at';
	public const SRFM_TEST_LAST_FAILURE_AT = 'srfm_test_webhook_last_failure_at';
	public const SRFM_TEST_LAST_ERROR      = 'srfm_test_webhook_last_error';

	private string $mode = 'test';

	/**
	 * Constructor function
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	/**
	 * Registers endpoint for webhook
	 *
	 * @return void
	 */
	public function register_endpoints() {
		register_rest_route(
			'sureforms',
			'/webhook',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'webhook_listener' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	/**
	 * Validates the Stripe signature for webhook requests through middleware.
	 *
	 * @return array<string, mixed>|bool
	 */
	public function validate_stripe_signature(): array|bool {
		// Get the raw payload and Stripe signature header
		$payload   = file_get_contents( 'php://input' );
		$signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

		if ( empty( $payload ) || empty( $signature ) ) {
			error_log( 'SureForms: Missing webhook payload or signature' );
			return false;
		}

		// Get payment settings
		$settings = get_option( Payments_Settings::OPTION_NAME, [] );
		if ( ! is_array( $settings ) ) {
			$settings = [];
		}
		$this->mode = $settings['payment_mode'] ?? 'test';

		// Get the appropriate webhook secret based on payment mode
		$webhook_secret = '';
		if ( 'live' === $this->mode ) {
			$webhook_secret = is_string( $settings['webhook_live_secret'] ?? '' ) ? $settings['webhook_live_secret'] : '';
		} else {
			$webhook_secret = is_string( $settings['webhook_test_secret'] ?? '' ) ? $settings['webhook_test_secret'] : '';
		}

		if ( empty( $webhook_secret ) ) {
			error_log( 'SureForms: Webhook secret not configured for mode: ' . $this->mode );
			return false;
		}

		// Prepare request data for middleware
		$middleware_request_data = [
			'payload'        => $payload,
			'signature'      => $signature,
			'webhook_secret' => $webhook_secret,
		];

		// Make request to middleware for signature verification
		$response = wp_remote_post(
			'prod' === SRFM_PAYMENTS_ENV ? SRFM_PAYMENTS_PROD . 'webhook/validate-signature' : SRFM_PAYMENTS_LOCAL . 'webhook/validate-signature',
			[
				'body'      => base64_encode( wp_json_encode( $middleware_request_data ) ?: '' ),
				'headers'   => [
					'Content-Type' => 'application/json',
				],
				'timeout'   => 10, // 10 second timeout
				'sslverify' => true,
			]
		);

		// Handle middleware communication errors
		if ( is_wp_error( $response ) ) {
			error_log( 'SureForms: Middleware request failed: ' . $response->get_error_message() );
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// Parse middleware response
		$validation_result = json_decode( $response_body, true );

		if ( 200 === $response_code && is_array( $validation_result ) ) {
			return $validation_result;
		}

		return false;
	}

	/**
	 * This function listens webhook events.
	 *
	 * @return void
	 */
	public function webhook_listener(): void {
		$event = $this->validate_stripe_signature();

		// error_log( 'SureForms webhook event type: ' . $event->type );

		if ( ! $event || ! isset( $event['type'] ) ) {
			error_log( 'SureForms: Invalid webhook event' );
			return;
		}

		switch ( $event['type'] ) {
			case 'charge.refund.updated':
				// If not available event.data['object'] then return.
				if ( ! isset( $event['data']['object'] ) ) {
					error_log( 'SureForms: Invalid webhook event' );
					return;
				}

				$charge = $event['data']['object'];
				$this->charge_refund( $charge );
				break;
		}

		$success = constant( 'self::SRFM_' . strtoupper( $this->mode ) . '_LAST_SUCCESS_AT' );
		if ( is_string( $success ) ) {
			update_option( $success, time() );
		}
		http_response_code( 200 );
	}

	/**
	 * Refunds form entry payment via webhook call
	 *
	 * @param array<string, mixed> $charge Payment charge object.
	 * @return void
	 */
	public function charge_refund( array $charge ): void {
		$payment_intent    = sanitize_text_field( $charge['payment_intent'] ?? '' );
		$get_payment_entry = Payments::get_by_transaction_id( $payment_intent );

		if ( ! $get_payment_entry ) {
			error_log( 'SureForms: Could not find payment entry via charge ID: ' . ( $charge['id'] ?? 'unknown' ) );
			return;
		}

		$payment_entry_id = $get_payment_entry['id'] ?? 0;
		$refund_amount    = is_numeric( $charge['amount'] ?? 0 ) ? (int) $charge['amount'] : 0;

		$currency           = is_string( $charge['currency'] ?? '' ) ? $charge['currency'] : 'usd';
		$update_refund_data = $this->update_refund_data( $payment_entry_id, $charge, $refund_amount, $currency, 'webhook' );

		if ( ! $update_refund_data ) {
			error_log( 'SureForms: Failed to update refund data for payment entry ID: ' . $payment_entry_id );
			return;
		}

		error_log(
			sprintf(
				'SureForms: Payment refunded. Amount: %s for entry ID: %s',
				$refund_amount,
				$payment_entry_id
			)
		);
	}

	/**
	 * Calculate total refunds for a payment.
	 *
	 * @param int $payment_id Payment ID.
	 * @return float Total refunded amount.
	 */
	public function calculate_total_refunds( int $payment_id ): float {
		// Use the new refunded_amount column for direct access
		return Payments::get_refunded_amount( $payment_id );
	}

	/**
	 * Update refund data for a payment.
	 *
	 * @param int                  $payment_id Payment ID.
	 * @param array<string, mixed> $refund_response Refund response data.
	 * @param int                  $refund_amount Refund amount in cents.
	 * @param string               $currency Currency code.
	 * @param string|null          $payment Payment method.
	 * @return bool Whether the update was successful.
	 */
	public function update_refund_data( int $payment_id, array $refund_response, int $refund_amount, string $currency, ?string $payment = null ): bool {
		if ( empty( $payment_id ) || empty( $refund_response ) ) {
			return false;
		}

		// Get payment record if not provided
		$payment = Payments::get( $payment_id );
		if ( ! $payment ) {
			error_log( 'SureForms: Payment record not found for ID: ' . $payment_id );
			return false;
		}

		$check_if_refund_already_exists = $this->check_if_refund_already_exists( $payment, $refund_response );
		if ( $check_if_refund_already_exists ) {
			return true;
		}

		// Prepare refund data for payment_data column
		$refund_data = [
			'refund_id'      => sanitize_text_field( $refund_response['id'] ?? '' ),
			'amount'         => absint( $refund_amount ),
			'currency'       => sanitize_text_field( strtoupper( $currency ) ),
			'status'         => sanitize_text_field( $refund_response['status'] ?? 'processed' ),
			'created'        => time(),
			'reason'         => sanitize_text_field( $refund_response['reason'] ?? 'requested_by_customer' ),
			'description'    => sanitize_text_field( $refund_response['description'] ?? '' ),
			'receipt_number' => sanitize_text_field( $refund_response['receipt_number'] ?? '' ),
			'refunded_by'    => 'stripe_dashboard',
			'refunded_at'    => gmdate( 'Y-m-d H:i:s' ),
		];

		// Validate refund amount to prevent over-refunding
		$original_amount    = floatval( $payment['total_amount'] );
		$existing_refunds   = floatval( $payment['refunded_amount'] ?? 0 ); // Use column directly
		$new_refund_amount  = $refund_amount / 100; // Convert cents to dollars
		$total_after_refund = $existing_refunds + $new_refund_amount;

		if ( $total_after_refund > $original_amount ) {
			error_log(
				sprintf(
					'SureForms: Over-refund attempt blocked. Payment ID: %d, Original: $%s, Existing refunds: $%s, New refund: $%s',
					$payment_id,
					number_format( $original_amount, 2 ),
					number_format( $existing_refunds, 2 ),
					number_format( $new_refund_amount, 2 )
				)
			);
			return false;
		}

		// Add refund data to payment_data column (for audit trail)
		$payment_data_result = Payments::add_refund_to_payment_data( $payment_id, $refund_data );

		// Update the refunded_amount column
		$refund_amount_result = Payments::add_refund_amount( $payment_id, $new_refund_amount );

		// Calculate appropriate payment status
		$payment_status = 'succeeded'; // Default to current status
		if ( $total_after_refund >= $original_amount ) {
			$payment_status = 'refunded'; // Fully refunded
		} elseif ( $total_after_refund > 0 ) {
			$payment_status = 'partially_refunded'; // Partially refunded
		}

		// Update payment status and log
		$current_logs   = Helper::get_array_value( $payment['log'] );
		$refund_type    = $total_after_refund >= $original_amount ? 'Full' : 'Partial';
		$new_log        = [
			'title'     => sprintf( '%s Payment Refund', $refund_type ),
			'timestamp' => time(),
			'messages'  => [
				sprintf( 'Refund ID: %s', $refund_response['id'] ?? 'N/A' ),
				sprintf( 'Refund Amount: %s %s', number_format( $refund_amount / 100, 2 ), strtoupper( $currency ) ),
				sprintf(
					'Total Refunded: %s %s of %s %s',
					number_format( $total_after_refund, 2 ),
					strtoupper( $currency ),
					number_format( $original_amount, 2 ),
					strtoupper( $currency )
				),
				sprintf( 'Refund Status: %s', $refund_response['status'] ?? 'processed' ),
				sprintf( 'Payment Status: %s', ucfirst( str_replace( '_', ' ', $payment_status ) ) ),
				sprintf( 'Refunded by: %s', wp_get_current_user()->display_name ),
			],
		];
		$current_logs[] = $new_log;

		$update_data = [
			'status' => $payment_status,
			'log'    => $current_logs,
		];

		// Update payment record with status and log
		$payment_update_result = Payments::update( $payment_id, $update_data );

		// Check if all operations succeeded
		if ( false === $payment_data_result ) {
			error_log( 'SureForms: Failed to store refund data in payment_data for payment ID: ' . $payment_id );
		}

		if ( false === $refund_amount_result ) {
			error_log( 'SureForms: Failed to update refunded_amount column for payment ID: ' . $payment_id );
			return false;
		}

		if ( false === $payment_update_result ) {
			error_log( 'SureForms: Failed to update payment status and log for payment ID: ' . $payment_id );
			return false;
		}

		error_log(
			sprintf(
				'SureForms: Refund processed successfully. Payment ID: %d, Refund ID: %s, Amount: %s %s',
				$payment_id,
				$refund_data['refund_id'],
				number_format( $refund_amount / 100, 2 ),
				$currency
			)
		);

		return true;
	}

	/**
	 * Check if the refund already exists
	 *
	 * @param array<string, mixed> $payment Payment record data.
	 * @param array<string, mixed> $refund Refund response from Stripe.
	 * @return bool True if refund already exists, false otherwise.
	 * @since x.x.x
	 */
	private function check_if_refund_already_exists( array $payment, array $refund ): bool {
		$refund_id = $refund['id'] ?? '';

		$payment_refunds = isset( $payment['payment_data'] ) && isset( $payment['payment_data']['refunds'] ) ? $payment['payment_data']['refunds'] : [];

		if ( ! empty( $payment_refunds ) && is_array( $payment_refunds ) ) {
			foreach ( $payment_refunds as $payment_refund ) {
				if ( isset( $payment_refund['refund_id'] ) && $payment_refund['refund_id'] === $refund_id ) {
					return true;
				}
			}
		}

		return false;
	}
}
