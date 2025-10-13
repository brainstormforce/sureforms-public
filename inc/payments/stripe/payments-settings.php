<?php
/**
 * Payments Settings Handler
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc\Payments\Stripe;

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
	 * Option name for storing payment settings.
	 *
	 * @var string
	 * @since x.x.x
	 */
	public const OPTION_NAME = 'srfm_payments_settings';

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

		register_rest_route(
			'sureforms/v1',
			'/payments/create-payment-webhook',
			[
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'handle_webhook_creation_request' ],
					'permission_callback' => [ $this, 'permission_check' ],
				],
			]
		);

		register_rest_route(
			'sureforms/v1',
			'/payments/delete-payment-webhook',
			[
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'handle_webhook_deletion_request' ],
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
	 * @since x.x.x
	 * @return array
	 */
	/**
	 * Add payments settings to global settings
	 *
	 * @param array<mixed> $settings Existing settings.
	 * @since x.x.x
	 * @return array<mixed>
	 */
	public function add_payments_settings( array $settings ): array {
		$payments_settings                  = get_option( self::OPTION_NAME, $this->get_default_settings() );
		$settings['srfm_payments_settings'] = is_array( $payments_settings ) ? $payments_settings : $this->get_default_settings();
		return $settings;
	}

	/**
	 * Get Stripe Connect URL
	 *
	 * @since x.x.x
	 * @return \WP_REST_Response
	 */
	public function get_stripe_connect_url() {
		// Stripe client ID from checkout-plugins-stripe-woo.
		$client_id = 'ca_KOXfLe7jv1m4L0iC4KNEMc5fT8AXWWuL';

		// Use the same redirect URI pattern as checkout-plugins-stripe-woo.
		$redirect_url        = admin_url( 'admin.php?page=sureforms_form_settings&tab=payments-settings' );
		$nonce               = wp_create_nonce( 'stripe-connect' );
		$redirect_with_nonce = add_query_arg( 'cpsw_connect_nonce', $nonce, $redirect_url );

		// Store our own callback data.
		set_transient( 'srfm_stripe_connect_nonce_' . get_current_user_id(), $nonce, HOUR_IN_SECONDS );

		// Create state parameter exactly like checkout-plugins-stripe-woo.
		$state_param = wp_json_encode(
			[
				'redirect' => $redirect_with_nonce,
			]
		);
		$state       = '';
		if ( is_string( $state_param ) ) {
			$state = base64_encode( $state_param ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		}

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
	 * @since x.x.x
	 * @return void
	 */
	public function intercept_stripe_callback(): void {
		// Check if this is a Stripe callback for our flow.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) || 'sureforms_form_settings' !== $_GET['page'] ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['tab'] ) || 'payments-settings' !== $_GET['tab'] ) {
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
	 * @since x.x.x
	 * @return void
	 */
	public function handle_stripe_callback(): void {
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
	 * Disconnect Stripe account
	 *
	 * @since x.x.x
	 * @return \WP_REST_Response
	 */
	public function disconnect_stripe() {
		// Delete Stripe webhook endpoints for both test and live modes.
		$this->delete_stripe_webhooks();

		$settings = get_option( self::OPTION_NAME, $this->get_default_settings() );
		if ( ! is_array( $settings ) ) {
			$settings = $this->get_default_settings();
		}

		$settings['stripe_connected']            = false;
		$settings['stripe_account_id']           = '';
		$settings['stripe_account_email']        = '';
		$settings['stripe_live_publishable_key'] = '';
		$settings['stripe_live_secret_key']      = '';
		$settings['stripe_test_publishable_key'] = '';
		$settings['stripe_test_secret_key']      = '';
		$settings['webhook_test_secret']         = '';
		$settings['webhook_test_url']            = '';
		$settings['webhook_test_id']             = '';
		$settings['webhook_live_secret']         = '';
		$settings['webhook_live_url']            = '';
		$settings['webhook_live_id']             = '';
		$settings['account_data']                = [];

		$updated = update_option( self::OPTION_NAME, $settings );

		// TODO: Handle proper error handling.

		return rest_ensure_response(
			[
				'success' => true,
				'message' => __( 'Stripe account disconnected successfully.', 'sureforms' ),
				'updated' => $updated,
			]
		);
	}

	/**
	 * Get available currencies
	 *
	 * @since x.x.x
	 * @return array<string, string>
	 */
	public static function get_currencies(): array {
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

	/**
	 * Handle webhook creation request (REST API handler)
	 *
	 * @since x.x.x
	 * @return \WP_REST_Response
	 */
	public function handle_webhook_creation_request() {
		// Call the core webhook creation function.
		$result = $this->setup_stripe_webhooks();

		return rest_ensure_response( $result );
	}

	/**
	 * Setup Stripe webhooks for both test and live modes
	 *
	 * @since x.x.x
	 * @return array<mixed> Array containing webhook creation results and details
	 */
	public function setup_stripe_webhooks(): array {
		$settings = get_option( self::OPTION_NAME, $this->get_default_settings() );
		if ( ! is_array( $settings ) ) {
			$settings = $this->get_default_settings();
		}

		if ( empty( $settings['stripe_connected'] ) ) {
			return [
				'success' => false,
				'message' => __( 'Stripe is not connected.', 'sureforms' ),
			];
		}

		$webhooks_created = 0;
		$error_message    = '';
		$webhook_url      = esc_url( get_home_url() . '/wp-json/sureforms/webhook' );
		$modes            = [ 'test', 'live' ];

		foreach ( $modes as $mode ) {
			$secret_key = 'live' === $mode
				? ( $settings['stripe_live_secret_key'] ?? '' )
				: ( $settings['stripe_test_secret_key'] ?? '' );

			if ( empty( $secret_key ) ) {
				continue;
			}

			try {
				$webhook_data = [
					'api_version'    => '2025-07-30.basil',
					'url'            => $webhook_url,
					'enabled_events' => [
						'charge.failed',
						'charge.succeeded',
						'payment_intent.succeeded',
						'charge.refund.updated',
						'charge.dispute.created',
						'charge.dispute.closed',
						'invoice.payment_succeeded',
					],
				];

				$api_response = Stripe_Helper::stripe_api_request( 'webhook_endpoints', 'POST', $webhook_data );

				if ( ! isset( $api_response['success'] ) || ! $api_response['success'] ) {
					$error_details = $api_response['error'] ?? [];
					$error_message = isset( $error_details['message'] ) ? $error_details['message'] : '';
					// TODO: Handle proper error handling.
					throw new \Exception( $error_message );
				}

				$webhook = $api_response['data'] ?? [];

				// Validate webhook response structure.
				if ( ! is_array( $webhook ) ) {
					throw new \Exception( __( 'Invalid webhook response format.', 'sureforms' ) );
				}

				if ( empty( $webhook['id'] ) ) {
					throw new \Exception( __( 'Webhook created but no ID returned.', 'sureforms' ) );
				}

				if ( empty( $webhook['secret'] ) ) {
					throw new \Exception( __( 'Webhook created but no secret returned.', 'sureforms' ) );
				}

				// Store webhook data in settings.
				if ( 'live' === $mode ) {
					$settings['webhook_live_secret'] = $webhook['secret'];
					$settings['webhook_live_id']     = $webhook['id'];
					$settings['webhook_live_url']    = $webhook_url;
				} else {
					$settings['webhook_test_secret'] = $webhook['secret'];
					$settings['webhook_test_id']     = $webhook['id'];
					$settings['webhook_test_url']    = $webhook_url;
				}

				$webhooks_created++;

			} catch ( \Exception $e ) {
				$error_message = $e->getMessage();
				// TODO: Handle proper error handling.
			}
		}

		// Update settings if any webhooks were created.
		if ( $webhooks_created > 0 ) {
			update_option( self::OPTION_NAME, $settings );
		}

		// Prepare response with webhook details.
		$response_data = [
			'success' => $webhooks_created > 0,
		];

		if ( $webhooks_created > 0 ) {
			$response_data['webhook_details'] = [
				'webhook_url' => $webhook_url,
				'test'        => [
					'webhook_secret' => $settings['webhook_test_secret'] ?? '',
					'webhook_id'     => $settings['webhook_test_id'] ?? '',
					'webhook_url'    => $webhook_url,
				],
				'live'        => [
					'webhook_secret' => $settings['webhook_live_secret'] ?? '',
					'webhook_id'     => $settings['webhook_live_id'] ?? '',
					'webhook_url'    => $webhook_url,
				],
			];
		}

		// Set appropriate message.
		if ( count( $modes ) === $webhooks_created ) {
			$response_data['message'] = sprintf(
				/* translators: %1$d: number of webhooks created */
				__( 'Webhooks created successfully for %1$d mode(s).', 'sureforms' ),
				$webhooks_created
			);
		} elseif ( $webhooks_created > 0 ) {
			$response_data['message'] = sprintf(
				/* translators: %1$d: number of webhooks created, %2$s: error message */
				__( 'Webhooks created for %1$d mode(s). Some modes may have failed: %2$s', 'sureforms' ),
				$webhooks_created,
				$error_message
			);
		} else {
			$response_data['message'] = $error_message ? $error_message : __( 'Failed to create webhooks.', 'sureforms' );
		}

		return $response_data;
	}

	/**
	 * Handle webhook deletion request (REST API handler)
	 *
	 * @since x.x.x
	 * @return \WP_REST_Response
	 */
	public function handle_webhook_deletion_request() {
		// Call the core webhook deletion function.
		$result = $this->delete_stripe_webhooks();

		return rest_ensure_response( $result );
	}

	/**
	 * Delete Stripe webhooks for both test and live modes
	 *
	 * @since x.x.x
	 * @return array<mixed> Array containing deletion results
	 */
	public function delete_stripe_webhooks(): array {
		$settings = get_option( self::OPTION_NAME, $this->get_default_settings() );
		if ( ! is_array( $settings ) ) {
			$settings = $this->get_default_settings();
		}

		if ( empty( $settings['stripe_connected'] ) ) {
			return [
				'success' => false,
				'message' => __( 'Stripe is not connected.', 'sureforms' ),
			];
		}

		$webhooks_deleted = 0;
		$error_message    = '';
		$modes            = [ 'test', 'live' ];

		$return_response = [];

		foreach ( $modes as $mode ) {
			$secret_key = 'live' === $mode
				? ( $settings['stripe_live_secret_key'] ?? '' )
				: ( $settings['stripe_test_secret_key'] ?? '' );

			$webhook_id = 'live' === $mode
				? ( $settings['webhook_live_id'] ?? '' )
				: ( $settings['webhook_test_id'] ?? '' );

			if ( empty( $secret_key ) || empty( $webhook_id ) ) {
				continue;
			}

			try {
				$api_response = Stripe_Helper::stripe_api_request( 'webhook_endpoints', 'DELETE', [], (string) $webhook_id );

				if ( ! isset( $api_response['success'] ) || ! $api_response['success'] ) {
					$error_details = $api_response['error'] ?? [];
					$error_message = isset( $error_details['message'] ) ? $error_details['message'] : '';
					// TODO: Handle proper error handling.
					$return_response[] = [
						'success' => false,
						'message' => $error_message,
					];
					continue;
				}

				$delete_result = $api_response['data'] ?? [];

				// Validate deletion response.
				if ( ! is_array( $delete_result ) ) {
					$return_response[] = [
						'success' => false,
						'message' => __( 'Invalid webhook deletion response format.', 'sureforms' ),
					];
					continue;
				}

				if ( empty( $delete_result['deleted'] ) || true !== $delete_result['deleted'] ) {
					$return_response[] = [
						'success' => false,
						'message' => __( 'Webhook deletion was not confirmed by Stripe.', 'sureforms' ),
					];
					continue;
				}

				// Clean up stored webhook data from settings.
				if ( 'live' === $mode ) {
					$settings['webhook_live_secret'] = '';
					$settings['webhook_live_id']     = '';
					$settings['webhook_live_url']    = '';
				} else {
					$settings['webhook_test_secret'] = '';
					$settings['webhook_test_id']     = '';
					$settings['webhook_test_url']    = '';
				}

				$webhooks_deleted++;
				$return_response[] = [
					'success' => true,
					'message' => __( 'Webhook deleted successfully.', 'sureforms' ),
				];

			} catch ( \Exception $e ) {
				$error_message = $e->getMessage();
				// TODO: Handle proper error handling.
				$return_response[] = [
					'success' => false,
					'message' => $error_message,
				];
			}
		}

		// Prepare response.
		$response_data = [
			'success' => $webhooks_deleted > 0,
		];

		// Update settings if any webhooks were deleted.
		if ( $webhooks_deleted > 0 ) {
			update_option( self::OPTION_NAME, $settings );
			$response_data['message'] = sprintf(
				/* translators: %d: number of webhooks deleted */
				__( 'Webhooks deleted successfully for %d mode(s).', 'sureforms' ),
				$webhooks_deleted
			);
		} else {
			$message = '';

			foreach ( $return_response as $response ) {
				// Since $response is always array{success: bool, message: string}, isset() is redundant.
				if ( $response['success'] && is_string( $response['message'] ) ) {
					$message .= $response['message'] . '<br>';
				}
			}

			$response_data['message'] = $message ? $message : __( 'Failed to delete webhooks.', 'sureforms' );
		}

		return $response_data;
	}

	/**
	 * Delete payment webhooks
	 *
	 * @param \WP_REST_Request|array<int, string>|null $request_or_modes Request object or array of modes to delete.
	 * @since x.x.x
	 * @return \WP_REST_Response
	 */
	public function delete_payment_webhooks( $request_or_modes = null ) {
		$settings = get_option( self::OPTION_NAME, $this->get_default_settings() );
		if ( ! is_array( $settings ) ) {
			$settings = $this->get_default_settings();
		}

		if ( empty( $settings['stripe_connected'] ) ) {
			return rest_ensure_response(
				[
					'success' => false,
					'message' => __( 'Stripe is not connected.', 'sureforms' ),
				]
			);
		}

		// Determine modes to delete.
		$modes = [];

		if ( is_array( $request_or_modes ) ) {
			// Direct array of modes passed.
			$modes = $request_or_modes;
		} elseif ( $request_or_modes && method_exists( $request_or_modes, 'get_param' ) ) {
			// REST request object - check for modes parameter first, then mode parameter.
			$request_modes = $request_or_modes->get_param( 'modes' );
			if ( ! empty( $request_modes ) && is_array( $request_modes ) ) {
				$modes = $request_modes;
			} else {
				// Fallback to single mode parameter for backward compatibility.
				$mode_to_delete = $request_or_modes->get_param( 'mode' ) ?? ( $settings['payment_mode'] ?? 'test' );
				$modes          = [ $mode_to_delete ];
			}
		} else {
			// Default to current payment mode.
			$modes = [ $settings['payment_mode'] ?? 'test' ];
		}

		// Validate modes.
		foreach ( $modes as $mode ) {
			if ( ! in_array( $mode, [ 'test', 'live' ], true ) ) {
				return rest_ensure_response(
					[
						'success' => false,
						'message' => __( 'Invalid payment mode specified.', 'sureforms' ),
					]
				);
			}
		}

		$webhooks_deleted = 0;
		$error_message    = '';

		foreach ( $modes as $mode ) {
			$secret_key = 'live' === $mode
				? ( $settings['stripe_live_secret_key'] ?? '' )
				: ( $settings['stripe_test_secret_key'] ?? '' );

			$webhook_id = 'live' === $mode ? ( $settings['webhook_live_id'] ?? '' ) : ( $settings['webhook_test_id'] ?? '' );

			if ( empty( $secret_key ) || empty( $webhook_id ) ) {
				continue;
			}

			try {
				$api_response = Stripe_Helper::stripe_api_request( 'webhook_endpoints', 'DELETE', [], (string) $webhook_id );

				if ( ! isset( $api_response['success'] ) || ! $api_response['success'] ) {
					$error_details = $api_response['error'] ?? [];
					$error_message = isset( $error_details['message'] ) ? $error_details['message'] : '';

					// TODO: Handle proper error handling.

					return rest_ensure_response(
						[
							'success' => false,
							'message' => $error_message,
						]
					);
				}

				$delete_result = $api_response['data'] ?? [];

				// Validate deletion response.
				if ( ! is_array( $delete_result ) ) {
					// TODO: Handle proper error handling.
					return rest_ensure_response(
						[
							'success' => false,
							'message' => __( 'Invalid webhook deletion response format.', 'sureforms' ),
						]
					);
				}

				if ( empty( $delete_result['deleted'] ) || true !== $delete_result['deleted'] ) {
					return rest_ensure_response(
						[
							'success' => false,
							'message' => __( 'Webhook deletion was not confirmed by Stripe.', 'sureforms' ),
						]
					);
				}

				// Clean up stored webhook data from settings.
				if ( 'live' === $mode ) {
					$settings['webhook_live_secret'] = '';
					$settings['webhook_live_id']     = '';
					$settings['webhook_live_url']    = '';
				} else {
					$settings['webhook_test_secret'] = '';
					$settings['webhook_test_id']     = '';
					$settings['webhook_test_url']    = '';
				}

				$webhooks_deleted++;

			} catch ( \Exception $e ) {
				$error_message = $e->getMessage();
				// TODO: Handle proper error handling.
			}
		}

		// Update settings if any webhooks were deleted.
		if ( $webhooks_deleted > 0 ) {
			update_option( self::OPTION_NAME, $settings );
		}

		if ( $webhooks_deleted > 0 ) {
			if ( count( $modes ) === 1 ) {
				$mode_label = 'live' === $modes[0] ? __( 'live', 'sureforms' ) : __( 'test', 'sureforms' );
				$message    = sprintf(
					/* translators: %s: mode name (test/live) */
					__( 'Webhook deleted successfully for %s mode.', 'sureforms' ),
					$mode_label
				);
			} else {
				$message = sprintf(
					/* translators: %d: number of modes */
					__( 'Webhooks deleted successfully for %d mode(s).', 'sureforms' ),
					$webhooks_deleted
				);
			}
			return rest_ensure_response(
				[
					'success' => true,
					'message' => $message,
				]
			);
		}
		return rest_ensure_response(
			[
				'success' => false,
				'message' => $error_message ? $error_message : __( 'Failed to delete webhook.', 'sureforms' ),
			]
		);
	}

	/**
	 * Get Stripe account information using stored account ID
	 *
	 * @since x.x.x
	 * @return array<mixed> Array containing account information or error details
	 */
	public function get_account_info(): array {
		$settings = get_option( self::OPTION_NAME, $this->get_default_settings() );
		if ( ! is_array( $settings ) ) {
			$settings = $this->get_default_settings();
		}

		// Check if Stripe is connected.
		if ( empty( $settings['stripe_connected'] ) ) {
			return [
				'success' => false,
				'error'   => [
					'message' => __( 'Stripe is not connected.', 'sureforms' ),
					'code'    => 'stripe_not_connected',
				],
			];
		}

		// Get account ID.
		$account_id = $settings['stripe_account_id'] ?? '';
		if ( empty( $account_id ) ) {
			return [
				'success' => false,
				'error'   => [
					'message' => __( 'Stripe account ID not found.', 'sureforms' ),
					'code'    => 'missing_account_id',
				],
			];
		}

		// Call Stripe API to get account information.
		$api_response = Stripe_Helper::stripe_api_request( 'accounts', 'GET', [], (string) $account_id );

		if ( ! isset( $api_response['success'] ) || ! $api_response['success'] ) {
			return $api_response; // Return the error from API.
		}

		return $api_response;
	}

	/**
	 * Get default settings
	 *
	 * @since x.x.x
	 * @return array<mixed>
	 */
	private function get_default_settings(): array {
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
			'webhook_test_secret'         => '',
			'webhook_test_url'            => '',
			'webhook_test_id'             => '',
			'webhook_live_secret'         => '',
			'webhook_live_url'            => '',
			'webhook_live_id'             => '',
			'account_data'                => [],
		];
	}

	/**
	 * Process OAuth success response.
	 *
	 * @since x.x.x
	 * @return \WP_REST_Response
	 */
	private function process_oauth_success() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['response'] ) ) {
			return rest_ensure_response(
				[
					'success' => false,
					'error'   => [
						'message' => __( 'Missing OAuth response data.', 'sureforms' ),
						'code'    => 'missing_response',
					],
				]
			);
		}

		$response_data = sanitize_text_field( wp_unslash( $_GET['response'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$decoded       = base64_decode( $response_data, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$response      = false;
		if ( is_string( $decoded ) ) {
			$response = json_decode( $decoded, true );
		}

		if ( ! is_array( $response ) ) {
			return rest_ensure_response(
				[
					'success' => false,
					'error'   => [
						'message' => __( 'Invalid OAuth response.', 'sureforms' ),
						'code'    => 'invalid_response',
					],
				]
			);
		}

		// Extract OAuth data following checkout-plugins-stripe-woo pattern.
		$settings = get_option( self::OPTION_NAME, $this->get_default_settings() );
		$settings = ( is_array( $settings ) && ! empty( $settings ) ) ? $settings : $this->get_default_settings();

		// Store live keys.
		if ( isset( $response['live'] ) && is_array( $response['live'] ) ) {
			$settings['stripe_live_publishable_key'] = sanitize_text_field( $response['live']['stripe_publishable_key'] ?? '' );
			$settings['stripe_live_secret_key']      = sanitize_text_field( $response['live']['access_token'] ?? '' );
			$settings['stripe_account_id']           = sanitize_text_field( $response['live']['stripe_user_id'] ?? '' );
		}

		// Store test keys.
		if ( isset( $response['test'] ) && is_array( $response['test'] ) ) {
			$settings['stripe_test_publishable_key'] = sanitize_text_field( $response['test']['stripe_publishable_key'] ?? '' );
			$settings['stripe_test_secret_key']      = sanitize_text_field( $response['test']['access_token'] ?? '' );
		}

		// Mark as connected.
		$settings['stripe_connected']     = true;
		$settings['stripe_account_email'] = isset( $response['account'], $response['account']['email'] )
			? sanitize_email( $response['account']['email'] )
			: '';

		// Save settings.
		update_option( self::OPTION_NAME, $settings );

		$account_info = $this->get_account_info();

		if (
			isset( $account_info['success'], $account_info['data'] )
			&& $account_info['success']
			&& ! empty( $account_info['data'] )
			&& is_array( $account_info['data'] )
		) {
			$settings['account_data'] = $account_info['data'];
			update_option( self::OPTION_NAME, $settings );
		}

		// Clean up transients.
		delete_transient( 'srfm_stripe_connect_nonce_' . get_current_user_id() );

		// Create webhooks for both live and test mode.
		$this->setup_stripe_webhooks();

		// Redirect to SureForms payments settings.
		wp_safe_redirect( admin_url( 'admin.php?page=sureforms_form_settings&tab=payments-settings&connected=1' ) );
		exit;
	}

	/**
	 * Process OAuth error response
	 *
	 * @since x.x.x
	 * @return void
	 */
	private function process_oauth_error(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['error'] ) ) {
			$error_data = sanitize_text_field( wp_unslash( $_GET['error'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$decoded    = base64_decode( $error_data, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			$error      = is_string( $decoded ) ? json_decode( $decoded, true ) : [];
			if ( ! is_array( $error ) ) {
				$error = [];
			}
		} else {
			$error = [];
		}

		$error_message = __( 'Failed to connect to Stripe.', 'sureforms' );
		if ( isset( $error['message'] ) && is_string( $error['message'] ) ) {
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
}
