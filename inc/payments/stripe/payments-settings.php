<?php
/**
 * Payments Settings Handler
 *
 * @package sureforms
 * @since 2.0.0
 */

namespace SRFM\Inc\Payments\Stripe;

use SRFM\Inc\Payments\Payment_Helper;
use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Payments Settings Class
 *
 * @since 2.0.0
 */
class Payments_Settings {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
		add_filter( 'srfm_global_settings_data', [ $this, 'add_payments_settings' ] );
		add_action( 'admin_init', [ $this, 'intercept_stripe_callback' ] );
		add_filter( 'srfm_entry_value', [ $this, 'filter_entry_value_for_payment' ], 10, 2 );
	}

	/**
	 * Filter entry value for payment blocks to display clickable link to payment admin page.
	 *
	 * This filter checks if the field block is a payment block and converts the payment ID
	 * into a clickable link that directs to the payment details page in admin.
	 *
	 * @param mixed        $value The current field value (payment ID).
	 * @param array<mixed> $args  Arguments containing field_name, label, and field_block_name.
	 * @since 2.0.0
	 * @return mixed The modified field value (clickable link) or original value if not a payment block.
	 */
	public function filter_entry_value_for_payment( $value, $args ) {
		// Check if this is a payment block.
		if ( ! isset( $args['field_block_name'] ) || 'srfm-payment' !== $args['field_block_name'] ) {
			return $value;
		}

		// Get the payment ID from the field value.
		$payment_id = is_numeric( $value ) ? intval( $value ) : 0;

		// If payment ID is not valid, return original value.
		if ( $payment_id <= 0 ) {
			return $value;
		}

		/**
		 * Generate the payment admin URL with hash-based routing.
		 * Example: http://localhost:10008/wp-admin/admin.php?page=sureforms_payments#/payment/323
		 */
		$base_url = add_query_arg(
			[
				'page' => 'sureforms_payments',
			],
			admin_url( 'admin.php' )
		);

		// Append hash route for specific payment.
		$url = $base_url . '#/payment/' . $payment_id;

		return sprintf(
			'<a type="button" href="%s" class="outline-1 border-none cursor-pointer transition-colors duration-300 ease-in-out font-semibold focus:ring-2 focus:ring-toggle-on focus:ring-offset-2 disabled:text-text-disabled rounded-md text-sm [&>svg]:size-5 gap-1 outline-none text-link-primary bg-transparent hover:text-link-primary-hover p-0 border-0 leading-none no-underline hover:underline" target="_blank">%s</a>',
			esc_url( $url ),
			esc_html__( 'View Payment', 'sureforms' )
		);
	}

	/**
	 * Register REST routes
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			'sureforms/v1',
			'/payments/stripe-connect',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ Stripe_Helper::class, 'get_stripe_connect_url' ],
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
	}

	/**
	 * Permission callback
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function permission_check() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Add payments settings to global settings
	 *
	 * Returns complete payment settings structure including global settings and all gateway settings.
	 *
	 * @param array<mixed> $settings Existing settings.
	 * @since 2.0.0
	 * @return array<mixed>
	 */
	public function add_payments_settings( $settings ) {
		// Get all payment settings (includes global + all gateways).
		$payment_settings = Payment_Helper::get_all_payment_settings();

		$settings['payment_settings'] = $payment_settings;

		return $settings;
	}

	/**
	 * Intercept Stripe OAuth callback
	 *
	 * This function validates the OAuth callback from Stripe Connect by:
	 * 1. Verifying user has admin capabilities
	 * 2. Checking for required page/tab parameters
	 * 3. Validating the nonce using wp_verify_nonce()
	 * 4. Comparing with stored transient for additional security
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function intercept_stripe_callback() {
		// Check if user has permission to connect Stripe.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

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
		if ( ! isset( $_GET['srfm_stripe_connect_nonce'] ) ) {
			return;
		}

		// Get and sanitize the nonce from URL.
		$nonce = sanitize_text_field( wp_unslash( $_GET['srfm_stripe_connect_nonce'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Verify the nonce using WordPress's built-in verification.
		if ( ! wp_verify_nonce( $nonce, 'stripe-connect' ) ) {
			wp_die(
				esc_html__( 'Security verification failed. Invalid nonce.', 'sureforms' ),
				esc_html__( 'Stripe Connect Error', 'sureforms' ),
				[ 'response' => 403 ]
			);
		}

		// Additional verification: Compare with stored transient.
		$saved_nonce = get_transient( 'srfm_stripe_connect_nonce_' . get_current_user_id() );

		if ( $nonce !== $saved_nonce ) {
			wp_die(
				esc_html__( 'Security verification failed. Nonce mismatch.', 'sureforms' ),
				esc_html__( 'Stripe Connect Error', 'sureforms' ),
				[ 'response' => 403 ]
			);
		}

		// This is our callback, handle it.
		$this->handle_stripe_callback();
	}

	/**
	 * Handle Stripe OAuth callback
	 *
	 * @since 2.0.0
	 * @return void
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
	 * Disconnect Stripe account
	 *
	 * @since 2.0.0
	 * @return \WP_REST_Response
	 */
	public function disconnect_stripe() {
		// Delete Stripe webhook endpoints for both test and live modes.
		$this->delete_stripe_webhooks();

		$settings = Stripe_Helper::get_all_stripe_settings();
		if ( ! is_array( $settings ) ) {
			$settings = Stripe_Helper::get_all_stripe_settings();
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
		$settings['account_name']                = '';

		$updated = Stripe_Helper::update_all_stripe_settings( $settings );

		return rest_ensure_response(
			[
				'success' => true,
				'message' => __( 'Stripe account disconnected successfully.', 'sureforms' ),
				'updated' => $updated,
			]
		);
	}

	/**
	 * Handle webhook creation request (REST API handler)
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @since 2.0.0
	 * @return \WP_REST_Response
	 */
	public function handle_webhook_creation_request( $request ) {
		// Get mode parameter from request (defaults to current payment mode).
		$mode = $request->get_param( 'mode' );

		// Validate mode parameter.
		if ( ! in_array( $mode, [ 'test', 'live' ], true ) ) {
			$settings = Stripe_Helper::get_all_stripe_settings();
			$mode     = is_array( $settings ) && isset( $settings['payment_mode'] ) ? $settings['payment_mode'] : 'test';
		}

		$mode = ! empty( $mode ) && is_string( $mode ) ? $mode : 'test';

		// Create webhook for the specified mode only.
		$result = $this->create_webhook_for_mode( $mode );

		return rest_ensure_response( $result );
	}

	/**
	 * Create Stripe webhook for a specific mode (test or live)
	 *
	 * @param string $mode The payment mode ('test' or 'live').
	 * @since 2.0.0
	 * @return array<mixed> Array containing webhook creation results and details
	 * @throws \Exception When the Stripe API request fails for any mode.
	 */
	public function create_webhook_for_mode( $mode ) {
		$settings = Stripe_Helper::get_all_stripe_settings();
		if ( ! is_array( $settings ) ) {
			$settings = Stripe_Helper::get_all_stripe_settings();
		}

		if ( empty( $settings['stripe_connected'] ) ) {
			return [
				'success' => false,
				'message' => __( 'Stripe is not connected.', 'sureforms' ),
			];
		}

		// Validate mode.
		if ( ! in_array( $mode, [ 'test', 'live' ], true ) ) {
			return [
				'success' => false,
				'message' => __( 'Invalid payment mode.', 'sureforms' ),
			];
		}

		// Get secret key for the mode.
		$secret_key = 'live' === $mode
			? ( $settings['stripe_live_secret_key'] ?? '' )
			: ( $settings['stripe_test_secret_key'] ?? '' );

		if ( empty( $secret_key ) ) {
			return [
				'success' => false,
				'message' => sprintf(
					/* translators: %s: payment mode (test/live) */
					__( 'Stripe %s secret key is missing.', 'sureforms' ),
					$mode
				),
			];
		}

		$webhook_url = Stripe_Helper::get_webhook_url( $mode );

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
					'customer.subscription.created',
					'customer.subscription.updated',
					'customer.subscription.deleted',
				],
			];

			$api_response = Stripe_Helper::stripe_api_request( 'webhook_endpoints', 'POST', $webhook_data, '', [ 'mode' => $mode ] );

			if ( ! isset( $api_response['success'] ) || ! $api_response['success'] ) {
				$error_details = $api_response['error'] ?? [];
				$error_message = $error_details['message'] ?? __( 'Failed to create webhook.', 'sureforms' );
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

			Stripe_Helper::update_all_stripe_settings( $settings );

			// Prepare response with webhook details.
			return [
				'success'         => true,
				'message'         => sprintf(
					/* translators: %s: payment mode (test/live) */
					__( 'Webhook created successfully for %s mode.', 'sureforms' ),
					$mode
				),
				'webhook_details' => [
					$mode => [
						'webhook_secret' => $webhook['secret'],
						'webhook_id'     => $webhook['id'],
						'webhook_url'    => $webhook_url,
					],
				],
			];

		} catch ( \Exception $e ) {
			return [
				'success' => false,
				'message' => $e->getMessage(),
			];
		}
	}

	/**
	 * Setup Stripe webhooks for both test and live modes
	 *
	 * @since 2.0.0
	 * @return array<mixed> Array containing webhook creation results and details
	 * @throws \Exception When the Stripe API request fails for any mode.
	 */
	public function setup_stripe_webhooks() {
		$settings = Stripe_Helper::get_all_stripe_settings();
		if ( ! is_array( $settings ) ) {
			$settings = Stripe_Helper::get_all_stripe_settings();
		}

		if ( empty( $settings['stripe_connected'] ) ) {
			return [
				'success' => false,
				'message' => __( 'Stripe is not connected.', 'sureforms' ),
			];
		}

		$webhooks_created = 0;
		$error_message    = '';
		$modes            = [ 'test', 'live' ];

		foreach ( $modes as $mode ) {
			$secret_key = 'live' === $mode
				? ( $settings['stripe_live_secret_key'] ?? '' )
				: ( $settings['stripe_test_secret_key'] ?? '' );

			if ( empty( $secret_key ) ) {
				continue;
			}

			$webhook_url = Stripe_Helper::get_webhook_url( $mode );

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
						'customer.subscription.created',
						'customer.subscription.updated',
						'customer.subscription.deleted',
					],
				];

				$api_response = Stripe_Helper::stripe_api_request( 'webhook_endpoints', 'POST', $webhook_data, '', [ 'mode' => $mode ] );

				if ( ! isset( $api_response['success'] ) || ! $api_response['success'] ) {
					$error_details = $api_response['error'] ?? [];
					$error_message = $error_details['message'] ?? '';
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
			}
		}

		// Update settings if any webhooks were created.
		if ( $webhooks_created > 0 ) {
			Stripe_Helper::update_all_stripe_settings( $settings );
		}

		// Prepare response with webhook details.
		$response_data = [
			'success' => $webhooks_created > 0,
		];

		if ( $webhooks_created > 0 ) {
			$response_data['webhook_details'] = [
				'test' => [
					'webhook_secret' => $settings['webhook_test_secret'] ?? '',
					'webhook_id'     => $settings['webhook_test_id'] ?? '',
					'webhook_url'    => Stripe_Helper::get_webhook_url( 'test' ),
				],
				'live' => [
					'webhook_secret' => $settings['webhook_live_secret'] ?? '',
					'webhook_id'     => $settings['webhook_live_id'] ?? '',
					'webhook_url'    => Stripe_Helper::get_webhook_url( 'live' ),
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
	 * Delete Stripe webhooks for both test and live modes
	 *
	 * @since 2.0.0
	 * @return array<mixed> Array containing deletion results
	 */
	public function delete_stripe_webhooks() {
		$settings = Stripe_Helper::get_all_stripe_settings();
		if ( ! is_array( $settings ) ) {
			$settings = Stripe_Helper::get_all_stripe_settings();
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

			if ( empty( $secret_key ) || empty( $webhook_id ) || ! is_string( $webhook_id ) ) {
				continue;
			}

			try {
				$api_response = Stripe_Helper::stripe_api_request( 'webhook_endpoints', 'DELETE', [], (string) $webhook_id, [ 'mode' => $mode ] );

				if ( ! isset( $api_response['success'] ) || ! $api_response['success'] ) {
					$error_details     = $api_response['error'] ?? [];
					$error_message     = $error_details['message'] ?? '';
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
				$error_message     = $e->getMessage();
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
			Stripe_Helper::update_all_stripe_settings( $settings );
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
	 * @since 2.0.0
	 * @return \WP_REST_Response
	 */
	public function delete_payment_webhooks( $request_or_modes = null ) {
		$settings = Stripe_Helper::get_all_stripe_settings();
		if ( ! is_array( $settings ) ) {
			$settings = Stripe_Helper::get_all_stripe_settings();
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

			if ( empty( $secret_key ) || empty( $webhook_id ) || ! is_string( $webhook_id ) ) {
				continue;
			}

			try {
				$api_response = Stripe_Helper::stripe_api_request( 'webhook_endpoints', 'DELETE', [], (string) $webhook_id, [ 'mode' => $mode ] );

				if ( ! isset( $api_response['success'] ) || ! $api_response['success'] ) {
					$error_details = $api_response['error'] ?? [];
					$error_message = $error_details['message'] ?? '';

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
			}
		}

		// Update settings if any webhooks were deleted.
		if ( $webhooks_deleted > 0 ) {
			Stripe_Helper::update_all_stripe_settings( $settings );
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
	 * @since 2.0.0
	 * @return string containing account name or empty string if not found
	 */
	public function get_account_name() {
		$settings = Stripe_Helper::get_all_stripe_settings();
		if ( ! is_array( $settings ) ) {
			$settings = Stripe_Helper::get_all_stripe_settings();
		}

		// Check if Stripe is connected.
		if ( empty( $settings['stripe_connected'] ) ) {
			return '';
		}

		// Get account ID.
		$account_id = $settings['stripe_account_id'] ?? '';
		if ( empty( $account_id ) || ! is_string( $account_id ) ) {
			return '';
		}

		// Call Stripe API to get account information.
		$api_response = Stripe_Helper::stripe_api_request( 'accounts', 'GET', [], (string) $account_id );

		$get_data      = isset( $api_response['data'] ) && is_array( $api_response['data'] ) ? $api_response['data'] : [];
		$get_settings  = isset( $get_data['settings'] ) && is_array( $get_data['settings'] ) ? $get_data['settings'] : [];
		$get_dashboard = isset( $get_settings['dashboard'] ) && is_array( $get_settings['dashboard'] ) ? $get_settings['dashboard'] : [];
		return isset( $get_dashboard['display_name'] ) && is_string( $get_dashboard['display_name'] ) ? $get_dashboard['display_name'] : '';
	}

	/**
	 * Process OAuth success response.
	 *
	 * This function processes the successful OAuth callback from Stripe and saves
	 * the API keys. Security checks have already been performed in intercept_stripe_callback().
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function process_oauth_success() {
		$response_data = isset( $_GET['response'] ) ? sanitize_text_field( wp_unslash( $_GET['response'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$decoded       = base64_decode( $response_data, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$response      = false;
		if ( is_string( $decoded ) ) {
			$response = json_decode( $decoded, true );
		}

		if ( ! is_array( $response ) ) {
			wp_die(
				esc_html__( 'Invalid OAuth response format.', 'sureforms' ),
				esc_html__( 'Stripe Connect Error', 'sureforms' ),
				[ 'response' => 400 ]
			);
		}

		// Extract OAuth data following checkout-plugins-stripe-woo pattern.
		$settings = Stripe_Helper::get_all_stripe_settings();
		$settings = is_array( $settings ) && ! empty( $settings ) ? $settings : Stripe_Helper::get_all_stripe_settings();

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
		Stripe_Helper::update_all_stripe_settings( $settings );

		$account_name = $this->get_account_name();

		if ( ! empty( $account_name ) && is_string( $account_name ) ) {
			$settings['account_name'] = $account_name;
			Stripe_Helper::update_all_stripe_settings( $settings );
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
	 * This function handles errors from the Stripe OAuth callback.
	 * Security checks have already been performed in intercept_stripe_callback().
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function process_oauth_error() {
		// Additional security check: Verify user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have permission to connect Stripe.', 'sureforms' ),
				esc_html__( 'Permission Denied', 'sureforms' ),
				[ 'response' => 403 ]
			);
		}

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
