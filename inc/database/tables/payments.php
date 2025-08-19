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

	public static function update( $entry_id, $data = [] ) {
		if ( empty( $entry_id ) ) {
			return false;
		}

		return self::get_instance()->use_update( $data, [ 'ID' => absint( $entry_id ) ] );
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
}
