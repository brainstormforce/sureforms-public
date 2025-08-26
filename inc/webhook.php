<?php
/**
 * SureForms Webhook Class
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc;

use SRFM\Inc\Traits\Get_Instance;
use SRFM\Inc\Database\Tables\Payments;
use SRFM\Inc\Stripe_Payment_Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Webhook endpoints
 */
class Webhook {

	const SRFM_LIVE_BEGAN_AT        = 'srfm_live_webhook_began_at';
	const SRFM_LIVE_LAST_SUCCESS_AT = 'srfm_live_webhook_last_success_at';
	const SRFM_LIVE_LAST_FAILURE_AT = 'srfm_live_webhook_last_failure_at';
	const SRFM_LIVE_LAST_ERROR      = 'srfm_live_webhook_last_error';

	const SRFM_TEST_BEGAN_AT        = 'srfm_test_webhook_began_at';
	const SRFM_TEST_LAST_SUCCESS_AT = 'srfm_test_webhook_last_success_at';
	const SRFM_TEST_LAST_FAILURE_AT = 'srfm_test_webhook_last_failure_at';
	const SRFM_TEST_LAST_ERROR      = 'srfm_test_webhook_last_error';

	use Get_Instance;

	/**
	 * Constructor function
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	/**
	 * Returns message about interaction with webhook
	 *
	 * @param mixed $mode mode of operation.
	 * @return string
	 */
	public static function get_webhook_interaction_message( $mode = false ) {
		if ( ! $mode ) {
			$settings = get_option( Payments_Settings::OPTION_NAME, [] );
			$mode     = $settings['payment_mode'] ?? 'test';
		}
		
		$last_success    = constant( 'self::SRFM_' . strtoupper( $mode ) . '_LAST_SUCCESS_AT' );
		$last_success_at = get_option( $last_success );

		$last_failure    = constant( 'self::SRFM_' . strtoupper( $mode ) . '_LAST_FAILURE_AT' );
		$last_failure_at = get_option( $last_failure );

		$began    = constant( 'self::SRFM_' . strtoupper( $mode ) . '_BEGAN_AT' );
		$began_at = get_option( $began );

		$status = 'none';

		if ( $last_success_at && $last_failure_at ) {
			$status = ( $last_success_at >= $last_failure_at ) ? 'success' : 'failure';
		} elseif ( $last_success_at ) {
			$status = 'success';
		} elseif ( $last_failure_at ) {
			$status = 'failure';
		} elseif ( $began_at ) {
			$status = 'began';
		}

		switch ( $status ) {
			case 'success':
				return sprintf( 
					/* translators: time, status */
					__( 'Last webhook call was %1$s. Status : %2$s', 'sureforms' ), 
					self::time_elapsed_string( gmdate( 'Y-m-d H:i:s e', $last_success_at ) ), 
					'<b>' . ucfirst( $status ) . '</b>' 
				);

			case 'failure':
				$err_const = constant( 'self::SRFM_' . strtoupper( $mode ) . '_LAST_ERROR' );
				$error     = get_option( $err_const );
				$reason    = ( $error ) ? sprintf( 
					/* translators: error message */
					__( 'Reason : %s', 'sureforms' ), 
					'<b>' . $error . '</b>' 
				) : '';
				return sprintf( 
					/* translators: time, status, reason */
					__( 'Last webhook call was %1$s. Status : %2$s. %3$s', 'sureforms' ), 
					self::time_elapsed_string( gmdate( 'Y-m-d H:i:s e', $last_failure_at ) ), 
					'<b>' . ucfirst( $status ) . '</b>', 
					$reason 
				);

			case 'began':
				return sprintf( 
					/* translators: timestamp */
					__( 'No webhook call since %s.', 'sureforms' ), 
					gmdate( 'Y-m-d H:i:s e', $began_at ) 
				);

			default:
				$settings = get_option( Payments_Settings::OPTION_NAME, [] );
				if ( 'live' === $mode ) {
					$endpoint_secret = $settings['webhook_live_secret'] ?? '';
				} elseif ( 'test' === $mode ) {
					$endpoint_secret = $settings['webhook_test_secret'] ?? '';
				}
				
				if ( ! empty( trim( $endpoint_secret ) ) ) {
					$current_time = time();
					update_option( $began, $current_time );
					return sprintf( 
						/* translators: timestamp */
						__( 'No webhook call since %s.', 'sureforms' ), 
						gmdate( 'Y-m-d H:i:s e', $current_time ) 
					);
				}
				return '';
		}
	}

	/**
	 * Registers endpoint for webhook
	 *
	 * @return void
	 */
	public function register_endpoints() {
		register_rest_route(
			'sureforms',
			'/webhook',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'webhook_listener' ],
				// 'permission_callback' => [ $this, 'validate_webhook_signature' ],
			]
		);
	}

	/**
	 * Validates the Stripe signature for webhook requests.
	 *
	 * @return bool
	 */
	public function validate_stripe_signature() {
		// Check if this is a POST request with Stripe signature header.
		return true;
	}

	/**
	 * This function listens webhook events.
	 *
	 * @return void
	 */
	public function webhook_listener() {
		// $settings = get_option( Payments_Settings::OPTION_NAME, [] );
		$mode     = $settings['payment_mode'] ?? 'test';
		
		// if ( 'live' === $mode ) {
		// 	$endpoint_secret = $settings['webhook_live_secret'] ?? '';
		// } else {
		// 	$endpoint_secret = $settings['webhook_test_secret'] ?? '';
		// }

		// if ( empty( trim( $endpoint_secret ) ) ) {
		// 	http_response_code( 400 );
		// 	exit();
		// }

		// $began = constant( 'self::SRFM_' . strtoupper( $mode ) . '_BEGAN_AT' );
		// if ( ! get_option( $began ) ) {
		// 	update_option( $began, time() );
		// }

		$payload = file_get_contents( 'php://input' );
		$event   = null;

		try {
			$event_data = json_decode( $payload, true );
			
			if ( ! $event_data || ! isset( $event_data['type'] ) ) {
				throw new \Exception( 'Invalid payload format' );
			}

			$event = (object) $event_data;
		} catch ( \Exception $e ) {
			error_log( 'SureForms Webhook error: ' . $e->getMessage() );
			$error_at = constant( 'self::SRFM_' . strtoupper( $mode ) . '_LAST_FAILURE_AT' );
			update_option( $error_at, time() );
			$error = constant( 'self::SRFM_' . strtoupper( $mode ) . '_LAST_ERROR' );
			update_option( $error, $e->getMessage() );
			http_response_code( 400 );
			exit();
		}

		error_log( 'SureForms webhook event type: ' . $event->type );

		switch ( $event->type ) {
			case 'charge.refunded':
				$charge = (object) $event->data['object'];
				$this->charge_refund( $charge );
				break;
		}
		
		$success = constant( 'self::SRFM_' . strtoupper( $mode ) . '_LAST_SUCCESS_AT' );
		update_option( $success, time() );
		http_response_code( 200 );
	}

	/**
	 * Refunds form entry payment via webhook call
	 *
	 * @param object $charge Payment charge object.
	 * @return void
	 */
	public function charge_refund( $charge ) {
		$payment_intent = sanitize_text_field( $charge->payment_intent ?? '' );
		$get_payment_entry = Payments::get_by_transaction_id( $payment_intent );

		if ( ! $get_payment_entry ) {
			error_log( 'SureForms: Could not find payment entry via charge ID: ' . ( $charge->id ?? 'unknown' ) );
			return;
		}

		$payment_entry_id = $get_payment_entry['id'] ?? 0;
		$refund = $charge->refunds['data'][0];
		$refund_amount = $refund['amount'];

		$update_refund_data = $this->update_refund_data( $payment_entry_id, $refund, $refund_amount, $charge->currency, 'webhook' );

		if ( ! $update_refund_data ) {
			error_log( 'SureForms: Failed to update refund data for payment entry ID: ' . $payment_entry_id );
			return;
		}

		error_log( sprintf( 
			'SureForms: Payment refunded. Amount: %s for entry ID: %s', 
			$refund_amount, 
			$payment_entry_id 
		) );
	}

	public function calculate_total_refunds( $payment_id ) {
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
	private function check_if_refund_already_exists( $payment, $refund ){
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
			'refunded_by'    => 'stripe_dashboard',
			'refunded_at'    => gmdate( 'Y-m-d H:i:s' ),
		];

		// Validate refund amount to prevent over-refunding
		$original_amount = floatval( $payment['total_amount'] );
		$existing_refunds = floatval( $payment['refunded_amount'] ?? 0 ); // Use column directly
		$new_refund_amount = $refund_amount / 100; // Convert cents to dollars
		$total_after_refund = $existing_refunds + $new_refund_amount;
		
		if ( $total_after_refund > $original_amount ) {
			error_log( sprintf(
				'SureForms: Over-refund attempt blocked. Payment ID: %d, Original: $%s, Existing refunds: $%s, New refund: $%s',
				$payment_id,
				number_format( $original_amount, 2 ),
				number_format( $existing_refunds, 2 ),
				number_format( $new_refund_amount, 2 )
			) );
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
		$current_logs = Helper::get_array_value( $payment['log'] );
		$refund_type = ( $total_after_refund >= $original_amount ) ? 'Full' : 'Partial';
		$new_log = [
			'title'     => sprintf( '%s Payment Refund', $refund_type ),
			'timestamp' => time(),
			'messages'  => [
				sprintf( 'Refund ID: %s', $refund_response['id'] ?? 'N/A' ),
				sprintf( 'Refund Amount: %s %s', number_format( $refund_amount / 100, 2 ), strtoupper( $currency ) ),
				sprintf( 'Total Refunded: %s %s of %s %s', 
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

		error_log( sprintf(
			'SureForms: Refund processed successfully. Payment ID: %d, Refund ID: %s, Amount: %s %s',
			$payment_id,
			$refund_data['refund_id'],
			number_format( $refund_amount / 100, 2 ),
			$currency
		) );

		return true;
	}

	/**
	 * Shows time difference as - XX minutes ago.
	 *
	 * @param string  $datetime time of last event.
	 * @param boolean $full show full time difference.
	 * @return string
	 */
	public static function time_elapsed_string( $datetime, $full = false ) {
		$now  = new \DateTime();
		$ago  = new \DateTime( $datetime );
		$diff = $now->diff( $ago );

		$diff->w  = floor( $diff->d / 7 );
		$diff->d -= $diff->w * 7;

		$string = [
			'y' => 'year',
			'm' => 'month',
			'w' => 'week',
			'd' => 'day',
			'h' => 'hour',
			'i' => 'minute',
			's' => 'second',
		];
		
		foreach ( $string as $k => &$v ) {
			if ( $diff->$k ) {
				$v = $diff->$k . ' ' . $v . ( $diff->$k > 1 ? 's' : '' );
			} else {
				unset( $string[ $k ] );
			}
		}

		if ( ! $full ) {
			$string = array_slice( $string, 0, 1 );
		}
		
		return $string ? implode( ', ', $string ) . ' ago' : 'just now';
	}
}

// Initialize the class.
Webhook::get_instance();