<?php
/**
 * SureForms Database Payment Table Class.
 *
 * @link       https://sureforms.com
 * @since      x.x.x
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
 * @since x.x.x
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
	 * @since x.x.x
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
	];

	/**
	 * Valid currencies (ISO 4217).
	 *
	 * @var array<string>
	 * @since x.x.x
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
	 * @since x.x.x
	 */
	private static $valid_gateways = [
		'stripe',
	];

	/**
	 * Valid payment modes.
	 *
	 * @var array<string>
	 * @since x.x.x
	 */
	private static $valid_modes = [
		'test',
		'live',
	];

	/**
	 * {@inheritDoc}
	 */
	public function get_schema() {
		return [
			// Payment ID.
			'id'                  => [
				'type' => 'number',
			],
			// Form ID.
			'form_id'             => [
				'type' => 'number',
			],
			'block_id'            => [
				'type'    => 'string',
				'default' => '',
			],
			// Payment status (Stripe).
			'status'              => [
				'type'    => 'string',
				'default' => 'pending',
			],
			// Total amount after discount.
			'total_amount'        => [
				'type'    => 'string',
				'default' => '0.00000000',
			],
			// Currency code.
			'currency'            => [
				'type'    => 'string',
				'default' => '',
			],
			// Entry ID.
			'entry_id'            => [
				'type'    => 'number',
				'default' => 0,
			],
			// Payment gateway.
			'gateway'             => [
				'type'    => 'string',
				'default' => '',
			],
			// Payment type.
			'type'                => [
				'type'    => 'string',
				'default' => '',
			],
			// Payment mode (test/live).
			'mode'                => [
				'type'    => 'string',
				'default' => '',
			],
			// Transaction ID from gateway.
			'transaction_id'      => [
				'type'    => 'string',
				'default' => '',
			],
			// Customer ID from gateway.
			'customer_id'         => [
				'type'    => 'string',
				'default' => '',
			],
			// Subscription ID (if recurring).
			'subscription_id'     => [
				'type'    => 'string',
				'default' => '',
			],
			// Subscription status.
			'subscription_status' => [
				'type'    => 'string',
				'default' => '',
			],
			// Payment data.
			'payment_data'        => [
				'type'    => 'array',
				'default' => [],
			],
			// Extra data (JSON).
			'extra'               => [
				'type'    => 'array',
				'default' => [],
			],
			// Payment log.
			'log'                 => [
				'type'    => 'array',
				'default' => [],
			],
			// Created date.
			'created_at'          => [
				'type' => 'datetime',
			],
			// Updated date.
			'updated_at'          => [
				'type' => 'datetime',
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
			'status VARCHAR(10) NOT NULL',
			'total_amount DECIMAL(26,8) NOT NULL',
			'currency VARCHAR(3) NOT NULL',
			'entry_id BIGINT(20) UNSIGNED NOT NULL',
			'gateway VARCHAR(20) NOT NULL',
			'type VARCHAR(12) NOT NULL',
			'mode VARCHAR(4) NOT NULL',
			'transaction_id VARCHAR(40) NOT NULL',
			'customer_id VARCHAR(40) NOT NULL',
			'subscription_id VARCHAR(40) NOT NULL',
			'subscription_status VARCHAR(10) NOT NULL',
			'payment_data LONGTEXT',
			'extra LONGTEXT',
			'log LONGTEXT',
			'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
			'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
		];
	}

	public static function add( $data ) {

		$instance = self::get_instance();

		return $instance->use_insert( $data );
	}

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
	 * @since x.x.x
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
	 * @since x.x.x
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
	 * @since x.x.x
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
	 * @since x.x.x
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
	 * @since x.x.x
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
	 * @since x.x.x
	 * @return array|null Payment data or null if not found.
	 */
	public static function get( $payment_id ) {
		if ( empty( $payment_id ) ) {
			return null;
		}

		$results = self::get_instance()->get_results( [ 'id' => absint( $payment_id ) ] );
		return isset( $results[0] ) ? $results[0] : null;
	}

	/**
	 * Get all payments with optional parameters.
	 *
	 * @param array $args Query arguments.
	 * @param bool  $set_limit Whether to apply limit to query.
	 * @since x.x.x
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
	 * @param string $status Status to filter by ('all', 'pending', 'succeeded', etc.).
	 * @param int    $form_id Optional form ID to filter by.
	 * @param array  $where_conditions Optional additional where conditions.
	 * @since x.x.x
	 * @return int Total count.
	 */
	public static function get_total_payments_by_status( $status = 'all', $form_id = 0, $where_conditions = [] ) {
		$instance = self::get_instance();
		$where    = [];

		// Add status condition
		if ( 'all' !== $status ) {
			$where[] = [
				[
					'key'     => 'status',
					'compare' => '=',
					'value'   => sanitize_text_field( $status ),
				],
			];
		}

		// Add form ID condition
		if ( $form_id > 0 ) {
			$where[] = [
				[
					'key'     => 'form_id',
					'compare' => '=',
					'value'   => absint( $form_id ),
				],
			];
		}

		// Add additional where conditions
		if ( ! empty( $where_conditions ) ) {
			$where = array_merge( $where, $where_conditions );
		}

		return $instance->get_total_count( $where );
	}

	/**
	 * Get payments count after specific timestamp.
	 *
	 * @param int $timestamp Unix timestamp.
	 * @since x.x.x
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
	 * @param array $where_conditions Optional where conditions.
	 * @since x.x.x
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
	 * @since x.x.x
	 * @return array Array of payment IDs.
	 */
	public static function get_all_payment_ids_for_form( $form_id ) {
		if ( empty( $form_id ) ) {
			return [];
		}

		$instance = self::get_instance();
		$results  = $instance->get_results(
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

		return $results;
	}

	/**
	 * Get form IDs by payment IDs.
	 *
	 * @param array $payment_ids Array of payment IDs.
	 * @since x.x.x
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
	 * Delete a payment.
	 *
	 * @param int $payment_id Payment ID.
	 * @since x.x.x
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
	 * @since x.x.x
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
	 * @since x.x.x
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

		return isset( $results[0] ) ? $results[0] : null;
	}

	/**
	 * Validate payment status.
	 *
	 * @param string $status Status to validate.
	 * @since x.x.x
	 * @return bool True if valid, false otherwise.
	 */
	public static function is_valid_status( $status ) {
		return in_array( $status, self::$valid_statuses, true );
	}

	/**
	 * Validate currency.
	 *
	 * @param string $currency Currency to validate.
	 * @since x.x.x
	 * @return bool True if valid, false otherwise.
	 */
	public static function is_valid_currency( $currency ) {
		return in_array( strtoupper( $currency ), self::$valid_currencies, true );
	}

	/**
	 * Validate gateway.
	 *
	 * @param string $gateway Gateway to validate.
	 * @since x.x.x
	 * @return bool True if valid, false otherwise.
	 */
	public static function is_valid_gateway( $gateway ) {
		return in_array( $gateway, self::$valid_gateways, true );
	}

	/**
	 * Validate mode.
	 *
	 * @param string $mode Mode to validate.
	 * @since x.x.x
	 * @return bool True if valid, false otherwise.
	 */
	public static function is_valid_mode( $mode ) {
		return in_array( $mode, self::$valid_modes, true );
	}
}
