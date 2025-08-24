<?php
/**
 * SureForms Webhook Class
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
			case 'charge.captured':
				$charge = (object) $event->data->object;
				$this->charge_capture( $charge );
				break;
			case 'charge.refunded':
				$charge = (object) $event->data->object;
				$this->charge_refund( $charge );
				break;
			case 'charge.dispute.created':
				$charge = (object) $event->data->object;
				$this->charge_dispute_created( $charge );
				break;
			case 'charge.dispute.closed':
				$dispute = (object) $event->data->object;
				$this->charge_dispute_closed( $dispute );
				break;
			case 'payment_intent.succeeded':
				$intent = (object) $event->data->object;
				$this->payment_intent_succeeded( $intent );
				break;
			case 'payment_intent.payment_failed':
				$intent = (object) $event->data->object;
				$this->payment_intent_failed( $intent );
				break;
			case 'review.opened':
				$review = (object) $event->data->object;
				$this->review_opened( $review );
				break;
			case 'review.closed':
				$review = (object) $event->data->object;
				$this->review_closed( $review );
				break;
		}
		
		$success = constant( 'self::SRFM_' . strtoupper( $mode ) . '_LAST_SUCCESS_AT' );
		update_option( $success, time() );
		http_response_code( 200 );
	}

	/**
	 * Captures charge for uncaptured charges via webhook calls
	 *
	 * @param object $charge Payment charge object.
	 * @return void
	 */
	public function charge_capture( $charge ) {
		$payment_intent = sanitize_text_field( $charge->payment_intent ?? '' );
		$form_entry_id  = $this->get_entry_id_from_intent( $payment_intent );
		
		if ( ! $form_entry_id ) {
			error_log( 'SureForms: Could not find form entry via charge ID: ' . ( $charge->id ?? 'unknown' ) );
			return;
		}

		$this->make_charge( $charge, $form_entry_id );
	}

	/**
	 * Make charge via webhook call
	 *
	 * @param object $intent Payment intent object.
	 * @param int    $form_entry_id Form entry ID.
	 * @return void
	 */
	public function make_charge( $intent, $form_entry_id ) {
		if ( isset( $intent->amount_refunded ) && $intent->amount_refunded > 0 ) {
			$partial_amount = $intent->amount_captured ?? 0;
			$currency       = strtoupper( $intent->currency ?? 'USD' );
			$partial_amount = $this->get_original_amount( $partial_amount, $currency );
			
			error_log( sprintf( 
				'SureForms: Payment partially captured with amount %1$s for entry ID - %2$s', 
				$partial_amount, 
				$form_entry_id 
			) );
			
			$this->update_entry_payment_status( $form_entry_id, 'partial', $partial_amount );
		} else {
			error_log( sprintf( 
				'SureForms: Payment completely captured for entry ID - %s', 
				$form_entry_id 
			) );
			
			$this->update_entry_payment_status( $form_entry_id, 'completed', $intent->amount ?? 0 );
		}
	}

	/**
	 * Refunds form entry payment via webhook call
	 *
	 * @param object $charge Payment charge object.
	 * @return void
	 */
	public function charge_refund( $charge ) {
		$payment_intent = sanitize_text_field( $charge->payment_intent ?? '' );
		$form_entry_id  = $this->get_entry_id_from_intent( $payment_intent );
		
		if ( ! $form_entry_id ) {
			error_log( 'SureForms: Could not find form entry via charge ID: ' . ( $charge->id ?? 'unknown' ) );
			return;
		}

		$captured    = $charge->captured ?? false;
		$currency    = strtoupper( $charge->currency ?? 'USD' );
		$raw_amount  = $charge->refunds->data[0]->amount ?? 0;
		$raw_amount  = $this->get_original_amount( $raw_amount, $currency );

		if ( ! $captured ) {
			$this->update_entry_payment_status( $form_entry_id, 'cancelled', 0 );
			return;
		}

		$this->update_entry_payment_status( $form_entry_id, 'refunded', $raw_amount );
		
		error_log( sprintf( 
			'SureForms: Payment refunded. Amount: %s for entry ID: %s', 
			$raw_amount, 
			$form_entry_id 
		) );
	}

	/**
	 * Handles charge.dispute.create webhook
	 *
	 * @param object $charge Payment charge object.
	 * @return void
	 */
	public function charge_dispute_created( $charge ) {
		$payment_intent = sanitize_text_field( $charge->payment_intent ?? '' );
		$form_entry_id  = $this->get_entry_id_from_intent( $payment_intent );
		
		if ( ! $form_entry_id ) {
			error_log( 'SureForms: Could not find form entry via charge ID: ' . ( $charge->id ?? 'unknown' ) );
			return;
		}

		$this->update_entry_payment_status( $form_entry_id, 'disputed' );
		error_log( 'SureForms: Payment disputed for entry ID: ' . $form_entry_id );
	}

	/**
	 * Handles charge.dispute.closed webhook
	 *
	 * @param object $dispute Dispute object received from webhook.
	 * @return void
	 */
	public function charge_dispute_closed( $dispute ) {
		$payment_intent = sanitize_text_field( $dispute->payment_intent ?? '' );
		$form_entry_id  = $this->get_entry_id_from_intent( $payment_intent );
		
		if ( ! $form_entry_id ) {
			error_log( 'SureForms: Could not find form entry for dispute ID: ' . ( $dispute->id ?? 'unknown' ) );
			return;
		}

		$status = 'lost' === ( $dispute->status ?? '' ) ? 'failed' : 'completed';
		$this->update_entry_payment_status( $form_entry_id, $status );
		
		error_log( sprintf( 
			'SureForms: Dispute closed with status %s for entry ID: %s', 
			$dispute->status ?? 'unknown', 
			$form_entry_id 
		) );
	}

	/**
	 * Handles webhook call of event payment_intent.succeeded
	 *
	 * @param object $intent Payment intent object received from webhook.
	 * @return void
	 */
	public function payment_intent_succeeded( $intent ) {
		$payment_intent = sanitize_text_field( $intent->id ?? '' );
		$form_entry_id  = $this->get_entry_id_from_intent( $payment_intent );
		
		if ( ! $form_entry_id ) {
			error_log( 'SureForms: Could not find form entry via payment intent: ' . ( $intent->id ?? 'unknown' ) );
			return;
		}

		$this->update_entry_payment_status( $form_entry_id, 'completed', $intent->amount ?? 0 );
		
		error_log( sprintf( 
			'SureForms: PaymentIntent %s succeeded for entry %s', 
			$intent->id ?? 'unknown', 
			$form_entry_id 
		) );
	}

	/**
	 * Handles webhook call payment_intent.payment_failed
	 *
	 * @param object $intent Payment intent object.
	 * @return void
	 */
	public function payment_intent_failed( $intent ) {
		$payment_intent = sanitize_text_field( $intent->id ?? '' );
		$form_entry_id  = $this->get_entry_id_from_intent( $payment_intent );
		
		if ( ! $form_entry_id ) {
			error_log( 'SureForms: Could not find form entry via payment intent: ' . ( $intent->id ?? 'unknown' ) );
			return;
		}

		$error_message = isset( $intent->last_payment_error->message ) 
			? $intent->last_payment_error->message 
			: 'Payment failed';
			
		$this->update_entry_payment_status( $form_entry_id, 'failed', 0, $error_message );
		
		error_log( sprintf( 
			'SureForms: Payment failed for entry %s. Reason: %s', 
			$form_entry_id, 
			$error_message 
		) );
	}

	/**
	 * Handles review.opened webhook
	 *
	 * @param object $review Review object from webhook.
	 * @return void
	 */
	public function review_opened( $review ) {
		$payment_intent = sanitize_text_field( $review->payment_intent ?? '' );
		$form_entry_id  = $this->get_entry_id_from_intent( $payment_intent );
		
		if ( ! $form_entry_id ) {
			error_log( 'SureForms: Could not find form entry via review ID: ' . ( $review->id ?? 'unknown' ) );
			return;
		}

		$this->update_entry_payment_status( $form_entry_id, 'under_review' );
		error_log( 'SureForms: Payment under review for entry ID: ' . $form_entry_id );
	}

	/**
	 * Handles review.closed webhook
	 *
	 * @param object $review Review object from webhook.
	 * @return void
	 */
	public function review_closed( $review ) {
		$payment_intent = sanitize_text_field( $review->payment_intent ?? '' );
		$form_entry_id  = $this->get_entry_id_from_intent( $payment_intent );
		
		if ( ! $form_entry_id ) {
			error_log( 'SureForms: Could not find form entry via review ID: ' . ( $review->id ?? 'unknown' ) );
			return;
		}

		$this->update_entry_payment_status( $form_entry_id, 'completed' );
		
		error_log( sprintf( 
			'SureForms: Review closed for entry %s. Reason: %s', 
			$form_entry_id, 
			$review->reason ?? 'unknown' 
		) );
	}

	/**
	 * Fetch form entry ID from payment intent
	 *
	 * @param string $payment_intent payment intent received from webhook.
	 * @return int|null Entry ID or null if not found.
	 */
	public function get_entry_id_from_intent( $payment_intent ) {
		global $wpdb;
		
		if ( empty( $payment_intent ) ) {
			return null;
		}

		$table_name = $wpdb->prefix . 'srfm_entries';
		
		return $wpdb->get_var( 
			$wpdb->prepare( 
				"SELECT id FROM {$table_name} WHERE payment_intent = %s", 
				$payment_intent 
			) 
		);
	}

	/**
	 * Update form entry payment status
	 *
	 * @param int    $entry_id Form entry ID.
	 * @param string $status Payment status.
	 * @param float  $amount Payment amount (optional).
	 * @param string $error_message Error message (optional).
	 * @return void
	 */
	public function update_entry_payment_status( $entry_id, $status, $amount = null, $error_message = null ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'srfm_entries';
		
		$update_data = [
			'payment_status' => $status,
		];
		
		if ( null !== $amount ) {
			$update_data['payment_amount'] = $amount;
		}
		
		if ( $error_message ) {
			$update_data['payment_error'] = $error_message;
		}
		
		$wpdb->update(
			$table_name,
			$update_data,
			[ 'id' => $entry_id ],
			null,
			[ '%d' ]
		);
	}

	/**
	 * Get original amount from cents
	 *
	 * @param int    $amount Amount in cents.
	 * @param string $currency Currency code.
	 * @return float Original amount.
	 */
	public function get_original_amount( $amount, $currency ) {
		$zero_decimal_currencies = [
			'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 
			'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 
			'XOF', 'XPF',
		];

		if ( in_array( strtoupper( $currency ), $zero_decimal_currencies, true ) ) {
			return floatval( $amount );
		}

		return floatval( $amount ) / 100;
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