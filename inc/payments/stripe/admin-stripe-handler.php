<?php
/**
 * Admin Stripe Handler for SureForms
 *
 * Handles admin-related Stripe operations including refunds for payments and subscriptions.
 *
 * @package SureForms
 * @since 2.0.0
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
 * Admin Stripe Handler class.
 *
 * Manages admin operations for Stripe payments including refunds, cancellations,
 * and payment management for both one-time and subscription payments.
 *
 * @since 2.0.0
 */
class Admin_Stripe_Handler {
	use Get_Instance;

	/**
	 * Payment mode.
	 *
	 * @var string
	 * @since 2.0.0
	 */
	private string $payment_mode = 'test';

	/**
	 * Constructor
	 */
	public function __construct() {
		// AJAX handlers for admin refund operations.
		add_action( 'wp_ajax_srfm_stripe_cancel_subscription', [ $this, 'ajax_cancel_subscription' ] );
		add_action( 'wp_ajax_srfm_stripe_pause_subscription', [ $this, 'ajax_pause_subscription' ] );
		add_action( 'wp_ajax_srfm_stripe_refund_payment', [ $this, 'refund_payment' ] );
		// Admin notices.
		add_action( 'admin_notices', [ $this, 'webhook_configuration_notice' ] );
	}

	/**
	 * AJAX handler for subscription cancellation (following WPForms pattern)
	 *
	 * @since 2.0.0
	 */
	/**
	 * AJAX handler for subscription cancellation (following WPForms pattern)
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function ajax_cancel_subscription() {
		// Security checks.
		if ( ! isset( $_POST['payment_id'] ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Missing payment ID.', 'sureforms' ) ] );
		}

		// Verify nonce.
		if (
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ),
				'srfm_payment_admin_nonce'
			)
		) {
			wp_send_json_error( __( 'Invalid nonce.', 'sureforms' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'You are not allowed to perform this action.', 'sureforms' ) ] );
		}

		$payment_id = absint( $_POST['payment_id'] );

		// Get payment record.
		$payment = Payments::get( $payment_id );

		$this->payment_mode = $payment['payment_mode'] ?? 'test';
		if ( ! $payment ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Payment not found in the database.', 'sureforms' ) ] );
		}

		// Validate it's a subscription payment.
		if ( empty( $payment['type'] ) || 'subscription' !== $payment['type'] ) {
			wp_send_json_error( [ 'message' => esc_html__( 'This is not a subscription payment.', 'sureforms' ) ] );
		}

		if ( empty( $payment['subscription_id'] ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Subscription ID not found.', 'sureforms' ) ] );
		}

		// Cancel the subscription.
		$cancel_result = $this->cancel_subscription( $payment['subscription_id'] );
		if ( ! $cancel_result ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Subscription cancellation failed.', 'sureforms' ) ] );
		}

		// Get current logs and add cancel log entry.
		$current_logs = Helper::get_array_value( $payment['log'] );

		// Build log messages array.
		$log_messages = [
			sprintf(
				/* translators: %s: Stripe subscription ID */
				__( 'Subscription ID: %s', 'sureforms' ),
				$payment['subscription_id']
			),
			sprintf(
				/* translators: %s: payment gateway name */
				__( 'Payment Gateway: %s', 'sureforms' ),
				'Stripe'
			),
			sprintf(
				/* translators: %s: subscription status */
				__( 'Subscription Status: %s', 'sureforms' ),
				__( 'Canceled', 'sureforms' )
			),
			sprintf(
				/* translators: %s: user display name */
				__( 'Canceled by: %s', 'sureforms' ),
				wp_get_current_user()->display_name
			),
			__( 'Note: The subscription has been permanently canceled. The customer will no longer be charged and will lose access to subscription benefits.', 'sureforms' ),
		];

		// Create new log entry.
		$new_log        = [
			'title'      => __( 'Subscription Canceled', 'sureforms' ),
			'created_at' => current_time( 'mysql' ),
			'messages'   => $log_messages,
		];
		$current_logs[] = $new_log;

		// Update database status to canceled (following WPForms pattern).
		$updated = Payments::update(
			$payment_id,
			[
				'subscription_status' => 'canceled',
				'status'              => 'canceled',
				'log'                 => $current_logs,
			]
		);
		if ( ! $updated ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Failed to update subscription status in database.', 'sureforms' ) ] );
		}

		wp_send_json_success( [ 'message' => esc_html__( 'Subscription canceled successfully.', 'sureforms' ) ] );
	}

	/**
	 * Process payment refund
	 *
	 * @since 2.0.0
	 * @return void
	 * @throws \Exception If unable to process refund.
	 */
	public function refund_payment() {
		// Verify nonce.
		if (
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ),
				'srfm_payment_admin_nonce'
			)
		) {
			wp_send_json_error( __( 'Invalid nonce.', 'sureforms' ) );
		}

		// Check if user has permission to refund payments.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', 'sureforms' ) );
		}

		$payment_id     = intval( $_POST['payment_id'] ?? 0 );
		$transaction_id = sanitize_text_field( wp_unslash( $_POST['transaction_id'] ?? '' ) );
		$refund_amount  = isset( $_POST['refund_amount'] ) ? absint( $_POST['refund_amount'] ) : 0;
		$refund_notes   = isset( $_POST['refund_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['refund_notes'] ) ) : '';

		if ( $refund_amount <= 0 ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid refund amount.', 'sureforms' ) ] );
		}

		if ( empty( $payment_id ) || empty( $transaction_id ) || $refund_amount <= 0 ) {
			wp_send_json_error( __( 'Invalid payment data.', 'sureforms' ) );
		}

		try {
			// Get payment from database.
			$payment = Payments::get( $payment_id );
			if ( ! $payment ) {
				wp_send_json_error( __( 'Payment not found.', 'sureforms' ) );
			}

			$this->payment_mode = $payment['payment_mode'] ?? 'test';

			// Detect subscription payments and route to specialized handler (following WPForms pattern).
			if ( ! empty( $payment['type'] ) && ! empty( $payment['subscription_id'] ) ) {
				$this->refund_subscription_payment( $payment, $refund_amount, $refund_notes );
			}

			// Verify payment status (for one-time payments).
			if ( 'succeeded' !== $payment['status'] && 'partially_refunded' !== $payment['status'] ) {
				wp_send_json_error( __( 'Only succeeded or partially refunded payments can be refunded.', 'sureforms' ) );
			}

			// Verify transaction ID matches.
			if ( $transaction_id !== $payment['transaction_id'] ) {
				wp_send_json_error( __( 'Transaction ID mismatch.', 'sureforms' ) );
			}

			// Create refund using Stripe API directly.
			$stripe_refund_data = [
				'amount'   => $refund_amount,
				'metadata' => [
					'source'      => 'SureForms',
					'payment_id'  => $payment_id,
					'refunded_at' => time(),
					'refunded_by' => get_current_user_id(),
				],
			];

			// Add refund notes/reason to Stripe API request if provided.
			if ( ! empty( $refund_notes ) ) {
				// Add to metadata for detailed notes.
				$stripe_refund_data['metadata']['refund_notes'] = $refund_notes;
				// Set reason as requested_by_customer (Stripe accepts: duplicate, fraudulent, requested_by_customer).
				$stripe_refund_data['reason'] = 'requested_by_customer';
			}

			// Determine if we're refunding by charge ID or payment intent ID.
			if ( is_string( $transaction_id ) && strpos( $transaction_id, 'ch_' ) === 0 ) {
				$stripe_refund_data['charge'] = $transaction_id;
			} elseif ( is_string( $transaction_id ) && strpos( $transaction_id, 'pi_' ) === 0 ) {
				$stripe_refund_data['payment_intent'] = $transaction_id;
			} else {
				throw new \Exception( __( 'Invalid transaction ID format for refund.', 'sureforms' ) );
			}

			$refund_response = Stripe_Helper::stripe_api_request( 'refunds', 'POST', $stripe_refund_data, '', [ 'mode' => $this->payment_mode ] );

			if ( ! $refund_response['success'] ) {
				$error_message = $refund_response['error']['message'] ?? __( 'Failed to process refund through Stripe API.', 'sureforms' );
				throw new \Exception( $error_message );
			}

			$refund = $refund_response['data'];

			// Store refund data and update payment status/log.
			$refund_stored = $this->update_refund_data( $payment_id, $refund, $refund_amount, $payment['currency'], null, $refund_notes );
			if ( ! $refund_stored ) {
				throw new \Exception( __( 'Failed to update payment record after refund.', 'sureforms' ) );
			}

			wp_send_json_success(
				[
					'message'   => __( 'Payment refunded successfully.', 'sureforms' ),
					'refund_id' => is_array( $refund ) && isset( $refund['id'] ) ? $refund['id'] : '',
					'status'    => is_array( $refund ) && isset( $refund['status'] ) ? $refund['status'] : 'processed',
				]
			);

		} catch ( \Exception $e ) {
			wp_send_json_error( __( 'Failed to process refund. Please try again.', 'sureforms' ) );
		}
	}

	/**
	 * Cancel subscription (following WPForms pattern)
	 *
	 * @param string $subscription_id Subscription ID.
	 * @since 2.0.0
	 * @return bool Success status.
	 */
	public function cancel_subscription( $subscription_id ) {
		try {
			// Retrieve the subscription using direct Stripe API.
			$subscription_response = Stripe_Helper::stripe_api_request( 'subscriptions', 'GET', [], $subscription_id, [ 'mode' => $this->payment_mode ] );

			if ( ! $subscription_response['success'] ) {
				return false;
			}

			$subscription = $subscription_response['data'];

			// If subscription is valid, check the status. If status is not 'active', return true early.
			if ( isset( $subscription['status'] ) && ! in_array( $subscription['status'], [ 'active', 'trialing' ], true ) ) {
				return true;
			}

			$updated_metadata = array_merge(
				isset( $subscription['metadata'] ) && is_array( $subscription['metadata'] ) ? $subscription['metadata'] : [],
				[
					'canceled_by' => 'sureforms_dashboard',
				]
			);

			Stripe_Helper::stripe_api_request(
				'subscriptions',
				'POST',
				[
					'metadata' => $updated_metadata,
				],
				$subscription_id,
				[ 'mode' => $this->payment_mode ]
			);

			// Cancel the subscription.
			$cancelled_subscription_response = Stripe_Helper::stripe_api_request(
				'subscriptions',
				'DELETE',
				[],
				$subscription_id,
				[ 'mode' => $this->payment_mode ]
			);

			if ( ! $cancelled_subscription_response['success'] ) {
				return false;
			}

			return true;

		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * AJAX handler for subscription pause
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function ajax_pause_subscription() {
		// Security checks.
		if ( ! isset( $_POST['payment_id'] ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Missing payment ID.', 'sureforms' ) ] );
		}

		// Verify nonce.
		if (
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ),
				'srfm_payment_admin_nonce'
			)
		) {
			wp_send_json_error( __( 'Invalid nonce.', 'sureforms' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'You are not allowed to perform this action.', 'sureforms' ) ] );
		}

		$payment_id = absint( $_POST['payment_id'] );

		// Get payment record.
		$payment = Payments::get( $payment_id );
		if ( ! $payment ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Payment not found in the database.', 'sureforms' ) ] );
		}

		$this->payment_mode = $payment['payment_mode'] ?? 'test';

		// Validate it's a subscription payment.
		if ( empty( $payment['type'] ) || 'subscription' !== $payment['type'] ) {
			wp_send_json_error( [ 'message' => esc_html__( 'This is not a subscription payment.', 'sureforms' ) ] );
		}

		if ( empty( $payment['subscription_id'] ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Subscription ID not found.', 'sureforms' ) ] );
		}

		// Pause the subscription.
		$pause_result = $this->pause_subscription( $payment['subscription_id'] );
		if ( ! $pause_result ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Subscription pause failed.', 'sureforms' ) ] );
		}

		// Get current logs and add pause log entry.
		$current_logs = Helper::get_array_value( $payment['log'] );

		// Build log messages array.
		$log_messages = [
			sprintf(
				/* translators: %s: Stripe subscription ID */
				__( 'Subscription ID: %s', 'sureforms' ),
				$payment['subscription_id']
			),
			sprintf(
				/* translators: %s: payment gateway name */
				__( 'Payment Gateway: %s', 'sureforms' ),
				'Stripe'
			),
			sprintf(
				/* translators: %s: subscription status */
				__( 'Subscription Status: %s', 'sureforms' ),
				__( 'Paused', 'sureforms' )
			),
			sprintf(
				/* translators: %s: user display name */
				__( 'Paused by: %s', 'sureforms' ),
				wp_get_current_user()->display_name
			),
			__( 'Note: The subscription billing has been paused. No charges will occur until the subscription is resumed.', 'sureforms' ),
		];

		// Create new log entry.
		$new_log        = [
			'title'      => __( 'Subscription Paused', 'sureforms' ),
			'created_at' => current_time( 'mysql' ),
			'messages'   => $log_messages,
		];
		$current_logs[] = $new_log;

		// Update database status to paused with log.
		$updated = Payments::update(
			$payment_id,
			[
				'subscription_status' => 'paused',
				'log'                 => $current_logs,
			]
		);
		if ( ! $updated ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Failed to update subscription status in database.', 'sureforms' ) ] );
		}

		wp_send_json_success( [ 'message' => esc_html__( 'Subscription paused successfully.', 'sureforms' ) ] );
	}

	/**
	 * Pause subscription
	 *
	 * @param string $subscription_id Subscription ID.
	 * @since 2.0.0
	 * @return bool Success status.
	 */
	public function pause_subscription( $subscription_id ) {
		try {
			// Retrieve subscription using direct Stripe API.
			$subscription_response = Stripe_Helper::stripe_api_request( 'subscriptions', 'GET', [], $subscription_id, [ 'mode' => $this->payment_mode ] );

			if ( ! $subscription_response['success'] ) {
				return false;
			}

			$subscription = $subscription_response['data'];

			$updated_metadata = array_merge(
				isset( $subscription['metadata'] ) && is_array( $subscription['metadata'] ) ? $subscription['metadata'] : [],
				[
					'paused_by' => 'sureforms_dashboard',
				]
			);

			// Pause the subscription using pause_collection.
			$paused_subscription_response = Stripe_Helper::stripe_api_request(
				'subscriptions',
				'POST',
				[
					'pause_collection' => [
						'behavior' => 'void',
					],
					'metadata'         => $updated_metadata,
				],
				$subscription_id,
				[ 'mode' => $this->payment_mode ]
			);

			if ( ! $paused_subscription_response['success'] ) {
				return false;
			}

			return true;

		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Update refund data in payment_data column and log
	 *
	 * @param int                      $payment_id Payment record ID.
	 * @param array<string,mixed>      $refund_response Refund response from Stripe.
	 * @param int                      $refund_amount Refund amount in cents.
	 * @param string                   $currency Currency code.
	 * @param array<string,mixed>|null $payment Payment record data.
	 * @param string                   $refund_notes Refund notes.
	 * @since 2.0.0
	 * @return bool True if successful, false otherwise.
	 */
	public function update_refund_data(
		int $payment_id,
		array $refund_response,
		int $refund_amount,
		string $currency,
		?array $payment = null,
		string $refund_notes = ''
	) {
		if ( empty( $payment_id ) || empty( $refund_response ) ) {
			return false;
		}

		// Get payment record if not provided.
		$payment = Payments::get( $payment_id );
		if ( ! $payment ) {
			return false;
		}

		$check_if_refund_already_exists = $this->check_if_refund_already_exists( $payment, $refund_response );
		if ( $check_if_refund_already_exists ) {
			return true;
		}

		// Prepare refund data for payment_data column.
		$refund_data = [
			'refund_id'      => is_string( $refund_response['id'] ) ? sanitize_text_field( $refund_response['id'] ) : '',
			'amount'         => absint( $refund_amount ),
			'currency'       => sanitize_text_field( strtoupper( $currency ) ),
			'status'         => is_string( $refund_response['status'] ) ? sanitize_text_field( $refund_response['status'] ) : 'processed',
			'created'        => time(),
			'reason'         => is_string( $refund_response['reason'] ) ? sanitize_text_field( $refund_response['reason'] ) : 'requested_by_customer',
			'description'    => is_string( $refund_response['description'] ) ? sanitize_text_field( $refund_response['description'] ) : '',
			'receipt_number' => is_string( $refund_response['receipt_number'] ) ? sanitize_text_field( $refund_response['receipt_number'] ) : '',
			'refunded_by'    => is_string( wp_get_current_user()->display_name ) ? sanitize_text_field( wp_get_current_user()->display_name ) : 'System',
			'refunded_at'    => gmdate( 'Y-m-d H:i:s' ),
		];

		// Validate refund amount to prevent over-refunding.
		$original_amount    = floatval( $payment['total_amount'] );
		$existing_refunds   = floatval( $payment['refunded_amount'] ); // Use column directly.
		$new_refund_amount  = Stripe_Helper::amount_from_stripe_format( $refund_amount, $currency );
		$total_after_refund = $existing_refunds + $new_refund_amount;

		if ( $total_after_refund > $original_amount ) {
			return false;
		}

		// Add refund data to payment_data column (for audit trail).
		Payments::add_refund_to_payment_data( $payment_id, $refund_data );

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
		$current_logs = Helper::get_array_value( $payment['log'] );
		$refund_type  = $total_after_refund >= $original_amount ? __( 'Full', 'sureforms' ) : __( 'Partial', 'sureforms' );

		// Build log messages array.
		$log_messages = [
			sprintf(
				/* translators: %s: refund ID */
				__( 'Refund ID: %s', 'sureforms' ),
				is_string( $refund_response['id'] ) ? $refund_response['id'] : 'N/A'
			),
			sprintf(
				/* translators: %s: payment gateway name (e.g., Stripe) */
				__( 'Payment Gateway: %s', 'sureforms' ),
				'Stripe'
			),
			sprintf(
				/* translators: 1: refund amount, 2: currency */
				__( 'Refund Amount: %1$s %2$s', 'sureforms' ),
				number_format( Stripe_Helper::amount_from_stripe_format( $refund_amount, $currency ), 2 ),
				strtoupper( $currency )
			),
			sprintf(
				/* translators: 1: total refunded, 2: currency, 3: original total, 4: currency */
				__( 'Total Refunded: %1$s %2$s of %3$s %4$s', 'sureforms' ),
				number_format( $total_after_refund, 2 ),
				strtoupper( $currency ),
				number_format( $original_amount, 2 ),
				strtoupper( $currency )
			),
			sprintf(
				/* translators: %s: status (e.g., succeeded, processed) */
				__( 'Refund Status: %s', 'sureforms' ),
				is_string( $refund_response['status'] ) ? $refund_response['status'] : 'processed'
			),
			sprintf(
				/* translators: %s: payment status (e.g., succeeded, refunded, partially_refunded) */
				__( 'Payment Status: %s', 'sureforms' ),
				ucfirst( str_replace( '_', ' ', $payment_status ) )
			),
			sprintf(
				/* translators: %s: user display name */
				__( 'Refunded by: %s', 'sureforms' ),
				wp_get_current_user()->display_name
			),
		];

		// Add refund notes to log if provided.
		if ( ! empty( $refund_notes ) ) {
			$log_messages[] = sprintf(
				/* translators: %s: refund notes */
				__( 'Refund Notes: %s', 'sureforms' ),
				$refund_notes
			);
		}

		/* translators: %s: refund type (Full or Partial) */
		$new_log        = [
			'title'      => sprintf(
				/* translators: %s: refund type (Full or Partial) */
				__( '%s Payment Refund', 'sureforms' ),
				$refund_type
			),
			'created_at' => current_time( 'mysql' ),
			'messages'   => $log_messages,
		];
		$current_logs[] = $new_log;

		$update_data = [
			'status' => $payment_status,
			'log'    => $current_logs,
		];

		// Update payment record with status and log.
		$payment_update_result = Payments::update( $payment_id, $update_data );

		if ( false === $refund_amount_result ) {
			return false;
		}

		if ( false === $payment_update_result ) {
			return false;
		}

		return true;
	}

	/**
	 * Display admin notice for webhook configuration issues.
	 *
	 * Shows a warning notice when webhooks are not properly configured.
	 * The notice will automatically disappear when a new Stripe request comes in.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function webhook_configuration_notice() {
		// Only show on admin pages.
		if ( ! is_admin() ) {
			return;
		}

		// Only show to users with manage_options capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Check if Stripe is connected.
		if ( ! Stripe_Helper::is_stripe_connected() ) {
			return;
		}

		// Check if webhooks are configured.
		if ( Stripe_Helper::is_webhook_configured() ) {
			return;
		}

		// Display the notice.
		?>
		<div class="notice notice-error is-dismissible">
			<p>
				<?php
				printf(
					/* translators: %1$s: Payment settings link */
					esc_html__(
						'Webhooks keep SureForms in sync with Stripe by automatically updating payment and subscription data. Please %1$s Webhook.',
						'sureforms'
					),
					sprintf(
						'<a href="%s">%s</a>',
						esc_url( Stripe_Helper::get_stripe_settings_url() ),
						esc_html__( 'configure', 'sureforms' )
					)
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Refund subscription payment with enhanced validation and error handling
	 *
	 * IMPORTANT: This method refunds the INITIAL/FIRST charge of a subscription only.
	 * The transaction_id field contains the charge ID from the first subscription payment.
	 * Subsequent renewal charges are NOT refunded by this method and should be refunded
	 * individually through their own payment records.
	 *
	 * @param array<string,mixed> $payment Payment record.
	 * @param int                 $refund_amount Refund amount in cents.
	 * @param string              $refund_notes Refund notes.
	 * @since 2.0.0
	 * @return void
	 * @throws \Exception If unable to determine the appropriate refund method.
	 */
	private function refund_subscription_payment( $payment, $refund_amount, $refund_notes = '' ) {
		try {
			// Step 1: Validate input parameters.
			if ( empty( $payment ) || ! is_array( $payment ) || $refund_amount <= 0 ) {
				wp_send_json_error( __( 'Invalid refund parameters provided.', 'sureforms' ) );
			}

			$payment_id     = isset( $payment['id'] ) && is_numeric( $payment['id'] ) ? intval( $payment['id'] ) : 0;
			$transaction_id = isset( $payment['transaction_id'] ) && is_string( $payment['transaction_id'] ) ? $payment['transaction_id'] : '';
			$currency       = is_string( $payment['currency'] ) ? $payment['currency'] : 'USD';

			// Step 2: Verify this is a subscription-related payment.
			$is_subscription_payment = $this->is_subscription_related_payment( $payment );
			if ( ! $is_subscription_payment ) {
				wp_send_json_error( __( 'This payment is not related to a subscription.', 'sureforms' ) );
			}

			// Step 3: Verify subscription payment status.
			// Note: 'active' status is used for subscription records, while 'succeeded' is used for one-time payments.
			$refundable_statuses = [ 'active', 'succeeded', 'partially_refunded' ];
			if ( empty( $payment['status'] ) || ! in_array( $payment['status'], $refundable_statuses, true ) ) {
				wp_send_json_error( __( 'Only active, succeeded, or partially refunded subscription payments can be refunded.', 'sureforms' ) );
			}

			// Step 4: Validate refund amount limits.
			$validation_result = $this->validate_subscription_refund_amount( $payment, $refund_amount );
			if ( ! $validation_result['valid'] ) {
				wp_send_json_error( $validation_result['message'] );
			}

			// Step 5: Validate Stripe connection.
			if ( ! Stripe_Helper::is_stripe_connected() ) {
				throw new \Exception( __( 'Stripe is not connected.', 'sureforms' ) );
			}

			// Step 6: Create refund using appropriate method based on transaction ID type.
			$refund = $this->create_subscription_refund( $payment, $transaction_id, $refund_amount, $refund_notes );

			if ( ! $refund || empty( $refund['id'] ) ) {
				throw new \Exception( __( 'Stripe refund creation failed. Please check your Stripe dashboard for more details.', 'sureforms' ) );
			}

			// Step 7: Update database with refund information.
			$refund_stored = $this->update_subscription_refund_data( $payment_id, $refund, $refund_amount, $currency, $refund_notes );

			if ( ! $refund_stored ) {
				wp_send_json_error( __( 'Refund was processed by Stripe but failed to update local records. Please check your payment records manually.', 'sureforms' ) );
			}

			// Step 8: Success response.
			wp_send_json_success(
				[
					'message'       => __( 'Subscription payment refunded successfully.', 'sureforms' ),
					'refund_id'     => isset( $refund['id'] ) && is_string( $refund['id'] ) ? $refund['id'] : '',
					'status'        => isset( $refund['status'] ) && is_string( $refund['status'] ) ? $refund['status'] : '',
					'type'          => 'subscription_refund',
					'charge_id'     => isset( $refund['charge'] ) && is_string( $refund['charge'] ) ? $refund['charge'] : '',
					'refund_amount' => number_format( $refund_amount / 100, 2 ),
					'currency'      => strtoupper( $currency ),
				]
			);

		} catch ( \Exception $e ) {
			// Provide more specific error messages based on error type.
			$error_message = $this->get_user_friendly_refund_error( $e->getMessage() );
			wp_send_json_error( $error_message );
		}
	}

	/**
	 * Check if payment is subscription-related
	 *
	 * @param array<string,mixed> $payment Payment record.
	 * @since 2.0.0
	 * @return bool True if payment is subscription-related, false otherwise.
	 */
	private function is_subscription_related_payment( $payment ) {
		// Check if it's a main subscription record.
		if ( ! empty( $payment['type'] ) && 'renewal' === $payment['type'] ) {
			return true;
		}

		// Check if it's a subscription billing cycle payment (has subscription_id).
		if ( ! empty( $payment['subscription_id'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Validate subscription refund amount
	 *
	 * @param array<string,mixed> $payment Payment record.
	 * @param int                 $refund_amount Refund amount in cents.
	 * @since 2.0.0
	 * @return array{valid: bool, message: string} Validation result with 'valid' boolean and 'message' string.
	 */
	private function validate_subscription_refund_amount( $payment, $refund_amount ) {
		$currency = isset( $payment['currency'] ) && is_string( $payment['currency'] ) ? $payment['currency'] : 'USD';

		$total_amount = isset( $payment['total_amount'] ) && is_string( $payment['total_amount'] ) ? floatval( $payment['total_amount'] ) : 0;
		$total_amount = Stripe_Helper::amount_to_stripe_format( $total_amount, $currency );

		$refunded_amount = isset( $payment['refunded_amount'] ) && is_string( $payment['refunded_amount'] ) ? floatval( $payment['refunded_amount'] ) : 0;
		$refunded_amount = Stripe_Helper::amount_to_stripe_format( $refunded_amount, $currency );

		$available_for_refund = $total_amount - $refunded_amount;

		if ( $refund_amount > $available_for_refund ) {
			return [
				'valid'   => false,
				'message' => sprintf(
					/* translators: 1: Maximum refundable amount (numeric), 2: Currency code (e.g. USD) */
					__( 'Refund amount exceeds available amount. Maximum refundable: %1$s %2$s', 'sureforms' ),
					number_format( $available_for_refund / 100, 2 ),
					isset( $payment['currency'] ) && is_string( $payment['currency'] ) ? strtoupper( $payment['currency'] ) : 'USD'
				),
			];
		}

		if ( $refund_amount <= 0 ) {
			return [
				'valid'   => false,
				'message' => __( 'Refund amount must be greater than zero.', 'sureforms' ),
			];
		}

		// Stripe minimum refund amount (usually $0.50 for most currencies).
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
	 * For subscriptions, the transaction_id field contains the charge ID from the FIRST/INITIAL payment.
	 * This ensures refunds are processed against the initial charge only, not any subsequent renewal charges.
	 * Subsequent renewal charges should be refunded individually through their own payment records.
	 *
	 * @param array<string,mixed> $payment Payment record.
	 * @param string              $transaction_id Transaction ID (charge ID from first payment for subscriptions).
	 * @param int                 $refund_amount Refund amount in cents.
	 * @param string              $refund_notes Refund notes.
	 * @since 2.0.0
	 * @return array<string,mixed>|false Refund data or false on failure.
	 * @throws \Exception If unable to determine the appropriate refund method.
	 */
	private function create_subscription_refund( $payment, $transaction_id, $refund_amount, $refund_notes = '' ) {
		// Method 1: Use charge ID directly (default for subscriptions - contains first payment charge).
		// For subscription payments, transaction_id contains the charge ID from the initial payment.
		if ( is_string( $transaction_id ) && strpos( $transaction_id, 'ch_' ) === 0 ) {
			return $this->create_refund_by_charge( $payment, $transaction_id, $refund_amount, $refund_notes );
		}

		// Method 2: Use payment intent ID if provided (fallback for legacy data).
		if ( is_string( $transaction_id ) && strpos( $transaction_id, 'pi_' ) === 0 ) {
			return $this->create_refund_by_payment_intent( $payment, $transaction_id, $refund_amount, $refund_notes );
		}

		// Method 3: Try to find charge ID in payment data (fallback for edge cases).
		$charge_id = $this->get_charge_id_from_payment( $payment );
		if ( is_string( $charge_id ) && '' !== $charge_id ) {
			return $this->create_refund_by_charge( $payment, $charge_id, $refund_amount, $refund_notes );
		}

		throw new \Exception( __( 'Unable to determine the appropriate refund method for this subscription payment.', 'sureforms' ) );
	}

	/**
	 * Create refund using charge ID
	 *
	 * @param array<string,mixed> $payment Payment record.
	 * @param string              $charge_id Stripe charge ID.
	 * @param int                 $refund_amount Refund amount in cents.
	 * @param string              $refund_notes Refund notes.
	 * @since 2.0.0
	 * @return array<string,mixed>|false Refund data or false on failure.
	 */
	private function create_refund_by_charge( $payment, $charge_id, $refund_amount, $refund_notes = '' ) {
		$metadata = [
			'refunded_by'     => 'sureforms_dashboard',
			'subscription_id' => $payment['subscription_id'] ?? '',
			'source'          => 'SureForms',
			'payment_id'      => $payment['id'] ?? '',
			'refunded_at'     => time(),
			'refund_type'     => 'subscription_billing',
			'refund_method'   => 'charge_refund',
		];

		// Add refund notes to metadata if provided.
		if ( ! empty( $refund_notes ) ) {
			$metadata['refund_notes'] = $refund_notes;
		}

		$refund_response = Stripe_Helper::stripe_api_request(
			'refunds',
			'POST',
			[
				'charge'   => $charge_id,
				'amount'   => $refund_amount,
				'reason'   => 'requested_by_customer',
				'metadata' => $metadata,
			],
			'',
			[ 'mode' => $this->payment_mode ]
		);

		return $refund_response['success'] ? $refund_response['data'] : false;
	}

	/**
	 * Create refund using payment intent ID
	 *
	 * @param array<string,mixed> $payment Payment record.
	 * @param string              $payment_intent_id Stripe payment intent ID.
	 * @param int                 $refund_amount Refund amount in cents.
	 * @param string              $refund_notes Refund notes.
	 * @since 2.0.0
	 * @return array<string,mixed>|false Refund data or false on failure.
	 */
	private function create_refund_by_payment_intent( $payment, $payment_intent_id, $refund_amount, $refund_notes = '' ) {
		$metadata = [
			'refunded_by'     => 'sureforms_dashboard',
			'subscription_id' => $payment['subscription_id'] ?? '',
			'source'          => 'SureForms',
			'payment_id'      => $payment['id'] ?? '',
			'refunded_at'     => time(),
			'refund_type'     => 'subscription_billing',
			'refund_method'   => 'payment_intent_refund',
		];

		// Add refund notes to metadata if provided.
		if ( ! empty( $refund_notes ) ) {
			$metadata['refund_notes'] = $refund_notes;
		}

		$refund_response = Stripe_Helper::stripe_api_request(
			'refunds',
			'POST',
			[
				'payment_intent' => $payment_intent_id,
				'amount'         => $refund_amount,
				'reason'         => 'requested_by_customer',
				'metadata'       => $metadata,
			],
			'',
			[ 'mode' => $this->payment_mode ]
		);

		return $refund_response['success'] ? $refund_response['data'] : false;
	}

	/**
	 * Update subscription refund data in database
	 *
	 * @param int                 $payment_id Payment record ID.
	 * @param array<string,mixed> $refund_response Refund response from Stripe.
	 * @param int                 $refund_amount Refund amount in cents.
	 * @param string              $currency Currency code.
	 * @param string              $refund_notes Refund notes.
	 * @since 2.0.0
	 * @return bool True if successful, false otherwise.
	 */
	private function update_subscription_refund_data(
		int $payment_id,
		array $refund_response,
		int $refund_amount,
		string $currency,
		?string $refund_notes = null
	) {
		if ( empty( $payment_id ) || empty( $refund_response ) ) {
			return false;
		}

		// Get payment record.
		$payment = Payments::get( $payment_id );
		if ( ! $payment ) {
			return false;
		}

		// Prepare refund data for payment_data column.
		$refund_data = [
			'refund_id'      => is_string( $refund_response['id'] ) ? sanitize_text_field( $refund_response['id'] ) : '',
			'amount'         => absint( $refund_amount ),
			'currency'       => is_string( $currency ) ? sanitize_text_field( strtoupper( $currency ) ) : 'USD',
			'status'         => is_string( $refund_response['status'] ) ? sanitize_text_field( $refund_response['status'] ) : 'processed',
			'created'        => time(),
			'reason'         => is_string( $refund_response['reason'] ) ? sanitize_text_field( $refund_response['reason'] ) : 'requested_by_customer',
			'description'    => is_string( $refund_response['description'] ) ? sanitize_text_field( $refund_response['description'] ) : '',
			'receipt_number' => is_string( $refund_response['receipt_number'] ) ? sanitize_text_field( $refund_response['receipt_number'] ) : '',
			'refunded_by'    => is_string( wp_get_current_user()->display_name ) ? sanitize_text_field( wp_get_current_user()->display_name ) : 'System',
			'refunded_at'    => gmdate( 'Y-m-d H:i:s' ),
			'type'           => 'subscription_refund',
		];

		// Validate refund amount to prevent over-refunding.
		$original_amount    = floatval( $payment['total_amount'] );
		$existing_refunds   = floatval( $payment['refunded_amount'] ?? 0 ); // Use column directly.
		$new_refund_amount  = Stripe_Helper::amount_from_stripe_format( $refund_amount, $currency );
		$total_after_refund = $existing_refunds + $new_refund_amount;

		if ( $total_after_refund > $original_amount ) {
			return false;
		}

		// Add refund data to payment_data column (for audit trail).
		$payment_data_result = Payments::add_refund_to_payment_data( $payment_id, $refund_data );
		if ( ! $payment_data_result ) {
			return false;
		}

		// Update the refunded_amount column.
		$refund_amount_result = Payments::add_refund_amount( $payment_id, $new_refund_amount );
		if ( ! $refund_amount_result ) {
			return false;
		}

		// Determine new payment status.
		$total_amount   = (float) $payment['total_amount'];
		$total_refunded = Payments::get_refunded_amount( $payment_id );
		$payment_status = $total_refunded >= $total_amount ? 'refunded' : 'partially_refunded';

		// Prepare comprehensive log entry.
		$current_logs       = Helper::get_array_value( $payment['log'] );
		$original_amount    = $total_amount;
		$total_after_refund = $total_refunded;
		$refund_type        = $total_after_refund >= $original_amount ? __( 'Full', 'sureforms' ) : __( 'Partial', 'sureforms' );

		// Build log messages array.
		$log_messages = [
			sprintf(
				/* translators: %s: refund ID */
				__( 'Refund ID: %s', 'sureforms' ),
				is_string( $refund_response['id'] ) ? $refund_response['id'] : 'N/A'
			),
			sprintf(
				/* translators: %s: payment gateway */
				__( 'Payment Gateway: %s', 'sureforms' ),
				'Stripe'
			),
			sprintf(
				/* translators: 1: refund amount, 2: currency code */
				__( 'Refund Amount: %1$s %2$s', 'sureforms' ),
				number_format( Stripe_Helper::amount_from_stripe_format( $refund_amount, $currency ), 2 ),
				strtoupper( $currency )
			),
			sprintf(
				/* translators: 1: total refunded, 2: currency, 3: original amount, 4: currency */
				__( 'Total Refunded: %1$s %2$s of %3$s %4$s', 'sureforms' ),
				number_format( $total_after_refund, 2 ),
				strtoupper( $currency ),
				number_format( $original_amount, 2 ),
				strtoupper( $currency )
			),
			sprintf(
				/* translators: %s: refund status */
				__( 'Refund Status: %s', 'sureforms' ),
				is_string( $refund_response['status'] ) ? $refund_response['status'] : 'processed'
			),
			sprintf(
				/* translators: %s: payment status */
				__( 'Payment Status: %s', 'sureforms' ),
				ucfirst( str_replace( '_', ' ', $payment_status ) )
			),
			sprintf(
				/* translators: %s: refunded by user */
				__( 'Refunded by: %s', 'sureforms' ),
				wp_get_current_user()->display_name
			),
		];

		// Add refund notes to log if provided.
		if ( ! empty( $refund_notes ) ) {
			$log_messages[] = sprintf(
				/* translators: %s: refund notes */
				__( 'Refund Notes: %s', 'sureforms' ),
				$refund_notes
			);
		}

		$new_log        = [
			'title'      => sprintf(
				/* translators: %s: refund type (Full/Partial) */
				__( '%s Subscription Payment Refund', 'sureforms' ),
				$refund_type
			),
			'created_at' => current_time( 'mysql' ),
			'messages'   => $log_messages,
		];
		$current_logs[] = $new_log;

		$update_data = [
			'status' => $payment_status,
			'log'    => $current_logs,
		];

		// Update payment record with status and log.
		$payment_update_result = Payments::update( $payment_id, $update_data );

		if ( ! $payment_update_result ) {
			return false;
		}

		return true;
	}

	/**
	 * Convert technical error messages to user-friendly ones
	 *
	 * @param string $technical_error Technical error message.
	 * @since 2.0.0
	 * @return string User-friendly error message.
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

		// Default fallback message.
		// translators: %s: technical error message returned from Stripe.
		return sprintf( __( 'Subscription refund failed: %s', 'sureforms' ), $technical_error );
	}

	/**
	 * Check if refund already exists for this payment
	 *
	 * @param array<string,mixed> $payment Payment record.
	 * @param array<string,mixed> $refund_response Refund response from Stripe.
	 * @since 2.0.0
	 * @return bool True if refund already exists, false otherwise.
	 */
	private function check_if_refund_already_exists( $payment, $refund_response ) {
		if ( empty( $payment['payment_data'] ) || empty( $refund_response['id'] ) ) {
			return false;
		}

		$payment_data = Helper::get_array_value( $payment['payment_data'] );
		if ( empty( $payment_data['refunds'] ) ) {
			return false;
		}

		$refund_id = $refund_response['id'];

		// O(1) lookup using refund ID as array key.
		return isset( $payment_data['refunds'][ $refund_id ] );
	}

	/**
	 * Get charge ID from payment data
	 *
	 * @param array<string,mixed> $payment Payment record.
	 * @since 2.0.0
	 * @return string|null Charge ID or null if not found.
	 */
	private function get_charge_id_from_payment( $payment ) {
		// Check if transaction_id is already a charge ID.
		if ( ! empty( $payment['transaction_id'] ) && is_string( $payment['transaction_id'] ) && strpos( $payment['transaction_id'], 'ch_' ) === 0 ) {
			return $payment['transaction_id'];
		}

		// Look in payment_data for charge_id.
		if ( empty( $payment['payment_data'] ) ) {
			return null;
		}

		$payment_data = Helper::get_array_value( $payment['payment_data'] );
		if ( empty( $payment_data ) ) {
			return null;
		}

		// Look for charge ID in various places in payment_data.
		$charge_keys = [
			'charge_id',
			'charge',
			'invoice_charge_id',
		];

		foreach ( $charge_keys as $key ) {
			$charge_id = $this->get_nested_value( $payment_data, $key );
			if ( ! empty( $charge_id ) && is_string( $charge_id ) && strpos( $charge_id, 'ch_' ) === 0 ) {
				return $charge_id;
			}
		}

		return null;
	}

	/**
	 * Get nested value from array using dot notation
	 *
	 * @param array<string,mixed> $array Array to search.
	 * @param string              $key Dot-separated key path.
	 * @since 2.0.0
	 * @return mixed Value or null if not found.
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
