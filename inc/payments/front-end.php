<?php
/**
 * SureForms Payments Frontend Class.
 *
 * @package sureforms
 * @since 2.0.0
 */

namespace SRFM\Inc\Payments;

use SRFM\Inc\Database\Tables\Payments;
use SRFM\Inc\Payments\Stripe\Stripe_Helper;
use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * SureForms Payments Frontend Class.
 *
 * @since 2.0.0
 */
class Front_End {
	use Get_Instance;

	/**
	 * Stores payment entries for later linking with form submissions.
	 *
	 * @var array
	 * @since 2.0.0
	 */
	private $stripe_payment_entries = [];

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		add_action( 'wp_ajax_srfm_create_payment_intent', [ $this, 'create_payment_intent' ] );
		add_action( 'wp_ajax_nopriv_srfm_create_payment_intent', [ $this, 'create_payment_intent' ] );
		add_action( 'wp_ajax_srfm_create_subscription_intent', [ $this, 'create_subscription_intent' ] );
		add_action( 'wp_ajax_nopriv_srfm_create_subscription_intent', [ $this, 'create_subscription_intent' ] ); // For non-logged-in users.
		add_filter( 'srfm_form_submit_data', [ $this, 'validate_payment_fields' ], 5, 1 );
		add_action( 'srfm_form_submit', [ $this, 'update_payment_entry_id_form_submit' ], 10, 1 );
		add_filter( 'srfm_show_options_values', [ $this, 'show_options_values' ], 10, 2 );
		add_filter( 'srfm_all_data_field_row', [ $this, 'skip_payment_fields_from_all_data' ], 10, 2 );
		add_filter( 'srfm_map_slug_to_submission_data_should_skip', [ $this, 'skip_payment_fields_from_submission_data' ], 10, 2 );
		add_filter( 'srfm_should_skip_field_from_sample_data', [ $this, 'skip_payment_fields_from_sample_data' ], 10, 2 );
	}

	/**
	 * Show options values
	 *
	 * @param bool $default_value Default value.
	 * @param bool $value Value.
	 * @since 2.0.0
	 * @return bool
	 */
	public function show_options_values( $default_value, $value ) {
		return $value ? true : $default_value;
	}
	/**
	 * Create payment intent
	 *
	 * @throws \Exception When Stripe configuration is invalid.
	 * @since 2.0.0
	 * @return void
	 */
	public function create_payment_intent() {
		// Verify nonce.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'srfm_stripe_payment_nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce.', 'sureforms' ) );
		}

		$amount         = intval( $_POST['amount'] ?? 0 );
		$currency       = sanitize_text_field( wp_unslash( $_POST['currency'] ?? 'usd' ) );
		$description    = sanitize_text_field( wp_unslash( $_POST['description'] ?? 'SureForms Payment' ) );
		$block_id       = sanitize_text_field( wp_unslash( $_POST['block_id'] ?? '' ) );
		$customer_email = sanitize_email( wp_unslash( $_POST['customer_email'] ?? '' ) );
		$customer_name  = sanitize_text_field( wp_unslash( $_POST['customer_name'] ?? '' ) );

		if ( $amount <= 0 ) {
			wp_send_json_error( __( 'Invalid payment amount.', 'sureforms' ) );
		}

		// Validate customer email (required for one-time payments).
		if ( empty( $customer_email ) || ! is_email( $customer_email ) ) {
			wp_send_json_error( __( 'Valid customer email is required for payments.', 'sureforms' ) );
		}

		try {
			// Validate Stripe connection.
			if ( ! Stripe_Helper::is_stripe_connected() ) {
				throw new \Exception( __( 'Stripe is not connected.', 'sureforms' ) );
			}

			$secret_key = Stripe_Helper::get_stripe_secret_key();

			if ( empty( $secret_key ) ) {
				throw new \Exception( __( 'Stripe secret key not found.', 'sureforms' ) );
			}

			// Create or get customer ID for logged-in users.
			$customer_id = null;
			if ( is_user_logged_in() ) {
				$customer_id = $this->get_or_create_stripe_customer(
					[
						'email' => $customer_email,
						'name'  => $customer_name,
					]
				);
			}

			$license_key = Stripe_Helper::get_license_key();

			// Create payment intent with confirm: true for immediate processing.
			$payment_intent_data = [
				'secret_key'                => $secret_key,
				'amount'                    => $amount,
				'currency'                  => strtolower( $currency ),
				'description'               => $description,
				'confirm'                   => false, // Will be confirmed by frontend.
				'receipt_email'             => $customer_email,
				'license_key'               => $license_key,
				'automatic_payment_methods' => [
					'enabled'         => true,
					'allow_redirects' => 'never',
				],
				'metadata'                  => [
					'source'          => 'SureForms',
					'block_id'        => $block_id,
					'original_amount' => $amount,
					'receipt_email'   => $customer_email,
					'customer_name'   => $customer_name,
				],
			];

			// Add customer ID to payment intent data if user is logged in.
			if ( ! empty( $customer_id ) ) {
				$payment_intent_data['customer'] = $customer_id;
			}

			$payment_intent_data = apply_filters(
				'srfm_create_payment_intent_data',
				$payment_intent_data,
				$customer_id
			);

			$payment_intent_data = wp_json_encode( $payment_intent_data );
			$payment_intent_data = is_string( $payment_intent_data ) ? $payment_intent_data : '';
			$payment_intent_data = base64_encode( $payment_intent_data ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

			$payment_intent = wp_remote_post(
				Stripe_Helper::middle_ware_base_url() . 'payment-intent/create',
				[
					'body'    => $payment_intent_data,
					'headers' => [
						'Content-Type' => 'application/json',
					],
				]
			);

			if ( is_wp_error( $payment_intent ) ) {
				throw new \Exception( Payment_Helper::get_error_message_by_key( 'failed_to_create_payment' ) );
			}

			$payment_intent = json_decode( wp_remote_retrieve_body( $payment_intent ), true );
			$payment_intent = is_array( $payment_intent ) ? $payment_intent : [];

			// Check if we have an error from Stripe API (verify both status and code).
			if ( isset( $payment_intent['status'] ) && 'error' === $payment_intent['status'] && isset( $payment_intent['code'] ) && ! empty( $payment_intent['code'] ) ) {
				// Handle amount_too_small error with custom message.
				if ( 'amount_too_small' === $payment_intent['code'] ) {
					// Format the amount for display.
					$currency_symbol  = Stripe_Helper::get_currency_symbol( $currency );
					$display_amount   = Stripe_Helper::amount_from_stripe_format( $amount, $currency );
					$formatted_amount = $currency_symbol . number_format( $display_amount, 2 );

					throw new \Exception(
						sprintf(
							/* translators: %s: formatted payment amount */
							__( 'The payment amount (%s) is below the minimum allowed. Stripe only processes amounts above 50¢.', 'sureforms' ),
							$formatted_amount
						)
					);
				}

				// For other error codes, use the message from Stripe API if available.
				if ( isset( $payment_intent['message'] ) && ! empty( $payment_intent['message'] ) ) {
					throw new \Exception( $payment_intent['message'] );
				}

				// Fallback if we have error code but no message.
				throw new \Exception( Payment_Helper::get_error_message_by_key( 'failed_to_create_payment' ) );
			}

			if ( ! isset( $payment_intent['client_secret'] ) || empty( $payment_intent['client_secret'] ) || ! isset( $payment_intent['id'] ) || empty( $payment_intent['id'] ) ) {
				throw new \Exception( Payment_Helper::get_error_message_by_key( 'failed_to_create_payment' ) );
			}

			wp_send_json_success(
				[
					'client_secret'     => $payment_intent['client_secret'],
					'payment_intent_id' => $payment_intent['id'],
					'customer_id'       => $customer_id,
				]
			);
		} catch ( \Exception $e ) {
			$error_message = $e->getMessage();
			$error_message = empty( $error_message ) ? Payment_Helper::get_error_message_by_key( 'failed_to_create_payment' ) : $error_message;
			wp_send_json_error( $error_message );
		}
	}

	/**
	 * Create subscription intent with improved error handling from simple-stripe-subscriptions
	 *
	 * @throws \Exception When Stripe configuration is invalid.
	 * @since 2.0.0
	 * @return void
	 */
	public function create_subscription_intent() {
		// Verify nonce.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'srfm_stripe_payment_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', 'sureforms' ) );
		}

		// Validate required fields like simple-stripe-subscriptions.
		$required_fields = [ 'amount', 'currency', 'description', 'block_id', 'interval', 'plan_name' ];
		foreach ( $required_fields as $field ) {
			if ( empty( $_POST[ $field ] ) ) {
				/* translators: %s: Field name */
				wp_send_json_error( sprintf( __( 'Missing required field: %s', 'sureforms' ), $field ) );
			}
		}

		$amount      = intval( $_POST['amount'] ?? 0 );
		$currency    = sanitize_text_field( wp_unslash( $_POST['currency'] ?? 'usd' ) );
		$description = sanitize_text_field( wp_unslash( $_POST['description'] ?? 'SureForms Subscription' ) );
		$block_id    = sanitize_text_field( wp_unslash( $_POST['block_id'] ?? '' ) );

		$subscription_interval = sanitize_text_field( wp_unslash( $_POST['interval'] ?? 'month' ) );
		$plan_name             = sanitize_text_field( wp_unslash( $_POST['plan_name'] ?? 'Subscription Plan' ) );
		$customer_email        = sanitize_email( wp_unslash( $_POST['customer_email'] ?? '' ) );
		$customer_name         = sanitize_text_field( wp_unslash( $_POST['customer_name'] ?? '' ) );

		// Validate customer email (required for all subscriptions).
		if ( empty( $customer_email ) || ! is_email( $customer_email ) ) {
			wp_send_json_error( __( 'Valid customer email is required for subscriptions.', 'sureforms' ) );
		}

		// Validate customer name (required for subscriptions).
		if ( empty( $customer_name ) ) {
			wp_send_json_error( __( 'Customer name is required for subscriptions.', 'sureforms' ) );
		}

		// Validate amount like simple-stripe-subscriptions.
		if ( $amount <= 0 ) {
			wp_send_json_error( __( 'Amount must be greater than 0', 'sureforms' ) );
		}

		// Validate interval like simple-stripe-subscriptions.
		$valid_intervals = [ 'day', 'week', 'month', 'year' ];
		if ( ! in_array( $subscription_interval, $valid_intervals, true ) ) {
			wp_send_json_error( __( 'Invalid billing interval', 'sureforms' ) );
		}

		try {
			// Validate Stripe connection.
			if ( ! Stripe_Helper::is_stripe_connected() ) {
				throw new \Exception( __( 'Stripe is not connected.', 'sureforms' ) );
			}

			$secret_key = Stripe_Helper::get_stripe_secret_key();

			if ( empty( $secret_key ) ) {
				throw new \Exception( __( 'Stripe secret key not found.', 'sureforms' ) );
			}

			// Get or create Stripe customer for subscriptions.
			$customer_id = $this->get_or_create_stripe_customer(
				[
					'email' => $customer_email,
					'name'  => $customer_name,
				]
			);
			if ( ! $customer_id ) {
				throw new \Exception( __( 'Failed to create customer for subscription.', 'sureforms' ) );
			}

			$license_key = Stripe_Helper::get_license_key();
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

			$endpoint = Stripe_Helper::middle_ware_base_url() . 'subscription/create';

			$subscription_data_body = wp_json_encode( $subscription_data );
			$subscription_data_body = is_string( $subscription_data_body ) ? $subscription_data_body : '';
			$subscription_data_body = base64_encode( $subscription_data_body ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

			if ( empty( $subscription_data_body ) ) {
				throw new \Exception( __( 'Failed to create subscription through middleware.', 'sureforms' ) );
			}

			// Call middleware subscription creation endpoint.
			$subscription_response = wp_remote_post(
				$endpoint,
				[
					'body'    => $subscription_data_body,
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

			if ( ! is_array( $subscription ) ) {
				wp_send_json_error( __( 'Invalid subscription data.', 'sureforms' ) );
			}

			if ( 'error' === $subscription['status'] ) {
				wp_send_json_error( isset( $subscription['message'] ) && ! empty( $subscription['message'] ) ? $subscription['message'] : __( 'Invalid subscription data.', 'sureforms' ) );
			}

			$payment_intent_id = isset( $subscription['setup_intent']['id'] ) && ! empty( $subscription['setup_intent']['id'] ) ? $subscription['setup_intent']['id'] : '';
			$subscription_id   = isset( $subscription['subscription_data']['id'] ) && ! empty( $subscription['subscription_data']['id'] ) ? $subscription['subscription_data']['id'] : '';
			$client_secret     = isset( $subscription['client_secret'] ) && ! empty( $subscription['client_secret'] ) ? $subscription['client_secret'] : '';
			if ( empty( $client_secret ) || empty( $subscription_id ) || empty( $payment_intent_id ) ) {
				throw new \Exception( __( 'Failed to create subscription.', 'sureforms' ) );
			}

			$response = [
				'type'              => 'subscription',
				'client_secret'     => $client_secret,
				'subscription_id'   => $subscription_id,
				'customer_id'       => $customer_id,
				'payment_intent_id' => $payment_intent_id,
				'amount'            => Stripe_Helper::amount_from_stripe_format( $amount, $currency ),
				'interval'          => $subscription_interval,
			];

			wp_send_json_success( $response );

		} catch ( \Exception $e ) {
			/* translators: %s: Error message */
			wp_send_json_error( sprintf( __( 'Unexpected error: %s', 'sureforms' ), $e->getMessage() ) );
		}
	}

	/**
	 * Validate payment fields before form submission
	 *
	 * @param array<mixed> $form_data Form data.
	 * @since 2.0.0
	 * @return array<mixed>
	 */
	public function validate_payment_fields( $form_data ) {
		// Check if form data is valid.
		if ( empty( $form_data ) || ! is_array( $form_data ) ) {
			return $form_data;
		}

		$payment_response = [];

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
				 */
				$payment_response = $this->verify_stripe_subscription_intent_and_save( $payment_value, $block_id, $form_data );
			} else {
				$payment_response = $this->verify_stripe_payment_intent_and_save( $payment_value, $payment_id, $block_id, $form_data );
			}

			if ( ! empty( $payment_response ) && isset( $payment_response['payment_id'] ) ) {
				// Modify the form data with the payment ID.
				$form_data[ $field_name ] = $payment_response['payment_id'];
			}
		}

		if ( ! empty( $payment_response ) && isset( $payment_response['error'] ) ) {
			$form_data = array_merge( $form_data, $payment_response );
		}

		return $form_data;
	}

	/**
	 * Simplified subscription verification using simple-stripe-subscriptions approach
	 *
	 * @param array<mixed> $subscription_value Subscription data from frontend.
	 * @param string       $block_id Block ID.
	 * @param array<mixed> $form_data Form data.
	 * @since 2.0.0
	 * @return void|array<mixed> True if subscription is verified and saved successfully.
	 */
	public function verify_stripe_subscription_intent_and_save( $subscription_value, $block_id, $form_data ) {
		$subscription_id = ! empty( $subscription_value['subscriptionId'] ) && is_string( $subscription_value['subscriptionId'] ) ? $subscription_value['subscriptionId'] : '';

		if ( empty( $subscription_id ) ) {
			return [
				'error' => __( 'Subscription ID not found.', 'sureforms' ),
			];
		}

		$customer_id     = ! empty( $subscription_value['customerId'] ) ? $subscription_value['customerId'] : '';
		$setup_intent_id = ! empty( $subscription_value['setupIntent'] ) && is_string( $subscription_value['setupIntent'] ) ? $subscription_value['setupIntent'] : '';

		if ( empty( $customer_id ) ) {
			return [
				'error' => __( 'Customer ID not found for the payment.', 'sureforms' ),
			];
		}

		try {
			// Get payment mode and secret key.
			$payment_mode = Stripe_Helper::get_stripe_mode();
			$secret_key   = Stripe_Helper::get_stripe_secret_key();

			if ( empty( $secret_key ) ) {
				return [
					'error' => __( 'Stripe secret key not found.', 'sureforms' ),
				];
			}

			// Update subscription with payment method from setup intent if available.
			$paid_invoice = [];
			if ( ! empty( $setup_intent_id ) ) {
				try {
					$setup_intent_response = Stripe_Helper::stripe_api_request(
						'setup_intents',
						'GET',
						[],
						$setup_intent_id
					);

					if ( ! $setup_intent_response['success'] ) {
						return [
							'error' => $setup_intent_response['error']['message'] ?? __( 'Failed to retrieve setup intent.', 'sureforms' ),
						];
					}

					$setup_intent = $setup_intent_response['data'];

					if ( ( isset( $setup_intent['payment_method'] ) && ! empty( $setup_intent['payment_method'] ) && is_string( $setup_intent['payment_method'] ) ) ) {

						// Prepare subscription update data.
						$subscription_update_data = [
							'default_payment_method' => $setup_intent['payment_method'],
							'collection_method'      => 'charge_automatically',
						];

						// Calculate cancel_at timestamp based on billing cycles and interval.
						$cancel_at = $this->prepare_cancel_at( $subscription_value );
						if ( ! empty( $cancel_at ) ) {
							$subscription_update_data['cancel_at'] = $cancel_at;
						}

						$subscription_update_response = Stripe_Helper::stripe_api_request(
							'subscriptions',
							'POST',
							$subscription_update_data,
							$subscription_id
						);

						if ( ! $subscription_update_response['success'] ) {
							return [
								'error' => $subscription_update_response['error']['message'] ?? __( 'Failed to update subscription.', 'sureforms' ),
							];
						}

						$subscription_update = $subscription_update_response['data'];

						if ( empty( $subscription_update['latest_invoice'] ) ) {
							return [
								'error' => __( 'Latest invoice not found on subscription.', 'sureforms' ),
							];
						}

						$invoice_response = Stripe_Helper::stripe_api_request(
							'invoices',
							'GET',
							[],
							$subscription_update['latest_invoice']
						);

						if ( ! $invoice_response['success'] ) {
							return [
								'error' => $invoice_response['error']['message'] ?? __( 'Failed to retrieve invoice.', 'sureforms' ),
							];
						}

						$invoice = $invoice_response['data'];

						// Ensure invoice auto-advance is enabled for recurring payments.
						// This tells Stripe to automatically finalize and charge future invoices.
						if ( empty( $invoice['auto_advance'] ) && ! empty( $invoice['id'] ) && is_string( $invoice['id'] ) ) {
							Stripe_Helper::stripe_api_request(
								'invoices',
								'POST',
								[ 'auto_advance' => true ],
								$invoice['id']
							);
						}

						// Extract payment intent from the invoice.
						$payment_intent_id = isset( $invoice['payment_intent'] ) && ! empty( $invoice['payment_intent'] ) && is_string( $invoice['payment_intent'] ) ? $invoice['payment_intent'] : '';

						if ( empty( $payment_intent_id ) ) {
							return [
								'error' => __( 'Payment intent not found on invoice.', 'sureforms' ),
							];
						}

						// Confirm the payment intent with payment method.
						// This completes the payment and activates the subscription.
						$paid_invoice_response = Stripe_Helper::stripe_api_request(
							'payment_intents',
							'POST',
							[ 'payment_method' => $setup_intent['payment_method'] ],
							$payment_intent_id . '/confirm'
						);

						if ( ! $paid_invoice_response['success'] ) {
							return [
								'error' => $paid_invoice_response['error']['message'] ?? __( 'Failed to confirm payment.', 'sureforms' ),
							];
						}

						$paid_invoice = $paid_invoice_response['data'];

						// Get the subscription.
						$subscription_response = Stripe_Helper::stripe_api_request(
							'subscriptions',
							'GET',
							[],
							$subscription_id
						);

						if ( ! $subscription_response['success'] ) {
							return [
								'error' => $subscription_response['error']['message'] ?? __( 'Failed to retrieve subscription.', 'sureforms' ),
							];
						}

						$subscription = $subscription_response['data'];
					}
				} catch ( \Exception $e ) {
					return [
						'error' => $e->getMessage(),
					];
				}
			}

			if ( empty( $subscription ) ) {
				return [
					'error' => __( 'Subscription not found for the payment.', 'sureforms' ),
				];
			}

			// Use simple-stripe-subscriptions validation logic - check if subscription is in good state.
			$is_subscription_active = in_array( $subscription['status'], [ 'active', 'trialing' ], true );
			$final_status           = $is_subscription_active ? 'active' : 'failed';

			$amount              = isset( $paid_invoice['amount'] ) && ! empty( $paid_invoice['amount'] ) ? $paid_invoice['amount'] : 0;
			$currency            = isset( $paid_invoice['currency'] ) && ! empty( $paid_invoice['currency'] ) ? $paid_invoice['currency'] : 'usd';
			$form_id             = isset( $form_data['form-id'] ) && ! empty( $form_data['form-id'] ) ? $form_data['form-id'] : 0;
			$subscription_status = isset( $subscription['status'] ) && ! empty( $subscription['status'] ) && is_string( $subscription['status'] ) ? $subscription['status'] : '';

			$invoice_status = isset( $paid_invoice['status'] ) && ! empty( $paid_invoice['status'] ) && is_string( $paid_invoice['status'] ) ? $paid_invoice['status'] : '';

			// Extract customer data.
			$customer_data = $this->extract_customer_data( $subscription_value );

			// Extract charge ID from the first payment intent for refund purposes.
			// For subscriptions, we store the charge ID in transaction_id so refunds can be processed.
			$charge_id = '';
			if ( ! empty( $paid_invoice['latest_charge'] ) && is_string( $paid_invoice['latest_charge'] ) ) {
				$charge_id = $paid_invoice['latest_charge'];
			} elseif ( ! empty( $paid_invoice['charges']['data'][0]['id'] ) && is_string( $paid_invoice['charges']['data'][0]['id'] ) ) {
				$charge_id = $paid_invoice['charges']['data'][0]['id'];
			}

			// Use charge ID as transaction_id if available, otherwise fall back to subscription ID.
			$transaction_id = ! empty( $charge_id ) ? $charge_id : $subscription_id;

			// Prepare minimal subscription data for database.
			$entry_data = [
				'form_id'             => $form_id,
				'block_id'            => $block_id,
				'status'              => $final_status,
				'total_amount'        => Stripe_Helper::amount_from_stripe_format( $amount, $currency ),
				'currency'            => $currency,
				'entry_id'            => 0,
				'gateway'             => 'stripe',
				'type'                => 'subscription',
				'mode'                => $payment_mode,
				'transaction_id'      => $transaction_id,
				'customer_id'         => $customer_id,
				'subscription_id'     => $subscription_id,
				'subscription_status' => $subscription_status,
				'srfm_txn_id'         => '', // Will be updated after getting payment entry ID.
				'customer_email'      => $customer_data['email'],
				'customer_name'       => $customer_data['name'],
				'payment_data'        => [
					'initial_invoice' => $paid_invoice,
					'subscription'    => $subscription,
					'payment_value'   => $subscription_value,
				],
			];

			// Get user ID if logged in.
			$user_id   = get_current_user_id();
			$user_info = $user_id > 0
				/* translators: %d: User ID */
				? sprintf( __( 'User ID: %d', 'sureforms' ), $user_id )
				/* translators: Message for guest user in payment logs */
				: __( 'Guest User', 'sureforms' );

			// If invoice is not paid then we need to set the status in the subscription log and return error.
			$paid_invoice_log = '';
			if ( 'paid' !== $invoice_status ) {
				/* translators: %s: Invoice status */
				$paid_invoice_log = sprintf( __( 'Invoice Status: %s', 'sureforms' ), $invoice_status );
			}

			// Add simple log entry.
			$entry_data['log'] = [
				[
					/* translators: Title for subscription verification log */
					'title'      => __( 'Subscription Verification', 'sureforms' ),
					'created_at' => current_time( 'mysql' ),
					'messages'   => [
						/* translators: %s: Subscription ID */
						sprintf( __( 'Subscription ID: %s', 'sureforms' ), $subscription_id ),
						/* translators: %s: Payment Gateway */
						sprintf( __( 'Payment Gateway: %s', 'sureforms' ), 'Stripe' ),
						/* translators: %s: Payment Intent ID */
						sprintf( __( 'Payment Intent ID: %s', 'sureforms' ), $setup_intent_id ),
						/* translators: %s: Charge ID */
						sprintf( __( 'Charge ID: %s', 'sureforms' ), ! empty( $charge_id ) ? $charge_id : 'N/A' ),
						/* translators: %s: Subscription Status */
						sprintf( __( 'Subscription Status: %s', 'sureforms' ), $subscription_status ),
						/* translators: %s: Customer ID */
						sprintf( __( 'Customer ID: %s', 'sureforms' ), $customer_id ),
						/* translators: 1: Amount, 2: Currency */
						sprintf( __( 'Amount: %1$s %2$s', 'sureforms' ), number_format( Stripe_Helper::amount_from_stripe_format( $amount, $currency ), 2 ), strtoupper( $currency ) ),
						$user_info,
						/* translators: %s: Payment mode (e.g. Live or Test) */
						sprintf( __( 'Mode: %s', 'sureforms' ), ucfirst( $payment_mode ) ),
						$paid_invoice_log,
					],
				],
			];

			// Save to database.
			$payment_entry_id = Payments::add( $entry_data );

			if ( $payment_entry_id ) {
				// Generate unique payment ID using the auto-increment ID and update the entry.
				$unique_payment_id = Stripe_Helper::generate_unique_payment_id( $payment_entry_id );
				// For initial subscription, set parent_subscription_id to itself (it's the parent).
				Payments::update(
					$payment_entry_id,
					[
						'srfm_txn_id'            => $unique_payment_id,
						'parent_subscription_id' => $payment_entry_id,
					]
				);

				// Store in static array for later entry linking.
				$this->stripe_payment_entries[] = [
					'payment_id' => $transaction_id,
					'block_id'   => $block_id,
					'form_id'    => $form_id,
				];

				return [
					'payment_id' => $payment_entry_id,
				];
			}
		} catch ( \Exception $e ) {
			return [
				'error' => empty( $e->getMessage() ) ? __( 'Failed to verify subscription.', 'sureforms' ) : $e->getMessage(),
			];
		}
	}

	/**
	 * Prepare cancel_at timestamp for subscription based on billing cycles and interval.
	 *
	 * @param array<string,mixed> $input_value Array containing subscriptionBillingCycles and subscriptionInterval.
	 * @since 2.0.0
	 * @return int|false|null Unix timestamp for cancel_at, or null if not applicable.
	 */
	public function prepare_cancel_at( $input_value ) {
		$subscription_billing_cycles = ! empty( $input_value['subscriptionBillingCycles'] ) ? $input_value['subscriptionBillingCycles'] : 0;
		$subscription_interval       = ! empty( $input_value['subscriptionInterval'] ) ? $input_value['subscriptionInterval'] : '';

		// Return null if billing cycles is 0, empty, or equals 'ongoing'.
		if ( empty( $subscription_billing_cycles ) || 'ongoing' === $subscription_billing_cycles || ! is_numeric( $subscription_billing_cycles ) ) {
			return null;
		}

		// Convert billing cycles to integer.
		$billing_cycles = (int) $subscription_billing_cycles;

		// Return null if billing cycles is less than or equal to 0.
		if ( $billing_cycles <= 0 ) {
			return null;
		}

		// Calculate cancel_at timestamp based on interval.
		$current_time = time();
		$cancel_at    = null;

		switch ( $subscription_interval ) {
			case 'day':
				// Add days: cycles * 1 day.
				$cancel_at = strtotime( "+{$billing_cycles} days", $current_time );
				break;

			case 'week':
				// Add weeks: cycles * 7 days.
				$cancel_at = strtotime( "+{$billing_cycles} weeks", $current_time );
				break;

			case 'month':
				// Add months: cycles * 1 month.
				$cancel_at = strtotime( "+{$billing_cycles} months", $current_time );
				break;

			case 'quarter':
				// Add quarters: cycles * 3 months.
				$total_months = $billing_cycles * 3;
				$cancel_at    = strtotime( "+{$total_months} months", $current_time );
				break;

			case 'year':
				// Add years: cycles * 1 year.
				$cancel_at = strtotime( "+{$billing_cycles} years", $current_time );
				break;

			default:
				// Invalid interval, return null.
				return null;
		}

		return $cancel_at;
	}

	/**
	 * Handle form submit and update payment entries with entry_id
	 *
	 * This function is called after a form submission to link the created entry
	 * with any associated Stripe payment records. It matches payment entries
	 * by form_id and updates them with the newly created entry_id.
	 *
	 * @param array<string,mixed> $form_submit_response The form submission response containing entry_id and form_id.
	 * @since 2.0.0
	 * @return void
	 */
	public function update_payment_entry_id_form_submit( $form_submit_response ) {
		// Check if entry_id exists in the form_submit_response.
		if ( ! empty( $form_submit_response['entry_id'] ) && ! empty( $this->stripe_payment_entries ) ) {
			$entry_id = is_numeric( $form_submit_response['entry_id'] ) ? intval( $form_submit_response['entry_id'] ) : 0;

			// Loop through stored payment entries to update with entry_id.
			foreach ( $this->stripe_payment_entries as $stripe_payment_entry ) {
				if ( ! empty( $stripe_payment_entry['payment_id'] ) && ! empty( $stripe_payment_entry['form_id'] ) ) {
					// Check if form_id matches.
					$stored_form_id   = isset( $stripe_payment_entry['form_id'] ) && ! empty( $stripe_payment_entry['form_id'] ) && is_numeric( $stripe_payment_entry['form_id'] ) ? intval( $stripe_payment_entry['form_id'] ) : 0;
					$response_form_id = isset( $form_submit_response['form_id'] ) && ! empty( $form_submit_response['form_id'] ) && is_numeric( $form_submit_response['form_id'] ) ? intval( $form_submit_response['form_id'] ) : 0;

					$payment_id = is_string( $stripe_payment_entry['payment_id'] ) ? sanitize_text_field( $stripe_payment_entry['payment_id'] ) : '';

					if ( ! empty( $stored_form_id ) && $stored_form_id === $response_form_id ) {
						// Update the payment entry with the entry_id.
						$this->update_payment_entry_id( $payment_id, $entry_id );
					}
				}
			}
		}
	}

	/**
	 * Filter callback to determine if a payment field should be included in all data output.
	 *
	 * Excludes payment-related fields (like Stripe payment blocks) from being
	 * rendered in submission summaries, emails, exports, etc., as these fields
	 * serve as backend tracking data instead of user input.
	 *
	 * @since 2.0.0
	 *
	 * @param bool                  $should_add_field_row Whether this row should be output.
	 * @param array<string | mixed> $args Args describing the field row. Should contain 'block_name'.
	 * @return bool False for payment blocks; otherwise, original filter value.
	 */
	public function skip_payment_fields_from_all_data( $should_add_field_row, $args ) {
		// Check if the block is a payment block by inspecting the block name.
		$block_name = isset( $args['block_name'] ) && is_string( $args['block_name'] ) ? $args['block_name'] : '';
		if ( 'srfm-payment' === $block_name ) {
			return false;
		}
		return $should_add_field_row;
	}

	/**
	 * Skip payment fields from submission data.
	 *
	 * This function checks if a field is a payment field by validating its key prefix.
	 * Payment fields have keys that start with 'srfm-payment-' and should be skipped
	 * from certain data operations.
	 *
	 * @param bool         $default_value The default skip value.
	 * @param array<mixed> $args          Field arguments containing 'key', 'slug', and 'value'.
	 * @since 2.0.0
	 * @return bool True if the field should be skipped (is a payment field), false otherwise.
	 */
	public function skip_payment_fields_from_submission_data( $default_value, $args ) {
		// Validate that args is an array and has the 'key' parameter.
		if ( ! is_array( $args ) || ! isset( $args['key'] ) || ! is_string( $args['key'] ) ) {
			return $default_value;
		}

		// Check if the key starts with 'srfm-payment-' to identify payment fields.
		if ( 0 === strpos( $args['key'], 'srfm-payment-' ) ) {
			return true;
		}

		return $default_value;
	}

	/**
	 * Skip payment fields from sample data.
	 *
	 * This function determines if a field associated with a "srfm/payment" block
	 * should be skipped when processing sample data. If the provided arguments
	 * specify a block with the name 'srfm/payment', the function returns true to
	 * indicate that the field should be skipped. Otherwise, it returns the given
	 * default value.
	 *
	 * @param bool         $default_value The default skip value.
	 * @param array<mixed> $args          Field arguments containing at least 'block_name'.
	 * @since 2.0.0
	 * @return bool True if the field should be skipped (is a payment block), false otherwise.
	 */
	public function skip_payment_fields_from_sample_data( $default_value, $args ) {
		if ( ! is_array( $args ) || ! isset( $args['block_name'] ) || ! is_string( $args['block_name'] ) ) {
			return $default_value;
		}

		if ( 'srfm/payment' === $args['block_name'] ) {
			return true;
		}

		return $default_value;
	}

	/**
	 * Verify payment intent status
	 *
	 * @param array<mixed> $payment_value Payment value.
	 * @param string       $payment_id Payment ID.
	 * @param string       $block_id Block ID.
	 * @param array<mixed> $form_data Form data.
	 *
	 * @since 2.0.0
	 * @return void|array<mixed>
	 */
	private function verify_stripe_payment_intent_and_save( $payment_value, $payment_id, $block_id, $form_data ) {
		try {
			$payment_mode = Stripe_Helper::get_stripe_mode();
			$secret_key   = Stripe_Helper::get_stripe_secret_key();

			if ( empty( $secret_key ) ) {
				return [
					'error' => __( 'Stripe secret key not found.', 'sureforms' ),
				];
			}

			// Retrieve confirmed payment intent status.
			$retrieve_body = apply_filters(
				'srfm_retrieve_payment_intent_data',
				[
					'secret_key'        => $secret_key,
					'payment_intent_id' => $payment_id,
				]
			);

			$retrieve_body = wp_json_encode( $retrieve_body );
			$retrieve_body = is_string( $retrieve_body ) ? $retrieve_body : '';
			$retrieve_body = base64_encode( $retrieve_body ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

			if ( empty( $retrieve_body ) ) {
				return [
					'error' => __( 'Failed to retrieve payment intent.', 'sureforms' ),
				];
			}

			// Call middleware retrieve endpoint to get confirmed payment intent.
			$retrieve_response = wp_remote_post(
				Stripe_Helper::middle_ware_base_url() . 'payment-intent/capture',
				[
					'body'    => $retrieve_body,
					'headers' => [
						'Content-Type' => 'application/json',
					],
				]
			);

			if ( is_wp_error( $retrieve_response ) ) {
				return [
					'error' => __( 'Failed to retrieve payment intent.', 'sureforms' ),
				];
			}

			$confirmed_payment_intent = json_decode( wp_remote_retrieve_body( $retrieve_response ), true );

			if ( empty( $confirmed_payment_intent ) && ! is_array( $confirmed_payment_intent ) ) {
				return [
					'error' => __( 'Failed to retrieve payment intent.', 'sureforms' ),
				];
			}

			// Strict type validation and array check to resolve phpstan errors.
			if ( is_array( $confirmed_payment_intent ) && isset( $confirmed_payment_intent['status'] ) && 'error' === $confirmed_payment_intent['status'] ) {
				return [
					'error' => __( 'Failed to retrieve payment intent.', 'sureforms' ),
				];
			}

			// Check if payment was actually confirmed successfully, safely.
			$confirmed_status = is_array( $confirmed_payment_intent ) && isset( $confirmed_payment_intent['status'] ) ? (string) $confirmed_payment_intent['status'] : '';
			if ( ! in_array( $confirmed_status, [ 'succeeded', 'requires_capture' ], true ) ) {
				return [
					'error' => __( 'Payment was not confirmed successfully.', 'sureforms' ),
				];
			}

			$entry_data = [];

			$form_id                  = isset( $form_data['form-id'] ) && ! empty( $form_data['form-id'] ) && is_numeric( $form_data['form-id'] ) ? intval( $form_data['form-id'] ) : 0;
			$confirm_payment_status   = is_array( $confirmed_payment_intent ) && isset( $confirmed_payment_intent['status'] ) && ! empty( $confirmed_payment_intent['status'] ) ? (string) $confirmed_payment_intent['status'] : '';
			$confirm_payment_amount   = is_array( $confirmed_payment_intent ) && isset( $confirmed_payment_intent['amount'] ) && ! empty( $confirmed_payment_intent['amount'] ) ? intval( $confirmed_payment_intent['amount'] ) : 0;
			$confirm_payment_currency = is_array( $confirmed_payment_intent ) && isset( $confirmed_payment_intent['currency'] ) && ! empty( $confirmed_payment_intent['currency'] ) ? (string) $confirmed_payment_intent['currency'] : 'usd';
			$confirm_payment_id       = is_array( $confirmed_payment_intent ) && isset( $confirmed_payment_intent['id'] ) && ! empty( $confirmed_payment_intent['id'] ) ? (string) $confirmed_payment_intent['id'] : '';

			// Extract customer data.
			$customer_data = $this->extract_customer_data( $payment_value );

			// update payment status and save to the payment entries table.
			$entry_data['form_id']        = $form_id;
			$entry_data['block_id']       = $block_id;
			$entry_data['status']         = $confirm_payment_status;
			$entry_data['total_amount']   = Stripe_Helper::amount_from_stripe_format( $confirm_payment_amount, $confirm_payment_currency );
			$entry_data['currency']       = $confirm_payment_currency;
			$entry_data['entry_id']       = 0;
			$entry_data['gateway']        = 'stripe';
			$entry_data['type']           = 'payment';
			$entry_data['mode']           = $payment_mode;
			$entry_data['transaction_id'] = $confirm_payment_id;
			$entry_data['srfm_txn_id']    = ''; // Will be updated after getting payment entry ID.
			$entry_data['customer_email'] = $customer_data['email'];
			$entry_data['customer_name']  = $customer_data['name'];
			$entry_data['customer_id']    = $customer_data['customer_id'];
			$entry_data['payment_data']   = [
				'payment_value' => $payment_value,
			];

			// Get user ID if logged in.
			$user_id = get_current_user_id();
			/* translators: %d: User ID */
			$user_info = $user_id > 0 ? sprintf( __( 'User ID: %d', 'sureforms' ), $user_id ) : __( 'Guest User', 'sureforms' );

			// Add initial log entry for audit trail.
			$entry_data['log'] = [
				[
					'title'      => __( 'Payment Verification', 'sureforms' ),
					'created_at' => current_time( 'mysql' ),
					'messages'   => [
						/* translators: %s: Stripe transaction ID */
						sprintf( __( 'Transaction ID: %s', 'sureforms' ), $confirm_payment_id ),
						/* translators: %s: Payment gateway name. */
						sprintf( __( 'Payment Gateway: %s', 'sureforms' ), 'Stripe' ),
						/* translators: %1$s: amount, %2$s: currency. */
						sprintf( __( 'Amount: %1$s %2$s', 'sureforms' ), number_format( Stripe_Helper::amount_from_stripe_format( $confirm_payment_amount, $confirm_payment_currency ), 2 ), strtoupper( $confirm_payment_currency ) ),
						/* translators: %s: payment status */
						sprintf( __( 'Status: %s', 'sureforms' ), ucfirst( str_replace( '_', ' ', $confirm_payment_status ) ) ),
						$user_info,
						/* translators: %s: payment mode */
						sprintf( __( 'Mode: %s', 'sureforms' ), ucfirst( $payment_mode ) ),
					],
				],
			];

			$get_payment_entry_id = Payments::add( $entry_data );

			if ( $get_payment_entry_id ) {
				// Generate unique payment ID using the auto-increment ID and update the entry.
				$unique_payment_id = Stripe_Helper::generate_unique_payment_id( $get_payment_entry_id );
				Payments::update( $get_payment_entry_id, [ 'srfm_txn_id' => $unique_payment_id ] );

				$add_in_static_value = [
					'payment_id' => $confirm_payment_id,
					'block_id'   => $block_id,
					'form_id'    => $form_id,
				];

				$this->stripe_payment_entries[] = $add_in_static_value;

				return [
					'payment_id' => $get_payment_entry_id,
				];
			}
		} catch ( \Exception $e ) {
			return [
				'error' => $e->getMessage(),
			];
		}
	}

	/**
	 * Get or create Stripe customer
	 *
	 * @param array<string,string> $customer_data Customer data containing 'email' and 'name' from POST.
	 * @since 2.0.0
	 * @return string|false Customer ID on success, false on failure.
	 */
	private function get_or_create_stripe_customer( $customer_data = [] ) {
		$current_user = wp_get_current_user();

		if ( $current_user->ID > 0 ) {
			// Logged-in user - check for existing customer ID in user meta.
			$customer_id = get_user_meta( $current_user->ID, 'srfm_stripe_customer_id', true );

			if ( ! empty( $customer_id ) && is_string( $customer_id ) && $this->verify_stripe_customer( $customer_id ) ) {
				return $customer_id;
			}

			// Create new customer for logged-in user.
			return $this->create_stripe_customer_for_user( $current_user, $customer_data );
		}

		// Non-logged-in user - create temporary customer.
		return $this->create_stripe_customer_for_guest( $customer_data );
	}

	/**
	 * Create Stripe customer for logged-in user
	 *
	 * @param \WP_User             $user WordPress user object.
	 * @param array<string,string> $post_customer_data Customer data from POST containing 'email' and 'name'.
	 * @since 2.0.0
	 * @return string|false Customer ID on success, false on failure.
	 * @throws \Exception When Stripe API request fails.
	 */
	private function create_stripe_customer_for_user( $user, $post_customer_data = [] ) {
		try {
			// Use POST email if provided, else use logged-in user email.
			$customer_email = ! empty( $post_customer_data['email'] ) ? $post_customer_data['email'] : $user->user_email;

			// Use POST name if provided, else use logged-in user name.
			$customer_name = ! empty( $post_customer_data['name'] ) ? $post_customer_data['name'] : ( trim( $user->first_name . ' ' . $user->last_name ) );
			$customer_name = ! empty( $customer_name ) ? $customer_name : $user->display_name;

			// Build description with provided email and name.
			$description_parts = [];
			if ( ! empty( $customer_email ) ) {
				$description_parts[] = $customer_email;
			}
			if ( ! empty( $customer_name ) ) {
				$description_parts[] = $customer_name;
			}
			$description = ! empty( $description_parts ) ? implode( ', ', $description_parts ) : sprintf( 'WordPress User ID: %d', $user->ID );

			$customer_data = [
				'email'       => $customer_email,
				'name'        => $customer_name,
				'description' => $description,
				'metadata'    => [
					'source'        => 'SureForms',
					'wp_user_id'    => $user->ID,
					'wp_username'   => $user->user_login,
					'wp_user_email' => $user->user_email,
				],
			];

			$customer_response = Stripe_Helper::stripe_api_request( 'customers', 'POST', $customer_data );

			if ( ! $customer_response['success'] || empty( $customer_response['data']['id'] ) ) {
				throw new \Exception( __( 'Failed to create Stripe customer.', 'sureforms' ) );
			}

			$customer = $customer_response['data'];

			// Save customer ID to user meta for future use.
			update_user_meta( $user->ID, 'srfm_stripe_customer_id', $customer['id'] );

			return $customer['id'];

		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Create Stripe customer for guest user
	 *
	 * @param array<string,string> $post_customer_data Customer data from POST containing 'email' and 'name'.
	 * @since 2.0.0
	 * @return string|false Customer ID on success, false on failure.
	 * @throws \Exception When Stripe API request fails.
	 */
	private function create_stripe_customer_for_guest( $post_customer_data = [] ) {
		try {
			// Use email and name from POST data.
			$customer_email = ! empty( $post_customer_data['email'] ) ? sanitize_email( $post_customer_data['email'] ) : '';
			$customer_name  = ! empty( $post_customer_data['name'] ) ? sanitize_text_field( $post_customer_data['name'] ) : '';

			// Build description with provided email and name.
			$description_parts = [];
			if ( ! empty( $customer_email ) ) {
				$description_parts[] = $customer_email;
			}
			if ( ! empty( $customer_name ) ) {
				$description_parts[] = $customer_name;
			}
			$description = ! empty( $description_parts ) ? implode( ', ', $description_parts ) : 'Guest User - SureForms Subscription';

			$customer_data = [
				'description' => $description,
				'metadata'    => [
					'source'     => 'SureForms',
					'user_type'  => 'guest',
					'created_at' => current_time( 'mysql' ),
					'ip_address' => $this->get_user_ip(),
				],
			];

			// Add email if available from POST data.
			if ( ! empty( $customer_email ) ) {
				$customer_data['email']                  = $customer_email;
				$customer_data['metadata']['form_email'] = $customer_email;
			}

			// Add name if available from POST data.
			if ( ! empty( $customer_name ) ) {
				$customer_data['name']                  = $customer_name;
				$customer_data['metadata']['form_name'] = $customer_name;
			}

			$customer_response = Stripe_Helper::stripe_api_request( 'customers', 'POST', $customer_data );

			if ( ! $customer_response['success'] || empty( $customer_response['data']['id'] ) ) {
				throw new \Exception( __( 'Failed to create Stripe guest customer.', 'sureforms' ) );
			}

			$customer = $customer_response['data'];

			return $customer['id'];

		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Verify Stripe customer exists
	 *
	 * @param string $customer_id Stripe customer ID.
	 * @since 2.0.0
	 * @return bool True if customer exists, false otherwise.
	 */
	private function verify_stripe_customer( $customer_id ) {
		try {
			$customer_response = Stripe_Helper::stripe_api_request( 'customers', 'GET', [], $customer_id );

			if ( ! $customer_response['success'] ) {
				return false;
			}

			$customer = $customer_response['data'] ?? [];
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
			return false;
		}
	}

	/**
	 * Get user IP address
	 *
	 * @since 2.0.0
	 * @return string User IP address.
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
	 * Update payment entry with entry_id
	 *
	 * @param string $payment_id Payment intent ID.
	 * @param int    $entry_id   Entry ID to update.
	 * @since 2.0.0
	 * @return bool True if payment entry updated, false otherwise.
	 */
	private function update_payment_entry_id( $payment_id, $entry_id ) {
			// Find the payment entry by transaction_id.
			$payment_entries = Payments::get_instance()->get_results(
				[ 'transaction_id' => $payment_id ],
				'id'
			);

		if ( ! empty( $payment_entries ) && is_array( $payment_entries ) && isset( $payment_entries[0] ) && is_array( $payment_entries[0] ) && isset( $payment_entries[0]['id'] ) ) {
			$payment_entry_id = intval( $payment_entries[0]['id'] );

			// Update the payment entry with entry_id using Payments class.
			$updated = Payments::update( $payment_entry_id, [ 'entry_id' => $entry_id ] );
			return $updated ? true : false;
		}

		return false;
	}

	/**
	 * Extract customer name and email from form data
	 *
	 * Uses the payment block's customerNameField and customerEmailField attributes
	 * to find the corresponding field slugs, then extracts the values from form data.
	 *
	 * @param array<string,mixed> $input_value Input value.
	 * @since 2.0.0
	 * @return array{name: string, email: string, customer_id: string} Customer data array.
	 */
	private function extract_customer_data( $input_value ) {
		$email = ! empty( $input_value['email'] ) && is_string( $input_value['email'] ) ? sanitize_email( $input_value['email'] ) : '';
		return [
			'name'        => ! empty( $input_value['name'] ) && is_string( $input_value['name'] ) ? sanitize_text_field( $input_value['name'] ) : '',
			'email'       => $email,
			'customer_id' => ! empty( $input_value['customerId'] ) && is_string( $input_value['customerId'] ) ? sanitize_text_field( $input_value['customerId'] ) : '',
		];
	}
}
