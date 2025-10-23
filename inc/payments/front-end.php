<?php
/**
 * SureForms Payments Frontend Class.
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc\Payments;

use SRFM\Inc\Database\Tables\Payments;
use SRFM\Inc\Traits\Get_Instance;
use SRFM\Inc\Payments\Stripe\Stripe_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * SureForms Payments Frontend Class.
 *
 * @since x.x.x
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
	 * @since x.x.x
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
	 * @throws \Exception When Stripe configuration is invalid.
	 * @since x.x.x
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

			// Create payment intent with confirm: true for immediate processing.
			$payment_intent_data = apply_filters(
				'srfm_create_payment_intent_data',
				[
					'secret_key'                => $secret_key,
					'amount'                    => $amount,
					'currency'                  => strtolower( $currency ),
					'description'               => $description,
					'confirm'                   => false, // Will be confirmed by frontend.
					"receipt_email"             => $customer_email,
					'automatic_payment_methods' => [
						'enabled'         => true,
						'allow_redirects' => 'never',
					],
					'metadata'                  => [
						'source'          => 'SureForms',
						'block_id'        => $block_id,
						'original_amount' => $amount,
						"receipt_email"             => $customer_email,
						"customer_name"             => $customer_name,
					],
				]
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
				throw new \Exception( __( 'Failed to create payment intent.', 'sureforms' ) );
			}

			$payment_intent = json_decode( wp_remote_retrieve_body( $payment_intent ), true );
			$payment_intent = is_array( $payment_intent ) ? $payment_intent : [];

			if ( ! isset( $payment_intent['client_secret'] ) || empty( $payment_intent['client_secret'] ) || ! isset( $payment_intent['id'] ) || empty( $payment_intent['id'] ) ) {
				throw new \Exception( __( 'Failed to create payment intent.', 'sureforms' ) );
			}

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
	 * @throws \Exception When Stripe configuration is invalid.
	 * @since x.x.x
	 * @return void
	 */
	public function create_subscription_intent() {
		// Verify nonce
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'srfm_stripe_payment_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', 'sureforms' ) );
		}

		// Validate required fields like simple-stripe-subscriptions.
		$required_fields = [ 'amount', 'currency', 'description', 'block_id', 'interval', 'plan_name' ];
		foreach ( $required_fields as $field ) {
			if ( empty( $_POST[ $field ] ) ) {
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

		// Validate interval like simple-stripe-subscriptions
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
			$customer_id = $this->get_or_create_stripe_customer([ 'email' => $customer_email, 'name' => $customer_name ]);
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
	 * @since x.x.x
	 * @return string License key or empty string if not available.
	 */
	private function get_license_key() {
		// Only proceed if Pro version is installed and Licensing class is loaded.
		if ( ! defined( 'SRFM_PRO_VER' ) || ! class_exists( 'SRFM_Pro\Admin\Licensing' ) ) {
			return '';
		}

		// Get instance safely (returns object or class-string).
		$licensing_instance = \SRFM_Pro\Admin\Licensing::get_instance();

		// Ensure we are working with an object before method calls.
		if ( ! is_object( $licensing_instance ) || ! method_exists( $licensing_instance, 'licensing_setup' ) ) {
			return '';
		}

		$license_setup = $licensing_instance->licensing_setup();
		if ( ! is_object( $license_setup ) || ! method_exists( $license_setup, 'settings' ) ) {
			return '';
		}

		$settings = $license_setup->settings();
		if ( ! is_object( $settings ) || ! property_exists( $settings, 'license_key' ) ) {
			return '';
		}

		return $settings->license_key;
	}

	/**
	 * Validate payment fields before form submission
	 *
	 * @param array<mixed> $form_data Form data.
	 * @since x.x.x
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
				$payment_response = $this->verify_stripe_payment_intent_and_save( $payment_value,$payment_id, $block_id, $form_data );
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
	 * Verify payment intent status
	 *
	 * @param array<mixed> $payment_value Payment value.
	 * @param string       $payment_id Payment ID.
	 * @param string       $block_id Block ID.
	 * @param array<mixed> $form_data Form data.
	 *
	 * @since x.x.x
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
			if ( is_array( $confirmed_payment_intent ) && isset( $confirmed_payment_intent['status'] ) && $confirmed_payment_intent['status'] === 'error' ) {
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

			$form_id                  = ( isset( $form_data['form-id'] ) && ! empty( $form_data['form-id'] ) && is_numeric( $form_data['form-id'] ) ) ? intval( $form_data['form-id'] ) : 0;
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
			$entry_data['total_amount']   = $this->amount_convert_cents_to_usd( $confirm_payment_amount );
			$entry_data['currency']       = $confirm_payment_currency;
			$entry_data['entry_id']       = 0;
			$entry_data['gateway']        = 'stripe';
			$entry_data['type']           = 'payment';
			$entry_data['mode']           = $payment_mode;
			$entry_data['transaction_id'] = $confirm_payment_id;
			$entry_data['srfm_txn_id']    = ''; // Will be updated after getting payment entry ID.
			$entry_data['customer_email'] = $customer_data['email'];
			$entry_data['customer_name']  = $customer_data['name'];

			$get_payment_entry_id = Payments::add( $entry_data );

			if ( $get_payment_entry_id ) {
				// Generate unique payment ID using the auto-increment ID and update the entry.
				$unique_payment_id = $this->generate_unique_payment_id( $get_payment_entry_id );
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
	 * Simplified subscription verification using simple-stripe-subscriptions approach
	 *
	 * @param array<mixed> $subscription_value Subscription data from frontend.
	 * @param string       $block_id Block ID.
	 * @param array<mixed> $form_data Form data.
	 * @since x.x.x
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
					$setup_intent = $this->stripe_api_request(
						'setup_intents',
						'GET',
						[],
						$setup_intent_id
					);

					if ( ( isset( $setup_intent['payment_method'] ) && ! empty( $setup_intent['payment_method'] ) && is_string( $setup_intent['payment_method'] ) ) ) {
						$subscription_update = $this->stripe_api_request(
							'subscriptions',
							'POST',
							[
								'default_payment_method' => $setup_intent['payment_method'],
								'collection_method'      => 'charge_automatically',
							],
							$subscription_id
						);

						if ( empty( $subscription_update['latest_invoice'] ) ) {
							return [
								'error' => __( 'Latest invoice not found on subscription.', 'sureforms' ),
							];
						}

						$invoice = $this->stripe_api_request(
							'invoices',
							'GET',
							[],
							$subscription_update['latest_invoice']
						);

						// Ensure invoice auto-advance is enabled for recurring payments.
						// This tells Stripe to automatically finalize and charge future invoices.
						if ( empty( $invoice['auto_advance'] ) && ! empty( $invoice['id'] ) && is_string( $invoice['id'] ) ) {
							$this->stripe_api_request(
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
						$paid_invoice = $this->stripe_api_request(
							'payment_intents',
							'POST',
							[ 'payment_method' => $setup_intent['payment_method'] ],
							$payment_intent_id . '/confirm'
						);

						// Get the subscription.
						$subscription = $this->stripe_api_request(
							'subscriptions',
							'GET',
							[],
							$subscription_id
						);
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
			$is_subscription_active = in_array( $subscription['status'], [ 'active', 'trialing' ] );
			$final_status           = $is_subscription_active ? 'active' : 'failed';

			$amount              = isset( $paid_invoice['amount'] ) && ! empty( $paid_invoice['amount'] ) ? $paid_invoice['amount'] : 0;
			$currency            = isset( $paid_invoice['currency'] ) && ! empty( $paid_invoice['currency'] ) ? $paid_invoice['currency'] : 'usd';
			$form_id             = isset( $form_data['form-id'] ) && ! empty( $form_data['form-id'] ) ? intval( $form_data['form-id'] ) : 0;
			$subscription_status = isset( $subscription['status'] ) && ! empty( $subscription['status'] ) && is_string( $subscription['status'] ) ? $subscription['status'] : '';

			// Extract customer data.
			$customer_data = $this->extract_customer_data( $subscription_value );

			// Prepare minimal subscription data for database.
			$entry_data = [
				'form_id'             => $form_id,
				'block_id'            => $block_id,
				'status'              => $final_status,
				'total_amount'        => $this->amount_convert_cents_to_usd( $amount ),
				'currency'            => $currency,
				'entry_id'            => 0,
				'gateway'             => 'stripe',
				'type'                => 'subscription',
				'mode'                => $payment_mode,
				'transaction_id'      => $subscription_id,
				'customer_id'         => $customer_id,
				'subscription_id'     => $subscription_id,
				'subscription_status' => $subscription_status,
				'srfm_txn_id'         => '', // Will be updated after getting payment entry ID.
				'customer_email'      => $customer_data['email'],
				'customer_name'       => $customer_data['name'],
				'payment_data'        => [
					'initial_invoice' => $paid_invoice,
					'subscription'    => $subscription,
				],
			];

			// If invoice is not paid then we need to set the status in the subscription log and return error.
			$paid_invoice_log = '';
			if ( $paid_invoice['status'] !== 'paid' ) {
				$paid_invoice_log = sprintf( 'Invoice Status: %s', $paid_invoice['status'] );
			}

			// Add simple log entry.
			$entry_data['log'] = [
				[
					'title'     => 'Subscription Verification',
					'timestamp' => time(),
					'messages'  => [
						sprintf( 'Subscription ID: %s', $subscription_id ),
						sprintf( 'Payment Intent ID: %s', $setup_intent_id ),
						sprintf( 'Subscription Status: %s', $subscription_status ),
						sprintf( 'Customer ID: %s', $customer_id ),
						sprintf( 'Total Amount: %s %s', number_format( $amount, 2 ), strtoupper( $currency ) ),
						$paid_invoice_log,
					],
				],
			];

			// Save to database.
			$payment_entry_id = Payments::add( $entry_data );

			if ( $payment_entry_id ) {
				// Generate unique payment ID using the auto-increment ID and update the entry.
				$unique_payment_id = $this->generate_unique_payment_id( $payment_entry_id );
				Payments::update( $payment_entry_id, [ 'srfm_txn_id' => $unique_payment_id ] );

				// Store in static array for later entry linking.
				$this->stripe_payment_entries[] = [
					'payment_id' => $subscription_id,
					'block_id'   => $block_id,
					'form_id'    => $form_id,
				];

				// Create the first individual payment record for this subscription if payment was successful.
				if ( $is_subscription_active && ! empty( $paid_invoice ) ) {
					$this->create_subscription_single_payment(
						$subscription,
						$paid_invoice,
						$form_id,
						$block_id
					);
				}

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
	 * Create individual payment record for subscription billing cycle.
	 * This method can be called by webhooks or other processes to track individual subscription payments.
	 *
	 * @param array<mixed> $subscription Stripe subscription data.
	 * @param array<mixed> $invoice_data Invoice data from Stripe.
	 * @param int          $form_id Form ID associated with the subscription.
	 * @param string       $block_id Block ID associated with the subscription.
	 * @since x.x.x
	 * @return void Payment ID if successful, false otherwise.
	 */
	public function create_subscription_single_payment( $subscription, $invoice_data, $form_id = 0, $block_id = '' ) {
		if ( empty( $subscription['id'] ) || empty( $invoice_data ) ) {
			return;
		}

		// Use provided form_id and block_id, or fall back to subscription record values.
		$form_id                           = ! empty( $form_id ) ? $form_id : ( $subscription['form_id'] ?? 0 );
		$block_id                          = ! empty( $block_id ) ? $block_id : ( $subscription['block_id'] ?? '' );
		$status                            = isset( $invoice_data['status'] ) && ! empty( $invoice_data['status'] ) && is_string( $invoice_data['status'] ) ? $invoice_data['status'] : '';
		$amount                            = isset( $invoice_data['amount'] ) && ! empty( $invoice_data['amount'] ) ? $invoice_data['amount'] : 0;
		$currency                          = isset( $invoice_data['currency'] ) && ! empty( $invoice_data['currency'] ) && is_string( $invoice_data['currency'] ) ? $invoice_data['currency'] : 'usd';
		$entry_id                          = isset( $subscription['entry_id'] ) && ! empty( $subscription['entry_id'] ) ? $subscription['entry_id'] : 0;
		$customer_id                       = isset( $invoice_data['customer'] ) && ! empty( $invoice_data['customer'] ) ? $invoice_data['customer'] : '';
		$paid_latest_charge_transaction_id = isset( $invoice_data['latest_charge'] ) && ! empty( $invoice_data['latest_charge'] ) ? $invoice_data['latest_charge'] : '';
		$subscription_id                   = isset( $subscription['id'] ) && ! empty( $subscription['id'] ) ? $subscription['id'] : '';

		// Get payment mode.
		$payment_mode = Stripe_Helper::get_stripe_mode();

		// Prepare subscription individual payment entry data.
		$entry_data = [
			'form_id'             => $form_id,
			'block_id'            => $block_id,
			'status'              => $status,
			'total_amount'        => $this->amount_convert_cents_to_usd( $amount ),
			'refunded_amount'     => '0.00000000', // Required field - default to 0.
			'currency'            => $currency,
			'entry_id'            => $entry_id, // Link to same entry as subscription.
			'gateway'             => 'stripe',
			'type'                => 'renewal', // Subscription billing cycle payment (renewal).
			'mode'                => $payment_mode,
			'transaction_id'      => $paid_latest_charge_transaction_id,
			'customer_id'         => $customer_id,
			'subscription_id'     => $subscription_id, // Link back to subscription.
			'subscription_status' => '', // Not applicable for individual payments.
			'customer_email'      => isset( $subscription['customer_email'] ) ? sanitize_email( $subscription['customer_email'] ) : '',
			'customer_name'       => isset( $subscription['customer_name'] ) ? sanitize_text_field( $subscription['customer_name'] ) : '',
			'payment_data'        => $invoice_data, // Store complete invoice data for refunds and debugging.
		];

		// Add log entry for audit trail.
		$entry_data['log'] = [
			[
				'title'     => 'Subscription Renewal Payment',
				'timestamp' => time(),
				'messages'  => [
					sprintf( 'Subscription ID: %s', $subscription_id ),
					sprintf( 'Transaction ID: %s', $paid_latest_charge_transaction_id ),
					sprintf( 'Invoice ID: %s', isset( $invoice_data['id'] ) && ! empty( $invoice_data['id'] ) ? $invoice_data['id'] : 'N/A' ),
					sprintf( 'Amount: %s %s', number_format( $amount, 2 ), strtoupper( $currency ) ),
					sprintf( 'Status: %s', $status ),
					'Created via subscription billing cycle',
				],
			],
		];

		// Save to database.
		$payment_entry_id = Payments::add( $entry_data );

		if ( $payment_entry_id ) {
			// Store in static array for later entry linking.
			$this->stripe_payment_entries[] = [
				'payment_id' => $paid_latest_charge_transaction_id,
				'block_id'   => $block_id,
				'form_id'    => $form_id,
			];
		}
	}

	/**
	 * Convert cents amount to USD
	 *
	 * @param int $amount Amount in cents.
	 * @since x.x.x
	 * @return float Amount in USD.
	 */
	private function amount_convert_cents_to_usd( $amount ) {
		return $amount / 100;
	}
	/**
	 * Get or create Stripe customer
	 *
	 * @param array<string,string> $customer_data Customer data containing 'email' and 'name' from POST.
	 * @since x.x.x
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
	 * @since x.x.x
	 * @return string|false Customer ID on success, false on failure.
	 */
	private function create_stripe_customer_for_user( $user, $post_customer_data = [] ) {
		try {
			// Use POST email if provided, else use logged-in user email.
			$customer_email = ! empty( $post_customer_data['email'] ) ? $post_customer_data['email'] : $user->user_email;

			// Use POST name if provided, else use logged-in user name.
			$customer_name = ! empty( $post_customer_data['name'] ) ? $post_customer_data['name'] : ( trim( $user->first_name . ' ' . $user->last_name ) ?: $user->display_name );

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
	 * @param array<string,string> $post_customer_data Customer data from POST containing 'email' and 'name'.
	 * @since x.x.x
	 * @return string|false Customer ID on success, false on failure.
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
	 * @since x.x.x
	 * @return bool True if customer exists, false otherwise.
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
	 * @since x.x.x
	 * @return array Form data with email and name if available.
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
	 * @since x.x.x
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
	 * Handle form submit and update payment entries with entry_id
	 *
	 * This function is called after a form submission to link the created entry
	 * with any associated Stripe payment records. It matches payment entries
	 * by form_id and updates them with the newly created entry_id.
	 *
	 * @param array<string,mixed> $form_submit_response The form submission response containing entry_id and form_id.
	 * @since x.x.x
	 * @return void
	 */
	public function update_payment_entry_id_form_submit( $form_submit_response ) {
		// Check if entry_id exists in the form_submit_response.
		if ( ! empty( $form_submit_response['entry_id'] ) && ! empty( $this->stripe_payment_entries ) ) {
			$entry_id = intval( $form_submit_response['entry_id'] );

			// Loop through stored payment entries to update with entry_id.
			foreach ( $this->stripe_payment_entries as $stripe_payment_entry ) {
				if ( ! empty( $stripe_payment_entry['payment_id'] ) && ! empty( $stripe_payment_entry['form_id'] ) ) {
					// Check if form_id matches.
					$stored_form_id   = isset( $stripe_payment_entry['form_id'] ) && ! empty( $stripe_payment_entry['form_id'] ) ? intval( $stripe_payment_entry['form_id'] ) : 0;
					$response_form_id = isset( $form_submit_response['form_id'] ) && ! empty( $form_submit_response['form_id'] ) ? intval( $form_submit_response['form_id'] ) : 0;

					if ( ! empty( $stored_form_id ) && $stored_form_id === $response_form_id ) {
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
	 * @since x.x.x
	 * @return void
	 */
	private function update_payment_entry_id( $payment_id, $entry_id ) {
		try {
			// Find the payment entry by transaction_id.
			$payment_entries = Payments::get_instance()->get_results(
				[ 'transaction_id' => $payment_id ],
				'id'
			);

			if ( ! empty( $payment_entries ) && is_array( $payment_entries ) && isset( $payment_entries[0] ) && is_array( $payment_entries[0] ) && isset( $payment_entries[0]['id'] ) ) {
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
	 * @param string       $endpoint Stripe API endpoint (e.g., 'subscriptions', 'customers').
	 * @param string       $method HTTP method (GET, POST, DELETE, etc.).
	 * @param array<mixed> $data Request data.
	 * @param string       $resource_id Resource ID for specific resource operations.
	 * @since x.x.x
	 * @return array|false API response or false on failure.
	 */
	private function stripe_api_request( $endpoint, $method = 'POST', $data = [], $resource_id = '' ) {
		// Validate Stripe connection.
		if ( ! Stripe_Helper::is_stripe_connected() ) {
			// TODO: Handle proper error handling.
			return false;
		}

		$secret_key = Stripe_Helper::get_stripe_secret_key();

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
			// TODO: Handle proper error handling.
			return false;
		}

		$return_body = json_decode( $body, true );
		$return_body = is_array( $return_body ) ? $return_body : [];

		return $return_body;
	}

	/**
	 * Flatten Stripe data for form-encoded requests
	 *
	 * @param array<mixed> $data Data to flatten.
	 * @param string       $prefix Prefix for nested keys.
	 * @since x.x.x
	 * @return array Flattened data.
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
				// Convert boolean values to string for Stripe API compatibility.
				// Stripe expects "true"/"false" strings, not 1/0 integers.
				if ( is_bool( $value ) ) {
					$result[ $new_key ] = $value ? 'true' : 'false';
				} else {
					$result[ $new_key ] = $value;
				}
			}
		}

		return $result;
	}

	/**
	 * Generate unique transaction ID (deprecated - kept for backwards compatibility)
	 *
	 * Format: {6-random-chars}_{payment_id}
	 * Example: a2bf45_pi_3QhgmFHqS7N4oFQh0x4UQjBv
	 *
	 * @param string $payment_id Payment intent ID or subscription ID.
	 * @since x.x.x
	 * @return string Generated transaction ID.
	 */
	private function generate_transaction_id( $payment_id ) {
		// Generate 6-character random alphanumeric string.
		$characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
		$random     = '';
		for ( $i = 0; $i < 6; $i++ ) {
			$random .= $characters[ wp_rand( 0, strlen( $characters ) - 1 ) ];
		}

		return $random . '_' . $payment_id;
	}

	/**
	 * Generate unique payment ID using base36 encoding and random string, always 14 characters.
	 *
	 * Format: {base36_encoded_id}{random_chars},
	 * Example: 3F7B9A1E4C7D2A (exactly 14 chars)
	 *
	 * @param int $auto_increment_id The database auto-increment ID.
	 * @since x.x.x
	 * @return string Generated unique payment ID (always 14 characters).
	 */
	private function generate_unique_payment_id( $auto_increment_id ) {
		// Convert the auto-increment ID to base36.
		$encoded_id = base_convert( $auto_increment_id, 10, 36 );

		// Calculate the length of random part needed to make the ID exactly 14 chars.
		$random_length = 14 - strlen( $encoded_id );
		if ( $random_length < 1 ) {
			$random_length = 1; // Always leave at least 1 random char for collision prevention.
		}

		// Generate random part using only valid base36 (alphanumeric) chars.
		// bin2hex gives 2 chars per byte, so we need ceil($random_length / 2) bytes.
		$random_bytes = bin2hex( random_bytes( (int) ceil( $random_length / 2 ) ) );
		$random_part = substr( $random_bytes, 0, $random_length );

		$unique_id = strtoupper( $encoded_id . $random_part );

		// Ensure exactly 14 chars.
		return substr( $unique_id, 0, 14 );
	}

	/**
	 * Extract customer name and email from form data
	 *
	 * Uses the payment block's customerNameField and customerEmailField attributes
	 * to find the corresponding field slugs, then extracts the values from form data.
	 *
	 * @param array<string,mixed> $input_value Input value.
	 * @since x.x.x
	 * @return array{name: string, email: string} Customer data array.
	 */
	private function extract_customer_data( $input_value ) {
		$customer_data = [
			'name'  => ! empty( $input_value['name'] ) && is_string( $input_value['name'] ) ? sanitize_text_field( $input_value['name'] ) : '',
			'email' => ! empty( $input_value['email'] ) && is_email( $input_value['email'] ) ? sanitize_email( $input_value['email'] ) : '',
		];

		return $customer_data;
	}
}
