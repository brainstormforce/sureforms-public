<?php
/**
 * Global Payment Helper functions for SureForms Payments.
 *
 * This class handles payment settings and operations that are common across
 * all payment gateways (Stripe, PayPal, etc.). Gateway-specific logic should
 * be in their respective helper classes.
 *
 * @package sureforms
 * @since 2.0.0
 */

namespace SRFM\Inc\Payments;

use SRFM\Inc\Field_Validation;
use SRFM\Inc\Payments\Stripe\Stripe_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Global Payment Helper class for multi-gateway support.
 *
 * @since 2.0.0
 */
class Payment_Helper {
	/**
	 * Get all payment settings (global settings + all gateways).
	 *
	 * Retrieves the complete payment settings structure:
	 * payment_settings -> [currency, payment_mode, stripe, paypal, etc]
	 *
	 * @since 2.0.0
	 * @return array<string, mixed> The complete payment settings array.
	 */
	public static function get_all_payment_settings() {
		$payment_settings = \SRFM\Inc\Helper::get_srfm_option( 'payment_settings', [] );

		if ( ! is_array( $payment_settings ) || empty( $payment_settings ) ) {
			return self::get_default_payment_settings();
		}

		// Ensure required keys exist.
		if ( ! isset( $payment_settings['currency'] ) ) {
			$payment_settings['currency'] = 'USD';
		}

		if ( ! isset( $payment_settings['payment_mode'] ) ) {
			$payment_settings['payment_mode'] = 'test';
		}

		if ( ! isset( $payment_settings['stripe'] ) ) {
			$payment_settings['stripe'] = Stripe_Helper::get_default_stripe_settings();
		}

		return $payment_settings;
	}

	/**
	 * Update all payment settings.
	 *
	 * Stores the complete payment settings array in:
	 * srfm_options -> payment_settings
	 *
	 * @param array<string, mixed> $settings The complete payment settings array.
	 * @since 2.0.0
	 * @return bool True on success, false on failure.
	 */
	public static function update_payment_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			return false;
		}

		\SRFM\Inc\Helper::update_srfm_option( 'payment_settings', $settings );
		return true;
	}

	/**
	 * Get settings for a specific payment gateway.
	 *
	 * @param string $gateway Gateway identifier (e.g., 'stripe', 'paypal').
	 * @since 2.0.0
	 * @return array<string, mixed> Gateway settings array, or empty array if not found.
	 */
	public static function get_gateway_settings( $gateway ) {
		if ( ! is_string( $gateway ) || empty( $gateway ) ) {
			return [];
		}

		$payment_settings = self::get_all_payment_settings();

		return isset( $payment_settings[ $gateway ] ) && is_array( $payment_settings[ $gateway ] )
			? $payment_settings[ $gateway ]
			: [];
	}

	/**
	 * Update settings for a specific payment gateway.
	 *
	 * @param string               $gateway  Gateway identifier (e.g., 'stripe', 'paypal').
	 * @param array<string, mixed> $settings Gateway settings to save.
	 * @since 2.0.0
	 * @return bool True on success, false on failure.
	 */
	public static function update_gateway_settings( $gateway, $settings ) {
		if ( ! is_string( $gateway ) || empty( $gateway ) || ! is_array( $settings ) ) {
			return false;
		}

		$payment_settings             = self::get_all_payment_settings();
		$payment_settings[ $gateway ] = $settings;

		return self::update_payment_settings( $payment_settings );
	}

	/**
	 * Get a global payment setting (currency or payment_mode).
	 *
	 * @param string $key     Setting key (e.g., 'currency', 'payment_mode').
	 * @param mixed  $default Default value if setting not found.
	 * @since 2.0.0
	 * @return mixed Setting value or default.
	 */
	public static function get_global_setting( $key, $default = '' ) {
		if ( ! is_string( $key ) || empty( $key ) ) {
			return $default;
		}

		$payment_settings = self::get_all_payment_settings();

		return $payment_settings[ $key ] ?? $default;
	}

	/**
	 * Update a global payment setting (currency or payment_mode).
	 *
	 * @param string $key   Setting key to update.
	 * @param mixed  $value Value to set.
	 * @since 2.0.0
	 * @return bool True on success, false on failure.
	 */
	public static function update_global_setting( $key, $value ) {
		if ( ! is_string( $key ) || empty( $key ) ) {
			return false;
		}

		$payment_settings         = self::get_all_payment_settings();
		$payment_settings[ $key ] = $value;

		return self::update_payment_settings( $payment_settings );
	}

	/**
	 * Get the default currency.
	 *
	 * @since 2.0.0
	 * @return string The currency code (e.g., 'USD').
	 */
	public static function get_currency() {
		$response = self::get_global_setting( 'currency', 'USD' );
		return ! empty( $response ) && is_string( $response ) ? $response : 'USD';
	}

	/**
	 * Get the current payment mode (test or live).
	 *
	 * @since 2.0.0
	 * @return string The current payment mode ('test' or 'live').
	 */
	public static function get_payment_mode() {
		$response = self::get_global_setting( 'payment_mode', 'test' );
		return ! empty( $response ) && is_string( $response ) ? $response : 'test';
	}

	/**
	 * Get comprehensive currency data for all supported currencies.
	 *
	 * This is the single source of truth for all currency-related data.
	 * Contains currency name, symbol, and decimal places.
	 *
	 * @since 2.0.0
	 * @return array<string, array<string, mixed>> Array of currency data keyed by currency code.
	 */
	public static function get_all_currencies_data() {
		return [
			'USD' => [
				'name'           => __( 'US Dollar', 'sureforms' ),
				'symbol'         => '$',
				'decimal_places' => 2,
			],
			'EUR' => [
				'name'           => __( 'Euro', 'sureforms' ),
				'symbol'         => '€',
				'decimal_places' => 2,
			],
			'GBP' => [
				'name'           => __( 'British Pound', 'sureforms' ),
				'symbol'         => '£',
				'decimal_places' => 2,
			],
			'JPY' => [
				'name'           => __( 'Japanese Yen', 'sureforms' ),
				'symbol'         => '¥',
				'decimal_places' => 0,
			],
			'AUD' => [
				'name'           => __( 'Australian Dollar', 'sureforms' ),
				'symbol'         => 'A$',
				'decimal_places' => 2,
			],
			'CAD' => [
				'name'           => __( 'Canadian Dollar', 'sureforms' ),
				'symbol'         => 'C$',
				'decimal_places' => 2,
			],
			'CHF' => [
				'name'           => __( 'Swiss Franc', 'sureforms' ),
				'symbol'         => 'CHF',
				'decimal_places' => 2,
			],
			'CNY' => [
				'name'           => __( 'Chinese Yuan', 'sureforms' ),
				'symbol'         => '¥',
				'decimal_places' => 2,
			],
			'SEK' => [
				'name'           => __( 'Swedish Krona', 'sureforms' ),
				'symbol'         => 'kr',
				'decimal_places' => 2,
			],
			'NZD' => [
				'name'           => __( 'New Zealand Dollar', 'sureforms' ),
				'symbol'         => 'NZ$',
				'decimal_places' => 2,
			],
			'MXN' => [
				'name'           => __( 'Mexican Peso', 'sureforms' ),
				'symbol'         => 'MX$',
				'decimal_places' => 2,
			],
			'SGD' => [
				'name'           => __( 'Singapore Dollar', 'sureforms' ),
				'symbol'         => 'S$',
				'decimal_places' => 2,
			],
			'HKD' => [
				'name'           => __( 'Hong Kong Dollar', 'sureforms' ),
				'symbol'         => 'HK$',
				'decimal_places' => 2,
			],
			'NOK' => [
				'name'           => __( 'Norwegian Krone', 'sureforms' ),
				'symbol'         => 'kr',
				'decimal_places' => 2,
			],
			'KRW' => [
				'name'           => __( 'South Korean Won', 'sureforms' ),
				'symbol'         => '₩',
				'decimal_places' => 0,
			],
			'TRY' => [
				'name'           => __( 'Turkish Lira', 'sureforms' ),
				'symbol'         => '₺',
				'decimal_places' => 2,
			],
			'RUB' => [
				'name'           => __( 'Russian Ruble', 'sureforms' ),
				'symbol'         => '₽',
				'decimal_places' => 2,
			],
			'INR' => [
				'name'           => __( 'Indian Rupee', 'sureforms' ),
				'symbol'         => '₹',
				'decimal_places' => 2,
			],
			'BRL' => [
				'name'           => __( 'Brazilian Real', 'sureforms' ),
				'symbol'         => 'R$',
				'decimal_places' => 2,
			],
			'ZAR' => [
				'name'           => __( 'South African Rand', 'sureforms' ),
				'symbol'         => 'R',
				'decimal_places' => 2,
			],
			'AED' => [
				'name'           => __( 'UAE Dirham', 'sureforms' ),
				'symbol'         => 'د.إ',
				'decimal_places' => 2,
			],
			'PHP' => [
				'name'           => __( 'Philippine Peso', 'sureforms' ),
				'symbol'         => '₱',
				'decimal_places' => 2,
			],
			'IDR' => [
				'name'           => __( 'Indonesian Rupiah', 'sureforms' ),
				'symbol'         => 'Rp',
				'decimal_places' => 2,
			],
			'MYR' => [
				'name'           => __( 'Malaysian Ringgit', 'sureforms' ),
				'symbol'         => 'RM',
				'decimal_places' => 2,
			],
			'THB' => [
				'name'           => __( 'Thai Baht', 'sureforms' ),
				'symbol'         => '฿',
				'decimal_places' => 2,
			],
			'BIF' => [
				'name'           => __( 'Burundian Franc', 'sureforms' ),
				'symbol'         => 'FBu',
				'decimal_places' => 0,
			],
			'CLP' => [
				'name'           => __( 'Chilean Peso', 'sureforms' ),
				'symbol'         => '$',
				'decimal_places' => 0,
			],
			'DJF' => [
				'name'           => __( 'Djiboutian Franc', 'sureforms' ),
				'symbol'         => 'Fdj',
				'decimal_places' => 0,
			],
			'GNF' => [
				'name'           => __( 'Guinean Franc', 'sureforms' ),
				'symbol'         => 'FG',
				'decimal_places' => 0,
			],
			'KMF' => [
				'name'           => __( 'Comorian Franc', 'sureforms' ),
				'symbol'         => 'CF',
				'decimal_places' => 0,
			],
			'MGA' => [
				'name'           => __( 'Malagasy Ariary', 'sureforms' ),
				'symbol'         => 'Ar',
				'decimal_places' => 0,
			],
			'PYG' => [
				'name'           => __( 'Paraguayan Guaraní', 'sureforms' ),
				'symbol'         => '₲',
				'decimal_places' => 0,
			],
			'RWF' => [
				'name'           => __( 'Rwandan Franc', 'sureforms' ),
				'symbol'         => 'FRw',
				'decimal_places' => 0,
			],
			'UGX' => [
				'name'           => __( 'Ugandan Shilling', 'sureforms' ),
				'symbol'         => 'USh',
				'decimal_places' => 0,
			],
			'VND' => [
				'name'           => __( 'Vietnamese Đồng', 'sureforms' ),
				'symbol'         => '₫',
				'decimal_places' => 0,
			],
			'VUV' => [
				'name'           => __( 'Vanuatu Vatu', 'sureforms' ),
				'symbol'         => 'VT',
				'decimal_places' => 0,
			],
			'XAF' => [
				'name'           => __( 'Central African CFA Franc', 'sureforms' ),
				'symbol'         => 'FCFA',
				'decimal_places' => 0,
			],
			'XOF' => [
				'name'           => __( 'West African CFA Franc', 'sureforms' ),
				'symbol'         => 'CFA',
				'decimal_places' => 0,
			],
			'XPF' => [
				'name'           => __( 'CFP Franc', 'sureforms' ),
				'symbol'         => '₣',
				'decimal_places' => 0,
			],
		];
	}

	/**
	 * Get currency names for all supported currencies.
	 *
	 * @since 2.0.0
	 * @return array<string, mixed> Array of currency names keyed by currency code.
	 */
	public static function get_currency_names() {
		$currencies = self::get_all_currencies_data();
		$names      = [];

		foreach ( $currencies as $code => $data ) {
			$names[ $code ] = $data['name'];
		}

		return $names;
	}

	/**
	 * Get currency symbol.
	 *
	 * @param string $currency Currency code.
	 * @since 2.0.0
	 * @return string Currency symbol or empty string.
	 */
	public static function get_currency_symbol( $currency ) {
		if ( empty( $currency ) || ! is_string( $currency ) ) {
			return '';
		}

		$currency      = strtoupper( $currency );
		$currencies    = self::get_all_currencies_data();
		$currency_data = $currencies[ $currency ] ?? null;

		$symbol = ! empty( $currency_data ) ? $currency_data['symbol'] : '';
		return is_string( $symbol ) ? $symbol : '';
	}

	/**
	 * Get list of zero-decimal currencies.
	 *
	 * Zero-decimal currencies don't use decimal points in payment APIs.
	 * For these currencies, amounts are passed as-is without multiplying/dividing by 100.
	 *
	 * @since 2.0.0
	 * @return array<string> Array of zero-decimal currency codes.
	 */
	public static function get_zero_decimal_currencies() {
		$currencies         = self::get_all_currencies_data();
		$zero_decimal_codes = [];

		foreach ( $currencies as $code => $data ) {
			if ( 0 === $data['decimal_places'] ) {
				$zero_decimal_codes[] = $code;
			}
		}

		return $zero_decimal_codes;
	}

	/**
	 * Check if currency is zero-decimal.
	 *
	 * @param string $currency Currency code.
	 * @since 2.0.0
	 * @return bool True if zero-decimal currency.
	 */
	public static function is_zero_decimal_currency( $currency ) {
		if ( empty( $currency ) || ! is_string( $currency ) ) {
			return false;
		}

		$currency      = strtoupper( $currency );
		$currencies    = self::get_all_currencies_data();
		$currency_data = $currencies[ $currency ] ?? null;

		return $currency_data && 0 === $currency_data['decimal_places'];
	}

	/**
	 * Get all payment-related translatable strings for frontend use.
	 *
	 * This is the single source of truth for all payment UI strings.
	 * Each string has a unique key (slug) for easy reference in JavaScript.
	 *
	 * @since 2.0.0
	 * @return array<string, string> Array of translatable strings keyed by slug.
	 */
	public static function get_payment_strings() {
		return [
			'unknown_error'                     => __( 'An unknown error occurred. Please try again or contact the site administrator.', 'sureforms' ),
			// Payment validation messages.
			'payment_unavailable'               => __( 'Payment is currently unavailable. Please contact the site administrator.', 'sureforms' ),
			'payment_amount_not_configured'     => __( 'Payment is currently unavailable. Please contact the site administrator to configure the payment amount.', 'sureforms' ),
			'invalid_variable_amount'           => __( 'Invalid payment amount', 'sureforms' ),
			'amount_below_minimum'              => __( 'Payment amount must be at least {symbol}{amount}.', 'sureforms' ),

			// Field mapping validation.
			'payment_name_not_mapped'           => __( 'Payment is currently unavailable. Please contact the site administrator to configure the customer name field.', 'sureforms' ),
			'payment_email_not_mapped'          => __( 'Payment is currently unavailable. Please contact the site administrator to configure the customer email field.', 'sureforms' ),
			'payment_name_required'             => __( 'Please enter your name.', 'sureforms' ),
			'payment_email_required'            => __( 'Please enter your email.', 'sureforms' ),

			// Payment processing messages.
			'payment_failed'                    => __( 'Payment failed', 'sureforms' ),
			'payment_successful'                => __( 'Payment successful', 'sureforms' ),
			'payment_could_not_be_completed'    => __( 'Payment could not be completed. Please try again or contact the site administrator.', 'sureforms' ),

			// Stripe decline codes - Card declined errors.
			'generic_decline'                   => __( 'Your card was declined. Please try a different payment method or contact your bank.', 'sureforms' ),
			'card_declined'                     => __( 'Your card was declined. Please try a different payment method or contact your bank.', 'sureforms' ),
			'insufficient_funds'                => __( 'Your card has insufficient funds. Please use a different payment method.', 'sureforms' ),
			'lost_card'                         => __( 'Your card was declined because it has been reported as lost. Please contact your bank.', 'sureforms' ),
			'stolen_card'                       => __( 'Your card was declined because it has been reported as stolen. Please contact your bank.', 'sureforms' ),
			'expired_card'                      => __( 'Your card has expired. Please use a different payment method.', 'sureforms' ),
			'pickup_card'                       => __( 'Your card was declined. Please contact your bank for more information.', 'sureforms' ),
			'restricted_card'                   => __( 'Your card was declined due to restrictions. Please contact your bank.', 'sureforms' ),
			'security_violation'                => __( 'Your card was declined due to a security violation. Please contact your bank.', 'sureforms' ),
			'service_not_allowed'               => __( 'Your card does not support this type of purchase. Please use a different payment method.', 'sureforms' ),
			'stop_payment_order'                => __( 'A stop payment order has been placed on this card. Please contact your bank.', 'sureforms' ),
			'testmode_decline'                  => __( 'A test card was used in a live environment. Please use a real card.', 'sureforms' ),
			'withdrawal_count_limit_exceeded'   => __( 'Your card has exceeded its withdrawal limit. Please contact your bank.', 'sureforms' ),
			'incorrect_cvc'                     => __( 'Your card\'s security code is incorrect. Please check and try again.', 'sureforms' ),
			'incorrect_number'                  => __( 'Your card number is incorrect. Please check and try again.', 'sureforms' ),
			'invalid_cvc'                       => __( 'Your card\'s security code is invalid. Please check and try again.', 'sureforms' ),
			'invalid_expiry_month'              => __( 'Your card\'s expiration month is invalid. Please check and try again.', 'sureforms' ),
			'invalid_expiry_year'               => __( 'Your card\'s expiration year is invalid. Please check and try again.', 'sureforms' ),
			'invalid_number'                    => __( 'Your card number is invalid. Please check and try again.', 'sureforms' ),
			'processing_error'                  => __( 'An error occurred while processing your card. Please try again.', 'sureforms' ),
			'reenter_transaction'               => __( 'The transaction could not be processed. Please try again.', 'sureforms' ),
			'card_not_supported'                => __( 'Your card is not supported for this transaction. Please use a different payment method.', 'sureforms' ),
			'currency_not_supported'            => __( 'Your card does not support the currency used for this transaction. Please use a different payment method.', 'sureforms' ),
			'duplicate_transaction'             => __( 'A transaction with identical details was submitted recently. Please wait a moment and try again.', 'sureforms' ),
			'invalid_account'                   => __( 'The account associated with your card is invalid. Please contact your bank.', 'sureforms' ),
			'invalid_amount'                    => __( 'The payment amount is invalid. Please contact the site administrator.', 'sureforms' ),
			'issuer_not_available'              => __( 'Your card issuer could not be reached. Please try again later.', 'sureforms' ),
			'merchant_blacklist'                => __( 'Your card was declined. Please contact your bank for more information.', 'sureforms' ),
			'new_account_information_available' => __( 'Your card information needs to be updated. Please contact your bank.', 'sureforms' ),
			'no_action_taken'                   => __( 'The card cannot be used for this transaction. Please contact your bank.', 'sureforms' ),
			'not_permitted'                     => __( 'The transaction is not permitted. Please contact your bank.', 'sureforms' ),
			'offline_pin_required'              => __( 'Your card requires offline PIN authentication. Please try again.', 'sureforms' ),
			'online_or_offline_pin_required'    => __( 'Your card requires PIN authentication. Please try again.', 'sureforms' ),
			'pin_try_exceeded'                  => __( 'You have exceeded the maximum number of PIN attempts. Please contact your bank.', 'sureforms' ),
			'revocation_of_all_authorizations'  => __( 'All authorizations for this card have been revoked. Please contact your bank.', 'sureforms' ),
			'revocation_of_authorization'       => __( 'The authorization for this transaction has been revoked. Please try again.', 'sureforms' ),
			'transaction_not_allowed'           => __( 'This transaction is not allowed. Please contact your bank.', 'sureforms' ),
			'try_again_later'                   => __( 'The transaction could not be processed. Please try again later.', 'sureforms' ),
			'live_mode_test_card'               => __( 'Your card was declined. Your request was in live mode, but used a known test card.', 'sureforms' ),
			'test_mode_live_card'               => __( 'Your card was declined. Your request was in test mode, but used a non test card. For a list of valid test cards, visit: https://stripe.com/docs/testing.', 'sureforms' ),

			// Default values and placeholders.
			'sureforms_subscription'            => __( 'SureForms Subscription', 'sureforms' ),
			'sureforms_payment'                 => __( 'SureForms Payment', 'sureforms' ),
			'subscription_plan'                 => __( 'Subscription Plan', 'sureforms' ),
			'sureforms_customer'                => __( 'SureForms Customer', 'sureforms' ),
			'customer_example_email'            => 'customer@example.com', // Not translatable - example email.
			'amount_placeholder'                => __( 'Please complete the form to view the amount.', 'sureforms' ),
			'failed_to_create_payment'          => __( 'Failed to create payment. Please contact the site owner', 'sureforms' ),
		];
	}

	/**
	 * Retrieve a user-friendly payment error message by error key.
	 *
	 * @param string $key Error key received from payment processing/Stripe.
	 *
	 * @since 2.0.0
	 * @return string Localized error message or a generic "Unknown error" message if not found.
	 */
	public static function get_error_message_by_key( $key ) {
		$messages = self::get_payment_strings();
		if ( isset( $messages[ $key ] ) ) {
			return $messages[ $key ];
		}
		return __( 'Unknown error', 'sureforms' );
	}

	/**
	 * Validate payment amount against stored form configuration.
	 *
	 * This function verifies that the payment amount and currency submitted
	 * match the configured values in the form's payment block settings.
	 * It handles both fixed and minimum amount validations for single and subscription payments.
	 *
	 * @since 2.2.2
	 * @param int|float $amount   Amount in smallest currency unit (e.g., cents for USD).
	 * @param string    $currency Currency code (e.g., 'usd', 'eur').
	 * @param int       $form_id  WordPress post ID of the form.
	 * @param string    $block_id Block identifier for the payment block.
	 * @return array {
	 *     Validation result.
	 *
	 *     @type bool   $valid   Whether the validation passed.
	 *     @type string $message Error message if validation failed, empty if valid.
	 * }
	 */
	public static function validate_payment_amount( $amount, $currency, $form_id, $block_id ) {
		// Retrieve block configuration from post meta.
		$block_config = Field_Validation::get_or_migrate_block_config_for_legacy_form( $form_id );

		// Check if block config exists.
		if ( empty( $block_config ) || ! is_array( $block_config ) ) {
			return [
				'valid'   => false,
				'message' => __( 'Invalid form configuration.', 'sureforms' ),
			];
		}

		// Check if payment block exists in configuration.
		if ( ! isset( $block_config[ $block_id ] ) || ! is_array( $block_config[ $block_id ] ) ) {
			return [
				'valid'   => false,
				'message' => __( 'Payment configuration not found for this form.', 'sureforms' ),
			];
		}

		$payment_config     = $block_config[ $block_id ];
		$global_currency    = strtolower( self::get_currency() );
		$submitted_currency = strtolower( $currency );
		if ( $global_currency !== $submitted_currency ) {
			return [
				'valid'   => false,
				/* translators: 1: expected currency, 2: received currency */
				'message' => sprintf( __( 'Currency mismatch: expected %1$s, received %2$s.', 'sureforms' ), strtoupper( $global_currency ), strtoupper( $submitted_currency ) ),
			];
		}

		// Get amount type (fixed or minimum).
		$amount_type = $payment_config['amount_type'] ?? 'fixed';

		// Validate based on amount type.
		if ( 'fixed' === $amount_type ) {
			// Fixed amount validation - must match exactly.
			$configured_amount = isset( $payment_config['fixed_amount'] ) ? floatval( $payment_config['fixed_amount'] ) : 10.00;

			// Allow small floating point difference (0.01) due to rounding.
			if ( abs( $amount - $configured_amount ) > 0.01 ) {
				return [
					'valid'   => false,
					/* translators: 1: expected amount with currency */
					'message' => sprintf( __( 'Payment amount must be exactly %1$s.', 'sureforms' ), $configured_amount . ' ' . strtoupper( $currency ) ),
				];
			}
		} elseif ( 'variable' === $amount_type ) {
			// Minimum amount validation - must be >= minimum.
			$minimum_amount = isset( $payment_config['minimum_amount'] ) ? floatval( $payment_config['minimum_amount'] ) : 0;

			if ( $amount < $minimum_amount ) {
				return [
					'valid'   => false,
					/* translators: 1: minimum amount with currency */
					'message' => sprintf( __( 'Payment amount must be at least %1$s.', 'sureforms' ), $minimum_amount . ' ' . strtoupper( $currency ) ),
				];
			}

			// Validate dynamic amount from dropdown/multi-choice field.
			$dynamic_amount_validation = self::validate_dynamic_amount_field(
				$payment_config,
				$block_config,
				$amount,
				$currency
			);

			if ( null !== $dynamic_amount_validation ) {
				return $dynamic_amount_validation;
			}
		}

		// Validation passed.
		return [
			'valid'   => true,
			'message' => '',
		];
	}

	/**
	 * Store payment intent metadata in transient for verification.
	 *
	 * Stores payment intent details temporarily to verify that the payment intent
	 * was created through our system and hasn't been tampered with.
	 *
	 * @since 2.2.2
	 * @param string               $block_id          Block identifier.
	 * @param string               $payment_intent_id Payment intent ID from Stripe.
	 * @param array<string, mixed> $metadata          Payment metadata to store.
	 * @return bool True on success, false on failure.
	 */
	public static function store_payment_intent_metadata( $block_id, $payment_intent_id, $metadata ) {
		if ( empty( $block_id ) || empty( $payment_intent_id ) ) {
			return false;
		}

		// Create transient key: srfm_pi_{block_id}_{payment_intent_id}.
		$transient_key = 'srfm_pi_' . sanitize_key( $block_id ) . '_' . sanitize_key( $payment_intent_id );

		// Add timestamp to metadata.
		$metadata['created_at'] = time();

		// Store for 1 hour (3600 seconds).
		return set_transient( $transient_key, $metadata, 3600 );
	}

	/**
	 * Verify payment intent and validate amount.
	 *
	 * Verifies that the payment intent was created through our system and validates
	 * the payment amount matches the expected amount based on form configuration.
	 *
	 * @since 2.3.0
	 * @param string               $block_id          Block identifier.
	 * @param string               $payment_intent_id Payment intent ID from Stripe.
	 * @param array<string, mixed> $form_data         Submitted form data.
	 * @return array {
	 *     Verification result.
	 *
	 *     @type bool   $valid   Whether verification passed.
	 *     @type string $message Error message if verification failed, empty if valid.
	 * }
	 */
	public static function verify_payment_intent( $block_id, $payment_intent_id, $form_data ) {
		// Get form ID from form data for verification.
		$form_id = isset( $form_data['form-id'] ) && ! empty( $form_data['form-id'] ) && is_numeric( $form_data['form-id'] ) ? intval( $form_data['form-id'] ) : 0;

		// Validate required parameters.
		if ( empty( $block_id ) || empty( $payment_intent_id ) || empty( $form_id ) ) {
			return [
				'valid'   => false,
				'message' => __( 'Invalid payment verification parameters.', 'sureforms' ),
			];
		}

		// Verify payment intent was created through our system.
		$transient_key = 'srfm_pi_' . sanitize_key( $block_id ) . '_' . sanitize_key( $payment_intent_id );
		$metadata      = get_transient( $transient_key );

		if ( empty( $metadata ) || ! is_array( $metadata ) ) {
			return [
				'valid'   => false,
				'message' => __( 'Payment verification failed. Invalid payment intent.', 'sureforms' ),
			];
		}

		$payment_amount = isset( $metadata['amount'] ) && ! empty( $metadata['amount'] ) && is_numeric( $metadata['amount'] ) ? floatval( $metadata['amount'] ) : 0;

		// Validate payment amount matches configuration.
		$amount_validation = self::validate_payment_intent_amount( $block_id, $form_id, $form_data, $payment_amount );

		if ( false === $amount_validation['valid'] ) {
			return $amount_validation;
		}

		// Verification passed.
		return [
			'valid'   => true,
			'message' => '',
		];
	}

	/**
	 * Delete payment intent metadata from transient.
	 *
	 * Cleans up stored metadata after successful payment verification.
	 *
	 * @since 2.2.2
	 * @param string $block_id          Block identifier.
	 * @param string $payment_intent_id Payment intent ID from Stripe.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_payment_intent_metadata( $block_id, $payment_intent_id ) {
		if ( empty( $block_id ) || empty( $payment_intent_id ) ) {
			return false;
		}

		// Create transient key: srfm_pi_{block_id}_{payment_intent_id}.
		$transient_key = 'srfm_pi_' . sanitize_key( $block_id ) . '_' . sanitize_key( $payment_intent_id );

		return delete_transient( $transient_key );
	}

	/**
	 * Validate dynamic amount field from dropdown or multi-choice.
	 *
	 * @param array<string, mixed> $payment_config Payment block configuration.
	 * @param array<string, mixed> $block_config  All block configurations.
	 * @param float                $submitted_amount_decimal Submitted amount in decimal.
	 * @param string               $currency Currency code.
	 * @return array|null Validation result array or null if validation passes.
	 * @since 2.3.0
	 */
	private static function validate_dynamic_amount_field( $payment_config, $block_config, $submitted_amount_decimal, $currency ) {
		// Check if variable amount field is from dropdown or multi-choice block.
		$dynamic_amount_field_block_name = $payment_config['variable_amount_field_block_name'] ?? '';

		if ( empty( $dynamic_amount_field_block_name ) ) {
			// Return null because it can be old form configuration.
			return null;
		}

		if ( 'srfm/dropdown' !== $dynamic_amount_field_block_name && 'srfm/multi-choice' !== $dynamic_amount_field_block_name ) {
			return null; // Not a dropdown/multi-choice, skip validation.
		}

		// Get the slug of the variable amount field.
		$variable_amount_field_slug = ! empty( $payment_config['variable_amount_field'] ) && is_string( $payment_config['variable_amount_field'] ) ? $payment_config['variable_amount_field'] : '';

		// Find the block config for the variable amount field by matching slug and block name.
		$variable_amount_block_config = self::get_block_config_by_name_and_slug( $block_config, $dynamic_amount_field_block_name, $variable_amount_field_slug );

		// Verify the variable amount block config was found.
		if ( empty( $variable_amount_block_config ) || ! is_array( $variable_amount_block_config ) ) {
			return [
				'valid'   => false,
				'message' => __( 'Variable amount field configuration not found.', 'sureforms' ),
			];
		}

		// Check if single selection is enabled (only validate for single selection).
		$is_single_selection = false;
		if ( 'srfm/dropdown' === $dynamic_amount_field_block_name ) {
			// For dropdown, check if multi_select is disabled (single selection).
			$is_single_selection = empty( $variable_amount_block_config['multi_select'] );
		} elseif ( 'srfm/multi-choice' === $dynamic_amount_field_block_name ) {
			// For multi-choice, check if single_selection is enabled.
			$is_single_selection = ! empty( $variable_amount_block_config['single_selection'] );
		}

		// Only validate amount matches options if single selection is enabled.
		if ( $is_single_selection ) {
			// Validate that submitted amount matches one of the allowed option values.
			$allowed_options = $variable_amount_block_config['options'] ?? [];
			if ( empty( $allowed_options ) || ! is_array( $allowed_options ) ) {
				return [
					'valid'   => false,
					'message' => __( 'No payment options are configured for this field.', 'sureforms' ),
				];
			}

			// Extract allowed values from options.
			$allowed_values = [];
			foreach ( $allowed_options as $option ) {
				if ( isset( $option['value'] ) && ! empty( $option['value'] ) ) {
					$allowed_values[] = floatval( $option['value'] );
				}
			}

			// Check if submitted amount matches any allowed value.
			$amount_is_valid = false;
			foreach ( $allowed_values as $allowed_value ) {
				// Allow small floating point difference (0.01) due to rounding.
				if ( abs( $submitted_amount_decimal - $allowed_value ) <= 0.01 ) {
					$amount_is_valid = true;
					break;
				}
			}

			if ( ! $amount_is_valid ) {
				return [
					'valid'   => false,
					/* translators: %s: currency code */
					'message' => sprintf( __( 'Invalid payment amount. Please select a valid amount from the available options.', 'sureforms' ), strtoupper( $currency ) ),
				];
			}
		}

		// Validation passed for dynamic amount field.
		return null;
	}

	/**
	 * Validate payment intent amount matches form configuration.
	 *
	 * Validates that the payment amount from Stripe matches the expected amount
	 * based on form configuration, including dynamic amounts from dropdown/multi-choice fields.
	 *
	 * @since 2.3.0
	 * @param string               $block_id       Block identifier.
	 * @param int                  $form_id        Form post ID.
	 * @param array<string, mixed> $form_data      Submitted form data.
	 * @param int|float            $payment_amount Payment amount from Stripe (in smallest currency unit).
	 * @return array {
	 *     Validation result.
	 *
	 *     @type bool   $valid   Whether validation passed.
	 *     @type string $message Error message if validation failed, empty if valid.
	 * }
	 */
	private static function validate_payment_intent_amount( $block_id, $form_id, $form_data, $payment_amount ) {
		// Get block configuration.
		$block_config = Field_Validation::get_or_migrate_block_config_for_legacy_form( $form_id );

		if ( empty( $block_config ) || ! isset( $block_config[ $block_id ] ) ) {
			return [
				'valid'   => false,
				/* translators: %1$s: expected amount, %2$s: payment amount */
				'message' => __( 'Payment configuration not found.', 'sureforms' ),
			];
		}

		$payment_config = $block_config[ $block_id ];
		$amount_type    = $payment_config['amount_type'] ?? 'fixed';

		// For fixed amounts, validate against configured amount.
		if ( 'fixed' === $amount_type ) {
			$configured_amount = isset( $payment_config['fixed_amount'] ) ? floatval( $payment_config['fixed_amount'] ) : 0;

			// Allow small floating point difference (0.01) due to rounding.
			if ( abs( $payment_amount - $configured_amount ) > 0.01 ) {
				return [
					'valid'   => false,
					/* translators: %1$s: expected amount, %2$s: payment amount */
					'message' => sprintf( __( 'Payment amount mismatch. Expected %1$s, received %2$s.', 'sureforms' ), $configured_amount, $payment_amount ),
				];
			}

			return [
				'valid'   => true,
				'message' => '',
			];
		}

		// For variable amounts, validate based on source field.
		if ( 'variable' === $amount_type ) {
			// Check if variable amount comes from dropdown/multi-choice.
			$dynamic_amount_field_block_name = $payment_config['variable_amount_field_block_name'] ?? '';
			$variable_amount_field_slug      = $payment_config['variable_amount_field'] ?? '';

			// Skipping if it is old form configuration.
			if ( empty( $dynamic_amount_field_block_name ) || empty( $variable_amount_field_slug ) ) {
				return [
					'valid'   => true,
					'message' => '',
				];
			}

			$submitted_field_value = self::get_form_submitted_value_by_slug_and_block_name( $variable_amount_field_slug, $dynamic_amount_field_block_name, $form_data );

			if ( empty( $submitted_field_value ) ) {
				return [
					'valid'   => false,
					'message' => __( 'Variable amount field value is required.', 'sureforms' ),
				];
			}

			if ( 'srfm/dropdown' === $dynamic_amount_field_block_name || 'srfm/multi-choice' === $dynamic_amount_field_block_name ) {
				// Get the block config for the variable amount field by matching slug and block name.
				$variable_amount_block_config = self::get_block_config_by_name_and_slug( $block_config, $dynamic_amount_field_block_name, $variable_amount_field_slug );

				if ( empty( $variable_amount_block_config ) || ! is_string( $submitted_field_value ) ) {
					return [
						'valid'   => false,
						'message' => __( 'Variable amount field configuration not found.', 'sureforms' ),
					];
				}

				// To get the expected amount we need to check by the value of the submitted field. we will have the values now we need to check the expected amount in the block config. because block config dropdown/multi-choice has the expected amount in the options.
				$get_expected_amount = self::get_amount_by_the_config_options( $submitted_field_value, $variable_amount_block_config );

				// Validate payment amount matches expected amount.
				if ( abs( $payment_amount - $get_expected_amount ) > 0.01 ) {
					return [
						'valid'   => false,
						/* translators: %1$s: expected amount, %2$s: payment amount */
						'message' => sprintf( __( 'Payment amount mismatch. Expected %1$s, received %2$s.', 'sureforms' ), $get_expected_amount, $payment_amount ),
					];
				}
			} elseif ( 'srfm/number' === $dynamic_amount_field_block_name ) {
				$is_valid_amount = is_numeric( $submitted_field_value ) ? true : false;

				if ( ! $is_valid_amount ) {
					return [
						'valid'   => false,
						'message' => __( 'Variable amount field value is required.', 'sureforms' ),
					];
				}

				$converted_payment_amount = floatval( $submitted_field_value );

				// Validate payment amount matches expected amount.
				if ( abs( $payment_amount - $converted_payment_amount ) > 0.01 ) {
					return [
						'valid'   => false,
						/* translators: %1$s: expected amount, %2$s: payment amount */
						'message' => sprintf( __( 'Payment amount mismatch. Expected %1$s, received %2$s.', 'sureforms' ), $converted_payment_amount, $payment_amount ),
					];
				}
			}

			// For other variable amount sources (e.g., number field), validate minimum amount.
			$minimum_amount = isset( $payment_config['minimum_amount'] ) ? floatval( $payment_config['minimum_amount'] ) : 0;

			if ( $payment_amount < $minimum_amount ) {
				return [
					'valid'   => false,
					/* translators: %1$s: minimum amount, %2$s: payment amount */
					'message' => sprintf( __( 'Payment amount below minimum. Minimum: %1$s, received %2$s.', 'sureforms' ), $minimum_amount, $payment_amount ),
				];
			}
		}

		// Validation passed.
		return [
			'valid'   => true,
			'message' => '',
		];
	}

	/**
	 * Get amount by matching submitted value with config options.
	 *
	 * @param string       $submitted_field_value The submitted value (string, can be "value1 | value2" for multi-select).
	 * @param array<mixed> $block_config          Block configuration containing options.
	 * @return float|null Expected amount if found, null otherwise.
	 * @since 2.3.0
	 */
	private static function get_amount_by_the_config_options( $submitted_field_value, $block_config ) {
		if ( empty( $submitted_field_value ) || ! is_string( $submitted_field_value ) ) {
			return null;
		}

		// Get options from block config.
		$options = $block_config['options'] ?? [];

		if ( empty( $options ) || ! is_array( $options ) ) {
			return null;
		}

		// Check if multi-select is enabled.
		$is_multi_select = false;
		$block_name      = $block_config['block_name'] ?? '';

		if ( 'srfm/dropdown' === $block_name ) {
			$is_multi_select = ! empty( $block_config['multi_select'] );
		} elseif ( 'srfm/multi-choice' === $block_name ) {
			// For multi-choice, multi-select is when single_selection is disabled.
			$is_multi_select = empty( $block_config['single_selection'] );
		}

		$expected_amount = null;

		// Handle multi-select case (submitted value format: "value1 | value2").
		if ( $is_multi_select && false !== strpos( $submitted_field_value, ' | ' ) ) {
			// Explode the submitted value by " | " delimiter.
			$submitted_values = explode( ' | ', $submitted_field_value );

			$combine_amount = 0;

			foreach ( $options as $option ) {
				$option_label = isset( $option['label'] ) ? trim( $option['label'] ) : '';

				foreach ( $submitted_values as $submitted_value ) {
					if ( trim( $submitted_value ) === $option_label ) {
						$combine_amount += floatval( $option['value'] );
						break;
					}
				}
			}

			$expected_amount = $combine_amount;

		} else {
			// Handle single select case (submitted value is a simple string).
			foreach ( $options as $option ) {
				$option_label = isset( $option['label'] ) ? trim( $option['label'] ) : '';
				if ( trim( $submitted_field_value ) === $option_label ) {
					$expected_amount = floatval( $option['value'] );
					break;
				}
			}
		}

		return $expected_amount;
	}

	/**
	 * Get block configuration by block name and slug.
	 *
	 * @param array<mixed> $block_config All block configurations.
	 * @param string       $block_name   Block name to search for.
	 * @param string       $slug         Slug to match.
	 * @return array|null Block configuration if found, null otherwise.
	 * @since 2.3.0
	 */
	private static function get_block_config_by_name_and_slug( $block_config, $block_name, $slug ) {
		foreach ( $block_config as $config ) {
			if ( empty( $config ) || ! is_array( $config ) ) {
				continue;
			}

			if ( isset( $config['slug'] ) && $config['slug'] === $slug && isset( $config['block_name'] ) && $config['block_name'] === $block_name ) {
				return $config;
			}
		}
		return null;
	}

	/**
	 * Get form submitted value for a specific field by slug and block name.
	 *
	 * @param string       $variable_amount_field_slug    Slug of the field to find.
	 * @param string       $dynamic_amount_field_block_name Block name of the field.
	 * @param array<mixed> $form_data                     Form submission data.
	 * @return mixed|null Field value if found, null otherwise.
	 * @since 2.3.0
	 */
	private static function get_form_submitted_value_by_slug_and_block_name( $variable_amount_field_slug, $dynamic_amount_field_block_name, $form_data ) {
		$block_name = null;
		if ( 'srfm/dropdown' === $dynamic_amount_field_block_name ) {
			$block_name = 'srfm-dropdown';
		} elseif ( 'srfm/multi-choice' === $dynamic_amount_field_block_name ) {
			$block_name = 'srfm-input-multi-choice';
		} elseif ( 'srfm/number' === $dynamic_amount_field_block_name ) {
			$block_name = 'srfm-number';
		}

		// Now we need to get the submitted value.
		// Here is the structure of the form data name.
		// srfm-input-multi-choice-398dbcfe-lbl-UGxlYXNlIGNob29zZSBvcHRpb24-multi-choice
		// {block_name}-{block_id}-lbl-{combined-id}-{slug}.
		$submitted_field_value = null;
		foreach ( $form_data as $field_key => $field_value ) {
			// Check if field key starts with block_name- and ends with -slug.
			$is_start_with_block_name = strpos( $field_key, $block_name . '-' ) === 0;
			$is_last_with_slug        = substr( $field_key, -strlen( '-' . $variable_amount_field_slug ) ) === '-' . $variable_amount_field_slug;

			if ( $is_start_with_block_name && $is_last_with_slug ) {
				$submitted_field_value = $field_value;
				break;
			}
		}

		return $submitted_field_value;
	}

	/**
	 * Get default payment settings (global + all gateways).
	 *
	 * @since 2.0.0
	 * @return array<string, mixed> Default payment settings structure.
	 */
	private static function get_default_payment_settings() {
		return [
			'currency'     => 'USD',
			'payment_mode' => 'test',
			'stripe'       => Stripe_Helper::get_default_stripe_settings(),
		];
	}
}
