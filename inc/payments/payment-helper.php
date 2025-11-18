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
