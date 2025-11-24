<?php
/**
 * SureForms Webhook Class
 *
 * @package sureforms
 * @since 2.0.0
 */

namespace SRFM\Inc\Payments\Stripe;

use SRFM\Inc\Database\Tables\Payments;
use SRFM\Inc\Helper;
use SRFM\Inc\Payments\Payment_Helper;
use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Stripe Webhook handler class.
 *
 * @since 2.0.0
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

	/**
	 * Payment mode.
	 *
	 * @var string
	 * @since 2.0.0
	 */
	private $mode = 'test';

	/**
	 * Constructor function.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	/**
	 * Registers endpoint for webhook.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_endpoints() {
		// Test mode webhook endpoint.
		register_rest_route(
			'sureforms',
			'/webhook_test',
			[
				'methods'             => 'POST',
				'callback'            => function() {
					$this->webhook_listener( 'test' );
				},
				'permission_callback' => '__return_true',
			]
		);

		// Live mode webhook endpoint.
		register_rest_route(
			'sureforms',
			'/webhook_live',
			[
				'methods'             => 'POST',
				'callback'            => function() {
					$this->webhook_listener( 'live' );
				},
				'permission_callback' => '__return_true',
			]
		);
	}

	/**
	 * Validates the Stripe signature for webhook requests through middleware.
	 *
	 * @param string|null $mode The payment mode ('test' or 'live'). If null, uses setting.
	 * @since 2.0.0
	 * @return array<string, mixed>|bool
	 */
	public function validate_stripe_signature( $mode = null ) {
		// Get the raw payload and Stripe signature header.
		$payload = file_get_contents( 'php://input' );
		// phpcs:disable
		$signature = ! empty( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) && is_string( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ? sanitize_text_field( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) : '';
		// phpcs:enable
		$signature = trim( $signature );

		if ( empty( $payload ) || empty( $signature ) ) {
			Helper::srfm_log( 'Missing webhook payload or signature.' );
			return false;
		}

		// Get payment settings using the new format.
		$settings = Stripe_Helper::get_all_stripe_settings();
		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		// Determine mode: use parameter if provided, otherwise fall back to global payment mode.
		$validate_mode = ! empty( $mode ) && in_array( $mode, [ 'test', 'live' ], true ) ? $mode : Payment_Helper::get_payment_mode();
		$this->mode    = ! empty( $validate_mode ) && is_string( $validate_mode ) ? $validate_mode : 'test';

		// Get the appropriate webhook secret based on payment mode.
		$webhook_secret = '';
		if ( 'live' === $this->mode ) {
			$webhook_secret = is_string( $settings['webhook_live_secret'] ?? '' ) ? $settings['webhook_live_secret'] : '';
		} else {
			$webhook_secret = is_string( $settings['webhook_test_secret'] ?? '' ) ? $settings['webhook_test_secret'] : '';
		}

		if ( empty( $webhook_secret ) ) {
			Helper::srfm_log( 'Webhook secret not configured for mode: ' . $this->mode . '.' );
			return false;
		}

		// Prepare request data for middleware.
		$middleware_request_data = [
			'payload'        => $payload,
			'signature'      => $signature,
			'webhook_secret' => $webhook_secret,
		];

		$endpoint = Stripe_Helper::middle_ware_base_url() . 'webhook/validate-signature';

		// Make request to middleware for signature verification.
		$response = wp_remote_post(
			$endpoint,
			[
				'body'      => Helper::srfm_base64_json_encode( $middleware_request_data ),
				'headers'   => [
					'Content-Type' => 'application/json',
				],
				'timeout'   => 10, // 10 second timeout.
				'sslverify' => true,
			]
		);

		// Handle middleware communication errors.
		if ( is_wp_error( $response ) ) {
			Helper::srfm_log( 'Middleware request failed: ' . $response->get_error_message() . '.' );
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// Parse middleware response.
		$validation_result = json_decode( $response_body, true );

		if ( 200 === $response_code && is_array( $validation_result ) ) {
			return $validation_result;
		}

		return false;
	}

	/**
	 * Development version - skips signature validation for testing.
	 * This function is intended for development purposes only and should not be used in production.
	 *
	 * @since 2.0.0
	 * @return array<string, mixed>|bool
	 */
	public function dev_validate_stripe_signature() {
		// Get the raw payload.
		$payload = file_get_contents( 'php://input' );

		if ( empty( $payload ) ) {
			Helper::srfm_log( 'Missing webhook payload.', 'SureForms DEV: ' );
			return false;
		}

		// Parse JSON payload directly (no signature verification).
		$event = json_decode( $payload, true );

		if ( ! $event || ! is_array( $event ) ) {
			Helper::srfm_log( 'Invalid JSON payload.', 'SureForms DEV: ' );
			return false;
		}

		Helper::srfm_log( 'Event type: ' . ( $event['type'] ?? 'unknown' ) . '.', 'SureForms DEV: ' );

		return $event;
	}

	/**
	 * This function listens webhook events.
	 *
	 * @param string|null $mode The payment mode ('test' or 'live'). If null, uses setting.
	 * @since 2.0.0
	 * @return void
	 */
	public function webhook_listener( $mode = null ) {
		$event = $this->validate_stripe_signature( $mode );

		if ( ! $event || ! isset( $event['type'] ) ) {
			Helper::srfm_log( 'Invalid webhook event.' );
			return;
		}

		Helper::srfm_log( 'Processing event type: ' . $event['type'] . '.' );
		Helper::srfm_log( $event, 'Processing ectual event : ' );

		switch ( $event['type'] ) {
			case 'charge.refund.updated':
				// Handle refund webhook event.
				$event_data = isset( $event['data'] ) && is_array( $event['data'] ) ? $event['data'] : [];
				if ( ! isset( $event_data['object'] ) ) {
					Helper::srfm_log( 'charge.refund.updated: Invalid webhook event - missing data object.' );
					return;
				}
				// Note: $event['data']['object'] is a Refund object, not a Charge object.
				$refund = $event_data['object'] ?? [];
				$this->handle_refund_record( $refund );
				break;

			case 'invoice.payment_succeeded':
				$event_data = isset( $event['data'] ) && is_array( $event['data'] ) ? $event['data'] : [];
				if ( ! isset( $event_data['object'] ) ) {
					Helper::srfm_log( 'Invalid webhook event.' );
					return;
				}
				$invoice = $event_data['object'] ?? [];
				$this->handle_invoice_payment_succeeded( $invoice );
				break;

			case 'customer.subscription.deleted':
				$event_data = isset( $event['data'] ) && is_array( $event['data'] ) ? $event['data'] : [];
				if ( ! isset( $event_data['object'] ) ) {
					Helper::srfm_log( 'customer.subscription.deleted: Invalid webhook event - missing data object.' );
					return;
				}
				$subscription = $event_data['object'] ?? [];
				$this->handle_subscription_deleted( $subscription );
				break;

			default:
				Helper::srfm_log( 'Unhandled event type: ' . $event['type'] . '.' );
				break;
		}

		$success = constant( 'self::SRFM_' . strtoupper( $this->mode ) . '_LAST_SUCCESS_AT' );
		if ( is_string( $success ) ) {
			update_option( $success, time() );
		}
		http_response_code( 200 );
	}

	/**
	 * Handles refund record - both creation and cancellation via webhook call.
	 *
	 * @param array<string, mixed> $refund Refund object from Stripe webhook.
	 * @since 2.0.0
	 * @return void
	 */
	public function handle_refund_record( $refund ) {
		$refund_id = ! empty( $refund['id'] ) && is_string( $refund['id'] ) ? sanitize_text_field( $refund['id'] ) : '';
		Helper::srfm_log( 'Processing refund: ' . $refund_id );

		// Extract payment identifiers from refund object.
		$payment_intent = ! empty( $refund['payment_intent'] ) && is_string( $refund['payment_intent'] ) ? sanitize_text_field( $refund['payment_intent'] ) : '';
		$charge_id      = ! empty( $refund['charge'] ) && is_string( $refund['charge'] ) ? sanitize_text_field( $refund['charge'] ) : '';

		Helper::srfm_log(
			sprintf(
				'Refund lookup info - Refund ID: %s, Payment Intent: %s, Charge ID: %s',
				$refund_id,
				$payment_intent ? $payment_intent : 'null',
				$charge_id ? $charge_id : 'null'
			)
		);

		$get_payment_entry = null;
		$lookup_method     = '';

		// Method 1: Try to find payment by payment_intent (for one-time payments).
		if ( ! empty( $payment_intent ) ) {
			Helper::srfm_log( 'Attempting lookup by payment_intent: ' . $payment_intent );
			$get_payment_entry = Payments::get_by_transaction_id( $payment_intent );
			if ( $get_payment_entry ) {
				$lookup_method = 'payment_intent';
				Helper::srfm_log( 'Found payment entry by payment_intent' );
			}
		}

		// Method 2: Try to find by charge ID (for subscription payments and one-time fallback).
		if ( ! $get_payment_entry && ! empty( $charge_id ) ) {
			Helper::srfm_log( 'Attempting lookup by charge_id: ' . $charge_id );
			$get_payment_entry = Payments::get_by_transaction_id( $charge_id );
			if ( $get_payment_entry ) {
				$lookup_method = 'charge_id';
				Helper::srfm_log( 'Found payment entry by charge_id' );
			}
		}

		// Method 3: Try to find in payment_data for one-time payments that might have charge stored there.
		if ( ! $get_payment_entry && ! empty( $charge_id ) ) {
			Helper::srfm_log( 'Attempting lookup in payment_data by charge_id' );
			// This would require a custom query or storing charge_id differently.
			// For now, log that we're trying this method.
		}

		// Final check: If still not found, log detailed error and return.
		if ( ! $get_payment_entry ) {
			Helper::srfm_log(
				sprintf(
					'REFUND FAILED: Could not find payment entry. Refund ID: %s, Payment Intent: %s, Charge ID: %s. Full refund object: %s',
					$refund_id,
					$payment_intent ? $payment_intent : 'null',
					$charge_id ? $charge_id : 'null',
					wp_json_encode( $refund )
				)
			);
			return;
		}

		// Extract refund details.
		$payment_entry_id = ! empty( $get_payment_entry['id'] ) && is_numeric( $get_payment_entry['id'] ) ? intval( $get_payment_entry['id'] ) : 0;
		$refund_amount    = isset( $refund['amount'] ) && ( is_numeric( $refund['amount'] ) || is_float( $refund['amount'] ) || is_string( $refund['amount'] ) ) ? $refund['amount'] : 0;
		$currency         = ! empty( $refund['currency'] ) && is_string( $refund['currency'] ) ? sanitize_text_field( strtolower( $refund['currency'] ) ) : 'usd';
		$refund_status    = ! empty( $refund['status'] ) && is_string( $refund['status'] ) ? sanitize_text_field( $refund['status'] ) : 'unknown';

		Helper::srfm_log(
			sprintf(
				'Processing refund for payment entry ID: %d, Amount: %s %s, Status: %s',
				$payment_entry_id,
				Stripe_Helper::amount_from_stripe_format( $refund_amount, $currency ),
				strtoupper( $currency ),
				$refund_status
			)
		);

		// Route based on refund status.
		if ( 'canceled' === $refund_status ) {
			// Handle refund cancellation.
			Helper::srfm_log( 'Refund status is canceled - processing refund cancellation.' );
			$this->process_refund_cancellation( $payment_entry_id, $refund, $currency, $lookup_method );
			return;
		}

		// Handle refund creation (succeeded status).
		if ( 'succeeded' !== $refund_status ) {
			Helper::srfm_log( 'Unexpected refund status: ' . $refund_status . '. Skipping processing.' );
			return;
		}

		// Update refund data in database.
		$update_refund_data = $this->update_refund_data( $payment_entry_id, $refund, $refund_amount, $currency, 'webhook' );

		if ( ! $update_refund_data ) {
			Helper::srfm_log( 'REFUND FAILED: Failed to update refund data for payment entry ID: ' . $payment_entry_id );
			return;
		}

		Helper::srfm_log(
			sprintf(
				'REFUND SUCCESS: Payment refunded successfully. Refund ID: %s, Amount: %s %s, Payment Entry ID: %d (found via %s)',
				$refund_id,
				Stripe_Helper::amount_from_stripe_format( $refund_amount, $currency ),
				$currency,
				$payment_entry_id,
				$lookup_method
			)
		);
	}

	/**
	 * Handles invoice.payment_succeeded webhook for subscription payments.
	 *
	 * @param array<string, mixed> $invoice Invoice object from Stripe.
	 * @since 2.0.0
	 * @return void
	 */
	public function handle_invoice_payment_succeeded( $invoice ) {
		$invoice_id = $invoice['id'] ?? 'unknown';
		Helper::srfm_log( 'Processing invoice.payment_succeeded webhook. Invoice ID: ' . $invoice_id . '.' );

		// Validate billing reason is subscription cycle.
		$billing_reason = ! empty( $invoice['billing_reason'] ) && is_string( $invoice['billing_reason'] ) ? sanitize_text_field( $invoice['billing_reason'] ) : '';
		if ( 'subscription_cycle' !== $billing_reason ) {
			Helper::srfm_log( 'Invoice payment succeeded - not a subscription cycle payment. Billing reason: ' . $billing_reason . '.' );
			return;
		}

		Helper::srfm_log( 'Billing reason validated: subscription_cycle.' );

		// Extract subscription ID using backward-compatible helper.
		$subscription_id = $this->extract_subscription_id_from_invoice( $invoice );
		if ( empty( $subscription_id ) ) {
			Helper::srfm_log( 'Invoice payment succeeded - missing subscription ID. Invoice ID: ' . $invoice_id . '.' );
			return;
		}

		Helper::srfm_log( 'Subscription ID extracted successfully: ' . $subscription_id . '.' );

		// Find subscription record in database.
		$subscription_record = Payments::get_main_subscription_record( $subscription_id );
		if ( ! $subscription_record ) {
			Helper::srfm_log( 'Invoice payment succeeded - subscription not found in database: ' . $subscription_id . '.' );
			return;
		}

		Helper::srfm_log( 'Subscription record found in database. Record ID: ' . ( ! empty( $subscription_record['id'] ) && is_numeric( $subscription_record['id'] ) ? intval( $subscription_record['id'] ) : 'unknown' ) . '.' );

		// Extract invoice data using backward-compatible helpers.
		$charge_id   = $this->extract_charge_id_from_invoice( $invoice );
		$amount_paid = isset( $invoice['amount_paid'] ) && ( is_numeric( $invoice['amount_paid'] ) || is_float( $invoice['amount_paid'] ) || is_string( $invoice['amount_paid'] ) ) ? $invoice['amount_paid'] : 0;
		$currency    = ! empty( $invoice['currency'] ) && is_string( $invoice['currency'] ) ? sanitize_text_field( strtolower( $invoice['currency'] ) ) : 'usd';

		Helper::srfm_log(
			sprintf(
				'Invoice details - Charge ID: %s, Amount: %s %s.',
				$charge_id ? $charge_id : 'empty',
				Stripe_Helper::amount_from_stripe_format( $amount_paid, $currency ),
				$currency
			)
		);

		// Check if this payment was already processed.
		if ( ! empty( $charge_id ) ) {
			$existing_payment = Payments::get_by_transaction_id( $charge_id );
			if ( $existing_payment ) {
				Helper::srfm_log( 'Invoice payment already processed. Charge ID: ' . $charge_id . '.' );
				return;
			}
		}

		// Extract block_id from line items metadata.
		$block_id            = '';
		$invoice_lines       = isset( $invoice['lines'] ) && is_array( $invoice['lines'] ) ? $invoice['lines'] : [];
		$invoice_line_data   = isset( $invoice_lines['data'] ) && is_array( $invoice_lines['data'] ) ? $invoice_lines['data'] : [];
		$invoice_line_data_0 = isset( $invoice_line_data[0] ) && is_array( $invoice_line_data[0] ) ? $invoice_line_data[0] : [];
		$metadata            = isset( $invoice_line_data_0['metadata'] ) && is_array( $invoice_line_data_0['metadata'] ) ? $invoice_line_data_0['metadata'] : [];
		$block_id            = ! empty( $metadata['block_id'] ) && is_string( $metadata['block_id'] ) ? sanitize_text_field( $metadata['block_id'] ) : '';

		Helper::srfm_log( 'Block ID from metadata: ' . ( $block_id ? $block_id : 'not found' ) . '.' );

		// Check if this is the initial payment or a renewal.
		$is_initial_payment = empty( $subscription_record['transaction_id'] ?? '' );

		Helper::srfm_log(
			sprintf(
				'Payment type detected: %s. Subscription record has transaction_id: %s.',
				$is_initial_payment ? 'Initial Payment' : 'Renewal Payment',
				! empty( $subscription_record['transaction_id'] ?? '' ) ? 'YES' : 'NO'
			)
		);

		if ( $is_initial_payment ) {
			Helper::srfm_log( 'Processing as initial subscription payment...' );
			$this->process_initial_subscription_payment( $subscription_record, $invoice, $charge_id );
		} else {
			Helper::srfm_log( 'Processing as subscription renewal payment...' );
			$this->process_subscription_renewal_payment( $subscription_record, $invoice, $charge_id, $block_id );
		}

		Helper::srfm_log(
			sprintf(
				'Subscription payment processed successfully. Type: %s, Subscription ID: %s, Amount: %s %s.',
				$is_initial_payment ? 'Initial' : 'Renewal',
				$subscription_id,
				Stripe_Helper::amount_from_stripe_format( $amount_paid, $currency ),
				$currency
			)
		);
	}

	/**
	 * Handles customer.subscription.deleted webhook for subscription cancellations.
	 *
	 * @param array<string, mixed> $subscription Subscription object from Stripe.
	 * @since 2.0.0
	 * @return void
	 */
	public function handle_subscription_deleted( $subscription ) {
		$subscription_id = ! empty( $subscription['id'] ) && is_string( $subscription['id'] ) ? sanitize_text_field( $subscription['id'] ) : '';
		Helper::srfm_log( 'Processing customer.subscription.deleted webhook. Subscription ID: ' . $subscription_id . '.' );

		if ( empty( $subscription_id ) ) {
			Helper::srfm_log( 'Subscription deleted - missing subscription ID.' );
			return;
		}

		Helper::srfm_log( 'Subscription ID extracted successfully: ' . $subscription_id . '.' );

		// Find subscription record in database.
		$subscription_record = Payments::get_main_subscription_record( $subscription_id );
		if ( ! $subscription_record ) {
			Helper::srfm_log( 'Subscription deleted - subscription not found in database: ' . $subscription_id . '.' );
			return;
		}

		$subscription_db_id = ! empty( $subscription_record['id'] ) && is_numeric( $subscription_record['id'] ) ? intval( $subscription_record['id'] ) : 0;
		Helper::srfm_log( 'Subscription record found in database. Record ID: ' . $subscription_db_id . '.' );

		// Extract cancellation details from subscription object.
		$canceled_at           = isset( $subscription['canceled_at'] ) && is_numeric( $subscription['canceled_at'] ) ? intval( $subscription['canceled_at'] ) : time();
		$cancellation_details  = isset( $subscription['cancellation_details'] ) && is_array( $subscription['cancellation_details'] ) ? $subscription['cancellation_details'] : [];
		$cancellation_reason   = ! empty( $cancellation_details['reason'] ) && is_string( $cancellation_details['reason'] ) ? sanitize_text_field( $cancellation_details['reason'] ) : '';
		$cancellation_feedback = ! empty( $cancellation_details['feedback'] ) && is_string( $cancellation_details['feedback'] ) ? sanitize_text_field( $cancellation_details['feedback'] ) : '';
		$status                = ! empty( $subscription['status'] ) && is_string( $subscription['status'] ) ? sanitize_text_field( $subscription['status'] ) : 'canceled';

		// Prepare log entry for subscription cancellation.
		$current_logs = isset( $subscription_record['log'] ) && is_array( $subscription_record['log'] ) ? $subscription_record['log'] : [];
		$log_messages = [
			/* translators: %s: Subscription ID */
			sprintf( __( 'Subscription ID: %s', 'sureforms' ), $subscription_id ),
			/* translators: %s: Payment Gateway */
			sprintf( __( 'Payment Gateway: %s', 'sureforms' ), 'Stripe' ),
			/* translators: %s: Status */
			sprintf( __( 'Status: %s', 'sureforms' ), ucfirst( $status ) ),
			/* translators: %s: Canceled date */
			sprintf( __( 'Canceled at: %s', 'sureforms' ), gmdate( 'Y-m-d H:i:s', $canceled_at ) ),
		];

		if ( ! empty( $cancellation_reason ) ) {
			$log_messages[] = sprintf(
				/* translators: %s: Cancellation reason */
				__( 'Cancellation Reason: %s', 'sureforms' ),
				ucfirst( str_replace( '_', ' ', $cancellation_reason ) )
			);
		}

		if ( ! empty( $cancellation_feedback ) ) {
			$log_messages[] = sprintf(
				/* translators: %s: Cancellation feedback */
				__( 'Feedback: %s', 'sureforms' ),
				ucfirst( str_replace( '_', ' ', $cancellation_feedback ) )
			);
		}

		$new_log = [
			'title'      => __( 'Subscription Canceled', 'sureforms' ),
			'created_at' => current_time( 'mysql' ),
			'messages'   => $log_messages,
		];

		$current_logs[] = $new_log;

		// Update subscription record with canceled status.
		$update_data = [
			'status' => 'canceled',
			'log'    => $current_logs,
		];

		$result = Payments::update( $subscription_db_id, $update_data );

		if ( false === $result ) {
			Helper::srfm_log( 'Failed to update subscription record for cancellation. Subscription ID: ' . $subscription_id . ', DB ID: ' . $subscription_db_id . '.' );
			return;
		}

		Helper::srfm_log(
			sprintf(
				'Subscription canceled successfully. Subscription ID: %s, DB ID: %d, Canceled at: %s.',
				$subscription_id,
				$subscription_db_id,
				gmdate( 'Y-m-d H:i:s', $canceled_at )
			)
		);
	}

	/**
	 * Update refund data for a payment.
	 *
	 * @param int                  $payment_id Payment ID.
	 * @param array<string, mixed> $refund_response Refund response data.
	 * @param int|float|string     $refund_amount Refund amount in cents.
	 * @param string               $currency Currency code.
	 * @param string|null          $payment Payment method.
	 * @since 2.0.0
	 * @return bool Whether the update was successful.
	 */
	public function update_refund_data( $payment_id, $refund_response, $refund_amount, $currency, $payment = null ) {
		if ( empty( $payment_id ) || empty( $refund_response ) ) {
			return false;
		}

		// Get payment record if not provided.
		$payment = Payments::get( $payment_id );
		if ( ! $payment ) {
			Helper::srfm_log( 'Payment record not found for ID: ' . $payment_id . '.' );
			return false;
		}

		$check_if_refund_already_exists = $this->check_if_refund_already_exists( $payment, $refund_response );
		if ( $check_if_refund_already_exists ) {
			return true;
		}

		// Prepare refund data for payment_data column.
		$refund_data = [
			'refund_id'      => ! empty( $refund_response['id'] ) && is_string( $refund_response['id'] ) ? sanitize_text_field( $refund_response['id'] ) : '',
			'amount'         => absint( $refund_amount ),
			'currency'       => sanitize_text_field( strtoupper( $currency ) ),
			'status'         => ! empty( $refund_response['status'] ) && is_string( $refund_response['status'] ) ? sanitize_text_field( $refund_response['status'] ) : 'processed',
			'created'        => time(),
			'reason'         => ! empty( $refund_response['reason'] ) && is_string( $refund_response['reason'] ) ? sanitize_text_field( $refund_response['reason'] ) : 'requested_by_customer',
			'description'    => ! empty( $refund_response['description'] ) && is_string( $refund_response['description'] ) ? sanitize_text_field( $refund_response['description'] ) : '',
			'receipt_number' => ! empty( $refund_response['receipt_number'] ) && is_string( $refund_response['receipt_number'] ) ? sanitize_text_field( $refund_response['receipt_number'] ) : '',
			'refunded_by'    => 'stripe_dashboard',
			'refunded_at'    => gmdate( 'Y-m-d H:i:s' ),
		];

		// Validate refund amount to prevent over-refunding.
		$original_amount    = floatval( $payment['total_amount'] );
		$existing_refunds   = floatval( $payment['refunded_amount'] ?? 0 ); // Use column directly.
		$new_refund_amount  = Stripe_Helper::amount_from_stripe_format( $refund_amount, $currency );
		$total_after_refund = $existing_refunds + $new_refund_amount;

		if ( $total_after_refund > $original_amount ) {
			Helper::srfm_log(
				sprintf(
					'Over-refund attempt blocked. Payment ID: %d, Original: $%s, Existing refunds: $%s, New refund: $%s.',
					$payment_id,
					number_format( $original_amount, 2 ),
					number_format( $existing_refunds, 2 ),
					number_format( $new_refund_amount, 2 )
				)
			);
			return false;
		}

		// Add refund data to payment_data column (for audit trail).
		$payment_data_result = Payments::add_refund_to_payment_data( $payment_id, $refund_data );

		// Update the refunded_amount column.
		$refund_amount_result = Payments::add_refund_amount( $payment_id, $new_refund_amount );

		// Calculate appropriate payment status.
		$payment_status = 'succeeded'; // Default to current status.
		if ( $total_after_refund >= $original_amount ) {
			$payment_status = 'refunded'; // Fully refunded.
		} elseif ( $total_after_refund > 0 ) {
			$payment_status = 'partially_refunded'; // Partially refunded.
		}

		// Update payment status and log.
		$current_logs   = Helper::get_array_value( $payment['log'] );
		$refund_type    = $total_after_refund >= $original_amount ? __( 'Full', 'sureforms' ) : __( 'Partial', 'sureforms' );
		$new_log        = [
			// translators: %s: Refund type (e.g., Full, Partial).
			'title'      => sprintf( __( '%s Payment Refund', 'sureforms' ), $refund_type ),
			'created_at' => current_time( 'mysql' ),
			'messages'   => [
				// translators: %s: Refund ID.
				sprintf( __( 'Refund ID: %s', 'sureforms' ), ! empty( $refund_response['id'] ) && is_string( $refund_response['id'] ) ? sanitize_text_field( $refund_response['id'] ) : 'N/A' ),
				// translators: %s: Payment gateway name (e.g., Stripe).
				sprintf( __( 'Payment Gateway: %s', 'sureforms' ), 'Stripe' ),
				// translators: 1: Refund amount, 2: Currency.
				sprintf( __( 'Refund Amount: %1$s %2$s', 'sureforms' ), number_format( Stripe_Helper::amount_from_stripe_format( $refund_amount, $currency ), 2 ), strtoupper( $currency ) ),
				sprintf(
					/* translators: 1: Total refunded amount, 2: Currency, 3: Original amount, 4: Currency */
					__( 'Total Refunded: %1$s %2$s of %3$s %4$s', 'sureforms' ),
					number_format( $total_after_refund, 2 ),
					strtoupper( $currency ),
					number_format( $original_amount, 2 ),
					strtoupper( $currency )
				),
				// translators: %s: Refund status (e.g., succeeded, failed).
				sprintf( __( 'Refund Status: %s', 'sureforms' ), ! empty( $refund_response['status'] ) && is_string( $refund_response['status'] ) ? sanitize_text_field( $refund_response['status'] ) : 'processed' ),
				// translators: %s: Payment status (e.g., refunded, partially refunded).
				sprintf( __( 'Payment Status: %s', 'sureforms' ), ucfirst( str_replace( '_', ' ', $payment_status ) ) ),
				// translators: %s: Refunded by method (e.g., Webhook).
				sprintf( __( 'Refunded by: %s', 'sureforms' ), __( 'Webhook', 'sureforms' ) ),
			],
		];
		$current_logs[] = $new_log;

		$update_data = [
			'status' => $payment_status,
			'log'    => $current_logs,
		];

		// Update payment record with status and log.
		$payment_update_result = Payments::update( $payment_id, $update_data );

		// Check if all operations succeeded.
		if ( false === $payment_data_result ) {
			Helper::srfm_log( 'Failed to store refund data in payment_data for payment ID: ' . $payment_id . '.' );
		}

		if ( false === $refund_amount_result ) {
			Helper::srfm_log( 'Failed to update refunded_amount column for payment ID: ' . $payment_id . '.' );
			return false;
		}

		if ( false === $payment_update_result ) {
			Helper::srfm_log( 'Failed to update payment status and log for payment ID: ' . $payment_id . '.' );
			return false;
		}

		Helper::srfm_log(
			sprintf(
				/* translators: %d: Payment ID, %s: Refund ID, %s: Amount, %s: Currency */
				'Refund processed successfully. Payment ID: %d, Refund ID: %s, Amount: %s %s.',
				$payment_id,
				$refund_data['refund_id'],
				Stripe_Helper::amount_from_stripe_format( $refund_amount, $currency ),
				$currency
			)
		);

		return true;
	}

	/**
	 * Process refund cancellation - reverses a previously processed refund.
	 *
	 * @param int                  $payment_id Payment ID.
	 * @param array<string, mixed> $refund Refund object from Stripe webhook.
	 * @param string               $currency Currency code.
	 * @param string               $lookup_method How the payment was found.
	 * @since 2.0.0
	 * @return void
	 */
	private function process_refund_cancellation( $payment_id, $refund, $currency, $lookup_method ) {
		$refund_id = ! empty( $refund['id'] ) && is_string( $refund['id'] ) ? sanitize_text_field( $refund['id'] ) : '';

		Helper::srfm_log( 'Processing refund cancellation for refund ID: ' . $refund_id );

		// Get payment record.
		$payment = Payments::get( $payment_id );
		if ( ! $payment ) {
			Helper::srfm_log( 'REFUND CANCELLATION FAILED: Payment record not found for ID: ' . $payment_id );
			return;
		}

		// Get payment_data and check if refund exists.
		$payment_data = Helper::get_array_value( $payment['payment_data'] ?? [] );
		$refunds      = isset( $payment_data['refunds'] ) && is_array( $payment_data['refunds'] ) ? $payment_data['refunds'] : [];

		// Check if the refund exists in the payment data.
		if ( ! isset( $refunds[ $refund_id ] ) ) {
			Helper::srfm_log(
				sprintf(
					'REFUND CANCELLATION SKIPPED: Refund ID %s not found in payment data for payment ID %d. It may have already been canceled or never existed.',
					$refund_id,
					$payment_id
				)
			);
			return;
		}

		// Get the refund data to extract the amount and currency.
		$existing_refund              = $refunds[ $refund_id ];
		$canceled_refund_amount_cents = isset( $existing_refund['amount'] ) && is_numeric( $existing_refund['amount'] ) ? $existing_refund['amount'] : 0;
		$refund_currency              = ! empty( $existing_refund['currency'] ) && is_string( $existing_refund['currency'] ) ? sanitize_text_field( strtolower( $existing_refund['currency'] ) ) : $currency;

		if ( $canceled_refund_amount_cents <= 0 ) {
			Helper::srfm_log( 'REFUND CANCELLATION FAILED: Invalid refund amount in existing refund data.' );
			return;
		}

		// Convert from Stripe format (cents) to decimal format (handles zero-decimal currencies).
		$canceled_refund_amount = Stripe_Helper::amount_from_stripe_format( $canceled_refund_amount_cents, $refund_currency );

		// Remove the refund from payment_data.
		unset( $refunds[ $refund_id ] );
		$payment_data['refunds'] = $refunds;

		// Update payment_data in database.
		$payment_data_update = Payments::update(
			$payment_id,
			[
				'payment_data' => $payment_data,
			]
		);

		if ( false === $payment_data_update ) {
			Helper::srfm_log( 'REFUND CANCELLATION FAILED: Could not update payment_data for payment ID: ' . $payment_id );
			return;
		}

		// Subtract the refund amount from refunded_amount column.
		$current_refunded_amount = floatval( $payment['refunded_amount'] ?? 0 );
		$new_refunded_amount     = max( 0, $current_refunded_amount - $canceled_refund_amount );

		$refund_amount_update = Payments::update(
			$payment_id,
			[
				'refunded_amount' => $new_refunded_amount,
			]
		);

		if ( false === $refund_amount_update ) {
			Helper::srfm_log( 'REFUND CANCELLATION FAILED: Could not update refunded_amount for payment ID: ' . $payment_id );
			return;
		}

		// Recalculate payment status based on new refunded amount.
		$original_amount = floatval( $payment['total_amount'] );
		$payment_status  = 'succeeded'; // Default.

		if ( $new_refunded_amount >= $original_amount ) {
			$payment_status = 'refunded'; // Fully refunded.
		} elseif ( $new_refunded_amount > 0 ) {
			$payment_status = 'partially_refunded'; // Partially refunded.
		}

		// Extract failure reason from refund object.
		$failure_reason = ! empty( $refund['failure_reason'] ) && is_string( $refund['failure_reason'] ) ? sanitize_text_field( $refund['failure_reason'] ) : 'unknown';

		// Add log entry for the cancellation.
		$current_logs = Helper::get_array_value( $payment['log'] );
		$new_log      = [
			'title'      => __( 'Refund Canceled', 'sureforms' ),
			'created_at' => current_time( 'mysql' ),
			'messages'   => [
				// translators: %s: Refund ID.
				sprintf( __( 'Refund ID: %s', 'sureforms' ), $refund_id ),
				// translators: %s: Payment gateway name (e.g., Stripe).
				sprintf( __( 'Payment Gateway: %s', 'sureforms' ), 'Stripe' ),
				// translators: 1: Canceled amount, 2: Currency.
				sprintf( __( 'Canceled Refund Amount: %1$s %2$s', 'sureforms' ), number_format( $canceled_refund_amount, 2 ), strtoupper( $refund_currency ) ),
				sprintf(
					/* translators: 1: Remaining refunded amount, 2: Currency, 3: Original amount, 4: Currency */
					__( 'Remaining Refunded: %1$s %2$s of %3$s %4$s', 'sureforms' ),
					number_format( $new_refunded_amount, 2 ),
					strtoupper( $refund_currency ),
					number_format( $original_amount, 2 ),
					strtoupper( $refund_currency )
				),
				// translators: %s: Failure reason.
				sprintf( __( 'Cancellation Reason: %s', 'sureforms' ), ucfirst( str_replace( '_', ' ', $failure_reason ) ) ),
				// translators: %s: Payment status (e.g., succeeded, partially refunded).
				sprintf( __( 'Payment Status: %s', 'sureforms' ), ucfirst( str_replace( '_', ' ', $payment_status ) ) ),
				// translators: %s: Canceled by method (e.g., Webhook).
				sprintf( __( 'Canceled by: %s', 'sureforms' ), __( 'Webhook', 'sureforms' ) ),
			],
		];

		$current_logs[] = $new_log;

		// Update payment status and log.
		$status_update = Payments::update(
			$payment_id,
			[
				'status' => $payment_status,
				'log'    => $current_logs,
			]
		);

		if ( false === $status_update ) {
			Helper::srfm_log( 'REFUND CANCELLATION: Updated amounts but failed to update payment status and log for payment ID: ' . $payment_id );
			return;
		}

		Helper::srfm_log(
			sprintf(
				'REFUND CANCELLATION SUCCESS: Refund ID: %s, Canceled Amount: %s %s, Remaining Refunded: %s %s, Payment Status: %s, Payment ID: %d (found via %s)',
				$refund_id,
				number_format( $canceled_refund_amount, 2 ),
				strtoupper( $refund_currency ),
				number_format( $new_refunded_amount, 2 ),
				strtoupper( $refund_currency ),
				$payment_status,
				$payment_id,
				$lookup_method
			)
		);
	}

	/**
	 * Extracts subscription ID from invoice object with backward compatibility.
	 * Handles both old and new Stripe API structures.
	 *
	 * @param array<string, mixed> $invoice Invoice object from Stripe.
	 * @since 2.0.0
	 * @return string Subscription ID or empty string if not found.
	 */
	private function extract_subscription_id_from_invoice( $invoice ) {
		// Method 1: Parent subscription_details (new API structure - 2025+).
		$subscription_parent  = isset( $invoice['parent'] ) && is_array( $invoice['parent'] ) ? $invoice['parent'] : [];
		$subscription_details = isset( $subscription_parent['subscription_details'] ) && is_array( $subscription_parent['subscription_details'] ) ? $subscription_parent['subscription_details'] : [];
		$subscription         = isset( $subscription_details['subscription'] ) && is_string( $subscription_details['subscription'] ) ? sanitize_text_field( $subscription_details['subscription'] ) : '';
		if ( ! empty( $subscription ) ) {
			Helper::srfm_log( 'Subscription ID found at: $invoice[\'parent\'][\'subscription_details\'][\'subscription\'].' );
			return $subscription;
		}

		// Not found - log invoice structure for debugging.
		Helper::srfm_log(
			sprintf(
				'Subscription ID not found in invoice. Invoice ID: %s, Keys present: %s.',
				! empty( $invoice['id'] ) && is_string( $invoice['id'] ) ? sanitize_text_field( $invoice['id'] ) : 'unknown',
				implode( ', ', array_keys( $invoice ) )
			)
		);

		return '';
	}

	/**
	 * Extracts charge ID from invoice object with backward compatibility.
	 * Falls back to payment_intent, fetching from API, or invoice ID if charge is not available.
	 *
	 * @param array<string, mixed> $invoice Invoice object from Stripe.
	 * @since 2.0.0
	 * @return string Charge ID, payment_intent, or invoice ID.
	 */
	private function extract_charge_id_from_invoice( $invoice ) {
		// Method 1: Direct charge field (old API structure and most common).
		if ( ! empty( $invoice['charge'] ) ) {
			Helper::srfm_log( 'Charge ID found at: $invoice[\'charge\'].' );
			return ! empty( $invoice['charge'] ) && is_string( $invoice['charge'] ) ? sanitize_text_field( $invoice['charge'] ) : '';
		}

		// Method 2: Payment intent (alternative in newer API or pending payments).
		if ( ! empty( $invoice['payment_intent'] ) ) {
			Helper::srfm_log( 'Charge ID not found, using payment_intent as transaction ID: $invoice[\'payment_intent\'].' );
			return ! empty( $invoice['payment_intent'] ) && is_string( $invoice['payment_intent'] ) ? sanitize_text_field( $invoice['payment_intent'] ) : '';
		}

		// Method 3: Fetch invoice from Stripe API to get charge ID.
		if ( ! empty( $invoice['id'] ) ) {
			$invoice_id = ! empty( $invoice['id'] ) && is_string( $invoice['id'] ) ? sanitize_text_field( $invoice['id'] ) : '';
			Helper::srfm_log( 'Attempting to fetch charge ID from Stripe API using invoice ID: ' . $invoice_id . '.' );

			$api_response = Stripe_Helper::stripe_api_request( 'invoices', 'GET', [], $invoice_id, [ 'mode' => $this->mode ] );

			if ( $api_response['success'] && ! empty( $api_response['data']['charge'] ) ) {
				$charge_id = sanitize_text_field( $api_response['data']['charge'] );
				Helper::srfm_log( 'Charge ID successfully retrieved from Stripe API: ' . $charge_id . '.' );
				return $charge_id;
			}

			Helper::srfm_log( 'Failed to retrieve charge ID from Stripe API. Response: ' . wp_json_encode( $api_response ) . '.' );
		}

		// Method 4: Invoice ID as absolute last resort (for tracking purposes).
		if ( ! empty( $invoice['id'] ) ) {
			Helper::srfm_log( 'WARNING: Using invoice ID as transaction ID (last resort): $invoice[\'id\'].' );
			return ! empty( $invoice['id'] ) && is_string( $invoice['id'] ) ? sanitize_text_field( $invoice['id'] ) : '';
		}

		Helper::srfm_log( 'CRITICAL: No transaction identifier found in invoice object.' );
		return '';
	}

	/**
	 * Process initial subscription payment.
	 *
	 * @param array<string, mixed> $subscription_record Subscription record from database.
	 * @param array<string, mixed> $invoice Invoice object from Stripe.
	 * @param string               $charge_id Charge ID from Stripe.
	 * @since 2.0.0
	 * @return void
	 */
	private function process_initial_subscription_payment( $subscription_record, $invoice, $charge_id ) {
		$subscription_id = ! empty( $subscription_record['id'] ) && is_numeric( $subscription_record['id'] ) ? intval( $subscription_record['id'] ) : 0;

		if ( ! $subscription_id ) {
			Helper::srfm_log( 'Invalid subscription record for initial payment processing.' );
			return;
		}

		// Update subscription record with transaction ID and set status to active.
		$update_data = [
			'transaction_id' => $charge_id,
			'status'         => 'succeeded',
		];

		// Generate srfm_txn_id if it's not already set.
		$current_srfm_txn_id = ! empty( $subscription_record['srfm_txn_id'] ) && is_string( $subscription_record['srfm_txn_id'] ) ? sanitize_text_field( $subscription_record['srfm_txn_id'] ) : '';
		if ( empty( $current_srfm_txn_id ) ) {
			$unique_payment_id          = Stripe_Helper::generate_unique_payment_id( $subscription_id );
			$update_data['srfm_txn_id'] = $unique_payment_id;
			Helper::srfm_log( 'Generated srfm_txn_id for initial subscription payment: ' . $unique_payment_id . '.' );
		}

		$currency       = isset( $invoice['currency'] ) && is_string( $invoice['currency'] ) ? sanitize_text_field( strtolower( $invoice['currency'] ) ) : 'usd';
		$invoice_amount = isset( $invoice['amount_paid'] ) && ( is_numeric( $invoice['amount_paid'] ) || is_float( $invoice['amount_paid'] ) || is_string( $invoice['amount_paid'] ) ) ? $invoice['amount_paid'] : 0;
		$invoice_id     = isset( $invoice['id'] ) && is_string( $invoice['id'] ) ? sanitize_text_field( $invoice['id'] ) : '';

		// Add log entry for initial payment success.
		$current_logs       = isset( $subscription_record['log'] ) && is_array( $subscription_record['log'] ) ? $subscription_record['log'] : [];
		$new_log            = [
			'title'      => __( 'Initial Subscription Payment Succeeded', 'sureforms' ),
			'created_at' => current_time( 'mysql' ),
			'messages'   => [
				/* translators: %s: Charge ID */
				sprintf( __( 'Charge ID: %s', 'sureforms' ), $charge_id ),
				/* translators: %s: Invoice ID */
				sprintf( __( 'Invoice ID: %s', 'sureforms' ), $invoice_id ),
				sprintf(
					/* translators: 1: Amount, 2: Currency */
					__( 'Amount: %1$s %2$s', 'sureforms' ),
					number_format( Stripe_Helper::amount_from_stripe_format( $invoice_amount, $currency ), 2 ),
					strtoupper( $currency )
				),
				__( 'Payment Status: Succeeded', 'sureforms' ),
				__( 'Subscription Status: Active', 'sureforms' ),
			],
		];
		$current_logs[]     = $new_log;
		$update_data['log'] = $current_logs;

		$result = Payments::update( $subscription_id, $update_data );

		if ( false === $result ) {
			Helper::srfm_log( 'Failed to update subscription record for initial payment. Subscription ID: ' . $subscription_id . '.' );
		} else {
			Helper::srfm_log( 'Initial subscription payment processed successfully. Subscription ID: ' . $subscription_id . '.' );
		}
	}

	/**
	 * Process subscription renewal payment.
	 *
	 * @param array<string, mixed> $subscription_record Subscription record from database.
	 * @param array<string, mixed> $invoice Invoice object from Stripe.
	 * @param string               $charge_id Charge ID from Stripe.
	 * @param string               $block_id Block ID from metadata.
	 * @since 2.0.0
	 * @return void
	 */
	private function process_subscription_renewal_payment( $subscription_record, $invoice, $charge_id, $block_id ) {
		$customer_id    = ! empty( $subscription_record['customer_id'] ) && is_string( $subscription_record['customer_id'] ) ? sanitize_text_field( $subscription_record['customer_id'] ) : '';
		$customer_email = ! empty( $subscription_record['customer_email'] ) && is_string( $subscription_record['customer_email'] ) ? sanitize_email( $subscription_record['customer_email'] ) : '';
		$customer_name  = ! empty( $subscription_record['customer_name'] ) && is_string( $subscription_record['customer_name'] ) ? sanitize_text_field( $subscription_record['customer_name'] ) : '';

		$invoice_amount = isset( $invoice['amount_paid'] ) && ( is_numeric( $invoice['amount_paid'] ) || is_float( $invoice['amount_paid'] ) || is_string( $invoice['amount_paid'] ) ) ? $invoice['amount_paid'] : 0;
		$currency       = isset( $invoice['currency'] ) && is_string( $invoice['currency'] ) ? sanitize_text_field( strtolower( $invoice['currency'] ) ) : 'usd';
		$amount_paid    = Stripe_Helper::amount_from_stripe_format( $invoice_amount, $currency );

		$block_id = empty( $block_id ) || ! is_string( $block_id ) ? '' : $block_id;
		$block_id = empty( $block_id ) && ! empty( $subscription_record['block_id'] ) && is_string( $subscription_record['block_id'] ) ? sanitize_text_field( $subscription_record['block_id'] ) : '';

		$form_id  = ! empty( $subscription_record['form_id'] ) && is_numeric( $subscription_record['form_id'] ) ? intval( $subscription_record['form_id'] ) : 0;
		$entry_id = ! empty( $subscription_record['entry_id'] ) && is_numeric( $subscription_record['entry_id'] ) ? intval( $subscription_record['entry_id'] ) : 0;

		$subscription_id = ! empty( $subscription_record['subscription_id'] ) && is_string( $subscription_record['subscription_id'] ) ? $subscription_record['subscription_id'] : '';

		$invoice_id     = ! empty( $invoice['id'] ) && is_string( $invoice['id'] ) ? sanitize_text_field( $invoice['id'] ) : '';
		$payment_intent = ! empty( $invoice['payment_intent'] ) && is_string( $invoice['payment_intent'] ) ? sanitize_text_field( $invoice['payment_intent'] ) : '';
		$billing_reason = ! empty( $invoice['billing_reason'] ) && is_string( $invoice['billing_reason'] ) ? sanitize_text_field( $invoice['billing_reason'] ) : '';

		$logs = [
			[
				'title'      => __( 'Subscription Charge Payment', 'sureforms' ),
				'created_at' => current_time( 'mysql' ),
				'messages'   => [
					/* translators: %s: Charge ID */
					sprintf( __( 'Transaction ID: %s', 'sureforms' ), $charge_id ),
					/* translators: %s: Payment Gateway */
					sprintf( __( 'Payment Gateway: %s', 'sureforms' ), 'Stripe' ),
					/* translators: 1: Amount, 2: Currency */
					sprintf( __( 'Amount: %1$s %2$s', 'sureforms' ), $amount_paid, strtoupper( $currency ) ),
					/* translators: %s: Status */
					sprintf( __( 'Status: %s', 'sureforms' ), __( 'Succeeded', 'sureforms' ) ),
					/* translators: %s: Subscription ID */
					sprintf( __( 'Subscription ID: %s', 'sureforms' ), $subscription_id ),
					/* translators: %s: Invoice ID */
					sprintf( __( 'Invoice ID: %s', 'sureforms' ), $invoice_id ),
					/* translators: %s: Customer ID */
					sprintf( __( 'Customer ID: %s', 'sureforms' ), $customer_id ),
					/* translators: %s: Customer Email */
					sprintf( __( 'Customer Email: %s', 'sureforms' ), $customer_email ),
					/* translators: %s: Customer Name */
					sprintf( __( 'Customer Name: %s', 'sureforms' ), $customer_name ),
					__( 'Created via subscription billing cycle', 'sureforms' ),
				],
			],
		];

		// Get parent subscription database ID for linking renewal payments.
		$parent_subscription_db_id = ! empty( $subscription_record['id'] ) && is_numeric( $subscription_record['id'] ) ? intval( $subscription_record['id'] ) : 0;

		// Prepare renewal payment data.
		$payment_data = [
			'form_id'                => $form_id,
			'block_id'               => $block_id,
			'status'                 => 'succeeded',
			'total_amount'           => $amount_paid,
			'currency'               => $currency,
			'entry_id'               => $entry_id,
			'type'                   => 'renewal',
			'transaction_id'         => $charge_id,
			'gateway'                => 'stripe',
			'mode'                   => $this->mode,
			'subscription_id'        => $subscription_id,
			'parent_subscription_id' => $parent_subscription_db_id,
			'srfm_txn_id'            => '', // Will be updated after getting payment entry ID.
			'customer_email'         => $customer_email,
			'customer_name'          => $customer_name,
			'customer_id'            => $customer_id,
			'payment_data'           => [
				'invoice_id'     => $invoice_id,
				'payment_intent' => $payment_intent,
				'billing_reason' => $billing_reason,
				'amount_paid'    => $amount_paid,
			],
			'log'                    => $logs,
		];

		// Create the renewal payment record.
		$payment_entry_id = Payments::add( $payment_data );

		if ( $payment_entry_id ) {
			// Generate unique payment ID using the auto-increment ID and update the entry.
			$unique_payment_id = Stripe_Helper::generate_unique_payment_id( $payment_entry_id );
			Payments::update( $payment_entry_id, [ 'srfm_txn_id' => $unique_payment_id ] );
			Helper::srfm_log( 'Renewal payment record created with srfm_txn_id: ' . $unique_payment_id . ', Payment ID: ' . $payment_entry_id . '.' );
		} else {
			Helper::srfm_log( 'Failed to create renewal payment record.' );
		}
	}

	/**
	 * Check if the refund already exists.
	 *
	 * @param array<string, mixed> $payment Payment record data.
	 * @param array<string, mixed> $refund Refund response from Stripe.
	 * @return bool True if refund already exists, false otherwise.
	 * @since 2.0.0
	 */
	private function check_if_refund_already_exists( $payment, $refund ) {
		$refund_id = $refund['id'] ?? '';

		if ( empty( $refund_id ) ) {
			return false;
		}

		// Use Helper::get_array_value() to handle stdClass objects.
		$payment_data = Helper::get_array_value( $payment['payment_data'] ?? [] );

		if ( empty( $payment_data['refunds'] ) ) {
			return false;
		}

		// O(1) lookup using refund ID as array key.
		return isset( $payment_data['refunds'][ $refund_id ] );
	}
}
