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
	];

	/**
	 * Valid currencies (ISO 4217).
	 *
	 * @var array<string>
	 * @since x.x.x
	 */
	private static $valid_currencies = [
		'USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF', 'CNY', 'SEK', 'NZD',
		'MXN', 'SGD', 'HKD', 'NOK', 'TRY', 'RUB', 'INR', 'BRL', 'ZAR', 'KRW',
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
			// Created date.
			'created_at'    => [
				'type' => 'datetime',
			],
			// Updated date.
			'updated_at'    => [
				'type' => 'datetime',
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
			'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
			'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
			'extra LONGTEXT',
            'log LONGTEXT',
		];
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

		return isset( $extra_data[ $sanitized_key ] ) ? $extra_data[ $sanitized_key ] : $default;
	}

	/**
	 * Validate payment data.
	 *
	 * @param array<string,mixed> $data Payment data to validate.
	 * @since x.x.x
	 * @return array<string> Array of validation errors, empty if valid.
	 */
	public static function validate( $data ) {
		$errors = [];

		// Required fields validation.
		if ( empty( $data['form_id'] ) || ! is_numeric( $data['form_id'] ) ) {
			$errors[] = 'Form ID is required and must be numeric.';
		}

		// Status validation.
		if ( ! empty( $data['status'] ) && ! in_array( $data['status'], self::$valid_statuses, true ) ) {
			$errors[] = 'Invalid payment status. Must be one of: ' . implode( ', ', self::$valid_statuses );
		}

		// Currency validation.
		if ( ! empty( $data['currency'] ) && ! self::is_valid_currency( $data['currency'] ) ) {
			$errors[] = 'Invalid currency code. Must be a valid ISO 4217 currency code.';
		}

		// Gateway validation.
		if ( ! empty( $data['gateway'] ) && ! in_array( $data['gateway'], self::$valid_gateways, true ) ) {
			$errors[] = 'Invalid payment gateway. Currently only Stripe is supported.';
		}

		// Amount validation.
		foreach ( [ 'subtotal_amount', 'discount_amount', 'total_amount' ] as $amount_field ) {
			if ( isset( $data[ $amount_field ] ) && ! is_numeric( $data[ $amount_field ] ) ) {
				$field_name = ucwords( str_replace( '_', ' ', $amount_field ) );
				$errors[] = $field_name . ' must be numeric.';
			}
		}

		// Mode validation.
		if ( ! empty( $data['mode'] ) && ! in_array( $data['mode'], self::$valid_modes, true ) ) {
			$errors[] = 'Payment mode must be either "test" or "live".';
		}

		// Entry ID validation.
		if ( isset( $data['entry_id'] ) && ! is_numeric( $data['entry_id'] ) ) {
			$errors[] = 'Entry ID must be numeric.';
		}

		// Extra data validation (JSON).
		if ( isset( $data['extra'] ) && ! is_array( $data['extra'] ) ) {
			$errors[] = 'Extra data must be an array.';
		}

		return $errors;
	}

	/**
	 * Sanitize payment data.
	 *
	 * @param array<string,mixed> $data Payment data to sanitize.
	 * @since x.x.x
	 * @return array<string,mixed> Sanitized data.
	 */
	public static function sanitize( $data ) {
		$sanitized = [];

		// Sanitize numeric fields.
		foreach ( [ 'form_id', 'entry_id', 'is_published' ] as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$sanitized[ $field ] = absint( $data[ $field ] );
			}
		}

		// Sanitize text fields.
		foreach ( [ 'status', 'currency', 'gateway', 'type', 'mode', 'transaction_id', 'customer_id', 'subscription_id', 'subscription_status' ] as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$sanitized[ $field ] = sanitize_text_field( $data[ $field ] );
			}
		}

		// Sanitize amount fields.
		foreach ( [ 'subtotal_amount', 'discount_amount', 'total_amount' ] as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$sanitized[ $field ] = number_format( (float) $data[ $field ], 8, '.', '' );
			}
		}

		// Sanitize title.
		if ( isset( $data['title'] ) ) {
			$sanitized['title'] = sanitize_text_field( $data['title'] );
		}

		// Handle datetime fields.
		if ( isset( $data['date_created_gmt'] ) ) {
			$sanitized['date_created_gmt'] = sanitize_text_field( $data['date_created_gmt'] );
		} else {
			$sanitized['date_created_gmt'] = current_time( 'mysql', true );
		}

		if ( isset( $data['date_updated_gmt'] ) ) {
			$sanitized['date_updated_gmt'] = sanitize_text_field( $data['date_updated_gmt'] );
		} else {
			$sanitized['date_updated_gmt'] = current_time( 'mysql', true );
		}

		// Handle extra data (ensure it's an array).
		if ( isset( $data['extra'] ) ) {
			$sanitized['extra'] = is_array( $data['extra'] ) ? $data['extra'] : [];
		}

		return $sanitized;
	}

	/**
	 * Format payment data for safe output.
	 *
	 * @param array<string,mixed> $data Payment data to format.
	 * @since x.x.x
	 * @return array<string,mixed> Formatted data.
	 */
	public static function format_for_output( $data ) {
		if ( empty( $data ) || ! is_array( $data ) ) {
			return [];
		}

		$formatted = [];

		// Escape string fields for output.
		foreach ( $data as $key => $value ) {
			switch ( $key ) {
				case 'title':
					$formatted[ $key ] = esc_html( $value );
					break;
				case 'subtotal_amount':
				case 'discount_amount':
				case 'total_amount':
					$formatted[ $key ] = number_format( (float) $value, 2 );
					break;
				case 'extra':
					$formatted[ $key ] = is_array( $value ) ? $value : Helper::get_array_value( $value );
					break;
				default:
					$formatted[ $key ] = is_string( $value ) ? esc_attr( $value ) : $value;
					break;
			}
		}

		return $formatted;
	}

	/**
	 * Calculate total amount from subtotal and discount.
	 *
	 * @param float $subtotal Subtotal amount.
	 * @param float $discount Discount amount.
	 * @since x.x.x
	 * @return float Calculated total.
	 */
	public static function calculate_total( $subtotal, $discount = 0.0 ) {
		$subtotal = max( 0, (float) $subtotal );
		$discount = max( 0, (float) $discount );
		return max( 0, $subtotal - $discount );
	}

	/**
	 * Check if currency code is valid.
	 *
	 * @param string $currency Currency code to validate.
	 * @since x.x.x
	 * @return bool True if valid, false otherwise.
	 */
	public static function is_valid_currency( $currency ) {
		return in_array( strtoupper( $currency ), self::$valid_currencies, true );
	}

	/**
	 * Log payment action for audit trail.
	 *
	 * @param string              $action Payment action performed.
	 * @param int                 $payment_id Payment ID.
	 * @param array<string,mixed> $details Additional details.
	 * @since x.x.x
	 * @return bool True on success, false on failure.
	 */
	public static function log_payment_action( $action, $payment_id, $details = [] ) {
		$log_entry = [
			'action'     => sanitize_text_field( $action ),
			'payment_id' => absint( $payment_id ),
			'details'    => $details,
			'timestamp'  => current_time( 'timestamp' ),
			'user_id'    => get_current_user_id(),
		];

		// Log to WordPress error log.
		error_log( 'SureForms Payment Action: ' . wp_json_encode( $log_entry ) );

		// Fire action for external logging systems.
		do_action( 'srfm_payment_action_logged', $log_entry );

		return true;
	}

	/**
	 * Add a new payment record.
	 *
	 * @param array<string,mixed> $data Payment data.
	 * @since x.x.x
	 * @return int|false Payment ID on success, false on failure.
	 */
	public static function insert( $data ) {
		// Validate data.
		$errors = self::validate( $data );
		if ( ! empty( $errors ) ) {
			self::log_payment_action( 'insert_failed', 0, [ 'errors' => $errors ] );
			return false;
		}

		// Sanitize data.
		$data = self::sanitize( $data );

		// Calculate total if not provided.
		if ( ! isset( $data['total_amount'] ) && isset( $data['subtotal_amount'], $data['discount_amount'] ) ) {
			$data['total_amount'] = number_format(
				self::calculate_total( $data['subtotal_amount'], $data['discount_amount'] ),
				8,
				'.',
				''
			);
		}

		// Remove ID if exists (inserting new record).
		if ( isset( $data['id'] ) ) {
			unset( $data['id'] );
		}

		$instance = self::get_instance();
		$result   = $instance->use_insert( $data );

		if ( $result ) {
			self::log_payment_action( 'insert_success', $result, [ 'data' => $data ] );
		} else {
			self::log_payment_action( 'insert_failed', 0, [ 'data' => $data ] );
		}

		return $result;
	}

	/**
	 * Insert multiple payment records in batch.
	 *
	 * @param array<array<string,mixed>> $payments Array of payment data.
	 * @since x.x.x
	 * @return array<int> Array of inserted payment IDs.
	 */
	public static function insert_batch( $payments ) {
		if ( empty( $payments ) || ! is_array( $payments ) ) {
			return [];
		}

		$inserted_ids = [];

		// Use transaction for batch insert.
		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );

		try {
			foreach ( $payments as $payment_data ) {
				$payment_id = self::insert( $payment_data );
				if ( $payment_id ) {
					$inserted_ids[] = $payment_id;
				} else {
					// Rollback on any failure.
					$wpdb->query( 'ROLLBACK' );
					self::log_payment_action( 'batch_insert_failed', 0, [ 'failed_at' => count( $inserted_ids ) ] );
					return [];
				}
			}
			$wpdb->query( 'COMMIT' );
			self::log_payment_action( 'batch_insert_success', 0, [ 'count' => count( $inserted_ids ) ] );
		} catch ( \Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			self::log_payment_action( 'batch_insert_exception', 0, [ 'error' => $e->getMessage() ] );
			return [];
		}

		return $inserted_ids;
	}

	/**
	 * Get payment by ID.
	 *
	 * @param int $payment_id Payment ID.
	 * @since x.x.x
	 * @return array<string,mixed> Payment data or empty array.
	 */
	public static function get_by_id( $payment_id ) {
		if ( empty( $payment_id ) ) {
			return [];
		}

		$results = self::get_instance()->get_results(
			[
				'id' => absint( $payment_id ),
			]
		);

		return isset( $results[0] ) ? Helper::get_array_value( $results[0] ) : [];
	}

	/**
	 * Get payments by form ID.
	 *
	 * @param int                 $form_id Form ID.
	 * @param array<string,mixed> $args    Additional arguments.
	 * @since x.x.x
	 * @return array<array<string,mixed>> Array of payments.
	 */
	public static function get_by_form_id( $form_id, $args = [] ) {
		if ( empty( $form_id ) ) {
			return [];
		}

		$default_args = [
			'limit'   => 10,
			'offset'  => 0,
			'orderby' => 'date_created_gmt',
			'order'   => 'DESC',
		];

		$args = wp_parse_args( $args, $default_args );

		$where_clauses = [ 'form_id' => absint( $form_id ) ];

		// Add status filter if provided.
		if ( ! empty( $args['status'] ) ) {
			$where_clauses['status'] = sanitize_text_field( $args['status'] );
		}

		$extra_queries = [
			sprintf( 'ORDER BY `%1$s` %2$s', esc_sql( $args['orderby'] ), esc_sql( $args['order'] ) ),
			sprintf( 'LIMIT %1$d, %2$d', absint( $args['offset'] ), absint( $args['limit'] ) ),
		];

		return self::get_instance()->get_results( $where_clauses, '*', $extra_queries );
	}

	/**
	 * Get payments by entry ID.
	 *
	 * @param int $entry_id Entry ID.
	 * @since x.x.x
	 * @return array<array<string,mixed>> Array of payments.
	 */
	public static function get_by_entry_id( $entry_id ) {
		if ( empty( $entry_id ) ) {
			return [];
		}

		return self::get_instance()->get_results(
			[
				'entry_id' => absint( $entry_id ),
			],
			'*',
			[ 'ORDER BY date_created_gmt DESC' ]
		);
	}

	/**
	 * Get payment by transaction ID.
	 *
	 * @param string $transaction_id Transaction ID.
	 * @since x.x.x
	 * @return array<string,mixed> Payment data or empty array.
	 */
	public static function get_by_transaction_id( $transaction_id ) {
		if ( empty( $transaction_id ) ) {
			return [];
		}

		$results = self::get_instance()->get_results(
			[
				'transaction_id' => sanitize_text_field( $transaction_id ),
			]
		);

		return isset( $results[0] ) ? Helper::get_array_value( $results[0] ) : [];
	}

	/**
	 * Get payments by customer ID.
	 *
	 * @param string              $customer_id Customer ID.
	 * @param array<string,mixed> $args        Additional arguments.
	 * @since x.x.x
	 * @return array<array<string,mixed>> Array of payments.
	 */
	public static function get_by_customer_id( $customer_id, $args = [] ) {
		if ( empty( $customer_id ) ) {
			return [];
		}

		$default_args = [
			'limit'   => 10,
			'offset'  => 0,
			'orderby' => 'date_created_gmt',
			'order'   => 'DESC',
		];

		$args = wp_parse_args( $args, $default_args );

		$extra_queries = [
			sprintf( 'ORDER BY `%1$s` %2$s', esc_sql( $args['orderby'] ), esc_sql( $args['order'] ) ),
			sprintf( 'LIMIT %1$d, %2$d', absint( $args['offset'] ), absint( $args['limit'] ) ),
		];

		return self::get_instance()->get_results(
			[
				'customer_id' => sanitize_text_field( $customer_id ),
			],
			'*',
			$extra_queries
		);
	}

	/**
	 * Get payments by status.
	 *
	 * @param string              $status Payment status.
	 * @param array<string,mixed> $args   Additional arguments.
	 * @since x.x.x
	 * @return array<array<string,mixed>> Array of payments.
	 */
	public static function get_by_status( $status, $args = [] ) {
		if ( empty( $status ) || ! in_array( $status, self::$valid_statuses, true ) ) {
			return [];
		}

		$default_args = [
			'limit'   => 10,
			'offset'  => 0,
			'orderby' => 'date_created_gmt',
			'order'   => 'DESC',
		];

		$args = wp_parse_args( $args, $default_args );

		$extra_queries = [
			sprintf( 'ORDER BY `%1$s` %2$s', esc_sql( $args['orderby'] ), esc_sql( $args['order'] ) ),
			sprintf( 'LIMIT %1$d, %2$d', absint( $args['offset'] ), absint( $args['limit'] ) ),
		];

		return self::get_instance()->get_results(
			[
				'status' => sanitize_text_field( $status ),
			],
			'*',
			$extra_queries
		);
	}

	/**
	 * Get all payments with pagination.
	 *
	 * @param int                 $limit  Number of records to return.
	 * @param int                 $offset Number of records to skip.
	 * @param array<string,mixed> $args   Additional arguments.
	 * @since x.x.x
	 * @return array<array<string,mixed>> Array of payments.
	 */
	public static function get_all( $limit = 10, $offset = 0, $args = [] ) {
		$default_args = [
			'orderby' => 'date_created_gmt',
			'order'   => 'DESC',
			'where'   => [],
		];

		$args = wp_parse_args( $args, $default_args );

		$extra_queries = [
			sprintf( 'ORDER BY `%1$s` %2$s', esc_sql( $args['orderby'] ), esc_sql( $args['order'] ) ),
			sprintf( 'LIMIT %1$d, %2$d', absint( $offset ), absint( $limit ) ),
		];

		return self::get_instance()->get_results( $args['where'], '*', $extra_queries );
	}

	/**
	 * Search payments with advanced criteria.
	 *
	 * @param array<string,mixed> $criteria Search criteria.
	 * @since x.x.x
	 * @return array<array<string,mixed>> Array of payments.
	 */
	public static function search( $criteria ) {
		if ( empty( $criteria ) || ! is_array( $criteria ) ) {
			return [];
		}

		$where_clauses = [];
		$extra_queries = [];

		// Handle different search criteria.
		if ( ! empty( $criteria['form_id'] ) ) {
			$where_clauses['form_id'] = absint( $criteria['form_id'] );
		}

		if ( ! empty( $criteria['status'] ) && in_array( $criteria['status'], self::$valid_statuses, true ) ) {
			$where_clauses['status'] = sanitize_text_field( $criteria['status'] );
		}

		if ( ! empty( $criteria['gateway'] ) ) {
			$where_clauses['gateway'] = sanitize_text_field( $criteria['gateway'] );
		}

		if ( ! empty( $criteria['currency'] ) ) {
			$where_clauses['currency'] = sanitize_text_field( $criteria['currency'] );
		}

		// Date range search.
		if ( ! empty( $criteria['date_from'] ) || ! empty( $criteria['date_to'] ) ) {
			$date_clauses = [];

			if ( ! empty( $criteria['date_from'] ) ) {
				$date_clauses[] = [
					'key'     => 'date_created_gmt',
					'compare' => '>=',
					'value'   => sanitize_text_field( $criteria['date_from'] ),
				];
			}

			if ( ! empty( $criteria['date_to'] ) ) {
				$date_clauses[] = [
					'key'     => 'date_created_gmt',
					'compare' => '<=',
					'value'   => sanitize_text_field( $criteria['date_to'] ),
				];
			}

			if ( ! empty( $date_clauses ) ) {
				$where_clauses[] = $date_clauses;
			}
		}

		// Amount range search.
		if ( ! empty( $criteria['amount_min'] ) || ! empty( $criteria['amount_max'] ) ) {
			$amount_clauses = [];

			if ( ! empty( $criteria['amount_min'] ) && is_numeric( $criteria['amount_min'] ) ) {
				$amount_clauses[] = [
					'key'     => 'total_amount',
					'compare' => '>=',
					'value'   => number_format( (float) $criteria['amount_min'], 8, '.', '' ),
				];
			}

			if ( ! empty( $criteria['amount_max'] ) && is_numeric( $criteria['amount_max'] ) ) {
				$amount_clauses[] = [
					'key'     => 'total_amount',
					'compare' => '<=',
					'value'   => number_format( (float) $criteria['amount_max'], 8, '.', '' ),
				];
			}

			if ( ! empty( $amount_clauses ) ) {
				$where_clauses[] = $amount_clauses;
			}
		}

		// Ordering.
		$orderby = ! empty( $criteria['orderby'] ) ? sanitize_text_field( $criteria['orderby'] ) : 'date_created_gmt';
		$order   = ! empty( $criteria['order'] ) && in_array( strtoupper( $criteria['order'] ), [ 'ASC', 'DESC' ], true ) ? strtoupper( $criteria['order'] ) : 'DESC';
		$extra_queries[] = sprintf( 'ORDER BY `%1$s` %2$s', esc_sql( $orderby ), esc_sql( $order ) );

		// Pagination.
		if ( isset( $criteria['limit'] ) && is_numeric( $criteria['limit'] ) ) {
			$limit  = absint( $criteria['limit'] );
			$offset = isset( $criteria['offset'] ) && is_numeric( $criteria['offset'] ) ? absint( $criteria['offset'] ) : 0;
			$extra_queries[] = sprintf( 'LIMIT %1$d, %2$d', $offset, $limit );
		}

		return self::get_instance()->get_results( $where_clauses, '*', $extra_queries );
	}

	/**
	 * Update payment record.
	 *
	 * @param int                 $payment_id Payment ID.
	 * @param array<string,mixed> $data       Data to update.
	 * @since x.x.x
	 * @return int|false Number of rows updated or false on error.
	 */
	public static function update( $payment_id, $data ) {
		if ( empty( $payment_id ) || empty( $data ) ) {
			return false;
		}

		// Validate data.
		$errors = self::validate( array_merge( [ 'form_id' => 1 ], $data ) ); // Add dummy form_id for validation.
		if ( ! empty( $errors ) ) {
			self::log_payment_action( 'update_failed', $payment_id, [ 'errors' => $errors ] );
			return false;
		}

		// Sanitize data.
		$data = self::sanitize( $data );

		// Always update the updated date.
		$data['date_updated_gmt'] = current_time( 'mysql', true );

		// Handle extra data merge (preserve existing data).
		if ( isset( $data['extra'] ) ) {
			$existing_extra = self::get_extra_data( $payment_id );
			$data['extra']  = array_merge( $existing_extra, $data['extra'] );
		}

		$result = self::get_instance()->use_update( $data, [ 'id' => absint( $payment_id ) ] );

		if ( $result ) {
			self::log_payment_action( 'update_success', $payment_id, [ 'data' => $data ] );
		} else {
			self::log_payment_action( 'update_failed', $payment_id, [ 'data' => $data ] );
		}

		return $result;
	}

	/**
	 * Update payment status.
	 *
	 * @param int    $payment_id Payment ID.
	 * @param string $status     New status.
	 * @since x.x.x
	 * @return int|false Number of rows updated or false on error.
	 */
	public static function update_status( $payment_id, $status ) {
		if ( empty( $payment_id ) || empty( $status ) ) {
			return false;
		}

		if ( ! in_array( $status, self::$valid_statuses, true ) ) {
			return false;
		}

		return self::update( $payment_id, [ 'status' => $status ] );
	}

	/**
	 * Update payment amounts.
	 *
	 * @param int                 $payment_id Payment ID.
	 * @param array<string,float> $amounts    Array of amounts to update.
	 * @since x.x.x
	 * @return int|false Number of rows updated or false on error.
	 */
	public static function update_amounts( $payment_id, $amounts ) {
		if ( empty( $payment_id ) || empty( $amounts ) ) {
			return false;
		}

		$update_data = [];

		// Validate and format amounts.
		foreach ( [ 'subtotal_amount', 'discount_amount', 'total_amount' ] as $field ) {
			if ( isset( $amounts[ $field ] ) && is_numeric( $amounts[ $field ] ) ) {
				$update_data[ $field ] = number_format( (float) $amounts[ $field ], 8, '.', '' );
			}
		}

		if ( empty( $update_data ) ) {
			return false;
		}

		return self::update( $payment_id, $update_data );
	}

	/**
	 * Bulk update payments.
	 *
	 * @param array<string,mixed> $conditions Update conditions.
	 * @param array<string,mixed> $data       Data to update.
	 * @since x.x.x
	 * @return int|false Number of rows updated or false on error.
	 */
	public static function bulk_update( $conditions, $data ) {
		if ( empty( $conditions ) || empty( $data ) ) {
			return false;
		}

		// Sanitize conditions and data.
		$conditions = self::sanitize( $conditions );
		$data       = self::sanitize( $data );

		// Always update the updated date.
		$data['date_updated_gmt'] = current_time( 'mysql', true );

		$result = self::get_instance()->use_update( $data, $conditions );

		self::log_payment_action( 'bulk_update', 0, [ 'conditions' => $conditions, 'data' => $data, 'affected_rows' => $result ] );

		return $result;
	}

	/**
	 * Hard delete payment record.
	 *
	 * @param int $payment_id Payment ID.
	 * @since x.x.x
	 * @return int|false Number of rows deleted or false on error.
	 */
	public static function delete( $payment_id ) {
		if ( empty( $payment_id ) ) {
			return false;
		}

		// Get payment data before deletion for logging.
		$payment_data = self::get_by_id( $payment_id );

		// Add action before deleting the payment.
		do_action( 'srfm_before_delete_payment', $payment_id, $payment_data );

		// Delete the payment.
		$result = self::get_instance()->use_delete( [ 'id' => absint( $payment_id ) ], [ '%d' ] );

		if ( $result ) {
			self::log_payment_action( 'delete_success', $payment_id, [ 'deleted_data' => $payment_data ] );
			do_action( 'srfm_after_delete_payment', $payment_id, $payment_data );
		} else {
			self::log_payment_action( 'delete_failed', $payment_id );
		}

		return $result;
	}

	/**
	 * Soft delete payment record (set is_published to 0).
	 *
	 * @param int $payment_id Payment ID.
	 * @since x.x.x
	 * @return int|false Number of rows updated or false on error.
	 */
	public static function soft_delete( $payment_id ) {
		if ( empty( $payment_id ) ) {
			return false;
		}

		$result = self::update( $payment_id, [ 'is_published' => 0 ] );

		if ( $result ) {
			self::log_payment_action( 'soft_delete_success', $payment_id );
		} else {
			self::log_payment_action( 'soft_delete_failed', $payment_id );
		}

		return $result;
	}

	/**
	 * Delete payments by form ID.
	 *
	 * @param int  $form_id     Form ID.
	 * @param bool $soft_delete Whether to soft delete or hard delete.
	 * @since x.x.x
	 * @return int|false Number of rows affected or false on error.
	 */
	public static function delete_by_form_id( $form_id, $soft_delete = true ) {
		if ( empty( $form_id ) ) {
			return false;
		}

		if ( $soft_delete ) {
			$result = self::bulk_update(
				[ 'form_id' => absint( $form_id ) ],
				[ 'is_published' => 0 ]
			);
			self::log_payment_action( 'soft_delete_by_form', 0, [ 'form_id' => $form_id, 'affected_rows' => $result ] );
		} else {
			// Get payments before deletion for logging.
			$payments = self::get_by_form_id( $form_id, [ 'limit' => 9999 ] );

			// Add action before deleting payments.
			do_action( 'srfm_before_delete_payments_by_form', $form_id, $payments );

			$result = self::get_instance()->use_delete( [ 'form_id' => absint( $form_id ) ], [ '%d' ] );

			if ( $result ) {
				self::log_payment_action( 'delete_by_form_success', 0, [ 'form_id' => $form_id, 'affected_rows' => $result ] );
				do_action( 'srfm_after_delete_payments_by_form', $form_id, $payments );
			} else {
				self::log_payment_action( 'delete_by_form_failed', 0, [ 'form_id' => $form_id ] );
			}
		}

		return $result;
	}
}