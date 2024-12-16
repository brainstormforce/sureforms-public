<?php
/**
 * Translatable Class file for Sureforms.
 *
 * @package Sureforms
 * @since 1.0.5
 */

namespace SRFM\Inc;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sureforms Translatable Class
 *
 * A helper class providing an interface for handling translation of text elements, specifically
 * frontend validation messages, used in the Sureforms plugin. This class enables dynamic and
 * reusable translated strings to enhance user experience across different languages.
 *
 * @since 1.0.5
 */
class Translatable {
	/**
	 * Retrieve default frontend validation messages.
	 *
	 * Returns an array of validation messages, each identified by a unique key. Messages are
	 * translated for frontend display, with placeholders included for dynamically populated values.
	 *
	 * @since 1.0.5
	 * @return array<string, string> Associative array of translated validation messages for frontend use.
	 */
	public static function get_frontend_validation_messages() {
		$translatable_array = self::dynamic_validation_messages();

		/**
		 * Filter for frontend validation messages.
		 *
		 * This filter allows developers to add or modify the default validation messages.
		 * Primarily intended for enabling custom validation messages and supporting pro functionality.
		 *
		 * @internal This filter is primarily used for internal purposes and pro functionality.
		 */
		return apply_filters( 'srfm_frontend_validation_messages', $translatable_array );
	}

	/**
	 * Retrieve default dynamic validation messages.
	 *
	 * @since 1.2.1
	 * @return array<string, string> Associative array of translated validation messages for dynamic use.
	 */
	public static function dynamic_validation_messages() {
		$translatable_array = self::dynamic_messages();

		/**
		 * Filter for dynamic validation messages.
		 *
		 * The `srfm_dynamic_validation_messages` filter allows developers to add or modify
		 * the default dynamic validation messages.
		 * This is primarily intended for enabling custom validation messages and supporting pro functionality.
		 *
		 * @internal This filter is primarily used for internal purposes and pro functionality.
		 */
		$filtered_array = apply_filters( 'srfm_dynamic_validation_messages', $translatable_array );

		$dynamic_options = get_option( 'srfm_default_dynamic_block_option', [] );
		if ( ! empty( $dynamic_options ) && is_array( $dynamic_options ) ) {
			foreach ( $dynamic_options as $key => $value ) {
				if ( isset( $filtered_array[ $key ] ) ) {
					$filtered_array[ $key ] = $value;
				}
			}
		}

		return $filtered_array;
	}

	/**
	 * Dynamic messages array
	 *
	 * @since 1.2.1
	 * @return array<string, string> Associative array of translated dynamic messages.
	 */
	public static function dynamic_messages() {
		return [
			'srfm_valid_phone_number'          => __( 'Please enter a valid phone number.', 'sureforms' ),
			'srfm_valid_url'                   => __( 'Please enter a valid URL.', 'sureforms' ),
			'srfm_confirm_email_same'          => __( 'Confirmation email does not match.', 'sureforms' ),
			'srfm_valid_email'                 => __( 'Please enter a valid email address.', 'sureforms' ),
			// Note: These password strength messages are prepared for the password block.
			// As of now, the password block is not registered in SureForms. Once registered, these messages should be used.
			// phpcs:ignore
			// 'srfm_confirm_password_same'       => __( 'Confirmation password does not match.', 'sureforms' ),
			// phpcs:enable

			/* translators: %s represents the minimum acceptable value */
			'srfm_input_min_value'             => __( 'Minimum value is %s', 'sureforms' ),

			/* translators: %s represents the maximum acceptable value */
			'srfm_input_max_value'             => __( 'Maximum value is %s', 'sureforms' ),

			/* translators: %s represents the minimum number of selections required */
			'srfm_dropdown_min_selections'     => __( 'Minimum %s selections are required', 'sureforms' ),

			/* translators: %s represents the maximum number of selections allowed */
			'srfm_dropdown_max_selections'     => __( 'Maximum %s selections are allowed', 'sureforms' ),

			/* translators: %s represents the minimum number of characters required */
			'srfm_multi_choice_min_selections' => __( 'Minimum %s selections are required', 'sureforms' ),

			/* translators: %s represents the maximum number of characters allowed */
			'srfm_multi_choice_max_selections' => __( 'Maximum %s selections are allowed', 'sureforms' ),
		];
	}
}
