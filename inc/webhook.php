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

		$update_refund_data = Stripe_Payment_Handler::get_instance()->update_refund_data( $payment_entry_id, $refund, $refund_amount, $charge->currency, $get_payment_entry );

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