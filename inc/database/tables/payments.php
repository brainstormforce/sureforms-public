<?php
/**
 * SureForms Database Payment Table Class.
 *
 * @link       https://sureforms.com
 * @since      2.0.0
 * @package    SureForms
 * @author     SureForms <https://sureforms.com/>
 */

namespace SRFM\Inc\Database\Tables;

use SRFM\Inc\Database\Base;
use SRFM\Inc\Helper;
use SRFM\Inc\Traits\Get_Instance;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * SureForms Database Payment Table Class.
 *
 * @since 2.0.0
 */
class Payments extends Base {
	use Get_Instance;

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected $table_suffix = 'payments';

	/**
	 * {@inheritDoc}
	 *
	 * @var int
	 */
	protected $table_version = 1;

	/**
	 * Valid payment statuses (Stripe-specific).
	 *
	 * @var array<string>
	 * @since 2.0.0
	 */
	private static $valid_statuses = [
		'pending',
		'succeeded',
		'failed',
		'canceled',
		'requires_action',
		'requires_payment_method',
		'processing',
		'refunded',
		'partially_refunded',
	];

	/**
	 * Valid currencies (ISO 4217).
	 *
	 * @var array<string>
	 * @since 2.0.0
	 */
	private static $valid_currencies = [
		'USD',
		'EUR',
		'GBP',
		'JPY',
		'CAD',
		'AUD',
		'CHF',
		'CNY',
		'SEK',
		'NZD',
		'MXN',
		'SGD',
		'HKD',
		'NOK',
		'TRY',
		'RUB',
		'INR',
		'BRL',
		'ZAR',
		'KRW',
	];

	/**
	 * Valid payment gateways.
	 *
	 * @var array<string>
	 * @since 2.0.0
	 */
	private static $valid_gateways = [
		'stripe',
	];

	/**
	 * Valid payment modes.
	 *
	 * @var array<string>
	 * @since 2.0.0
	 */
	private static $valid_modes = [
		'test',
		'live',
	];

	/**
	 * Valid subscription statuses (Stripe-specific).
	 *
	 * @var array<string>
	 * @since 2.0.0
	 */
	private static $valid_subscription_statuses = [
		'active',
		'canceled',
		'past_due',
		'unpaid',
		'trialing',
		'incomplete',
		'incomplete_expired',
		'paused',
	];

	/**
	 * {@inheritDoc}
	 */
	public function get_schema() {
		return [
			// Payment ID.
			'id'                     => [
				'type' => 'number',
			],
			// Form ID.
			'form_id'                => [
				'type' => 'number',
			],
			'block_id'               => [
				'type'    => 'string',
				'default' => '',
			],
			// Payment status (Stripe).
			'status'                 => [
				'type'    => 'string',
				'default' => 'pending',
			],
			// Total amount after discount.
			'total_amount'           => [
				'type'    => 'string',
				'default' => '0.00000000',
			],
			// Total refunded amount.
			'refunded_amount'        => [
				'type'    => 'string',
				'default' => '0.00000000',
			],
			// Currency code.
			'currency'               => [
				'type'    => 'string',
				'default' => '',
			],
			// Entry ID.
			'entry_id'               => [
				'type'    => 'number',
				'default' => 0,
			],
			// Payment gateway.
			'gateway'                => [
				'type'    => 'string',
				'default' => '',
			],
			// Payment type.
			'type'                   => [
				'type'    => 'string',
				'default' => '',
			],
			// Payment mode (test/live).
			'mode'                   => [
				'type'    => 'string',
				'default' => '',
			],
			// Transaction ID from gateway.
			'transaction_id'         => [
				'type'    => 'string',
				'default' => '',
			],
			// Customer ID from gateway.
			'customer_id'            => [
				'type'    => 'string',
				'default' => '',
			],
			// Subscription ID (if recurring).
			'subscription_id'        => [
				'type'    => 'string',
				'default' => '',
			],
			// Subscription status.
			'subscription_status'    => [
				'type'    => 'string',
				'default' => '',
			],
			// Parent subscription payment ID (for renewal payments).
			'parent_subscription_id' => [
				'type'    => 'number',
				'default' => 0,
			],
			// Payment data.
			'payment_data'           => [
				'type'    => 'array',
				'default' => [],
			],
			// Extra data (JSON).
			'extra'                  => [
				'type'    => 'array',
				'default' => [],
			],
			// Payment log.
			'log'                    => [
				'type'    => 'array',
				'default' => [],
			],
			// Created date.
			'created_at'             => [
				'type' => 'datetime',
			],
			// Updated date.
			'updated_at'             => [
				'type' => 'datetime',
			],
			// Transaction ID (custom format).
			'srfm_txn_id'            => [
				'type'    => 'string',
				'default' => '',
			],
			// Customer email.
			'customer_email'         => [
				'type'    => 'string',
				'default' => '',
			],
			// Customer name.
			'customer_name'          => [
				'type'    => 'string',
				'default' => '',
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_columns_definition() {
		return [
			'id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY',
			'form_id BIGINT(20) UNSIGNED',
			'block_id VARCHAR(255) NOT NULL',
			'status VARCHAR(50) NOT NULL',
			'total_amount DECIMAL(26,8) NOT NULL',
			'refunded_amount DECIMAL(26,8) NOT NULL',
			'currency VARCHAR(10) NOT NULL',
			'entry_id BIGINT(20) UNSIGNED NOT NULL',
			'gateway VARCHAR(20) NOT NULL',
			'type VARCHAR(30) NOT NULL',
			'mode VARCHAR(20) NOT NULL',
			'transaction_id VARCHAR(50) NOT NULL',
			'customer_id VARCHAR(50) NOT NULL',
			'subscription_id VARCHAR(50) NOT NULL',
			'subscription_status VARCHAR(20) NOT NULL',
			'parent_subscription_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0',
			'payment_data LONGTEXT',
			'extra LONGTEXT',
			'log LONGTEXT',
			'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
			'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
			'srfm_txn_id VARCHAR(100) NOT NULL',
			'customer_email VARCHAR(255) NOT NULL',
			'customer_name VARCHAR(255) NOT NULL',
		];
	}

	/**
	 * Add a new payment record.
	 *
	 * @param array<string,mixed> $data Payment data to insert.
	 * @since 2.0.0
	 * @return int|false The payment ID on success, false on error.
	 */
	public static function add( $data ) {

		$instance = self::get_instance();

		return $instance->use_insert( $data );
	}

	/**
	 * Update a payment record.
	 *
	 * @param int                 $payment_id Payment ID to update.
	 * @param array<string,mixed> $data       Data to update.
	 * @since 2.0.0
	 * @return int|false Number of rows updated or false on error.
	 */
	public static function update( $payment_id, $data = [] ) {
		if ( empty( $payment_id ) ) {
			return false;
		}

		return self::get_instance()->use_update( $data, [ 'id' => absint( $payment_id ) ] );
	}

	/**
	 * Get extra data for a payment.
	 *
	 * @param int $payment_id Payment ID.
	 * @since 2.0.0
	 * @return array<string,mixed> Extra data array.
	 */
	public static function get_extra_data( $payment_id ) {
		if ( empty( $payment_id ) ) {
			return [];
		}

		$result = self::get_instance()->get_results(
			[ 'id' => absint( $payment_id ) ],
			'extra'
		);

		return isset( $result[0] ) && is_array( $result[0] ) ? Helper::get_array_value( $result[0]['extra'] ) : [];
	}

	/**
	 * Update specific key in extra data.
	 *
	 * @param int    $payment_id Payment ID.
	 * @param string $key        Key to update.
	 * @param mixed  $value      Value to set.
	 * @since 2.0.0
	 * @return int|false Number of rows updated or false on error.
	 */
	public static function update_extra_key( $payment_id, $key, $value ) {
		if ( empty( $payment_id ) || empty( $key ) ) {
			return false;
		}

		// Get current extra data.
		$extra_data = self::get_extra_data( $payment_id );

		// Update specific key.
		$extra_data[ sanitize_key( $key ) ] = $value;

		// Update payment with new extra data.
		return self::update( $payment_id, [ 'extra' => $extra_data ] );
	}

	/**
	 * Add multiple key-value pairs to extra data.
	 *
	 * @param int                 $payment_id Payment ID.
	 * @param array<string,mixed> $data       Key-value pairs to add.
	 * @since 2.0.0
	 * @return int|false Number of rows updated or false on error.
	 */
	public static function add_extra_data( $payment_id, $data ) {
		if ( empty( $payment_id ) || empty( $data ) || ! is_array( $data ) ) {
			return false;
		}

		// Get current extra data.
		$extra_data = self::get_extra_data( $payment_id );

		// Merge new data with existing data.
		foreach ( $data as $key => $value ) {
			$extra_data[ sanitize_key( $key ) ] = $value;
		}

		// Update payment with new extra data.
		return self::update( $payment_id, [ 'extra' => $extra_data ] );
	}

	/**
	 * Remove specific key from extra data.
	 *
	 * @param int    $payment_id Payment ID.
	 * @param string $key        Key to remove.
	 * @since 2.0.0
	 * @return int|false Number of rows updated or false on error.
	 */
	public static function remove_extra_key( $payment_id, $key ) {
		if ( empty( $payment_id ) || empty( $key ) ) {
			return false;
		}

		// Get current extra data.
		$extra_data = self::get_extra_data( $payment_id );

		// Remove specific key.
		$sanitized_key = sanitize_key( $key );
		if ( isset( $extra_data[ $sanitized_key ] ) ) {
			unset( $extra_data[ $sanitized_key ] );

			// Update payment with modified extra data.
			return self::update( $payment_id, [ 'extra' => $extra_data ] );
		}

		return false;
	}

	/**
	 * Get specific value from extra data.
	 *
	 * @param int    $payment_id Payment ID.
	 * @param string $key        Key to get.
	 * @param mixed  $default    Default value if key not found.
	 * @since 2.0.0
	 * @return mixed Value from extra data or default.
	 */
	public static function get_extra_value( $payment_id, $key, $default = null ) {
		if ( empty( $payment_id ) || empty( $key ) ) {
			return $default;
		}

		$extra_data    = self::get_extra_data( $payment_id );
		$sanitized_key = sanitize_key( $key );

		return $extra_data[ $sanitized_key ] ?? $default;
	}

	/**
	 * Get a single payment by ID.
	 *
	 * @param int $payment_id Payment ID.
	 * @since 2.0.0
	 * @return array|null Payment data or null if not found.
	 */
	public static function get( $payment_id ) {
		if ( empty( $payment_id ) ) {
			return null;
		}

		$results = self::get_instance()->get_results( [ 'id' => absint( $payment_id ) ] );
		return is_array( $results ) && isset( $results[0] ) && is_array( $results[0] ) ? $results[0] : null;
	}

	/**
	 * Get all payments with optional parameters.
	 *
	 * @param array<mixed> $args Query arguments.
	 * @param bool         $set_limit Whether to apply limit to query.
	 * @since 2.0.0
	 * @return array Array of payments.
	 */
	public static function get_all( $args = [], $set_limit = true ) {
		$_args = wp_parse_args(
			$args,
			[
				'where'   => [],
				'columns' => '*',
				'limit'   => 20,
				'offset'  => 0,
				'orderby' => 'created_at',
				'order'   => 'DESC',
			]
		);

		$extra_queries = [
			sprintf( 'ORDER BY `%1$s` %2$s', Helper::get_string_value( esc_sql( $_args['orderby'] ) ), Helper::get_string_value( esc_sql( $_args['order'] ) ) ),
		];

		if ( $set_limit ) {
			$extra_queries[] = sprintf( 'LIMIT %1$d, %2$d', absint( $_args['offset'] ), absint( $_args['limit'] ) );
		}

		return self::get_instance()->get_results(
			$_args['where'],
			$_args['columns'],
			$extra_queries
		);
	}

	/**
	 * Get total payments count by status.
	 *
	 * @param string       $status Status to filter by ('all', 'pending', 'succeeded', etc.).
	 * @param int          $form_id Optional form ID to filter by.
	 * @param array<mixed> $where_conditions Optional additional where conditions.
	 * @since 2.0.0
	 * @return int Total count.
	 */
	public static function get_total_payments_by_status( $status = 'all', $form_id = 0, $where_conditions = [] ) {
		$instance = self::get_instance();
		$where    = [];

		// Add status condition.
		if ( 'all' !== $status ) {
			$where[] = [
				[
					'key'     => 'status',
					'compare' => '=',
					'value'   => sanitize_text_field( $status ),
				],
			];
		}

		// Add form ID condition.
		if ( $form_id > 0 ) {
			$where[] = [
				[
					'key'     => 'form_id',
					'compare' => '=',
					'value'   => absint( $form_id ),
				],
			];
		}

		// Add additional where conditions.
		if ( ! empty( $where_conditions ) ) {
			$where = array_merge( $where, $where_conditions );
		}

		return $instance->get_total_count( $where );
	}

	/**
	 * Get payments count after specific timestamp.
	 *
	 * @param int $timestamp Unix timestamp.
	 * @since 2.0.0
	 * @return int Count of payments.
	 */
	public static function get_payments_count_after( $timestamp ) {
		$instance = self::get_instance();
		$where    = [
			[
				[
					'key'     => 'created_at',
					'compare' => '>=',
					'value'   => gmdate( 'Y-m-d H:i:s', $timestamp ),
				],
			],
		];

		return $instance->get_total_count( $where );
	}

	/**
	 * Get available months for payments.
	 *
	 * @param array<mixed> $where_conditions Optional where conditions.
	 * @since 2.0.0
	 * @return array Array of month values and labels.
	 */
	public static function get_available_months( $where_conditions = [] ) {
		$results = self::get_instance()->get_results(
			$where_conditions,
			'DISTINCT DATE_FORMAT(created_at, "%Y%m") as month_value, DATE_FORMAT(created_at, "%M %Y") as month_label',
			[
				'ORDER BY month_value DESC',
			],
			false
		);

		$months = [];
		foreach ( $results as $result ) {
			if ( is_array( $result ) && isset( $result['month_value'], $result['month_label'] ) ) {
				$months[ $result['month_value'] ] = $result['month_label'];
			}
		}

		return $months;
	}

	/**
	 * Get all payment IDs for a specific form.
	 *
	 * @param int $form_id Form ID.
	 * @since 2.0.0
	 * @return array Array of payment IDs.
	 */
	public static function get_all_payment_ids_for_form( $form_id ) {
		if ( empty( $form_id ) ) {
			return [];
		}

		$instance = self::get_instance();
		return $instance->get_results(
			[
				[
					[
						'key'     => 'form_id',
						'compare' => '=',
						'value'   => absint( $form_id ),
					],
				],
			],
			'id'
		);
	}

	/**
	 * Get form IDs by payment IDs.
	 *
	 * @param array<mixed> $payment_ids Array of payment IDs.
	 * @since 2.0.0
	 * @return array Array of unique form IDs.
	 */
	public static function get_form_ids_by_payments( $payment_ids ) {
		if ( empty( $payment_ids ) || ! is_array( $payment_ids ) ) {
			return [];
		}

		$instance = self::get_instance();
		$results  = $instance->get_results(
			[
				[
					[
						'key'     => 'id',
						'compare' => 'IN',
						'value'   => array_map( 'absint', $payment_ids ),
					],
				],
			],
			'DISTINCT form_id'
		);

		return array_unique( array_column( $results, 'form_id' ) );
	}

	/**
	 * Get all distinct form IDs that have payments.
	 *
	 * @since 2.0.0
	 * @return array Array of unique form IDs that have at least one payment.
	 */
	public static function get_all_forms_with_payments() {
		$instance = self::get_instance();

		// Get distinct form IDs from the payments table.
		$results = $instance->get_results(
			[], // Empty where clause to get all records.
			'DISTINCT form_id'
		);

		$form_ids = array_unique( array_column( $results, 'form_id' ) );

		// Filter out any null or 0 form IDs and return as integers.
		return array_filter( array_map( 'absint', $form_ids ) );
	}

	/**
	 * Delete a payment.
	 *
	 * @param int $payment_id Payment ID.
	 * @since 2.0.0
	 * @return int|false Number of rows deleted or false on error.
	 */
	public static function delete( $payment_id ) {
		if ( empty( $payment_id ) ) {
			return false;
		}

		return self::get_instance()->use_delete( [ 'id' => absint( $payment_id ) ] );
	}

	/**
	 * Get payments by entry ID.
	 *
	 * @param int $entry_id Entry ID.
	 * @since 2.0.0
	 * @return array Array of payments.
	 */
	public static function get_by_entry_id( $entry_id ) {
		if ( empty( $entry_id ) ) {
			return [];
		}

		return self::get_all(
			[
				'where' => [
					[
						[
							'key'     => 'entry_id',
							'compare' => '=',
							'value'   => absint( $entry_id ),
						],
					],
				],
			]
		);
	}

	/**
	 * Get payments by transaction ID.
	 *
	 * @param string $transaction_id Transaction ID.
	 * @since 2.0.0
	 * @return array|null Payment data or null if not found.
	 */
	public static function get_by_transaction_id( $transaction_id ) {
		if ( empty( $transaction_id ) ) {
			return null;
		}

		$results = self::get_all(
			[
				'where' => [
					[
						[
							'key'     => 'transaction_id',
							'compare' => '=',
							'value'   => sanitize_text_field( $transaction_id ),
						],
					],
				],
				'limit' => 1,
			]
		);

		return $results[0] ?? null;
	}

	/**
	 * Validate payment status.
	 *
	 * @param string $status Status to validate.
	 * @since 2.0.0
	 * @return bool True if valid, false otherwise.
	 */
	public static function is_valid_status( $status ) {
		return in_array( $status, self::$valid_statuses, true );
	}

	/**
	 * Validate currency.
	 *
	 * @param string $currency Currency to validate.
	 * @since 2.0.0
	 * @return bool True if valid, false otherwise.
	 */
	public static function is_valid_currency( $currency ) {
		return in_array( strtoupper( $currency ), self::$valid_currencies, true );
	}

	/**
	 * Validate gateway.
	 *
	 * @param string $gateway Gateway to validate.
	 * @since 2.0.0
	 * @return bool True if valid, false otherwise.
	 */
	public static function is_valid_gateway( $gateway ) {
		return in_array( $gateway, self::$valid_gateways, true );
	}

	/**
	 * Validate mode.
	 *
	 * @param string $mode Mode to validate.
	 * @since 2.0.0
	 * @return bool True if valid, false otherwise.
	 */
	public static function is_valid_mode( $mode ) {
		return in_array( $mode, self::$valid_modes, true );
	}

	/**
	 * Validate subscription status.
	 *
	 * @param string $status Subscription status to validate.
	 * @since 2.0.0
	 * @return bool True if valid, false otherwise.
	 */
	public static function is_valid_subscription_status( $status ) {
		return in_array( $status, self::$valid_subscription_statuses, true );
	}

	/**
	 * Get all valid subscription statuses.
	 *
	 * @since 2.0.0
	 * @return array<string> Array of valid subscription statuses.
	 */
	public static function get_valid_subscription_statuses() {
		return self::$valid_subscription_statuses;
	}

	/**
	 * Get payment data for a payment.
	 *
	 * @param int $payment_id Payment ID.
	 * @since 2.0.0
	 * @return array<string,mixed> Payment data array.
	 */
	public static function get_payment_data( $payment_id ) {
		if ( empty( $payment_id ) ) {
			return [];
		}

		$result = self::get_instance()->get_results(
			[ 'id' => absint( $payment_id ) ],
			'payment_data'
		);

		return isset( $result[0] ) && is_array( $result[0] ) ? Helper::get_array_value( $result[0]['payment_data'] ) : [];
	}

	/**
	 * Add refund data to payment_data column.
	 *
	 * @param int          $payment_id Payment ID.
	 * @param array<mixed> $refund_data Refund data to add.
	 * @since 2.0.0
	 * @return int|false Number of rows updated or false on error.
	 */
	public static function add_refund_to_payment_data( $payment_id, $refund_data ) {
		if ( empty( $payment_id ) || empty( $refund_data ) || ! is_array( $refund_data ) ) {
			return false;
		}

		// Extract refund ID - required for using as array key.
		$refund_id = $refund_data['refund_id'] ?? '';
		if ( empty( $refund_id ) ) {
			return false; // Must have a refund ID.
		}

		// Get current payment data.
		$payment_data = self::get_payment_data( $payment_id );
		$payment_data = is_array( $payment_data ) ? $payment_data : [];

		// Initialize refunds array if it doesn't exist.
		if ( ! isset( $payment_data['refunds'] ) || ! is_array( $payment_data['refunds'] ) ) {
			$payment_data['refunds'] = [];
		}

		// Use refund ID as array key - automatically prevents duplicates!
		$payment_data['refunds'][ $refund_id ] = $refund_data;

		// Update payment with new payment data.
		return self::update( $payment_id, [ 'payment_data' => $payment_data ] );
	}

	/**
	 * Add refund amount to the refunded_amount column.
	 *
	 * @param int   $payment_id Payment ID.
	 * @param float $refund_amount Refund amount to add (in dollars).
	 * @since 2.0.0
	 * @return int|false Number of rows updated or false on error.
	 */
	public static function add_refund_amount( $payment_id, $refund_amount ) {
		if ( empty( $payment_id ) || $refund_amount <= 0 ) {
			return false;
		}

		// Get current payment data.
		$payment = self::get( $payment_id );
		if ( ! $payment ) {
			return false;
		}

		// Calculate new refunded amount.
		$current_refunded   = floatval( $payment['refunded_amount'] ?? 0 );
		$new_total_refunded = $current_refunded + floatval( $refund_amount );

		// Update refunded amount.
		return self::update( $payment_id, [ 'refunded_amount' => $new_total_refunded ] );
	}

	/**
	 * Get refunded amount for a payment.
	 *
	 * @param int $payment_id Payment ID.
	 * @since 2.0.0
	 * @return float Refunded amount in dollars.
	 */
	public static function get_refunded_amount( $payment_id ) {
		if ( empty( $payment_id ) ) {
			return 0.0;
		}

		$payment = self::get( $payment_id );
		if ( ! $payment ) {
			return 0.0;
		}

		return floatval( $payment['refunded_amount'] ?? 0 );
	}

	/**
	 * Get refundable amount for a payment.
	 *
	 * @param int $payment_id Payment ID.
	 * @since 2.0.0
	 * @return float Remaining refundable amount in dollars.
	 */
	public static function get_refundable_amount( $payment_id ) {
		if ( empty( $payment_id ) ) {
			return 0.0;
		}

		$payment = self::get( $payment_id );
		if ( ! $payment ) {
			return 0.0;
		}

		$total_amount    = floatval( $payment['total_amount'] ?? 0 );
		$refunded_amount = floatval( $payment['refunded_amount'] ?? 0 );

		return max( 0, $total_amount - $refunded_amount );
	}

	/**
	 * Check if payment is fully refunded.
	 *
	 * @param int $payment_id Payment ID.
	 * @since 2.0.0
	 * @return bool True if fully refunded, false otherwise.
	 */
	public static function is_fully_refunded( $payment_id ) {
		if ( empty( $payment_id ) ) {
			return false;
		}

		$payment = self::get( $payment_id );
		if ( ! $payment ) {
			return false;
		}

		$total_amount    = floatval( $payment['total_amount'] ?? 0 );
		$refunded_amount = floatval( $payment['refunded_amount'] ?? 0 );

		return $refunded_amount >= $total_amount && $total_amount > 0;
	}

	/**
	 * Check if payment is partially refunded.
	 *
	 * @param int $payment_id Payment ID.
	 * @since 2.0.0
	 * @return bool True if partially refunded, false otherwise.
	 */
	public static function is_partially_refunded( $payment_id ) {
		if ( empty( $payment_id ) ) {
			return false;
		}

		$refunded_amount = self::get_refunded_amount( $payment_id );
		return $refunded_amount > 0 && ! self::is_fully_refunded( $payment_id );
	}

	/**
	 * Get all individual payment transactions related to a subscription.
	 *
	 * @param string $subscription_id Stripe subscription ID.
	 * @since 2.0.0
	 * @return array Array of payment records linked to the subscription.
	 */
	public static function get_subscription_related_payments( $subscription_id ) {
		if ( empty( $subscription_id ) ) {
			return [];
		}

		// Get all payments with the subscription_id.
		// This automatically includes.
		// 1. The initial subscription payment (type='subscription').
		// 2. All renewal payments (type='renewal') created by webhooks.
		return self::get_all(
			[
				'where'   => [
					[
						[
							'key'     => 'subscription_id',
							'compare' => '=',
							'value'   => sanitize_text_field( $subscription_id ),
						],
					],
				],
				'orderby' => 'created_at',
				'order'   => 'DESC',
			],
			false
		);
	}

	/**
	 * Get the main subscription record by subscription ID.
	 *
	 * @param string $subscription_id Stripe subscription ID.
	 * @since 2.0.0
	 * @return array|null Subscription payment record or null if not found.
	 */
	public static function get_main_subscription_record( $subscription_id ) {
		if ( empty( $subscription_id ) ) {
			return null;
		}

		$results = self::get_all(
			[
				'where' => [
					[
						[
							'key'     => 'subscription_id',
							'compare' => '=',
							'value'   => sanitize_text_field( $subscription_id ),
						],
						[
							'key'     => 'type',
							'compare' => '=',
							'value'   => 'subscription',
						],
					],
				],
				'limit' => 1,
			]
		);

		return $results[0] ?? null;
	}

	/**
	 * Get all payments for main payments table.
	 * Shows: ALL payment records (subscription, renewal, payment)
	 * No filtering applied by default - filters only come from frontend user selections
	 *
	 * @param array<mixed> $args Query arguments.
	 * @param bool         $set_limit Whether to apply limit to query.
	 * @since 2.0.0
	 * @return array Array of payments for main table display.
	 */
	public static function get_all_main_payments( $args = [], $set_limit = true ) {
		global $wpdb;

		$_args = wp_parse_args(
			$args,
			[
				'where'   => [],
				'columns' => '*',
				'limit'   => 20,
				'offset'  => 0,
				'orderby' => 'created_at',
				'order'   => 'DESC',
			]
		);

		$instance   = self::get_instance();
		$table_name = $instance->get_tablename();

		// No default filtering - show ALL transactions.
		// Filters are applied only from frontend user selections via 'where' conditions.
		$where_clause = 'WHERE 1=1';
		$params       = [];

		// Handle additional where conditions if provided.
		if ( ! empty( $_args['where'] ) ) {
			foreach ( $_args['where'] as $where_group ) {
				if ( is_array( $where_group ) ) {
					foreach ( $where_group as $condition ) {
						if ( isset( $condition['key'], $condition['compare'], $condition['value'] ) ) {
							if ( strtoupper( $condition['compare'] ) === 'IN' && is_array( $condition['value'] ) ) {
								$placeholders  = implode( ',', array_fill( 0, count( $condition['value'] ), '%d' ) );
								$where_clause .= " AND {$condition['key']} IN ({$placeholders})";
								foreach ( $condition['value'] as $val ) {
									$params[] = $val;
								}
							} else {
								$where_clause .= " AND {$condition['key']} {$condition['compare']} %s";
								$params[]      = $condition['value'];
							}
						}
					}
				}
			}
		}

		// Order by.
		$order   = strtoupper( $_args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
		$orderby = esc_sql( $_args['orderby'] );
		// Resolve phpstan: Only allow $orderby and $columns as string (not array), fallback to defaults if needed.
		$order_clause = '';
		if ( ! empty( $_args['orderby'] ) && is_string( $_args['orderby'] ) ) {
			$orderby      = esc_sql( $_args['orderby'] );
			$order_clause = "ORDER BY {$orderby} {$order}";
		} else {
			// Fallback to default order by created_at.
			$order_clause = "ORDER BY created_at {$order}";
		}

		// Limit clause.
		$limit_clause = '';
		if ( $set_limit ) {
			$limit_clause = $wpdb->prepare( 'LIMIT %d, %d', absint( $_args['offset'] ), absint( $_args['limit'] ) );
		}

		// Build final query.
		$columns = '*';
		if ( ! empty( $_args['columns'] ) && is_string( $_args['columns'] ) ) {
			$columns = '*' === $_args['columns'] ? '*' : esc_sql( $_args['columns'] );
		}
		$query = "SELECT {$columns} FROM {$table_name} {$where_clause} {$order_clause} {$limit_clause}";

		// Execute query with parameters.
		if ( ! empty( $params ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query string is built dynamically above based on conditions.
			$query = $wpdb->prepare( $query, $params );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Custom table query with dynamic preparation, caching not applicable for dynamic queries.
		$results = $wpdb->get_results( $query, ARRAY_A );

		return is_array( $results ) ? $results : [];
	}

	/**
	 * Get total payments count by status for main payments table.
	 * Counts: ALL payment records (no default filtering)
	 * Filters are applied only from frontend user selections
	 *
	 * @param string       $status Status to filter by ('all', 'pending', 'succeeded', etc.).
	 * @param int          $form_id Optional form ID to filter by.
	 * @param array<mixed> $where_conditions Optional additional where conditions.
	 * @since 2.0.0
	 * @return int Total count.
	 */
	public static function get_total_main_payments_by_status( $status = 'all', $form_id = 0, $where_conditions = [] ) {
		global $wpdb;

		$instance   = self::get_instance();
		$table_name = $instance->get_tablename();

		// No default filtering - count ALL transactions.
		$where_clause = '1=1';
		$params       = [];

		// Add status condition.
		if ( 'all' !== $status ) {
			$where_clause .= ' AND status = %s';
			$params[]      = sanitize_text_field( $status );
		}

		// Add form ID condition.
		if ( $form_id > 0 ) {
			$where_clause .= ' AND form_id = %d';
			$params[]      = absint( $form_id );
		}

		// Handle additional where conditions if provided.
		if ( ! empty( $where_conditions ) ) {
			foreach ( $where_conditions as $where_group ) {
				if ( is_array( $where_group ) ) {
					foreach ( $where_group as $condition ) {
						if ( isset( $condition['key'], $condition['compare'], $condition['value'] ) ) {
							// Special handling for IN/NOT IN with arrays.
							if ( in_array( strtoupper( trim( $condition['compare'] ) ), [ 'IN', 'NOT IN' ], true ) && is_array( $condition['value'] ) ) {
								$ids = array_map( 'absint', $condition['value'] );
								// Prevent empty IN ().
								if ( empty( $ids ) ) {
									$ids = [ 0 ];
								}
								$placeholders  = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
								$where_clause .= " AND {$condition['key']} {$condition['compare']} ({$placeholders})";
								foreach ( $ids as $id ) {
									$params[] = $id;
								}
							} else {
								$where_clause .= " AND {$condition['key']} {$condition['compare']} %s";
								$params[]      = $condition['value'];
							}
						}
					}
				}
			}
		}

		// Build and execute query.
		$query = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_clause}";

		if ( ! empty( $params ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query string is built dynamically above based on conditions.
			$query = $wpdb->prepare( $query, $params );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Custom table query with dynamic preparation, caching not applicable for count operations.
		$result = $wpdb->get_var( $query );

		return absint( $result );
	}

	/**
	 * Check if payment is a subscription record.
	 *
	 * @param int $payment_id Payment ID.
	 * @since 2.0.0
	 * @return bool True if it's a subscription record, false otherwise.
	 */
	public static function is_subscription_record( $payment_id ) {
		if ( empty( $payment_id ) ) {
			return false;
		}

		$payment = self::get( $payment_id );
		if ( ! $payment ) {
			return false;
		}

		return 'subscription' === ( $payment['type'] ?? '' );
	}

	/**
	 * Check if payment is a subscription-related individual payment transaction.
	 * These are payment records that have a subscription_id (part of a subscription billing cycle).
	 *
	 * @param int $payment_id Payment ID.
	 * @since 2.0.0
	 * @return bool True if it's a subscription-related payment transaction, false otherwise.
	 */
	public static function is_subscription_payment_transaction( $payment_id ) {
		if ( empty( $payment_id ) ) {
			return false;
		}

		$payment = self::get( $payment_id );
		if ( ! $payment ) {
			return false;
		}

		return 'payment' === ( $payment['type'] ?? '' ) && ! empty( $payment['subscription_id'] );
	}
}
