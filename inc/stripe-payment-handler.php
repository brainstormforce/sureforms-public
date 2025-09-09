<?php
/**
 * Stripe Payment Handler
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
 * Stripe Payment Handler Class
 *
 * @since x.x.x
 */
class Stripe_Payment_Handler {
	use Get_Instance;

	private $stripe_payment_entries = [];

	private $application_fee = 3;

	/**
	 * Constructor
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		add_action( 'wp_ajax_srfm_create_payment_intent', [ $this, 'create_payment_intent' ] );
		add_action( 'wp_ajax_nopriv_srfm_create_payment_intent', [ $this, 'create_payment_intent' ] ); // For non-logged-in users.
		add_action( 'wp_ajax_srfm_create_subscription_intent', [ $this, 'create_subscription_intent' ] );
		add_action( 'wp_ajax_nopriv_srfm_create_subscription_intent', [ $this, 'create_subscription_intent' ] ); // For non-logged-in users.

		// AJAX endpoints for subscription refund and cancellation (following WPForms pattern)
		add_action( 'wp_ajax_srfm_refund_subscription_payment', [ $this, 'ajax_refund_subscription_payment' ] );
		add_action( 'wp_ajax_srfm_cancel_subscription', [ $this, 'ajax_cancel_subscription' ] );
		add_action( 'wp_ajax_srfm_refund_payment', [ $this, 'refund_payment' ] );
		add_filter( 'srfm_form_submit_data', [ $this, 'validate_payment_fields' ], 5, 1 );
		add_action( 'wp_head', [ $this, 'add_payment_styles' ] );
		add_action( 'srfm_form_submit', [ $this, 'handle_form_submit' ], 10, 1 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_payment_scripts' ] );
	}

	/**
	 * Enqueue payment scripts
	 *
	 * @return void
	 * @since x.x.x
	 */
	public function enqueue_payment_scripts() {
		wp_enqueue_script(
			'srfm-payment-entries',
			SRFM_URL . 'assets/js/payment-entries.js',
			[ 'jquery' ],
			SRFM_VER,
			true
		);
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

		$amount      = intval( $_POST['amount'] ?? 0 );
		$currency    = sanitize_text_field( wp_unslash( $_POST['currency'] ?? 'usd' ) );
		$description = sanitize_text_field( wp_unslash( $_POST['description'] ?? 'SureForms Payment' ) );
		$block_id    = sanitize_text_field( wp_unslash( $_POST['block_id'] ?? '' ) );

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

			// Create payment intent with confirm: true for immediate processing
			$payment_intent_data = apply_filters(
				'srfm_create_payment_intent_data',
				[
					'secret_key'                => $secret_key,
					'amount'                    => $amount,
					'currency'                  => strtolower( $currency ),
					'description'               => $description,
					'confirm'                   => false, // Will be confirmed by frontend
					'automatic_payment_methods' => [
						'enabled'         => true,
						'allow_redirects' => 'never',
					],
					'metadata'                  => [
						'source'          => 'SureForms',
						'block_id'        => $block_id,
						'original_amount' => $amount,
					],
				]
			);

			$payment_intent = wp_remote_post(
				'prod' === SRFM_PAYMENTS_ENV ? SRFM_PAYMENTS_PROD . 'payment-intent/create' : SRFM_PAYMENTS_LOCAL . 'payment-intent/create',
				[
					'body'    => base64_encode( wp_json_encode( $payment_intent_data ) ),
					'headers' => [
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
		// Check if form data is valid.
		if ( empty( $form_data ) || ! is_array( $form_data ) ) {
			return $form_data;
		}

		// Loop through form data to find payment fields.
		foreach ( $form_data as $field_name => $field_value ) {
			// Check if field name contains "-lbl-" pattern.
			if ( strpos( $field_name, '-lbl-' ) === false ) {
				continue;
			}

			// Split field name by "-lbl-" delimiter.
			$name_parts = explode( '-lbl-', $field_name );

			// Check if we have the expected parts.
			if ( count( $name_parts ) < 2 ) {
				continue;
			}

			// Check if the first part starts with "srfm-payment-".
			if ( ! ( strpos( $name_parts[0], 'srfm-payment-' ) === 0 ) ) {
				continue;
			}

			// Value will be in the form of the json string.
			$payment_value = json_decode( $field_value, true );

			if ( empty( $payment_value ) || ! is_array( $payment_value ) ) {
				continue;
			}

			$payment_id = ! empty( $payment_value['paymentId'] ) ? $payment_value['paymentId'] : '';

			if ( empty( $payment_id ) ) {
				continue;
			}

			$block_id     = ! empty( $payment_value['blockId'] ) ? $payment_value['blockId'] : '';
			$payment_type = ! empty( $payment_value['paymentType'] ) ? $payment_value['paymentType'] : '';

			if ( empty( $block_id ) || empty( $payment_type ) ) {
				continue;
			}

			if ( 'stripe-subscription' === $payment_type ) {
				$this->verify_stripe_subscription_intent_and_save( $payment_value, $block_id, $form_data );
			} else {
				$this->verify_stripe_payment_intent_and_save( $payment_id, $block_id, $form_data );
			}
		}

		return $form_data;
	}

	/**
	 * Simplified subscription verification using simple-stripe-subscriptions approach
	 *
	 * @param array  $subscription_value Subscription data from frontend.
	 * @param string $block_id Block ID.
	 * @param array  $form_data Form data.
	 * @return bool True if subscription is verified and saved successfully.
	 */
	public function verify_stripe_subscription_intent_and_save( $subscription_value, $block_id, $form_data ) {
		$subscription_id   = ! empty( $subscription_value['subscriptionId'] ) ? $subscription_value['subscriptionId'] : '';
		$payment_intent_id = ! empty( $subscription_value['paymentId'] ) ? $subscription_value['paymentId'] : '';

		error_log( 'SureForms: Starting simplified subscription verification. Subscription ID: ' . $subscription_id );

		if ( empty( $subscription_id ) ) {
			error_log( 'SureForms: Missing subscription ID' );
			return false;
		}

		if ( empty( $payment_intent_id ) ) {
			error_log( 'SureForms: Missing payment intent ID' );
			return false;
		}

		try {
			// Get payment settings
			$payment_settings = get_option( 'srfm_payments_settings', [] );
			$payment_mode     = $payment_settings['payment_mode'] ?? 'test';
			$secret_key       = 'live' === $payment_mode
				? $payment_settings['stripe_live_secret_key'] ?? ''
				: $payment_settings['stripe_test_secret_key'] ?? '';

			if ( empty( $secret_key ) ) {
				throw new \Exception( __( 'Stripe secret key not found.', 'sureforms' ) );
			}

			\Stripe\Stripe::setApiKey( $secret_key );

			// Simple approach: Just retrieve and validate the subscription like simple-stripe-subscriptions
			$subscription = \Stripe\Subscription::retrieve(
				[
					'id'     => $subscription_id,
					'expand' => [ 'latest_invoice.payment_intent' ],
				]
			);

			error_log( 'SureForms: Retrieved subscription status: ' . $subscription->status );

			// Use simple-stripe-subscriptions validation logic - check if subscription is in good state
			$is_subscription_active = in_array( $subscription->status, [ 'active', 'trialing' ] );
			$final_status           = $is_subscription_active ? 'succeeded' : 'failed';

			// Prepare minimal subscription data for database
			$entry_data = [
				'form_id'             => $form_data['form-id'] ?? '',
				'block_id'            => $block_id,
				'status'              => $final_status,
				'total_amount'        => $this->amount_convert_cents_to_usd( $subscription->latest_invoice ? $subscription->latest_invoice->amount_paid : 0 ),
				'currency'            => $subscription->currency ?? 'usd',
				'entry_id'            => 0,
				'gateway'             => 'stripe',
				'type'                => 'subscription',
				'mode'                => $payment_mode,
				'transaction_id'      => $payment_intent_id,
				'customer_id'         => $subscription->customer,
				'subscription_id'     => $subscription_id,
				'subscription_status' => $subscription->status,
			];

			// Add simple log entry
			$entry_data['log'] = [
				[
					'title'     => 'Subscription Verification',
					'timestamp' => time(),
					'messages'  => [
						sprintf( 'Subscription ID: %s', $subscription_id ),
						sprintf( 'Payment Intent ID: %s', $payment_intent_id ),
						sprintf( 'Subscription Status: %s', $subscription->status ),
						sprintf( 'Customer ID: %s', $subscription->customer ),
						sprintf( 'Total Amount: %s %s', number_format( $entry_data['total_amount'], 2 ), strtoupper( $entry_data['currency'] ) ),
					],
				],
			];

			// Save to database
			$payment_entry_id = Payments::add( $entry_data );

			if ( $payment_entry_id ) {
				// Store in static array for later entry linking
				$this->stripe_payment_entries[] = [
					'payment_id' => $subscription_id,
					'block_id'   => $block_id,
					'form_id'    => $form_data['form-id'] ?? '',
				];

				error_log( 'SureForms: Subscription verification complete. Status: ' . $final_status );
				return $is_subscription_active;
			}
				throw new \Exception( __( 'Failed to save subscription data.', 'sureforms' ) );

		} catch ( \Exception $e ) {
			error_log( 'SureForms Subscription Verification Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Handle form submit and update payment entries with entry_id
	 *
	 * This function is called after a form submission to link the created entry
	 * with any associated Stripe payment records. It matches payment entries
	 * by form_id and updates them with the newly created entry_id.
	 *
	 * @param array<string,mixed> $form_submit_response The form submission response containing entry_id and form_id.
	 * @return void
	 * @since x.x.x
	 */
	public function handle_form_submit( $form_submit_response ) {
		// Check if entry_id exists in the form_submit_response
		if ( ! empty( $form_submit_response['entry_id'] ) && ! empty( $this->stripe_payment_entries ) ) {
			$entry_id = intval( $form_submit_response['entry_id'] );

			// Loop through stored payment entries to update with entry_id
			foreach ( $this->stripe_payment_entries as $stripe_payment_entry ) {
				if ( ! empty( $stripe_payment_entry['payment_id'] ) && ! empty( $stripe_payment_entry['form_id'] ) ) {
					// Check if form_id matches
					$stored_form_id   = intval( $stripe_payment_entry['form_id'] );
					$response_form_id = intval( $form_submit_response['form_id'] ?? 0 );

					if ( $stored_form_id === $response_form_id ) {
						// Update the payment entry with the entry_id
						$this->update_payment_entry_id( $stripe_payment_entry['payment_id'], $entry_id );
					}
				}
			}
		}
	}

	/**
	 * Process payment refund
	 *
	 * @return void
	 * @since x.x.x
	 */
	public function refund_payment() {
		// Verify nonce
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'sureforms_admin_nonce' ) ) {
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
		$refund_amount  = intval( $_POST['refund_amount'] ?? 0 );

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
			if ( ! empty( $payment['type'] ) && 'subscription' === $payment['type'] ) {
				error_log( 'SureForms: Routing subscription payment to specialized refund handler' );
				$this->refund_subscription_payment( $payment_id, $transaction_id, $refund_amount );
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

			// Get license key for consistency with other endpoints
			$license_key = $this->get_license_key();

			// Prepare refund data for middleware
			$refund_data = apply_filters(
				'srfm_refund_payment_data',
				[
					'secret_key'     => $secret_key,
					'payment_intent' => $transaction_id,
					'amount'         => $refund_amount,
					'license_key'    => $license_key,
					'metadata'       => [
						'source'      => 'SureForms',
						'payment_id'  => $payment_id,
						'refunded_at' => time(),
					],
				]
			);

			// Call middleware refund endpoint
			$refund_response = wp_remote_post(
				'prod' === SRFM_PAYMENTS_ENV ? SRFM_PAYMENTS_PROD . 'refund/create' : SRFM_PAYMENTS_LOCAL . 'refund/create',
				[
					'body'    => base64_encode( wp_json_encode( $refund_data ) ),
					'headers' => [
						'Content-Type' => 'application/json',
					],
				]
			);

			if ( is_wp_error( $refund_response ) ) {
				throw new \Exception( __( 'Failed to process refund through middleware.', 'sureforms' ) );
			}

			$refund = json_decode( wp_remote_retrieve_body( $refund_response ), true );

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
	 * Update refund data in payment_data column and log
	 *
	 * @param int    $payment_id Payment record ID.
	 * @param array  $refund_response Refund response from Stripe.
	 * @param int    $refund_amount Refund amount in cents.
	 * @param string $currency Currency code.
	 * @param array  $payment Payment record data.
	 * @return bool True if successful, false otherwise.
	 * @since x.x.x
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
	 * Create subscription intent with improved error handling from simple-stripe-subscriptions
	 *
	 * @return void
	 * @throws \Exception When Stripe configuration is invalid.
	 * @since x.x.x
	 */
	public function create_subscription_intent() {
		// Verify nonce
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'srfm_stripe_payment_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', 'sureforms' ) );
			return;
		}

		// Validate required fields like simple-stripe-subscriptions
		$required_fields = [ 'amount', 'currency', 'description', 'block_id', 'interval', 'plan_name' ];
		foreach ( $required_fields as $field ) {
			if ( empty( $_POST[ $field ] ) ) {
				wp_send_json_error( sprintf( __( 'Missing required field: %s', 'sureforms' ), $field ) );
				return;
			}
		}

		$amount      = intval( $_POST['amount'] ?? 0 );
		$currency    = sanitize_text_field( wp_unslash( $_POST['currency'] ?? 'usd' ) );
		$description = sanitize_text_field( wp_unslash( $_POST['description'] ?? 'SureForms Subscription' ) );
		$block_id    = sanitize_text_field( wp_unslash( $_POST['block_id'] ?? '' ) );

		$subscription_interval       = sanitize_text_field( wp_unslash( $_POST['interval'] ?? 'month' ) );
		$plan_name                   = sanitize_text_field( wp_unslash( $_POST['plan_name'] ?? 'Subscription Plan' ) );
		$subscription_interval_count = absint( $_POST['subscription_interval_count'] ?? 1 );
		$subscription_trial_days     = absint( $_POST['subscription_trial_days'] ?? 0 );

		// Validate amount like simple-stripe-subscriptions
		if ( $amount <= 0 ) {
			wp_send_json_error( __( 'Amount must be greater than 0', 'sureforms' ) );
			return;
		}

		// Validate interval like simple-stripe-subscriptions
		$valid_intervals = [ 'day', 'week', 'month', 'year' ];
		if ( ! in_array( $subscription_interval, $valid_intervals, true ) ) {
			wp_send_json_error( __( 'Invalid billing interval', 'sureforms' ) );
			return;
		}

		try {
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

			// Initialize Stripe SDK
			if ( ! class_exists( '\Stripe\Stripe' ) ) {
				throw new \Exception( __( 'Stripe library not found.', 'sureforms' ) );
			}

			// Get or create Stripe customer for subscriptions
			$customer_id = $this->get_or_create_stripe_customer();
			if ( ! $customer_id ) {
				throw new \Exception( __( 'Failed to create customer for subscription.', 'sureforms' ) );
			}

			// Create subscription using simplified approach
			$response = $this->create_subscription(
				$amount,
				$currency,
				$description,
				$subscription_interval,
				$subscription_interval_count,
				$subscription_trial_days,
				0, // application_fee_amount - unused in simplified approach
				'', // stripe_account_id - unused in simplified approach
				$block_id,
				$customer_id,
				$secret_key
			);

			wp_send_json_success( $response );

		} catch ( \Exception $e ) {
			error_log( 'SureForms Subscription Error: ' . $e->getMessage() );
			wp_send_json_error( sprintf( __( 'Unexpected error: %s', 'sureforms' ), $e->getMessage() ) );
		}
	}

	/**
	 * Cancel subscription (following WPForms pattern)
	 *
	 * @param string $subscription_id Subscription ID.
	 * @return bool Success status.
	 */
	public function cancel_subscription( $subscription_id ) {
		try {
			error_log( 'SureForms: Attempting to cancel subscription: ' . $subscription_id );

			// Following WPForms pattern - retrieve subscription and cancel it
			$subscription = \Stripe\Subscription::retrieve(
				$subscription_id
			);

			if ( ! $subscription ) {
				error_log( 'SureForms: Subscription not found: ' . $subscription_id );
				return false;
			}

			// Update subscription metadata to track cancellation source (following WPForms pattern)
			\Stripe\Subscription::update(
				$subscription_id,
				[
					'metadata' => array_merge(
						$subscription->metadata->values(),
						[
							'canceled_by' => 'sureforms_dashboard',
						]
					),
				]
			);

			// Cancel the subscription
			$subscription->cancel();

			error_log( 'SureForms: Subscription cancelled successfully: ' . $subscription_id );
			return true;

		} catch ( \Stripe\Exception\ApiErrorException $e ) {
			error_log( 'SureForms: Stripe API error cancelling subscription: ' . $e->getMessage() );
			return false;
		} catch ( \Exception $e ) {
			error_log( 'SureForms: General error cancelling subscription: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * AJAX handler for subscription payment refund (following WPForms pattern)
	 *
	 * @since x.x.x
	 */
	public function ajax_refund_subscription_payment() {
		// Security checks
		if ( ! isset( $_POST['payment_id'] ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Missing payment ID.', 'sureforms' ) ] );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'You are not allowed to perform this action.', 'sureforms' ) ] );
		}

		check_ajax_referer( 'sureforms_admin_nonce', 'nonce' );

		$payment_id    = absint( $_POST['payment_id'] );
		$refund_amount = isset( $_POST['refund_amount'] ) ? absint( $_POST['refund_amount'] ) : null;

		// Get payment record
		$payment = Payments::get( $payment_id );
		if ( ! $payment ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Payment not found in the database.', 'sureforms' ) ] );
		}

		// Validate it's a subscription payment
		if ( empty( $payment['type'] ) || 'subscription' !== $payment['type'] ) {
			wp_send_json_error( [ 'message' => esc_html__( 'This is not a subscription payment.', 'sureforms' ) ] );
		}

		// Process the subscription refund
		try {
			$this->refund_subscription_payment( $payment_id, $payment['transaction_id'], $refund_amount );
			wp_send_json_success( [ 'message' => esc_html__( 'Subscription payment refunded successfully.', 'sureforms' ) ] );
		} catch ( \Exception $e ) {
			error_log( 'SureForms AJAX Subscription Refund Error: ' . $e->getMessage() );
			wp_send_json_error( [ 'message' => esc_html__( 'Subscription refund failed.', 'sureforms' ) ] );
		}
	}

	/**
	 * AJAX handler for subscription cancellation (following WPForms pattern)
	 *
	 * @since x.x.x
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
	 * Refund subscription payment following WPForms pattern
	 *
	 * This method handles refunding the payment intent associated with a subscription,
	 * similar to how WPForms handles subscription refunds.
	 *
	 * @param int    $payment_id Payment record ID.
	 * @param string $transaction_id Transaction ID (should be payment_intent_id for subscriptions).
	 * @param int    $refund_amount Refund amount in cents.
	 * @return void
	 * @since x.x.x
	 */
	private function refund_subscription_payment( $payment_id, $transaction_id, $refund_amount ) {
		try {
			// Get payment record
			$payment = Payments::get( $payment_id );
			if ( ! $payment ) {
				wp_send_json_error( __( 'Subscription payment not found.', 'sureforms' ) );
				return;
			}

			// Verify this is a subscription payment
			if ( 'subscription' !== $payment['type'] ) {
				wp_send_json_error( __( 'This is not a subscription payment.', 'sureforms' ) );
				return;
			}

			// Verify subscription payment status
			if ( 'succeeded' !== $payment['status'] && 'partially_refunded' !== $payment['status'] ) {
				wp_send_json_error( __( 'Only succeeded or partially refunded subscription payments can be refunded.', 'sureforms' ) );
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

			// Initialize Stripe SDK (following WPForms direct approach)
			\Stripe\Stripe::setApiKey( $secret_key );

			// Handle subscription refund following WPForms pattern
			$payment_intent_id = $this->get_subscription_payment_intent_id( $payment, $transaction_id );

			if ( ! $payment_intent_id ) {
				throw new \Exception( __( 'Unable to find payment intent for subscription refund.', 'sureforms' ) );
			}

			// Create refund using direct Stripe SDK (like WPForms)
			$refund = \Stripe\Refund::create(
				[
					'payment_intent' => $payment_intent_id,
					'amount'         => $refund_amount,
					'metadata'       => [
						'refunded_by'     => 'sureforms_dashboard',
						'subscription_id' => $payment['subscription_id'] ?? '',
						'source'          => 'SureForms',
						'payment_id'      => $payment_id,
						'refunded_at'     => time(),
					],
					'reason'         => 'requested_by_customer',
				]
			);

			if ( ! $refund ) {
				throw new \Exception( __( 'Stripe refund creation failed.', 'sureforms' ) );
			}

			// Update database following WPForms pattern
			$refund_stored = $this->update_subscription_refund_data( $payment_id, $refund, $refund_amount, $payment['currency'] );
			if ( ! $refund_stored ) {
				wp_send_json_error( __( 'Payment has been processed but could not update the payment table.', 'sureforms' ) );
			}

			wp_send_json_success(
				[
					'message'   => __( 'Subscription payment refunded successfully.', 'sureforms' ),
					'refund_id' => $refund->id,
					'status'    => $refund->status,
					'type'      => 'subscription_refund',
				]
			);

		} catch ( \Exception $e ) {
			error_log( 'SureForms Subscription Refund Error: ' . $e->getMessage() );
			wp_send_json_error( sprintf( __( 'Subscription refund failed: %s', 'sureforms' ), $e->getMessage() ) );
		}
	}

	/**
	 * Get payment intent ID for subscription refunds (following WPForms pattern)
	 *
	 * @param array  $payment Payment record.
	 * @param string $transaction_id Transaction ID from request.
	 * @return string|null Payment intent ID or null if not found.
	 * @since x.x.x
	 */
	private function get_subscription_payment_intent_id( $payment, $transaction_id ) {
		// For subscriptions, the transaction_id might be the subscription_id or payment_intent_id
		// We need to determine which one it is and get the correct payment_intent_id

		if ( strpos( $transaction_id, 'pi_' ) === 0 ) {
			// Already a payment intent ID
			return $transaction_id;
		}

		if ( strpos( $transaction_id, 'sub_' ) === 0 ) {
			// This is a subscription ID, we need to get the latest invoice's payment intent
			try {
				$subscription = \Stripe\Subscription::retrieve( $transaction_id );
				if ( $subscription && $subscription->latest_invoice ) {
					$invoice = \Stripe\Invoice::retrieve( $subscription->latest_invoice );
					if ( $invoice && $invoice->payment_intent ) {
						return $invoice->payment_intent;
					}
				}
			} catch ( \Exception $e ) {
				error_log( 'SureForms: Error retrieving subscription payment intent: ' . $e->getMessage() );
			}
		}

		// Fallback: try to use subscription_id from payment record
		if ( ! empty( $payment['subscription_id'] ) ) {
			try {
				$subscription = \Stripe\Subscription::retrieve( $payment['subscription_id'] );
				if ( $subscription && $subscription->latest_invoice ) {
					$invoice = \Stripe\Invoice::retrieve( $subscription->latest_invoice );
					if ( $invoice && $invoice->payment_intent ) {
						return $invoice->payment_intent;
					}
				}
			} catch ( \Exception $e ) {
				error_log( 'SureForms: Error retrieving subscription from payment record: ' . $e->getMessage() );
			}
		}

		return null;
	}

	/**
	 * Update subscription refund data in database (following WPForms pattern)
	 *
	 * @param int    $payment_id Payment record ID.
	 * @param object $refund_response Refund response from Stripe.
	 * @param int    $refund_amount Refund amount in cents.
	 * @param string $currency Currency code.
	 * @return bool True if successful, false otherwise.
	 * @since x.x.x
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
			'refund_id'      => sanitize_text_field( $refund_response->id ?? '' ),
			'amount'         => absint( $refund_amount ),
			'currency'       => sanitize_text_field( strtoupper( $currency ) ),
			'status'         => sanitize_text_field( $refund_response->status ?? 'processed' ),
			'created'        => time(),
			'reason'         => sanitize_text_field( $refund_response->reason ?? 'requested_by_customer' ),
			'description'    => sanitize_text_field( $refund_response->description ?? '' ),
			'receipt_number' => sanitize_text_field( $refund_response->receipt_number ?? '' ),
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
		$current_logs   = Helper::get_array_value( $payment['log'] );
		$original_amount = $total_amount;
		$total_after_refund = $total_refunded;
		$refund_type    = $total_after_refund >= $original_amount ? 'Full' : 'Partial';
		$new_log        = [
			'title'     => sprintf( '%s Subscription Payment Refund', $refund_type ),
			'timestamp' => time(),
			'messages'  => [
				sprintf( 'Refund ID: %s', $refund_response->id ?? 'N/A' ),
				sprintf( 'Refund Amount: %s %s', number_format( $refund_amount / 100, 2 ), strtoupper( $currency ) ),
				sprintf(
					'Total Refunded: %s %s of %s %s',
					number_format( $total_after_refund, 2 ),
					strtoupper( $currency ),
					number_format( $original_amount, 2 ),
					strtoupper( $currency )
				),
				sprintf( 'Refund Status: %s', $refund_response->status ?? 'processed' ),
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
		
		if ( ! $payment_update_result ) {
			error_log( 'SureForms: Failed to update subscription payment status and log' );
			return false;
		}

		return true;
	}

	/**
	 * Verify payment intent status
	 *
	 * @param string $payment_intent_id Payment intent ID.
	 * @return void|bool
	 * @since x.x.x
	 */
	private function verify_stripe_payment_intent_and_save( $payment_intent_id, $block_id, $form_data ) {
		try {
			$payment_settings = get_option( 'srfm_payments_settings', [] );
			$payment_mode     = $payment_settings['payment_mode'] ?? 'test';
			$secret_key       = 'live' === $payment_mode
				? $payment_settings['stripe_live_secret_key'] ?? ''
				: $payment_settings['stripe_test_secret_key'] ?? '';

			if ( empty( $secret_key ) ) {
				return false;
			}

			// Retrieve confirmed payment intent status
			$retrieve_data = apply_filters(
				'srfm_retrieve_payment_intent_data',
				[
					'secret_key'        => $secret_key,
					'payment_intent_id' => $payment_intent_id,
				]
			);

			// Call middleware retrieve endpoint to get confirmed payment intent
			$retrieve_response = wp_remote_post(
				'prod' === SRFM_PAYMENTS_ENV ? SRFM_PAYMENTS_PROD . 'payment-intent/capture' : SRFM_PAYMENTS_LOCAL . 'payment-intent/capture',
				[
					'body'    => base64_encode( wp_json_encode( $retrieve_data ) ),
					'headers' => [
						'Content-Type' => 'application/json',
					],
				]
			);

			if ( is_wp_error( $retrieve_response ) ) {
				throw new \Exception( __( 'Failed to retrieve payment intent.', 'sureforms' ) );
			}

			$confirmed_payment_intent = json_decode( wp_remote_retrieve_body( $retrieve_response ), true );

			if ( empty( $confirmed_payment_intent ) || isset( $confirmed_payment_intent['status'] ) && 'error' === $confirmed_payment_intent['status'] ) {
				throw new \Exception( __( 'Failed to retrieve payment intent.', 'sureforms' ) );
			}

			// Check if payment was actually confirmed successfully
			if ( ! in_array( $confirmed_payment_intent['status'], [ 'succeeded', 'requires_capture' ], true ) ) {
				throw new \Exception( __( 'Payment was not confirmed successfully.', 'sureforms' ) );
			}

			$entry_data = [];

			// update payment status and save to the payment entries table.
			$entry_data['form_id']        = $form_data['form-id'] ?? '';
			$entry_data['block_id']       = $block_id;
			$entry_data['status']         = $confirmed_payment_intent['status'];
			$entry_data['total_amount']   = $this->amount_convert_cents_to_usd( $confirmed_payment_intent['amount'] );
			$entry_data['currency']       = $confirmed_payment_intent['currency'];
			$entry_data['entry_id']       = 0;
			$entry_data['gateway']        = 'stripe';
			$entry_data['type']           = 'payment';
			$entry_data['mode']           = $payment_mode;
			$entry_data['transaction_id'] = $confirmed_payment_intent['id'];

			$get_payment_entry_id = Payments::add( $entry_data );

			if ( $get_payment_entry_id ) {
				$add_in_static_value = [
					'payment_id' => $payment_intent_id,
					'block_id'   => $block_id,
					'form_id'    => $form_data['form-id'] ?? '',
				];

				$this->stripe_payment_entries[] = $add_in_static_value;
			}

			// return 'succeeded' === $payment_intent->status;

		} catch ( \Exception $e ) {
			error_log( 'SureForms Payment Verification Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Convert USD amount to cents
	 *
	 * @param float $amount Amount in USD.
	 * @return int Amount in cents.
	 * @since x.x.x
	 */
	private function amount_convert_usd_to_cents( $amount ) {
		return $amount * 100;
	}

	/**
	 * Convert cents amount to USD
	 *
	 * @param int $amount Amount in cents.
	 * @return float Amount in USD.
	 * @since x.x.x
	 */
	private function amount_convert_cents_to_usd( $amount ) {
		return $amount / 100;
	}

	/**
	 * Update payment entry with entry_id
	 *
	 * @param string $payment_id Payment intent ID.
	 * @param int    $entry_id   Entry ID to update.
	 * @return void
	 * @since x.x.x
	 */
	private function update_payment_entry_id( $payment_id, $entry_id ) {
		try {
			// Find the payment entry by transaction_id
			$payment_entries = Payments::get_instance()->get_results(
				[ 'transaction_id' => $payment_id ],
				'id'
			);

			if ( ! empty( $payment_entries ) && isset( $payment_entries[0]['id'] ) ) {
				$payment_entry_id = intval( $payment_entries[0]['id'] );

				// Update the payment entry with entry_id using Payments class
				$updated = Payments::update( $payment_entry_id, [ 'entry_id' => $entry_id ] );

				if ( false === $updated ) {
					error_log( 'SureForms: Failed to update payment entry_id for payment_id: ' . $payment_id );
				}
			} else {
				error_log( 'SureForms: Payment entry not found for payment_id: ' . $payment_id );
			}
		} catch ( \Exception $e ) {
			error_log( 'SureForms: Error updating payment entry_id: ' . $e->getMessage() );
		}
	}

	/**
	 * Check if the SureForms Pro license is active
	 *
	 * @return bool True if the SureForms Pro license is active, false otherwise.
	 * @since x.x.x
	 */
	private function is_pro_license_active() {
		// First check if Pro version is installed.
		if ( ! defined( 'SRFM_PRO_VER' ) ) {
			return false;
		}

		// Check if the licensing class exists and license is active.
		if ( class_exists( 'SRFM_Pro\Admin\Licensing' ) ) {
			$licensing = \SRFM_Pro\Admin\Licensing::get_instance();
			if ( $licensing && method_exists( $licensing, 'is_license_active' ) ) {
				return $licensing->is_license_active();
			}
		}

		// Fallback: Check license status via API (similar to AI form builder).
		return $this->check_license_via_api();
	}

	/**
	 * Check license status via credits.startertemplates.com API
	 *
	 * @return bool True if license is active, false otherwise.
	 * @since x.x.x
	 */
	private function check_license_via_api() {
		// Get license key for API authentication.
		$license_key = $this->get_license_key();
		if ( empty( $license_key ) ) {
			return false;
		}

		// Make API request to check license status.
		$api_url  = SRFM_AI_MIDDLEWARE . 'license/verify';
		$response = wp_remote_post(
			$api_url,
			[
				'headers' => [
					'X-Token'      => base64_encode( $license_key ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
					'Content-Type' => 'application/json',
					'Referer'      => site_url(),
				],
				'timeout' => 30,
				'body'    => wp_json_encode(
					[
						'action' => 'verify_license',
						'domain' => site_url(),
					]
				),
			]
		);

		// Default to inactive if API call fails.
		$is_active = false;

		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$response_body = wp_remote_retrieve_body( $response );
			$data          = json_decode( $response_body, true );

			if ( is_array( $data ) && isset( $data['license_active'] ) ) {
				$is_active = (bool) $data['license_active'];
			}
		}

		return $is_active;
	}

	/**
	 * Get license key for API authentication
	 *
	 * @return string License key or empty string if not available.
	 * @since x.x.x
	 */
	private function get_license_key() {
		// Only proceed if Pro version is installed.
		if ( ! defined( 'SRFM_PRO_VER' ) || ! class_exists( 'SRFM_Pro\Admin\Licensing' ) ) {
			return '';
		}

		$licensing = \SRFM_Pro\Admin\Licensing::get_instance();
		if ( ! $licensing || ! method_exists( $licensing, 'licensing_setup' ) ) {
			return '';
		}

		$license_setup = $licensing->licensing_setup();
		if ( ! is_object( $license_setup ) || ! method_exists( $license_setup, 'settings' ) ) {
			return '';
		}

		return $license_setup->settings()->license_key ?? '';
	}

	/**
	 * Calculate total refunded amount for a payment
	 *
	 * @param int $payment_id Payment record ID.
	 * @return float Total refunded amount in dollars.
	 * @since x.x.x
	 */
	private function calculate_total_refunds( $payment_id ) {
		// Use the new refunded_amount column for direct access
		return Payments::get_refunded_amount( $payment_id );
	}

	/**
	 * Check if the refund already exists
	 *
	 * @param array $payment Payment record data.
	 * @param array $refund Refund response from Stripe.
	 * @return bool True if refund already exists, false otherwise.
	 * @since x.x.x
	 */
	private function check_if_refund_already_exists( $payment, $refund ) {
		$refund_id = $refund['id'] ?? '';

		$payment_refunds = isset( $payment['payment_data'] ) && isset( $payment['payment_data']['refunds'] ) ? $payment['payment_data']['refunds'] : [];

		if ( ! empty( $payment_refunds ) && is_array( $payment_refunds ) ) {
			foreach ( $payment_refunds as $refund ) {
				if ( isset( $refund['refund_id'] ) && $refund['refund_id'] === $refund_id ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Create subscription using the proven approach from simple-stripe-subscriptions
	 *
	 * @param int    $amount Subscription amount in cents.
	 * @param string $currency Subscription currency.
	 * @param string $description Subscription description.
	 * @param string $interval Billing interval (day, week, month, year).
	 * @param int    $interval_count Billing interval count.
	 * @param int    $trial_days Trial days.
	 * @param int    $application_fee_amount Application fee amount in cents.
	 * @param string $stripe_account_id Stripe account ID.
	 * @param string $block_id Block ID for metadata.
	 * @param string $customer_id Stripe customer ID.
	 * @return array Response data with client_secret and subscription_id.
	 * @throws \Exception When subscription creation fails.
	 * @since x.x.x
	 */
	private function create_subscription( $amount, $currency, $description, $interval, $interval_count, $trial_days, $application_fee_amount, $stripe_account_id, $block_id, $customer_id, $secret_key ) {

		// Initialize Stripe API key first
		\Stripe\Stripe::setApiKey( $secret_key );

		try {
			// Create product
			$product = \Stripe\Product::create(
				[
					'name'     => $description,
					'metadata' => [
						'source'   => 'SureForms',
						'block_id' => $block_id,
						'type'     => 'subscription',
					],
				]
			);

			// Create price - fix interval_count to use actual value instead of hardcoded 3
			$price = \Stripe\Price::create(
				[
					'unit_amount' => $amount,
					'currency'    => strtolower( $currency ),
					'recurring'   => [
						'interval'       => $interval,
						'interval_count' => $interval_count,
					],
					'product'     => $product->id,
				]
			);

			// Create subscription following simple-stripe-subscriptions exact approach
			$subscription_create_data = [
				'customer'         => $customer_id,
				'items'            => [
					[
						'price' => $price->id,
					],
				],
				'payment_behavior' => 'default_incomplete',
				'off_session'      => true, // Critical: Forces payment intent creation
				'payment_settings' => [
					'save_default_payment_method' => 'on_subscription',
					'payment_method_types'        => [ 'card', 'link' ],
				],
				'expand'           => [ 'latest_invoice.payment_intent' ],
				'metadata'         => [
					'source'           => 'SureForms',
					'block_id'         => $block_id,
					'original_amount'  => $amount,
					'billing_interval' => $interval,
					'interval_count'   => $interval_count,
				],
			];

			// Handle trial period
			if ( $trial_days > 0 ) {
				$subscription_create_data['trial_period_days'] = $trial_days;
			}

			// Create subscription via Stripe SDK
			$subscription = \Stripe\Subscription::create( $subscription_create_data );

			// Extract client secret following simple-stripe-subscriptions approach
			$client_secret = $subscription->latest_invoice->payment_intent->client_secret;

			// Validate payment intent status like simple-stripe-subscriptions
			$valid_statuses        = [ 'succeeded', 'requires_action', 'requires_confirmation', 'requires_payment_method' ];
			$payment_intent_status = $subscription->latest_invoice->payment_intent->status ?? 'missing';

			// Debug logging
			error_log( 'SureForms: Payment Intent Status = ' . $payment_intent_status );
			error_log( 'SureForms: Valid Statuses = ' . implode( ', ', $valid_statuses ) );

			if ( ! $subscription->latest_invoice->payment_intent ||
				 ! in_array( $payment_intent_status, $valid_statuses ) ) {
				throw new \Exception(
					sprintf(
						'Stripe subscription stopped. Invalid PaymentIntent status: %s. Expected: %s',
						$payment_intent_status,
						implode( ', ', $valid_statuses )
					)
				);
			}

			// Return simple response like simple-stripe-subscriptions
			$response = [
				'type'              => 'subscription',
				'client_secret'     => $client_secret,
				'subscription_id'   => $subscription->id,
				'customer_id'       => $customer_id,
				'payment_intent_id' => $subscription->latest_invoice->payment_intent->id,
				'status'            => $subscription->status,
				'amount'            => $this->amount_convert_cents_to_usd( $amount ),
				'interval'          => $interval,
			];

			error_log( 'SureForms: Subscription creation successful using simple approach. ID: ' . $subscription->id );

			return $response;

		} catch ( \Exception $e ) {
			throw new \Exception( 'Unexpected error: ' . $e->getMessage() );
		}
	}

	/**
	 * Get or create Stripe customer
	 *
	 * @return string|false Customer ID on success, false on failure.
	 * @since x.x.x
	 */
	private function get_or_create_stripe_customer() {
		$current_user = wp_get_current_user();

		if ( $current_user->ID > 0 ) {
			// Logged-in user - check for existing customer ID in user meta
			$customer_id = get_user_meta( $current_user->ID, 'srfm_stripe_customer_id', true );

			if ( ! empty( $customer_id ) && $this->verify_stripe_customer( $customer_id ) ) {
				return $customer_id;
			}

			// Create new customer for logged-in user
			return $this->create_stripe_customer_for_user( $current_user );
		}
			// Non-logged-in user - create temporary customer
			return $this->create_stripe_customer_for_guest();

	}
	/**
	 * Create Stripe customer for logged-in user
	 *
	 * @param \WP_User $user WordPress user object.
	 * @return string|false Customer ID on success, false on failure.
	 * @since x.x.x
	 */
	private function create_stripe_customer_for_user( $user ) {
		try {
			$customer_data = [
				'email'       => $user->user_email,
				'name'        => trim( $user->first_name . ' ' . $user->last_name ) ?: $user->display_name,
				'description' => sprintf( 'WordPress User ID: %d', $user->ID ),
				'metadata'    => [
					'source'        => 'SureForms',
					'wp_user_id'    => $user->ID,
					'wp_username'   => $user->user_login,
					'wp_user_email' => $user->user_email,
				],
			];

			$customer = \Stripe\Customer::create( $customer_data );

			// Save customer ID to user meta for future use
			update_user_meta( $user->ID, 'srfm_stripe_customer_id', $customer->id );

			return $customer->id;

		} catch ( \Exception $e ) {
			error_log( 'SureForms Stripe Customer Creation Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Create Stripe customer for guest user
	 *
	 * @return string|false Customer ID on success, false on failure.
	 * @since x.x.x
	 */
	private function create_stripe_customer_for_guest() {
		try {
			// Get form data if available for guest customer info
			$form_data = $this->get_form_data_for_guest_customer();

			$customer_data = [
				'description' => 'Guest User - SureForms Subscription',
				'metadata'    => [
					'source'     => 'SureForms',
					'user_type'  => 'guest',
					'created_at' => current_time( 'mysql' ),
					'ip_address' => $this->get_user_ip(),
				],
			];

			// Add email and name if available from form data
			if ( ! empty( $form_data['email'] ) ) {
				$customer_data['email']                  = sanitize_email( $form_data['email'] );
				$customer_data['metadata']['form_email'] = $form_data['email'];
			}

			if ( ! empty( $form_data['name'] ) ) {
				$customer_data['name']                  = sanitize_text_field( $form_data['name'] );
				$customer_data['metadata']['form_name'] = $form_data['name'];
			}

			$customer = \Stripe\Customer::create( $customer_data );

			return $customer->id;

		} catch ( \Exception $e ) {
			error_log( 'SureForms Stripe Guest Customer Creation Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Verify Stripe customer exists
	 *
	 * @param string $customer_id Stripe customer ID.
	 * @return bool True if customer exists, false otherwise.
	 * @since x.x.x
	 */
	private function verify_stripe_customer( $customer_id ) {
		try {
			$customer = \Stripe\Customer::retrieve( $customer_id );
			return ! empty( $customer->id ) && 'deleted' !== $customer->object;
		} catch ( \Exception $e ) {
			error_log( 'SureForms Stripe Customer Verification Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Get form data for guest customer creation
	 *
	 * @return array Form data with email and name if available.
	 * @since x.x.x
	 */
	private function get_form_data_for_guest_customer() {
		$form_data = [
			'email' => '',
			'name'  => '',
		];

		// Try to get email and name from common form field names
		$email_fields = [ 'email', 'user_email', 'customer_email', 'contact_email' ];
		$name_fields  = [ 'name', 'full_name', 'customer_name', 'first_name', 'last_name' ];

		foreach ( $email_fields as $field ) {
			if ( ! empty( $_POST[ $field ] ) && is_email( $_POST[ $field ] ) ) {
				$form_data['email'] = sanitize_email( wp_unslash( $_POST[ $field ] ) );
				break;
			}
		}

		foreach ( $name_fields as $field ) {
			if ( ! empty( $_POST[ $field ] ) ) {
				$form_data['name'] = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
				break;
			}
		}

		// Try to combine first_name and last_name if available
		if ( empty( $form_data['name'] ) ) {
			$first_name = ! empty( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
			$last_name  = ! empty( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';

			if ( $first_name || $last_name ) {
				$form_data['name'] = trim( $first_name . ' ' . $last_name );
			}
		}

		return $form_data;
	}

	/**
	 * Get user IP address
	 *
	 * @return string User IP address.
	 * @since x.x.x
	 */
	private function get_user_ip() {
		// Check for various IP address headers
		$ip_keys = [ 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ];

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				// Handle comma-separated IPs (from proxies)
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}
			}
		}

		return '127.0.0.1'; // Fallback
	}

	/**
	 * Save already completed subscription to database
	 *
	 * @param object $subscription Stripe subscription object.
	 * @param string $setup_intent_id Setup intent ID.
	 * @param array  $form_data Form data.
	 * @param string $block_id Block ID.
	 * @param string $payment_mode Payment mode.
	 * @return bool True on success, false on failure.
	 * @since x.x.x
	 */
	private function save_completed_subscription( $subscription, $setup_intent_id, $form_data, $block_id, $payment_mode ) {
		try {
			// Prepare subscription data for database
			$entry_data                        = [];
			$entry_data['form_id']             = $form_data['form-id'] ?? '';
			$entry_data['block_id']            = $block_id;
			$entry_data['status']              = 'succeeded';
			$entry_data['total_amount']        = $this->amount_convert_cents_to_usd( $subscription->latest_invoice ? $subscription->latest_invoice->amount_paid : 0 );
			$entry_data['currency']            = $subscription->currency ?? 'usd';
			$entry_data['entry_id']            = 0;
			$entry_data['gateway']             = 'stripe';
			$entry_data['type']                = 'subscription';
			$entry_data['mode']                = $payment_mode;
			$entry_data['transaction_id']      = $setup_intent_id;
			$entry_data['customer_id']         = $subscription->customer;
			$entry_data['subscription_id']     = $subscription->id;
			$entry_data['subscription_status'] = $subscription->status;

			// Store detailed subscription data
			$entry_data['payment_data'] = [
				'subscription'     => [
					'id'                   => $subscription->id,
					'status'               => $subscription->status,
					'customer_id'          => $subscription->customer,
					'current_period_start' => $subscription->current_period_start,
					'current_period_end'   => $subscription->current_period_end,
					'trial_start'          => $subscription->trial_start,
					'trial_end'            => $subscription->trial_end,
				],
				'already_complete' => true,
				'confirmed_at'     => time(),
			];

			// Add log entry
			$entry_data['log'] = [
				[
					'title'     => 'Subscription Already Complete',
					'timestamp' => time(),
					'messages'  => [
						sprintf( 'Subscription ID: %s', $subscription->id ),
						sprintf( 'Subscription Status: %s', $subscription->status ),
						sprintf( 'Customer ID: %s', $subscription->customer ),
						sprintf( 'Total Amount: %s %s', number_format( $entry_data['total_amount'], 2 ), strtoupper( $entry_data['currency'] ) ),
					],
				],
			];

			// Save to database
			$payment_entry_id = Payments::add( $entry_data );

			if ( $payment_entry_id ) {
				// Store in static array for later entry linking
				$this->stripe_payment_entries[] = [
					'payment_id' => $subscription->id,
					'block_id'   => $block_id,
					'form_id'    => $form_data['form-id'] ?? '',
				];

				error_log(
					sprintf(
						'SureForms: Already completed subscription saved. ID: %s, Status: %s, Payment Entry ID: %d',
						$subscription->id,
						$subscription->status,
						$payment_entry_id
					)
				);

				return true;
			}
				throw new \Exception( __( 'Failed to save completed subscription data.', 'sureforms' ) );

		} catch ( \Exception $e ) {
			error_log( 'SureForms Save Completed Subscription Error: ' . $e->getMessage() );
			return false;
		}
	}
}
