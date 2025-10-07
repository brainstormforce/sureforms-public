<?php
/**
 * SureForms Payments Frontend Class.
 *
 * @package sureforms
 * @since 1.0.0
 */

namespace SRFM\Inc\Payments;

use SRFM\Inc\Database\Tables\Payments;
use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * SureForms Payments Frontend Class.
 *
 * @since 1.0.0
 */
class Front_End {
	use Get_Instance;

	/**
	 * Stores payment entries for later linking with form submissions.
	 *
	 * @var array
	 * @since x.x.x
	 */
	private $stripe_payment_entries = [];

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'wp_ajax_srfm_create_payment_intent', [ $this, 'create_payment_intent' ] );
		add_action( 'wp_ajax_nopriv_srfm_create_payment_intent', [ $this, 'create_payment_intent' ] );
		add_action( 'wp_ajax_srfm_create_subscription_intent', [ $this, 'create_subscription_intent' ] );
		add_action( 'wp_ajax_nopriv_srfm_create_subscription_intent', [ $this, 'create_subscription_intent' ] ); // For non-logged-in users.
		add_filter( 'srfm_form_submit_data', [ $this, 'validate_payment_fields' ], 5, 1 );
		add_action( 'srfm_form_submit', [ $this, 'update_payment_entry_id_form_submit' ], 10, 1 );
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
			// Validate Stripe connection.
			if ( ! Stripe\Stripe_Helper::is_stripe_connected() ) {
				throw new \Exception( __( 'Stripe is not connected.', 'sureforms' ) );
			}

			$payment_mode = Stripe\Stripe_Helper::get_stripe_mode();
			$secret_key   = Stripe\Stripe_Helper::get_stripe_secret_key();

			if ( empty( $secret_key ) ) {
				throw new \Exception( __( 'Stripe secret key not found.', 'sureforms' ) );
			}

			// Create payment intent with confirm: true for immediate processing.
			$payment_intent_data = apply_filters(
				'srfm_create_payment_intent_data',
				[
					'secret_key'                => $secret_key,
					'amount'                    => $amount,
					'currency'                  => strtolower( $currency ),
					'description'               => $description,
					'confirm'                   => false, // Will be confirmed by frontend.
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
			// TODO: Handle proper error handling.
			wp_send_json_error( __( 'Failed to create payment intent. Please try again.', 'sureforms' ) );
		}
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

		// Validate required fields like simple-stripe-subscriptions.
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

		$subscription_interval = sanitize_text_field( wp_unslash( $_POST['interval'] ?? 'month' ) );
		$plan_name             = sanitize_text_field( wp_unslash( $_POST['plan_name'] ?? 'Subscription Plan' ) );

		// Validate amount like simple-stripe-subscriptions.
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
			// Validate Stripe connection.
			if ( ! Stripe\Stripe_Helper::is_stripe_connected() ) {
				throw new \Exception( __( 'Stripe is not connected.', 'sureforms' ) );
			}

			$secret_key = Stripe\Stripe_Helper::get_stripe_secret_key();

			if ( empty( $secret_key ) ) {
				throw new \Exception( __( 'Stripe secret key not found.', 'sureforms' ) );
			}

			// Initialize Stripe SDK.
			if ( ! class_exists( '\Stripe\Stripe' ) ) {
				throw new \Exception( __( 'Stripe library not found.', 'sureforms' ) );
			}

			// Get or create Stripe customer for subscriptions.
			$customer_id = $this->get_or_create_stripe_customer();
			if ( ! $customer_id ) {
				throw new \Exception( __( 'Failed to create customer for subscription.', 'sureforms' ) );
			}

			$license_key = $this->get_license_key();
			// Prepare subscription data for middleware.
			$subscription_data = apply_filters(
				'srfm_create_subscription_data',
				[
					'secret_key'  => $secret_key,
					'customer_id' => $customer_id,
					'amount'      => $amount,
					'currency'    => strtolower( $currency ),
					'description' => $description,
					'interval'    => $subscription_interval,
					'license_key' => $license_key,
					'block_id'    => $block_id,
					'plan_name'   => $plan_name,
					'metadata'    => [
						'source'           => 'SureForms',
						'block_id'         => $block_id,
						'original_amount'  => $amount,
						'billing_interval' => $subscription_interval,
					],
				]
			);

			$endpoint = 'prod' === SRFM_PAYMENTS_ENV ? SRFM_PAYMENTS_PROD . 'subscription/create' : SRFM_PAYMENTS_LOCAL . 'subscription/create';

			// Call middleware subscription creation endpoint.
			$subscription_response = wp_remote_post(
				$endpoint,
				[
					'body'    => base64_encode( wp_json_encode( $subscription_data ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
					'headers' => [
						'Content-Type' => 'application/json',
					],
					'timeout' => 60, // Subscription creation can take longer.
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
			$response          = [
				'type'              => 'subscription',
				'client_secret'     => $client_secret,
				'subscription_id'   => $subscription_id,
				'customer_id'       => $customer_id,
				'payment_intent_id' => $payment_intent_id,
				'amount'            => $this->amount_convert_cents_to_usd( $amount ),
				'interval'          => $subscription_interval,
			];

			wp_send_json_success( $response );

		} catch ( \Exception $e ) {
			// TODO: Handle proper error handling.
			wp_send_json_error( sprintf( __( 'Unexpected error: %s', 'sureforms' ), $e->getMessage() ) );
		}
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
	 * Verify payment intent status
	 *
	 * @param string $payment_intent_id Payment intent ID.
	 * @return void|bool
	 * @since x.x.x
	 */
	private function verify_stripe_payment_intent_and_save( $payment_intent_id, $block_id, $form_data ) {
		try {
			$payment_mode = Stripe\Stripe_Helper::get_stripe_mode();
			$secret_key   = Stripe\Stripe_Helper::get_stripe_secret_key();

			if ( empty( $secret_key ) ) {
				return false;
			}

			// Retrieve confirmed payment intent status.
			$retrieve_data = apply_filters(
				'srfm_retrieve_payment_intent_data',
				[
					'secret_key'        => $secret_key,
					'payment_intent_id' => $payment_intent_id,
				]
			);

			// Call middleware retrieve endpoint to get confirmed payment intent.
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

			// Check if payment was actually confirmed successfully.
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
		} catch ( \Exception $e ) {
			// TODO: Handle proper error handling.
			return false;
		}
	}

	/**
	 * Simplified subscription verification using simple-stripe-subscriptions approach
	 *
	 * @param array  $subscription_value Subscription data from frontend.
	 * @param string $block_id Block ID.
	 * @param array  $form_data Form data.
	 * @return bool|void True if subscription is verified and saved successfully.
	 */
	public function verify_stripe_subscription_intent_and_save( $subscription_value, $block_id, $form_data ) {
		$subscription_id = ! empty( $subscription_value['subscriptionId'] ) ? $subscription_value['subscriptionId'] : '';

		if ( empty( $subscription_id ) ) {
			// TODO: Handle proper error handling.
			return false;
		}

		$customer_id = ! empty( $subscription_value['customerId'] ) ? $subscription_value['customerId'] : '';

		if ( empty( $customer_id ) ) {
			// TODO: Handle proper error handling.
			return false;
		}

		try {
			// Get payment mode and secret key.
			$payment_mode = Stripe\Stripe_Helper::get_stripe_mode();
			$secret_key   = Stripe\Stripe_Helper::get_stripe_secret_key();

			if ( empty( $secret_key ) ) {
				throw new \Exception( __( 'Stripe secret key not found.', 'sureforms' ) );
			}

			// Update subscription with payment method from setup intent if available.
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

						// Explicitly attempt to pay the invoice.
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

						// TODO: Handle proper error handling.
					}
				} catch ( \Exception $e ) {
					// TODO: Handle proper error handling.
				}
			}

			// TODO: Handle proper error handling.

			// Use simple-stripe-subscriptions validation logic - check if subscription is in good state.
			$is_subscription_active = in_array( $subscription['status'], [ 'active', 'trialing' ] );
			$final_status           = $is_subscription_active ? 'succeeded' : 'failed';

			// Prepare minimal subscription data for database.
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
				'transaction_id'      => $paid_invoice['charge'] ?? $paid_invoice['payment_intent'] ?? $subscription_id,
				'customer_id'         => $customer_id,
				'subscription_id'     => $subscription_id,
				'subscription_status' => $paid_invoice['status'],
				'payment_data'        => [
					'initial_invoice' => $paid_invoice,
					'subscription'    => $subscription,
				],
			];

			// Add simple log entry.
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

			// Save to database.
			$payment_entry_id = Payments::add( $entry_data );

			if ( $payment_entry_id ) {
				// Store in static array for later entry linking.
				$this->stripe_payment_entries[] = [
					'payment_id' => $subscription_id,
					'block_id'   => $block_id,
					'form_id'    => $form_data['form-id'] ?? '',
				];

				// Create the first individual payment record for this subscription if payment was successful.
				if ( $is_subscription_active && ! empty( $paid_invoice ) ) {
					// Extract the correct refundable transaction ID from the paid invoice.
					// Prefer charge ID over payment intent as it's more directly refundable.
					$transaction_id = $paid_invoice['charge'] ?? $paid_invoice['payment_intent'] ?? $setup_intent_id;

					$this->create_subscription_single_payment(
						$subscription_id,
						$transaction_id, // Use charge or payment_intent ID for refundability.
						$paid_invoice,
						$form_data['form-id'] ?? 0,
						$block_id
					);
				}

				// TODO: Handle proper error handling.
				return $is_subscription_active;
			}
		} catch ( \Exception $e ) {
			// TODO: Handle proper error handling.
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
			// TODO: Handle proper error handling.
			return false;
		}

		// Get the main subscription record to inherit form and block details if not provided.
		$main_subscription = Payments::get_main_subscription_record( $subscription_id );
		if ( ! $main_subscription ) {
			// TODO: Handle proper error handling.
			return false;
		}

		// Use provided form_id and block_id, or fall back to subscription record values.
		$form_id  = ! empty( $form_id ) ? $form_id : ( $main_subscription['form_id'] ?? 0 );
		$block_id = ! empty( $block_id ) ? $block_id : ( $main_subscription['block_id'] ?? '' );

		// Get payment mode.
		$payment_mode = Stripe\Stripe_Helper::get_stripe_mode();

		// Prepare subscription individual payment entry data.
		$entry_data = [
			'form_id'             => $form_id,
			'block_id'            => $block_id,
			'status'              => 'paid' === $invoice_data['status'] ? 'succeeded' : 'failed',
			'total_amount'        => $this->amount_convert_cents_to_usd( $invoice_data['amount_paid'] ?? 0 ),
			'refunded_amount'     => '0.00000000', // Required field - default to 0.
			'currency'            => $invoice_data['currency'] ?? 'usd',
			'entry_id'            => $main_subscription['entry_id'] ?? 0, // Link to same entry as subscription.
			'gateway'             => 'stripe',
			'type'                => 'renewal', // Subscription billing cycle payment (renewal).
			'mode'                => $payment_mode,
			'transaction_id'      => $payment_intent_id,
			'customer_id'         => $invoice_data['customer'] ?? '',
			'subscription_id'     => $subscription_id, // Link back to subscription.
			'subscription_status' => '', // Not applicable for individual payments.
			'payment_data'        => $invoice_data, // Store complete invoice data for refunds and debugging.
		];

		// Add log entry for audit trail.
		$entry_data['log'] = [
			[
				'title'     => 'Subscription Renewal Payment',
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

		// TODO: Handle proper error handling.

		// Save to database.
		$payment_entry_id = Payments::add( $entry_data );

		if ( $payment_entry_id ) {
			// Store in static array for later entry linking.
			$this->stripe_payment_entries[] = [
				'payment_id' => $payment_intent_id,
				'block_id'   => $block_id,
				'form_id'    => $form_id,
			];

			// TODO: Handle proper error handling.
			return $payment_entry_id;
		}
			// TODO: Handle proper error handling.
			return false;

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
	 * Get or create Stripe customer
	 *
	 * @return string|false Customer ID on success, false on failure.
	 * @since x.x.x
	 */
	private function get_or_create_stripe_customer() {
		$current_user = wp_get_current_user();

		if ( $current_user->ID > 0 ) {
			// Logged-in user - check for existing customer ID in user meta.
			$customer_id = get_user_meta( $current_user->ID, 'srfm_stripe_customer_id', true );

			if ( ! empty( $customer_id ) && $this->verify_stripe_customer( $customer_id ) ) {
				return $customer_id;
			}

			// Create new customer for logged-in user.
			return $this->create_stripe_customer_for_user( $current_user );
		}
			// Non-logged-in user - create temporary customer.
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

			// Save customer ID to user meta for future use.
			update_user_meta( $user->ID, 'srfm_stripe_customer_id', $customer['id'] );

			return $customer['id'];

		} catch ( \Exception $e ) {
			// TODO: Handle proper error handling.
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
			// Get form data if available for guest customer info.
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

			// Add email and name if available from form data.
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
			// TODO: Handle proper error handling.
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
			// TODO: Handle proper error handling.
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

		// Try to get email and name from common form field names.
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

		// Try to combine first_name and last_name if available.
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
		// Check for various IP address headers.
		$ip_keys = [ 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ];

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				// Handle comma-separated IPs (from proxies).
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}
			}
		}

		return '127.0.0.1'; // Fallback.
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
	public function update_payment_entry_id_form_submit( $form_submit_response ) {
		// Check if entry_id exists in the form_submit_response.
		if ( ! empty( $form_submit_response['entry_id'] ) && ! empty( $this->stripe_payment_entries ) ) {
			$entry_id = intval( $form_submit_response['entry_id'] );

			// Loop through stored payment entries to update with entry_id.
			foreach ( $this->stripe_payment_entries as $stripe_payment_entry ) {
				if ( ! empty( $stripe_payment_entry['payment_id'] ) && ! empty( $stripe_payment_entry['form_id'] ) ) {
					// Check if form_id matches.
					$stored_form_id   = intval( $stripe_payment_entry['form_id'] );
					$response_form_id = intval( $form_submit_response['form_id'] ?? 0 );

					if ( $stored_form_id === $response_form_id ) {
						// Update the payment entry with the entry_id.
						$this->update_payment_entry_id( $stripe_payment_entry['payment_id'], $entry_id );
					}
				}
			}
		}
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
			// Find the payment entry by transaction_id.
			$payment_entries = Payments::get_instance()->get_results(
				[ 'transaction_id' => $payment_id ],
				'id'
			);

			if ( ! empty( $payment_entries ) && isset( $payment_entries[0]['id'] ) ) {
				$payment_entry_id = intval( $payment_entries[0]['id'] );

				// Update the payment entry with entry_id using Payments class.
				$updated = Payments::update( $payment_entry_id, [ 'entry_id' => $entry_id ] );

				if ( false === $updated ) {
					// TODO: Handle proper error handling.
				}
			} else {
				// TODO: Handle proper error handling.
			}
		} catch ( \Exception $e ) {
			// TODO: Handle proper error handling.
		}
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
		// Validate Stripe connection.
		if ( ! Stripe\Stripe_Helper::is_stripe_connected() ) {
			// TODO: Handle proper error handling.
			return false;
		}

		$payment_mode = Stripe\Stripe_Helper::get_stripe_mode();
		$secret_key   = Stripe\Stripe_Helper::get_stripe_secret_key();

		if ( empty( $secret_key ) ) {
			// TODO: Handle proper error handling.
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

		if ( ! empty( $data ) && in_array( $method, [ 'POST', 'PUT', 'PATCH' ], true ) ) {
			$args['body'] = http_build_query( $this->flatten_stripe_data( $data ) );
		} elseif ( ! empty( $data ) && 'GET' === $method ) {
			$url .= '?' . http_build_query( $this->flatten_stripe_data( $data ) );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			// TODO: Handle proper error handling.
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$code = wp_remote_retrieve_response_code( $response );

		if ( $code >= 400 ) {
			$error_data    = json_decode( $body, true );
			$error_message = $error_data['error']['message'] ?? 'Unknown Stripe API error';
			// TODO: Handle proper error handling.
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
				// Handle indexed arrays (like expand parameters).
				if ( array_keys( $value ) === range( 0, count( $value ) - 1 ) ) {
					foreach ( $value as $index => $item ) {
						$result[ $new_key . '[' . $index . ']' ] = $item;
					}
				} else {
					// Handle associative arrays (nested objects).
					$result = array_merge( $result, $this->flatten_stripe_data( $value, $new_key ) );
				}
			} else {
				$result[ $new_key ] = $value;
			}
		}

		return $result;
	}
}
