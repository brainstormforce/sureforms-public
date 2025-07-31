<?php
/**
 * Payments Settings Handler
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
 * Payments Settings Class
 *
 * @since x.x.x
 */
class Payments_Settings {
	use Get_Instance;

	/**
	 * Option name for storing payment settings
	 *
	 * @var string
	 * @since x.x.x
	 */
	const OPTION_NAME = 'srfm_payments_settings';

	/**
	 * Constructor
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
		add_filter( 'srfm_global_settings_data', [ $this, 'add_payments_settings' ] );
		add_action( 'admin_init', [ $this, 'intercept_stripe_callback' ] );
	}

	/**
	 * Register REST routes
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			'sureforms/v1',
			'/payments/stripe-connect',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_stripe_connect_url' ],
					'permission_callback' => [ $this, 'permission_check' ],
				],
			]
		);

		register_rest_route(
			'sureforms/v1',
			'/payments/stripe-disconnect',
			[
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'disconnect_stripe' ],
					'permission_callback' => [ $this, 'permission_check' ],
				],
			]
		);

		register_rest_route(
			'sureforms/v1',
			'/payments/stripe-callback',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'handle_stripe_callback' ],
					'permission_callback' => [ $this, 'permission_check' ],
				],
			]
		);
	}

	/**
	 * Permission callback
	 *
	 * @since x.x.x
	 * @return bool
	 */
	public function permission_check() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Add payments settings to global settings
	 *
	 * @param array $settings Existing settings.
	 * @return array
	 * @since x.x.x
	 */
	public function add_payments_settings( $settings ) {
		$payments_settings                  = get_option( self::OPTION_NAME, $this->get_default_settings() );
		$settings['srfm_payments_settings'] = $payments_settings;
		return $settings;
	}

	/**
	 * Get default settings
	 *
	 * @return array
	 * @since x.x.x
	 */
	private function get_default_settings() {
		return [
			'stripe_connected'            => false,
			'stripe_account_id'           => '',
			'stripe_account_email'        => '',
			'stripe_live_publishable_key' => '',
			'stripe_live_secret_key'      => '',
			'stripe_test_publishable_key' => '',
			'stripe_test_secret_key'      => '',
			'currency'                    => 'USD',
			'payment_mode'                => 'test',
		];
	}

	/**
	 * Get Stripe Connect URL
	 *
	 * @return \WP_REST_Response
	 * @since x.x.x
	 */
	public function get_stripe_connect_url() {
		// Stripe client ID from checkout-plugins-stripe-woo.
		$client_id = 'ca_KOXfLe7jv1m4L0iC4KNEMc5fT8AXWWuL';

		// Use the same redirect URI pattern as checkout-plugins-stripe-woo.
		$redirect_url        = admin_url( 'admin.php?page=wc-settings&tab=cpsw_api_settings' );
		$nonce               = wp_create_nonce( 'stripe-connect' );
		$redirect_with_nonce = add_query_arg( 'cpsw_connect_nonce', $nonce, $redirect_url );

		// Store our own callback data.
		set_transient( 'srfm_stripe_connect_nonce_' . get_current_user_id(), $nonce, HOUR_IN_SECONDS );

		// Create state parameter exactly like checkout-plugins-stripe-woo.
		$state = base64_encode(
			wp_json_encode(
				[
					'redirect' => $redirect_with_nonce,
				]
			)
		);

		$connect_url = add_query_arg(
			[
				'response_type'  => 'code',
				'client_id'      => $client_id,
				'stripe_landing' => 'login',
				'always_prompt'  => 'true',
				'scope'          => 'read_write',
				'state'          => $state,
			],
			'https://connect.stripe.com/oauth/authorize'
		);

		return rest_ensure_response( [ 'url' => $connect_url ] );
	}

	/**
	 * Intercept Stripe OAuth callback
	 *
	 * @return void
	 * @since x.x.x
	 */
	public function intercept_stripe_callback() {
		// Check if this is a Stripe callback for our flow.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) || 'wc-settings' !== $_GET['page'] ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['tab'] ) || 'cpsw_api_settings' !== $_GET['tab'] ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['cpsw_connect_nonce'] ) ) {
			return;
		}

		// Verify this is our nonce.
		$nonce       = sanitize_text_field( wp_unslash( $_GET['cpsw_connect_nonce'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$saved_nonce = get_transient( 'srfm_stripe_connect_nonce_' . get_current_user_id() );

		if ( $nonce !== $saved_nonce ) {
			return; // Not our callback.
		}

		// This is our callback, handle it.
		$this->handle_stripe_callback();
	}

	/**
	 * Handle Stripe OAuth callback
	 *
	 * @return void
	 * @since x.x.x
	 */
	public function handle_stripe_callback() {
		// Check if we have OAuth response data.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['response'] ) ) {
			$this->process_oauth_success();
			return;
		}

		// Check if we have OAuth error.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['error'] ) ) {
			$this->process_oauth_error();
			return;
		}

		// No response or error, redirect with generic error.
		$redirect_url = add_query_arg(
			[
				'page'  => 'sureforms_form_settings',
				'tab'   => 'payments-settings',
				'error' => rawurlencode( __( 'OAuth callback missing response data.', 'sureforms' ) ),
			],
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Process OAuth success response
	 *
	 * @return \WP_REST_Response
	 * @since x.x.x
	 */
	private function process_oauth_success() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['response'] ) ) {
			return new \WP_Error( 'missing_response', __( 'Missing OAuth response data.', 'sureforms' ) );
		}

		$response_data = sanitize_text_field( wp_unslash( $_GET['response'] ) );
		$response      = json_decode( base64_decode( $response_data ), true );

		if ( ! $response ) {
			return new \WP_Error( 'invalid_response', __( 'Invalid OAuth response.', 'sureforms' ) );
		}

		// Extract OAuth data following checkout-plugins-stripe-woo pattern.
		$settings = get_option( self::OPTION_NAME, $this->get_default_settings() );

		// Store live keys.
		if ( isset( $response['live'] ) ) {
			$settings['stripe_live_publishable_key'] = sanitize_text_field( $response['live']['stripe_publishable_key'] ?? '' );
			$settings['stripe_live_secret_key']      = sanitize_text_field( $response['live']['access_token'] ?? '' );
			$settings['stripe_account_id']           = sanitize_text_field( $response['live']['stripe_user_id'] ?? '' );
		}

		// Store test keys.
		if ( isset( $response['test'] ) ) {
			$settings['stripe_test_publishable_key'] = sanitize_text_field( $response['test']['stripe_publishable_key'] ?? '' );
			$settings['stripe_test_secret_key']      = sanitize_text_field( $response['test']['access_token'] ?? '' );
		}

		// Mark as connected.
		$settings['stripe_connected']     = true;
		$settings['stripe_account_email'] = sanitize_email( $response['account']['email'] ?? '' );

		// Save settings.
		update_option( self::OPTION_NAME, $settings );

		// Clean up transients.
		delete_transient( 'srfm_stripe_connect_nonce_' . get_current_user_id() );

		// Redirect to SureForms payments settings.
		wp_safe_redirect( admin_url( 'admin.php?page=sureforms_form_settings&tab=payments-settings&connected=1' ) );
		exit;
	}

	/**
	 * Process OAuth error response
	 *
	 * @return void
	 * @since x.x.x
	 */
	private function process_oauth_error() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['error'] ) ) {
			$error_data = sanitize_text_field( wp_unslash( $_GET['error'] ) );
			$error      = json_decode( base64_decode( $error_data ), true );
		} else {
			$error = [];
		}

		$error_message = __( 'Failed to connect to Stripe.', 'sureforms' );
		if ( isset( $error['message'] ) ) {
			$error_message = sanitize_text_field( $error['message'] );
		}

		// Clean up transients.
		delete_transient( 'srfm_stripe_connect_nonce_' . get_current_user_id() );

		// Redirect with error.
		$redirect_url = add_query_arg(
			[
				'page'  => 'sureforms_form_settings',
				'tab'   => 'payments-settings',
				'error' => rawurlencode( $error_message ),
			],
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Disconnect Stripe account
	 *
	 * @return \WP_REST_Response
	 * @since x.x.x
	 */
	public function disconnect_stripe() {
		$settings                                = get_option( self::OPTION_NAME, $this->get_default_settings() );
		$settings['stripe_connected']            = false;
		$settings['stripe_account_id']           = '';
		$settings['stripe_account_email']        = '';
		$settings['stripe_live_publishable_key'] = '';
		$settings['stripe_live_secret_key']      = '';
		$settings['stripe_test_publishable_key'] = '';
		$settings['stripe_test_secret_key']      = '';

		update_option( self::OPTION_NAME, $settings );

		return rest_ensure_response( [ 'success' => true ] );
	}

	/**
	 * Get available currencies
	 *
	 * @return array
	 * @since x.x.x
	 */
	public static function get_currencies() {
		return [
			'USD' => __( 'US Dollar', 'sureforms' ),
			'EUR' => __( 'Euro', 'sureforms' ),
			'GBP' => __( 'British Pound', 'sureforms' ),
			'JPY' => __( 'Japanese Yen', 'sureforms' ),
			'AUD' => __( 'Australian Dollar', 'sureforms' ),
			'CAD' => __( 'Canadian Dollar', 'sureforms' ),
			'CHF' => __( 'Swiss Franc', 'sureforms' ),
			'CNY' => __( 'Chinese Yuan', 'sureforms' ),
			'SEK' => __( 'Swedish Krona', 'sureforms' ),
			'NZD' => __( 'New Zealand Dollar', 'sureforms' ),
			'MXN' => __( 'Mexican Peso', 'sureforms' ),
			'SGD' => __( 'Singapore Dollar', 'sureforms' ),
			'HKD' => __( 'Hong Kong Dollar', 'sureforms' ),
			'NOK' => __( 'Norwegian Krone', 'sureforms' ),
			'KRW' => __( 'South Korean Won', 'sureforms' ),
			'TRY' => __( 'Turkish Lira', 'sureforms' ),
			'RUB' => __( 'Russian Ruble', 'sureforms' ),
			'INR' => __( 'Indian Rupee', 'sureforms' ),
			'BRL' => __( 'Brazilian Real', 'sureforms' ),
			'ZAR' => __( 'South African Rand', 'sureforms' ),
			'AED' => __( 'UAE Dirham', 'sureforms' ),
			'PHP' => __( 'Philippine Peso', 'sureforms' ),
			'IDR' => __( 'Indonesian Rupiah', 'sureforms' ),
			'MYR' => __( 'Malaysian Ringgit', 'sureforms' ),
			'THB' => __( 'Thai Baht', 'sureforms' ),
		];
	}
}

// Initialize the class.
Payments_Settings::get_instance();
