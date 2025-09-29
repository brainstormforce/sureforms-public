<?php
/**
 * Admin Stripe Handler for SureForms
 *
 * Handles admin-related Stripe operations including refunds for payments and subscriptions.
 *
 * @package SureForms
 * @since 1.0.0
 */

namespace SRFM\Inc\Payments\Stripe;

use SRFM\Inc\Database\Tables\Payments;
use SRFM\Inc\Helper;
use SRFM\Inc\Traits\Get_Instance;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Stripe Handler class
 *
 * Manages admin operations for Stripe payments including refunds, cancellations,
 * and payment management for both one-time and subscription payments.
 */
class Admin_Stripe_Handler {
	use Get_Instance;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * AJAX handler for subscription cancellation (following WPForms pattern)
	 *
	 * @since 1.0.0
	 */
	public function ajax_cancel_subscription() {
		// Security checks
		if ( ! isset( $_POST['payment_id'] ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Missing payment ID.', 'sureforms' ) ] );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'You are not allowed to perform this action.', 'sureforms' ) ] );
		}

		check_ajax_referer( 'sureforms_admin_nonce', 'nonce' );

		$payment_id = absint( $_POST['payment_id'] );

		// Get payment record
		$payment = Payments::get( $payment_id );
		if ( ! $payment ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Payment not found in the database.', 'sureforms' ) ] );
		}

		// Validate it's a subscription payment
		if ( empty( $payment['type'] ) || 'subscription' !== $payment['type'] ) {
			wp_send_json_error( [ 'message' => esc_html__( 'This is not a subscription payment.', 'sureforms' ) ] );
		}

		if ( empty( $payment['subscription_id'] ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Subscription ID not found.', 'sureforms' ) ] );
		}

		// Cancel the subscription
		$cancel_result = $this->cancel_subscription( $payment['subscription_id'] );
		if ( ! $cancel_result ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Subscription cancellation failed.', 'sureforms' ) ] );
		}

		// Update database status to cancelled (following WPForms pattern)
		$updated = Payments::update( $payment_id, [ 'subscription_status' => 'cancelled' ] );
		if ( ! $updated ) {
			error_log( 'SureForms: Failed to update subscription status to cancelled in database' );
			wp_send_json_error( [ 'message' => esc_html__( 'Failed to update subscription status in database.', 'sureforms' ) ] );
		}

		// Add log entry
		$log_message = sprintf(
			'Subscription cancelled from SureForms dashboard. Subscription ID: %s',
			$payment['subscription_id']
		);
		error_log( 'SureForms: ' . $log_message );

		wp_send_json_success( [ 'message' => esc_html__( 'Subscription cancelled successfully.', 'sureforms' ) ] );
	}

	/**
	 * Process payment refund
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function refund_payment() {
		// Verify nonce
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'srfm_payment_admin_nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce.', 'sureforms' ) );
			return;
		}

		// Check if user has permission to refund payments (admin or manage_options capability)
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', 'sureforms' ) );
			return;
		}

		$payment_id     = intval( $_POST['payment_id'] ?? 0 );
		$transaction_id = sanitize_text_field( wp_unslash( $_POST['transaction_id'] ?? '' ) );

		$refund_amount = isset( $_POST['refund_amount'] ) ? absint( $_POST['refund_amount'] ) : 0;
		if ( $refund_amount <= 0 ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid refund amount.', 'sureforms' ) ] );
		}

		if ( empty( $payment_id ) || empty( $transaction_id ) || $refund_amount <= 0 ) {
			wp_send_json_error( __( 'Invalid payment data.', 'sureforms' ) );
			return;
		}

		try {
			// Get payment from database
			$payment = Payments::get( $payment_id );
			if ( ! $payment ) {
				wp_send_json_error( __( 'Payment not found.', 'sureforms' ) );
				return;
			}

			// Detect subscription payments and route to specialized handler (following WPForms pattern)
			if ( ! empty( $payment['type'] ) && ! empty( $payment['subscription_id'] ) ) {
				error_log( 'SureForms: Routing subscription payment to specialized refund handler' );
				$this->refund_subscription_payment( $payment, $refund_amount );
				return;
			}

			// Verify payment status (for one-time payments)
			if ( 'succeeded' !== $payment['status'] && 'partially_refunded' !== $payment['status'] ) {
				wp_send_json_error( __( 'Only succeeded or partially refunded payments can be refunded.', 'sureforms' ) );
				return;
			}

			// Verify transaction ID matches
			if ( $transaction_id !== $payment['transaction_id'] ) {
				wp_send_json_error( __( 'Transaction ID mismatch.', 'sureforms' ) );
				return;
			}

			// Get payment settings
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

			// Create refund using Stripe API directly
			$stripe_refund_data = [
				'amount'   => $refund_amount,
				'metadata' => [
					'source'      => 'SureForms',
					'payment_id'  => $payment_id,
					'refunded_at' => time(),
					'refunded_by' => get_current_user_id(),
				],
			];

			// Determine if we're refunding by charge ID or payment intent ID
			if ( strpos( $transaction_id, 'ch_' ) === 0 ) {
				// Refunding by charge ID
				$stripe_refund_data['charge'] = $transaction_id;
			} elseif ( strpos( $transaction_id, 'pi_' ) === 0 ) {
				// Refunding by payment intent ID
				$stripe_refund_data['payment_intent'] = $transaction_id;
			} else {
				throw new \Exception( __( 'Invalid transaction ID format for refund.', 'sureforms' ) );
			}

			$refund = $this->stripe_api_request( 'refunds', 'POST', $stripe_refund_data );

			if ( false === $refund ) {
				throw new \Exception( __( 'Failed to process refund through Stripe API.', 'sureforms' ) );
			}

			if ( empty( $refund ) || isset( $refund['status'] ) && 'error' === $refund['status'] ) {
				$error_message = $refund['message'] ?? __( 'Unknown refund error.', 'sureforms' );
				throw new \Exception( $error_message );
			}

			// Store refund data and update payment status/log
			$refund_stored = $this->update_refund_data( $payment_id, $refund, $refund_amount, $payment['currency'] );
			if ( ! $refund_stored ) {
				throw new \Exception( __( 'Failed to update payment record after refund.', 'sureforms' ) );
			}

			wp_send_json_success(
				[
					'message'   => __( 'Payment refunded successfully.', 'sureforms' ),
					'refund_id' => $refund['id'] ?? '',
					'status'    => $refund['status'] ?? 'processed',
				]
			);

		} catch ( \Exception $e ) {
			error_log( 'SureForms Refund Error: ' . $e->getMessage() );
			wp_send_json_error( __( 'Failed to process refund. Please try again.', 'sureforms' ) );
		}
	}

	/**
	 * Cancel subscription (following WPForms pattern)
	 *
	 * @param string $subscription_id Subscription ID.
	 * @return bool Success status.
	 * @since 1.0.0
	 */
	public function cancel_subscription( $subscription_id ) {
		try {
			error_log( 'SureForms: Attempting to cancel subscription: ' . $subscription_id );

			// Retrieve and cancel subscription using direct Stripe API
			$subscription = $this->stripe_api_request( 'subscriptions', 'GET', [], $subscription_id );

			if ( ! $subscription ) {
				error_log( 'SureForms: Subscription not found: ' . $subscription_id );
				return false;
			}

			// Update subscription metadata to track cancellation source
			$updated_metadata = array_merge(
				$subscription['metadata'] ?? [],
				[
					'canceled_by' => 'sureforms_dashboard',
				]
			);

			$this->stripe_api_request(
				'subscriptions',
				'POST',
				[
					'metadata' => $updated_metadata,
				],
				$subscription_id
			);

			// Cancel the subscription
			$cancelled_subscription = $this->stripe_api_request(
				'subscriptions',
				'DELETE',
				[],
				$subscription_id
			);

			if ( ! $cancelled_subscription ) {
				error_log( 'SureForms: Failed to cancel subscription: ' . $subscription_id );
				return false;
			}

			error_log( 'SureForms: Subscription cancelled successfully: ' . $subscription_id );
			return true;

		} catch ( \Exception $e ) {
			error_log( 'SureForms: General error cancelling subscription: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Update refund data in payment_data column and log
	 *
	 * @param int    $payment_id Payment record ID.
	 * @param array  $refund_response Refund response from Stripe.
	 * @param int    $refund_amount Refund amount in cents.
	 * @param string $currency Currency code.
	 * @param array  $payment Payment record data.
	 * @return bool True if successful, false otherwise.
	 * @since 1.0.0
	 */
	public function update_refund_data( $payment_id, $refund_response, $refund_amount, $currency, $payment = null ) {
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
			'refunded_by'    => sanitize_text_field( wp_get_current_user()->display_name ?? 'System' ),
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
	 * Initialize WordPress hooks
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		// AJAX handlers for admin refund operations
		add_action( 'wp_ajax_srfm_stripe_cancel_subscription', [ $this, 'ajax_cancel_subscription' ] );
		add_action( 'wp_ajax_srfm_stripe_refund_payment', [ $this, 'refund_payment' ] );
	}

	/**
	 * Refund subscription payment with enhanced validation and error handling
	 *
	 * @param array $payment Payment record.
	 * @param int   $refund_amount Refund amount in cents.
	 * @return void
	 * @since 1.0.0
	 */
	private function refund_subscription_payment( $payment, $refund_amount ) {
		$payment_id     = $payment['id'] ?? 0;
		$transaction_id = $payment['transaction_id'] ?? '';

		error_log( 'SureForms: Starting subscription refund process. Payment ID: ' . $payment_id . ', Transaction ID: ' . $transaction_id . ', Amount: ' . $refund_amount );

		try {
			// Step 1: Validate input parameters
			if ( empty( $payment ) || ! is_array( $payment ) || $refund_amount <= 0 ) {
				error_log( 'SureForms: Invalid refund parameters provided' );
				wp_send_json_error( __( 'Invalid refund parameters provided.', 'sureforms' ) );
				return;
			}

			// Step 2: Verify this is a subscription-related payment
			$is_subscription_payment = $this->is_subscription_related_payment( $payment );
			if ( ! $is_subscription_payment ) {
				error_log( 'SureForms: Payment is not subscription-related. Type: ' . ( $payment['type'] ?? 'unknown' ) . ', Subscription ID: ' . ( $payment['subscription_id'] ?? 'none' ) );
				wp_send_json_error( __( 'This payment is not related to a subscription.', 'sureforms' ) );
				return;
			}

			// Step 3: Verify subscription payment status
			$refundable_statuses = [ 'succeeded', 'partially_refunded' ];
			if ( empty( $payment['status'] ) || ! in_array( $payment['status'], $refundable_statuses, true ) ) {
				error_log( 'SureForms: Subscription payment not in refundable status. Status: ' . ( $payment['status'] ?? 'unknown' ) );
				wp_send_json_error( __( 'Only succeeded or partially refunded subscription payments can be refunded.', 'sureforms' ) );
				return;
			}

			// Step 4: Validate refund amount limits
			$validation_result = $this->validate_subscription_refund_amount( $payment, $refund_amount );
			if ( ! $validation_result['valid'] ) {
				error_log( 'SureForms: Refund amount validation failed: ' . $validation_result['message'] );
				wp_send_json_error( $validation_result['message'] );
				return;
			}

			// Step 5: Get payment settings and validate Stripe connection
			$payment_settings = get_option( 'srfm_payments_settings', [] );
			if ( empty( $payment_settings['stripe_connected'] ) ) {
				error_log( 'SureForms: Stripe is not connected' );
				throw new \Exception( __( 'Stripe is not connected.', 'sureforms' ) );
			}

			// Step 6: Create refund using appropriate method based on transaction ID type
			$refund = $this->create_subscription_refund( $payment, $transaction_id, $refund_amount );

			if ( ! $refund || empty( $refund['id'] ) ) {
				error_log( 'SureForms: Stripe refund creation returned empty result' );
				throw new \Exception( __( 'Stripe refund creation failed. Please check your Stripe dashboard for more details.', 'sureforms' ) );
			}

			// Step 7: Update database with refund information
			error_log( 'SureForms: Updating database with refund information. Refund ID: ' . $refund['id'] );
			$refund_stored = $this->update_subscription_refund_data( $payment_id, $refund, $refund_amount, $payment['currency'] );

			if ( ! $refund_stored ) {
				error_log( 'SureForms: Failed to update payment record with refund data' );
				wp_send_json_error( __( 'Refund was processed by Stripe but failed to update local records. Please check your payment records manually.', 'sureforms' ) );
				return;
			}

			// Step 8: Success response
			error_log( 'SureForms: Subscription refund completed successfully. Refund ID: ' . $refund['id'] );
			wp_send_json_success(
				[
					'message'       => __( 'Subscription payment refunded successfully.', 'sureforms' ),
					'refund_id'     => $refund['id'],
					'status'        => $refund['status'],
					'type'          => 'subscription_refund',
					'charge_id'     => $refund['charge'] ?? '',
					'refund_amount' => number_format( $refund_amount / 100, 2 ),
					'currency'      => strtoupper( $payment['currency'] ?? 'USD' ),
				]
			);

		} catch ( \Exception $e ) {
			error_log( 'SureForms Subscription Refund Error: ' . $e->getMessage() );

			// Provide more specific error messages based on error type
			$error_message = $this->get_user_friendly_refund_error( $e->getMessage() );
			wp_send_json_error( $error_message );
		}
	}

	/**
	 * Private method to make Stripe API requests
	 *
	 * @param string $endpoint Stripe API endpoint.
	 * @param string $method HTTP method (GET, POST, DELETE).
	 * @param array  $data Request data.
	 * @param string $resource_id Resource ID for the request.
	 * @return array|false API response or false on failure.
	 * @since 1.0.0
	 */
	private function stripe_api_request( $endpoint, $method = 'POST', $data = [], $resource_id = '' ) {
		// Get payment settings and Stripe keys
		$payment_settings = get_option( 'srfm_payments_settings', [] );
		if ( empty( $payment_settings['stripe_connected'] ) ) {
			error_log( 'SureForms: Stripe is not connected' );
			return false;
		}

		$payment_mode = $payment_settings['payment_mode'] ?? 'test';
		$secret_key   = 'live' === $payment_mode
			? $payment_settings['stripe_live_secret_key'] ?? ''
			: $payment_settings['stripe_test_secret_key'] ?? '';

		if ( empty( $secret_key ) ) {
			error_log( 'SureForms: Stripe secret key not found' );
			return false;
		}

		$url = 'https://api.stripe.com/v1/' . $endpoint;
		if ( ! empty( $resource_id ) ) {
			$url .= '/' . $resource_id;
		}

		$headers = [
			'Authorization' => 'Bearer ' . $secret_key,
			'Content-Type'  => 'application/x-www-form-urlencoded',
		];

		$args = [
			'method'  => $method,
			'headers' => $headers,
			'timeout' => 30,
		];

		if ( ! empty( $data ) && in_array( $method, [ 'POST', 'PUT', 'PATCH' ] ) ) {
			$args['body'] = http_build_query( $this->flatten_stripe_data( $data ) );
		} elseif ( ! empty( $data ) && 'GET' === $method ) {
			$url .= '?' . http_build_query( $this->flatten_stripe_data( $data ) );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			error_log( 'SureForms Stripe API Error: ' . $response->get_error_message() );
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$code = wp_remote_retrieve_response_code( $response );

		if ( $code >= 400 ) {
			$error_data    = json_decode( $body, true );
			$error_message = $error_data['error']['message'] ?? 'Unknown Stripe API error';
			error_log( 'SureForms Stripe API Error (' . $code . '): ' . $error_message );
			return false;
		}

		return json_decode( $body, true );
	}

	/**
	 * Flatten Stripe data for form-encoded requests
	 *
	 * @param array  $data Data to flatten.
	 * @param string $prefix Prefix for nested keys.
	 * @return array Flattened data.
	 * @since 1.0.0
	 */
	private function flatten_stripe_data( $data, $prefix = '' ) {
		$result = [];

		foreach ( $data as $key => $value ) {
			$new_key = empty( $prefix ) ? $key : $prefix . '[' . $key . ']';

			if ( is_array( $value ) && ! empty( $value ) ) {
				// Handle indexed arrays (like expand parameters)
				if ( array_keys( $value ) === range( 0, count( $value ) - 1 ) ) {
					foreach ( $value as $index => $item ) {
						$result[ $new_key . '[' . $index . ']' ] = $item;
					}
				} else {
					// Handle associative arrays (nested objects)
					$result = array_merge( $result, $this->flatten_stripe_data( $value, $new_key ) );
				}
			} else {
				$result[ $new_key ] = $value;
			}
		}

		return $result;
	}

	/**
	 * Check if payment is subscription-related
	 *
	 * @param array $payment Payment record.
	 * @return bool True if payment is subscription-related, false otherwise.
	 * @since 1.0.0
	 */
	private function is_subscription_related_payment( $payment ) {
		// Check if it's a main subscription record
		if ( ! empty( $payment['type'] ) && 'subscription' === $payment['type'] ) {
			return true;
		}

		// Check if it's a subscription billing cycle payment (has subscription_id)
		if ( ! empty( $payment['subscription_id'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Validate subscription refund amount
	 *
	 * @param array $payment Payment record.
	 * @param int   $refund_amount Refund amount in cents.
	 * @return array Validation result with 'valid' boolean and 'message' string.
	 * @since 1.0.0
	 */
	private function validate_subscription_refund_amount( $payment, $refund_amount ) {
		$original_amount      = floatval( $payment['total_amount'] ) * 100; // Convert to cents
		$already_refunded     = floatval( $payment['refunded_amount'] ?? 0 ) * 100; // Convert to cents
		$available_for_refund = $original_amount - $already_refunded;

		if ( $refund_amount > $available_for_refund ) {
			return [
				'valid'   => false,
				'message' => sprintf(
					__( 'Refund amount exceeds available amount. Maximum refundable: %1$s %2$s', 'sureforms' ),
					number_format( $available_for_refund / 100, 2 ),
					strtoupper( $payment['currency'] ?? 'USD' )
				),
			];
		}

		if ( $refund_amount <= 0 ) {
			return [
				'valid'   => false,
				'message' => __( 'Refund amount must be greater than zero.', 'sureforms' ),
			];
		}

		// Stripe minimum refund amount (usually $0.50 for most currencies)
		if ( $refund_amount < 50 ) {
			return [
				'valid'   => false,
				'message' => __( 'Refund amount must be at least $0.50.', 'sureforms' ),
			];
		}

		return [
			'valid'   => true,
			'message' => '',
		];
	}

	/**
	 * Create refund for subscription payment using the most appropriate method
	 *
	 * @param array  $payment Payment record.
	 * @param string $transaction_id Transaction ID.
	 * @param int    $refund_amount Refund amount in cents.
	 * @return array|false Refund data or false on failure.
	 * @since 1.0.0
	 */
	private function create_subscription_refund( $payment, $transaction_id, $refund_amount ) {
		// Method 1: Use charge ID directly (most common for subscription billing payments)
		if ( strpos( $transaction_id, 'ch_' ) === 0 ) {
			error_log( 'SureForms: Creating refund using charge ID: ' . $transaction_id );
			return $this->create_refund_by_charge( $payment, $transaction_id, $refund_amount );
		}

		// Method 2: Use payment intent ID if provided
		if ( strpos( $transaction_id, 'pi_' ) === 0 ) {
			error_log( 'SureForms: Creating refund using payment intent ID: ' . $transaction_id );
			return $this->create_refund_by_payment_intent( $payment, $transaction_id, $refund_amount );
		}

		// Method 3: Try to resolve from subscription or setup intent
		error_log( 'SureForms: Attempting to resolve refund method for transaction: ' . $transaction_id );

		// First try to find charge ID in payment data
		$charge_id = $this->get_charge_id_from_payment( $payment );
		if ( $charge_id ) {
			error_log( 'SureForms: Found charge ID in payment data: ' . $charge_id );
			return $this->create_refund_by_charge( $payment, $charge_id, $refund_amount );
		}

		// Fallback to the complex payment intent resolution method
		$payment_intent_id = $this->get_subscription_payment_intent_id( $payment, $transaction_id );
		if ( $payment_intent_id ) {
			error_log( 'SureForms: Resolved payment intent ID: ' . $payment_intent_id );
			return $this->create_refund_by_payment_intent( $payment, $payment_intent_id, $refund_amount );
		}

		throw new \Exception( __( 'Unable to determine the appropriate refund method for this subscription payment.', 'sureforms' ) );
	}

	/**
	 * Create refund using charge ID
	 *
	 * @param array  $payment Payment record.
	 * @param string $charge_id Stripe charge ID.
	 * @param int    $refund_amount Refund amount in cents.
	 * @return array|false Refund data or false on failure.
	 * @since 1.0.0
	 */
	private function create_refund_by_charge( $payment, $charge_id, $refund_amount ) {
		return $this->stripe_api_request(
			'refunds',
			'POST',
			[
				'charge'   => $charge_id,
				'amount'   => $refund_amount,
				'reason'   => 'requested_by_customer',
				'metadata' => [
					'refunded_by'     => 'sureforms_dashboard',
					'subscription_id' => $payment['subscription_id'] ?? '',
					'source'          => 'SureForms',
					'payment_id'      => $payment['id'] ?? '',
					'refunded_at'     => time(),
					'refund_type'     => 'subscription_billing',
					'refund_method'   => 'charge_refund',
				],
			]
		);
	}

	/**
	 * Create refund using payment intent ID
	 *
	 * @param array  $payment Payment record.
	 * @param string $payment_intent_id Stripe payment intent ID.
	 * @param int    $refund_amount Refund amount in cents.
	 * @return array|false Refund data or false on failure.
	 * @since 1.0.0
	 */
	private function create_refund_by_payment_intent( $payment, $payment_intent_id, $refund_amount ) {
		return $this->stripe_api_request(
			'refunds',
			'POST',
			[
				'payment_intent' => $payment_intent_id,
				'amount'         => $refund_amount,
				'reason'         => 'requested_by_customer',
				'metadata'       => [
					'refunded_by'     => 'sureforms_dashboard',
					'subscription_id' => $payment['subscription_id'] ?? '',
					'source'          => 'SureForms',
					'payment_id'      => $payment['id'] ?? '',
					'refunded_at'     => time(),
					'refund_type'     => 'subscription_billing',
					'refund_method'   => 'payment_intent_refund',
				],
			]
		);
	}

	/**
	 * Update subscription refund data in database
	 *
	 * @param int    $payment_id Payment record ID.
	 * @param array  $refund_response Refund response from Stripe.
	 * @param int    $refund_amount Refund amount in cents.
	 * @param string $currency Currency code.
	 * @return bool True if successful, false otherwise.
	 * @since 1.0.0
	 */
	private function update_subscription_refund_data( $payment_id, $refund_response, $refund_amount, $currency ) {
		if ( empty( $payment_id ) || empty( $refund_response ) ) {
			return false;
		}

		// Get payment record
		$payment = Payments::get( $payment_id );
		if ( ! $payment ) {
			error_log( 'SureForms: Subscription payment record not found for ID: ' . $payment_id );
			return false;
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
			'refunded_by'    => sanitize_text_field( wp_get_current_user()->display_name ?? 'System' ),
			'refunded_at'    => gmdate( 'Y-m-d H:i:s' ),
			'type'           => 'subscription_refund',
		];

		// Validate refund amount to prevent over-refunding
		$original_amount    = floatval( $payment['total_amount'] );
		$existing_refunds   = floatval( $payment['refunded_amount'] ?? 0 ); // Use column directly
		$new_refund_amount  = $refund_amount / 100; // Convert cents to dollars
		$total_after_refund = $existing_refunds + $new_refund_amount;

		if ( $total_after_refund > $original_amount ) {
			error_log(
				sprintf(
					'SureForms: Over-refund attempt blocked for subscription. Payment ID: %d, Original: $%s, Existing refunds: $%s, New refund: $%s',
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
		if ( ! $payment_data_result ) {
			error_log( 'SureForms: Failed to add subscription refund data to payment_data column' );
			return false;
		}

		// Update the refunded_amount column
		$refund_amount_result = Payments::add_refund_amount( $payment_id, $new_refund_amount );
		if ( ! $refund_amount_result ) {
			error_log( 'SureForms: Failed to update subscription refunded amount' );
			return false;
		}

		// Determine new payment status
		$total_amount   = (float) $payment['total_amount'];
		$total_refunded = Payments::get_refunded_amount( $payment_id );
		$payment_status = $total_refunded >= $total_amount ? 'refunded' : 'partially_refunded';

		// Prepare comprehensive log entry
		$current_logs       = Helper::get_array_value( $payment['log'] );
		$original_amount    = $total_amount;
		$total_after_refund = $total_refunded;
		$refund_type        = $total_after_refund >= $original_amount ? 'Full' : 'Partial';
		$new_log            = [
			'title'     => sprintf( '%s Subscription Payment Refund', $refund_type ),
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
		$current_logs[]     = $new_log;

		$update_data = [
			'status' => $payment_status,
			'log'    => $current_logs,
		];

		// Update payment record with status and log
		$payment_update_result = Payments::update( $payment_id, $update_data );

		if ( ! $payment_update_result ) {
			error_log( 'SureForms: Failed to update subscription payment status and log' );
			return false;
		}

		return true;
	}

	/**
	 * Convert technical error messages to user-friendly ones
	 *
	 * @param string $technical_error Technical error message.
	 * @return string User-friendly error message.
	 * @since 1.0.0
	 */
	private function get_user_friendly_refund_error( $technical_error ) {
		$error_patterns = [
			'/charge.*already.*refunded/i'                 => __( 'This payment has already been fully refunded.', 'sureforms' ),
			'/charge.*not.*found/i'                        => __( 'The payment could not be found in Stripe.', 'sureforms' ),
			'/amount.*exceeds/i'                           => __( 'The refund amount exceeds the available refundable amount.', 'sureforms' ),
			'/payment.*intent.*not.*found/i'               => __( 'The payment for this subscription could not be found.', 'sureforms' ),
			'/subscription.*not.*found/i'                  => __( 'The subscription could not be found in Stripe.', 'sureforms' ),
			'/no.*successful.*payments/i'                  => __( 'This subscription has no successful payments to refund.', 'sureforms' ),
			'/invalid.*payment.*method/i'                  => __( 'The payment method for this subscription is invalid.', 'sureforms' ),
			'/insufficient.*permissions/i'                 => __( 'Insufficient permissions to process refunds.', 'sureforms' ),
			'/rate.*limit/i'                               => __( 'Too many requests. Please try again in a moment.', 'sureforms' ),
			'/network.*error|connection.*failed|timeout/i' => __( 'Network error. Please check your connection and try again.', 'sureforms' ),
		];

		foreach ( $error_patterns as $pattern => $friendly_message ) {
			if ( preg_match( $pattern, $technical_error ) ) {
				return $friendly_message;
			}
		}

		// Default fallback message
		return sprintf( __( 'Subscription refund failed: %s', 'sureforms' ), $technical_error );
	}

	/**
	 * Check if refund already exists for this payment
	 *
	 * @param array $payment Payment record.
	 * @param array $refund_response Refund response from Stripe.
	 * @return bool True if refund already exists, false otherwise.
	 * @since 1.0.0
	 */
	private function check_if_refund_already_exists( $payment, $refund_response ) {
		if ( empty( $payment['payment_data'] ) || empty( $refund_response['id'] ) ) {
			return false;
		}

		$payment_data = Helper::get_array_value( $payment['payment_data'] );
		if ( empty( $payment_data['refunds'] ) ) {
			return false;
		}

		foreach ( $payment_data['refunds'] as $existing_refund ) {
			if ( ! empty( $existing_refund['refund_id'] ) && $existing_refund['refund_id'] === $refund_response['id'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get charge ID from payment data
	 *
	 * @param array $payment Payment record.
	 * @return string|null Charge ID or null if not found.
	 * @since 1.0.0
	 */
	private function get_charge_id_from_payment( $payment ) {
		// Check if transaction_id is already a charge ID
		if ( ! empty( $payment['transaction_id'] ) && strpos( $payment['transaction_id'], 'ch_' ) === 0 ) {
			return $payment['transaction_id'];
		}

		// Look in payment_data for charge_id
		if ( empty( $payment['payment_data'] ) ) {
			return null;
		}

		$payment_data = Helper::get_array_value( $payment['payment_data'] );
		if ( empty( $payment_data ) ) {
			return null;
		}

		// Look for charge ID in various places in payment_data
		$charge_keys = [
			'charge_id',
			'charge',
			'invoice_charge_id',
		];

		foreach ( $charge_keys as $key ) {
			$charge_id = $this->get_nested_value( $payment_data, $key );
			if ( ! empty( $charge_id ) && strpos( $charge_id, 'ch_' ) === 0 ) {
				return $charge_id;
			}
		}

		return null;
	}

	/**
	 * Get payment intent ID for subscription refunds
	 *
	 * @param array  $payment Payment record.
	 * @param string $transaction_id Transaction ID from request.
	 * @return string|null Payment intent ID or null if not found.
	 * @since 1.0.0
	 */
	private function get_subscription_payment_intent_id( $payment, $transaction_id ) {
		// Check if transaction_id is already a payment intent ID
		if ( ! empty( $transaction_id ) && strpos( $transaction_id, 'pi_' ) === 0 ) {
			return $transaction_id;
		}

		// Look for payment intent in payment_data
		if ( ! empty( $payment['payment_data'] ) ) {
			$payment_data = Helper::get_array_value( $payment['payment_data'] );
			if ( ! empty( $payment_data ) ) {
				$payment_intent_keys = [
					'initial_invoice.payment_intent',
					'subscription.latest_invoice.payment_intent',
					'payment_intent_id',
					'payment_intent',
				];

				foreach ( $payment_intent_keys as $key ) {
					$payment_intent_id = $this->get_nested_value( $payment_data, $key );
					if ( ! empty( $payment_intent_id ) && strpos( $payment_intent_id, 'pi_' ) === 0 ) {
						return $payment_intent_id;
					}
				}
			}
		}

		return null;
	}

	/**
	 * Get nested value from array using dot notation
	 *
	 * @param array  $array Array to search.
	 * @param string $key Dot-separated key path.
	 * @return mixed Value or null if not found.
	 * @since 1.0.0
	 */
	private function get_nested_value( $array, $key ) {
		$keys  = explode( '.', $key );
		$value = $array;

		foreach ( $keys as $k ) {
			if ( ! is_array( $value ) || ! isset( $value[ $k ] ) ) {
				return null;
			}
			$value = $value[ $k ];
		}

		return $value;
	}
}

// Initialize the admin handler
// Admin_Stripe_Handler::get_instance();
