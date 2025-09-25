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

			// Extract payment ID - this will be the payment intent ID for one-time payments,
			// or the payment method ID (result.setupIntent.payment_method) for subscriptions.
			$payment_id   = ! empty( $payment_value['paymentId'] ) ? $payment_value['paymentId'] : '';
			$setup_intent = ! empty( $payment_value['setupIntent'] ) ? $payment_value['setupIntent'] : '';

			if ( empty( $payment_id ) && empty( $setup_intent ) ) {
				continue;
			}

			$block_id     = ! empty( $payment_value['blockId'] ) ? $payment_value['blockId'] : '';
			$payment_type = ! empty( $payment_value['paymentType'] ) ? $payment_value['paymentType'] : '';

			if ( empty( $block_id ) || empty( $payment_type ) ) {
				continue;
			}

			if ( 'stripe-subscription' === $payment_type ) {

				/**
				 * For subscription payments, we receive the following data structure:
				 * - paymentMethod: Stripe payment method ID (e.g., "pm_1S82ZkHqS7N4oFQhruGV67u1")
				 * - setupIntent: Stripe setup intent ID (e.g., "seti_1S82ZkHqS7N4oFQhPa4LYPYg")
				 * - subscriptionId: Stripe subscription ID (e.g., "sub_1S82ZiHqS7N4oFQhPGhm2eNR")
				 * - customerId: Stripe customer ID (e.g., "cus_T4Apjla33GlYAk")
				 * - blockId: Form block identifier (e.g., "be920796")
				 * - paymentType: Payment type identifier ("stripe-subscription")
				 * - status: Payment status ("succeeded")
				 * - paymentItems: Array containing subscription item details
				 */
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
		$subscription_id = ! empty( $subscription_value['subscriptionId'] ) ? $subscription_value['subscriptionId'] : '';

		if ( empty( $subscription_id ) ) {
			error_log( 'SureForms: Missing subscription ID' );
			return false;
		}

		$customer_id = ! empty( $subscription_value['customerId'] ) ? $subscription_value['customerId'] : '';

		if ( empty( $customer_id ) ) {
			error_log( 'SureForms: Missing customer ID' );
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

			// Update subscription with payment method from setup intent if available
			$setup_intent_id = ! empty( $subscription_value['setupIntent'] ) ? $subscription_value['setupIntent'] : '';
			$paid_invoice    = [];
			if ( ! empty( $setup_intent_id ) ) {
				try {
					$setup_intent = $this->stripe_api_request(
						'setup_intents',
						'GET',
						[],
						$setup_intent_id
					);

					if ( ! empty( $setup_intent['payment_method'] ) ) {
						$subscription_update = $this->stripe_api_request(
							'subscriptions',
							'POST',
							[
								'default_payment_method' => $setup_intent['payment_method'],
							],
							$subscription_id
						);

						$invoice = $this->stripe_api_request(
							'invoices',
							'GET',
							[],
							$subscription_update['latest_invoice']
						);

						// Explicitly attempt to pay the invoice
						$paid_invoice = $this->stripe_api_request(
							'invoices',
							'POST',
							[],
							$invoice['id'] . '/pay'
						);

						// Get the subscription.
						$subscription = $this->stripe_api_request(
							'subscriptions',
							'GET',
							[],
							$subscription_id
						);

						error_log( 'SureForms: Updated subscription default payment method: ' . $setup_intent['payment_method'] );
					}
				} catch ( \Exception $e ) {
					error_log( 'SureForms: Failed to update subscription payment method: ' . $e->getMessage() );
				}
			}

			error_log( 'SureForms: Retrieved subscription status: ' . $paid_invoice['status'] );

			// Use simple-stripe-subscriptions validation logic - check if subscription is in good state
			$is_subscription_active = in_array( $subscription['status'], [ 'active', 'trialing' ] );
			$final_status           = $is_subscription_active ? 'succeeded' : 'failed';

			// Prepare minimal subscription data for database
			$entry_data = [
				'form_id'             => $form_data['form-id'] ?? '',
				'block_id'            => $block_id,
				'status'              => $final_status,
				'total_amount'        => $this->amount_convert_cents_to_usd( ! empty( $paid_invoice['amount_paid'] ) ? $paid_invoice['amount_paid'] : 0 ),
				'currency'            => $paid_invoice['currency'] ?? 'usd',
				'entry_id'            => 0,
				'gateway'             => 'stripe',
				'type'                => 'subscription',
				'mode'                => $payment_mode,
				'transaction_id'      => $subscription_id,
				'customer_id'         => $customer_id,
				'subscription_id'     => $subscription_id,
				'subscription_status' => $paid_invoice['status'],
				'payment_data'        => [
					'initial_invoice' => $paid_invoice,
					'subscription'    => $subscription,
				],
			];

			// Add simple log entry
			$entry_data['log'] = [
				[
					'title'     => 'Subscription Verification',
					'timestamp' => time(),
					'messages'  => [
						sprintf( 'Subscription ID: %s', $subscription_id ),
						sprintf( 'Payment Intent ID: %s', $setup_intent_id ),
						sprintf( 'Subscription Status: %s', $paid_invoice['status'] ),
						sprintf( 'Customer ID: %s', $customer_id ),
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

				// Create the first individual payment record for this subscription if payment was successful
				if ( $is_subscription_active && ! empty( $paid_invoice ) ) {
					$this->create_subscription_single_payment(
						$subscription_id,
						$setup_intent_id, // Use setup intent ID as transaction ID for first payment
						$paid_invoice,
						$form_data['form-id'] ?? 0,
						$block_id
					);
				}

				error_log( 'SureForms: Subscription verification complete. Status: ' . $final_status );
				return $is_subscription_active;
			}
		} catch ( \Exception $e ) {
			error_log( 'SureForms Subscription Verification Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Create individual payment record for subscription billing cycle.
	 * This method can be called by webhooks or other processes to track individual subscription payments.
	 *
	 * @param string $subscription_id Stripe subscription ID.
	 * @param string $payment_intent_id Stripe payment intent ID.
	 * @param array  $invoice_data Invoice data from Stripe.
	 * @param int    $form_id Form ID associated with the subscription.
	 * @param string $block_id Block ID associated with the subscription.
	 * @since x.x.x
	 * @return int|false Payment ID if successful, false otherwise.
	 */
	public function create_subscription_single_payment( $subscription_id, $payment_intent_id, $invoice_data, $form_id = 0, $block_id = '' ) {
		if ( empty( $subscription_id ) || empty( $payment_intent_id ) || empty( $invoice_data ) ) {
			error_log( 'SureForms: Missing required data for creating subscription single payment' );
			return false;
		}

		// Get the main subscription record to inherit form and block details if not provided
		$main_subscription = Payments::get_main_subscription_record( $subscription_id );
		if ( ! $main_subscription ) {
			error_log( 'SureForms: Could not find main subscription record for ID: ' . $subscription_id );
			return false;
		}

		// Use provided form_id and block_id, or fall back to subscription record values
		$form_id  = ! empty( $form_id ) ? $form_id : ( $main_subscription['form_id'] ?? 0 );
		$block_id = ! empty( $block_id ) ? $block_id : ( $main_subscription['block_id'] ?? '' );

		// Get payment settings for mode detection
		$payment_settings = get_option( 'srfm_payments_settings', [] );
		$payment_mode     = $payment_settings['payment_mode'] ?? 'test';

		// Prepare subscription individual payment entry data
		$entry_data = [
			'form_id'             => $form_id,
			'block_id'            => $block_id,
			'status'              => $invoice_data['status'] === 'paid' ? 'succeeded' : 'failed',
			'total_amount'        => $this->amount_convert_cents_to_usd( $invoice_data['amount_paid'] ?? 0 ),
			'refunded_amount'     => '0.00000000', // Required field - default to 0
			'currency'            => $invoice_data['currency'] ?? 'usd',
			'entry_id'            => $main_subscription['entry_id'] ?? 0, // Link to same entry as subscription
			'gateway'             => 'stripe',
			'type'                => 'payment', // This is a payment transaction linked to subscription
			'mode'                => $payment_mode,
			'transaction_id'      => $payment_intent_id,
			'customer_id'         => $invoice_data['customer'] ?? '',
			'subscription_id'     => $subscription_id, // Link back to subscription
			'subscription_status' => '', // Not applicable for individual payments
		];

		// Add log entry for audit trail
		$entry_data['log'] = [
			[
				'title'     => 'Subscription Single Payment',
				'timestamp' => time(),
				'messages'  => [
					sprintf( 'Subscription ID: %s', $subscription_id ),
					sprintf( 'Payment Intent ID: %s', $payment_intent_id ),
					sprintf( 'Invoice ID: %s', $invoice_data['id'] ?? 'N/A' ),
					sprintf( 'Amount: %s %s', number_format( $entry_data['total_amount'], 2 ), strtoupper( $entry_data['currency'] ) ),
					sprintf( 'Status: %s', $entry_data['status'] ),
					'Created via subscription billing cycle',
				],
			],
		];

		// Log the data being inserted for debugging
		error_log( 'SureForms: Attempting to create subscription payment with data: ' . wp_json_encode( $entry_data ) );

		// Save to database
		$payment_entry_id = Payments::add( $entry_data );

		if ( $payment_entry_id ) {
			// Store in static array for later entry linking
			$this->stripe_payment_entries[] = [
				'payment_id' => $payment_intent_id,
				'block_id'   => $block_id,
				'form_id'    => $form_id,
			];

			error_log( 'SureForms: Created subscription single payment record. ID: ' . $payment_entry_id );
			return $payment_entry_id;
		}
			error_log( 'SureForms: Failed to create subscription single payment record. Data: ' . wp_json_encode( $entry_data ) );
			return false;

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

			// Get license key for consistency with other endpoints
			// $license_key = $this->get_license_key();

			// Prepare refund data for middleware
			// $refund_data = apply_filters(
			// 'srfm_refund_payment_data',
			// [
			// 'secret_key'     => $secret_key,
			// 'payment_intent' => $transaction_id,
			// 'amount'         => $refund_amount,
			// 'license_key'    => $license_key,
			// 'metadata'       => [
			// 'source'      => 'SureForms',
			// 'payment_id'  => $payment_id,
			// 'refunded_at' => time(),
			// ],
			// ]
			// );

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
		$subscription_interval_count = absint( $_POST['subscription_interval_count'] ?? 6 );
		$subscription_trial_days     = absint( $_POST['subscription_trial_days'] ?? 3 );

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
		$refund_amount = isset( $_POST['refund_amount'] ) ? absint( $_POST['refund_amount'] ) : 0;

		if ( $refund_amount <= 0 ) {
			wp_send_json_error( __( 'Invalid refund amount.', 'sureforms' ) );
			return;
		}

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
			$this->refund_subscription_payment( $payment, $refund_amount );
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
	 * Refund subscription payment with enhanced validation and error handling
	 *
	 * This method handles refunding the payment intent associated with a subscription,
	 * with comprehensive validation and detailed error reporting.
	 *
	 * @param int    $payment_id Payment record ID.
	 * @param string $transaction_id Transaction ID (subscription ID or payment intent ID).
	 * @param int    $refund_amount Refund amount in cents.
	 * @return void
	 * @since x.x.x
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
	 * Check if payment is subscription-related
	 *
	 * @param array $payment Payment record.
	 * @return bool True if payment is subscription-related, false otherwise.
	 * @since x.x.x
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
	 * Create refund for subscription payment using the most appropriate method
	 *
	 * @param array  $payment Payment record.
	 * @param string $transaction_id Transaction ID.
	 * @param int    $refund_amount Refund amount in cents.
	 * @return array|false Refund data or false on failure.
	 * @since x.x.x
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
	 * @since x.x.x
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
	 * @since x.x.x
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
	 * Get charge ID from payment data
	 *
	 * @param array $payment Payment record.
	 * @return string|null Charge ID or null if not found.
	 * @since x.x.x
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
	 * Validate subscription refund amount
	 *
	 * @param array $payment Payment record.
	 * @param int   $refund_amount Refund amount in cents.
	 * @return array Validation result with 'valid' boolean and 'message' string.
	 * @since x.x.x
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
	 * Convert technical error messages to user-friendly ones
	 *
	 * @param string $technical_error Technical error message.
	 * @return string User-friendly error message.
	 * @since x.x.x
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
	 * Get payment intent ID for subscription refunds with enhanced validation and error handling
	 *
	 * @param array  $payment Payment record.
	 * @param string $transaction_id Transaction ID from request.
	 * @return string|null Payment intent ID or null if not found.
	 * @since x.x.x
	 */
	private function get_subscription_payment_intent_id( $payment, $transaction_id ) {
		error_log( 'SureForms: Starting payment intent resolution for subscription refund. Transaction ID: ' . $transaction_id );

		// Validate payment record
		if ( empty( $payment ) || ! is_array( $payment ) ) {
			error_log( 'SureForms: Invalid payment record provided for payment intent resolution' );
			return null;
		}

		// Validate subscription payment type
		if ( empty( $payment['type'] ) || 'subscription' !== $payment['type'] ) {
			error_log( 'SureForms: Payment is not a subscription type. Type: ' . ( $payment['type'] ?? 'unknown' ) );
			return null;
		}

		// Validate subscription payment status - only allow refunds for succeeded or partially_refunded payments
		$refundable_statuses = [ 'succeeded', 'partially_refunded' ];
		if ( empty( $payment['status'] ) || ! in_array( $payment['status'], $refundable_statuses, true ) ) {
			error_log( 'SureForms: Subscription payment not in refundable status. Current status: ' . ( $payment['status'] ?? 'unknown' ) );
			return null;
		}

		// Method 1: Check if transaction_id is already a payment intent ID
		if ( ! empty( $transaction_id ) && strpos( $transaction_id, 'pi_' ) === 0 ) {
			error_log( 'SureForms: Transaction ID is already a payment intent: ' . $transaction_id );
			// Validate the payment intent exists and is refundable
			if ( $this->validate_payment_intent_for_refund( $transaction_id ) ) {
				return $transaction_id;
			}
		}

		// Method 2: Check if transaction_id is a setup intent (common for subscriptions)
		if ( ! empty( $transaction_id ) && strpos( $transaction_id, 'seti_' ) === 0 ) {
			error_log( 'SureForms: Transaction ID is a setup intent, looking for associated payment intent' );
			$payment_intent_id = $this->get_payment_intent_from_setup_intent( $transaction_id, $payment );
			if ( $payment_intent_id ) {
				return $payment_intent_id;
			}
		}

		// Method 3: Use subscription ID to get latest paid invoice
		$subscription_id = $this->get_subscription_id_from_payment( $payment, $transaction_id );
		if ( $subscription_id ) {
			error_log( 'SureForms: Attempting to resolve payment intent from subscription: ' . $subscription_id );
			$payment_intent_id = $this->get_payment_intent_from_subscription( $subscription_id );
			if ( $payment_intent_id ) {
				return $payment_intent_id;
			}
		}

		// Method 4: Look for payment intent in payment_data
		$payment_intent_id = $this->get_payment_intent_from_payment_data( $payment );
		if ( $payment_intent_id ) {
			error_log( 'SureForms: Found payment intent in payment_data: ' . $payment_intent_id );
			return $payment_intent_id;
		}

		error_log( 'SureForms: Failed to resolve payment intent ID for subscription refund. Payment ID: ' . ( $payment['id'] ?? 'unknown' ) );
		return null;
	}

	/**
	 * Validate that a payment intent is refundable
	 *
	 * @param string $payment_intent_id Payment intent ID to validate.
	 * @return bool True if payment intent is refundable, false otherwise.
	 * @since x.x.x
	 */
	private function validate_payment_intent_for_refund( $payment_intent_id ) {
		try {
			$payment_intent = $this->stripe_api_request( 'payment_intents', 'GET', [], $payment_intent_id );

			if ( ! $payment_intent ) {
				error_log( 'SureForms: Payment intent not found: ' . $payment_intent_id );
				return false;
			}

			// Check if payment intent is in a refundable status
			$refundable_statuses = [ 'succeeded' ];
			if ( ! in_array( $payment_intent['status'], $refundable_statuses, true ) ) {
				error_log( 'SureForms: Payment intent not in refundable status. Status: ' . $payment_intent['status'] );
				return false;
			}

			error_log( 'SureForms: Payment intent validated for refund: ' . $payment_intent_id );
			return true;

		} catch ( \Exception $e ) {
			error_log( 'SureForms: Error validating payment intent: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Get payment intent from setup intent (for subscription first payments)
	 *
	 * @param string $setup_intent_id Setup intent ID.
	 * @param array  $payment Payment record.
	 * @return string|null Payment intent ID or null if not found.
	 * @since x.x.x
	 */
	private function get_payment_intent_from_setup_intent( $setup_intent_id, $payment ) {
		try {
			// For subscriptions using setup intents, we need to find the actual payment intent
			// This usually means looking at the subscription's invoices
			if ( ! empty( $payment['subscription_id'] ) ) {
				return $this->get_payment_intent_from_subscription( $payment['subscription_id'] );
			}
			return null;

		} catch ( \Exception $e ) {
			error_log( 'SureForms: Error getting payment intent from setup intent: ' . $e->getMessage() );
			return null;
		}
	}

	/**
	 * Get subscription ID from payment record or transaction ID
	 *
	 * @param array  $payment Payment record.
	 * @param string $transaction_id Transaction ID.
	 * @return string|null Subscription ID or null if not found.
	 * @since x.x.x
	 */
	private function get_subscription_id_from_payment( $payment, $transaction_id ) {
		// Check payment record first
		if ( ! empty( $payment['subscription_id'] ) ) {
			return $payment['subscription_id'];
		}

		// Check if transaction_id is a subscription ID
		if ( ! empty( $transaction_id ) && strpos( $transaction_id, 'sub_' ) === 0 ) {
			return $transaction_id;
		}

		return null;
	}

	/**
	 * Get payment intent from subscription's latest paid invoice
	 *
	 * @param string $subscription_id Subscription ID.
	 * @return string|null Payment intent ID or null if not found.
	 * @since x.x.x
	 */
	private function get_payment_intent_from_subscription( $subscription_id ) {
		try {
			// Get subscription details
			$subscription = $this->stripe_api_request( 'subscriptions', 'GET', [], $subscription_id );
			if ( ! $subscription ) {
				error_log( 'SureForms: Subscription not found: ' . $subscription_id );
				return null;
			}

			// Check subscription status
			$active_statuses = [ 'active', 'trialing', 'past_due' ];
			if ( ! in_array( $subscription['status'], $active_statuses, true ) ) {
				error_log( 'SureForms: Subscription not in active status: ' . $subscription['status'] );
			}

			// Get latest invoice
			if ( empty( $subscription['latest_invoice'] ) ) {
				error_log( 'SureForms: No latest invoice found for subscription: ' . $subscription_id );
				return null;
			}

			$invoice = $this->stripe_api_request( 'invoices', 'GET', [], $subscription['latest_invoice'] );
			if ( ! $invoice ) {
				error_log( 'SureForms: Invoice not found: ' . $subscription['latest_invoice'] );
				return null;
			}

			// Check if invoice was paid
			if ( 'paid' !== $invoice['status'] ) {
				error_log( 'SureForms: Latest invoice not paid. Status: ' . $invoice['status'] );

				// Try to find the last paid invoice
				$paid_invoice = $this->find_last_paid_invoice( $subscription_id );
				if ( $paid_invoice && ! empty( $paid_invoice['payment_intent'] ) ) {
					error_log( 'SureForms: Found last paid invoice with payment intent: ' . $paid_invoice['payment_intent'] );
					return $paid_invoice['payment_intent'];
				}

				return null;
			}

			// Get payment intent from invoice
			if ( empty( $invoice['payment_intent'] ) ) {
				error_log( 'SureForms: No payment intent found in invoice: ' . $subscription['latest_invoice'] );
				return null;
			}

			error_log( 'SureForms: Successfully resolved payment intent from subscription: ' . $invoice['payment_intent'] );
			return $invoice['payment_intent'];

		} catch ( \Exception $e ) {
			error_log( 'SureForms: Error getting payment intent from subscription: ' . $e->getMessage() );
			return null;
		}
	}

	/**
	 * Find the last paid invoice for a subscription
	 *
	 * @param string $subscription_id Subscription ID.
	 * @return array|null Invoice data or null if not found.
	 * @since x.x.x
	 */
	private function find_last_paid_invoice( $subscription_id ) {
		try {
			// Get invoices for the subscription
			$invoices = $this->stripe_api_request(
				'invoices',
				'GET',
				[
					'subscription' => $subscription_id,
					'status'       => 'paid',
					'limit'        => 10,
				]
			);

			if ( empty( $invoices['data'] ) ) {
				error_log( 'SureForms: No paid invoices found for subscription: ' . $subscription_id );
				return null;
			}

			// Return the most recent paid invoice
			$last_paid_invoice = $invoices['data'][0];
			error_log( 'SureForms: Found last paid invoice: ' . $last_paid_invoice['id'] );
			return $last_paid_invoice;

		} catch ( \Exception $e ) {
			error_log( 'SureForms: Error finding last paid invoice: ' . $e->getMessage() );
			return null;
		}
	}

	/**
	 * Get payment intent from payment_data stored in database
	 *
	 * @param array $payment Payment record.
	 * @return string|null Payment intent ID or null if not found.
	 * @since x.x.x
	 */
	private function get_payment_intent_from_payment_data( $payment ) {
		if ( empty( $payment['payment_data'] ) ) {
			return null;
		}

		$payment_data = Helper::get_array_value( $payment['payment_data'] );
		if ( empty( $payment_data ) ) {
			return null;
		}

		// Look for payment intent in various places in payment_data
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

		return null;
	}

	/**
	 * Get nested value from array using dot notation
	 *
	 * @param array  $array Array to search.
	 * @param string $key Dot-separated key path.
	 * @return mixed Value or null if not found.
	 * @since x.x.x
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
		$license_key = $this->get_license_key();
		// Prepare subscription data for middleware
		$subscription_data = apply_filters(
			'srfm_create_subscription_data',
			[
				'secret_key'             => $secret_key,
				'customer_id'            => $customer_id,
				'amount'                 => $amount,
				'currency'               => strtolower( $currency ),
				'description'            => $description,
				'interval'               => $interval,
				'interval_count'         => $interval_count,
				'trial_days'             => $trial_days,
				'application_fee_amount' => $application_fee_amount,
				'stripe_account_id'      => $stripe_account_id,
				'license_key'            => $license_key,
				'block_id'               => $block_id,
				'metadata'               => [
					'source'           => 'SureForms',
					'block_id'         => $block_id,
					'original_amount'  => $amount,
					'billing_interval' => $interval,
					'interval_count'   => $interval_count,
				],
			]
		);

		$endpoint = 'prod' === SRFM_PAYMENTS_ENV ? SRFM_PAYMENTS_PROD . 'subscription/create' : SRFM_PAYMENTS_LOCAL . 'subscription/create';

		// Call middleware subscription creation endpoint
		$subscription_response = wp_remote_post(
			$endpoint,
			[
				'body'    => base64_encode( wp_json_encode( $subscription_data ) ),
				'headers' => [
					'Content-Type' => 'application/json',
				],
				'timeout' => 60, // Subscription creation can take longer
			]
		);

		if ( is_wp_error( $subscription_response ) ) {
			throw new \Exception( __( 'Failed to create subscription through middleware.', 'sureforms' ) );
		}

		$response_body = wp_remote_retrieve_body( $subscription_response );
		if ( empty( $response_body ) ) {
			throw new \Exception( __( 'Empty response from subscription creation.', 'sureforms' ) );
		}

		$subscription = json_decode( $response_body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			throw new \Exception( __( 'Invalid JSON response from subscription creation.', 'sureforms' ) );
		}

		$payment_intent_id = $subscription['setup_intent']['id'];
		$subscription_id   = $subscription['subscription_data']['id'];
		$client_secret     = $subscription['client_secret'];
		return [
			'type'              => 'subscription',
			'client_secret'     => $client_secret,
			'subscription_id'   => $subscription_id,
			'customer_id'       => $customer_id,
			'payment_intent_id' => $payment_intent_id,
			'amount'            => $this->amount_convert_cents_to_usd( $amount ),
			'interval'          => $interval,
		];
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

			$customer = $this->stripe_api_request( 'customers', 'POST', $customer_data );

			if ( ! $customer || empty( $customer['id'] ) ) {
				throw new \Exception( __( 'Failed to create Stripe customer.', 'sureforms' ) );
			}

			// Save customer ID to user meta for future use
			update_user_meta( $user->ID, 'srfm_stripe_customer_id', $customer['id'] );

			return $customer['id'];

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

			$customer = $this->stripe_api_request( 'customers', 'POST', $customer_data );

			if ( ! $customer || empty( $customer['id'] ) ) {
				throw new \Exception( __( 'Failed to create Stripe guest customer.', 'sureforms' ) );
			}

			return $customer['id'];

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
			$customer = $this->stripe_api_request( 'customers', 'GET', [], $customer_id );
			/**
			 * Stripe API returns customer object with the following structure:
			 * {
			 *     "id": "cus_Syq4hfWO9S5XC2",
			 *     "object": "customer",
			 *     "deleted": true // Present and true only if customer is deleted
			 * }
			 *
			 * When a customer is deleted, the 'deleted' property is set to true.
			 * Active customers do not have this property or it's set to false.
			 */

			$is_deleted_customer = isset( $customer['deleted'] ) && true === $customer['deleted'];

			return ! empty( $customer['id'] ) && false === $is_deleted_customer;
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
	 * Make a direct Stripe API call
	 *
	 * @param string $endpoint Stripe API endpoint (e.g., 'subscriptions', 'customers').
	 * @param string $method HTTP method (GET, POST, DELETE, etc.).
	 * @param array  $data Request data.
	 * @param string $resource_id Resource ID for specific resource operations.
	 * @return array|false API response or false on failure.
	 * @since x.x.x
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
	 * @since x.x.x
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
}
