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
		$translatable_array = [
			'valid_phone_number'          => __( 'Please enter a valid phone number.', 'sureforms' ),
			'valid_url'                   => __( 'Please enter a valid URL.', 'sureforms' ),
			'confirm_email_same'          => __( 'Confirmation email does not match.', 'sureforms' ),
			'valid_email'                 => __( 'Please enter a valid email address.', 'sureforms' ),
			'confirm_password_same'       => __( 'Confirmation password does not match.', 'sureforms' ),

			/* translators: %s represents the minimum acceptable value */
			'input_min_value'             => __( 'Minimum value is %s', 'sureforms' ),

			/* translators: %s represents the maximum acceptable value */
			'input_max_value'             => __( 'Maximum value is %s', 'sureforms' ),

			/* translators: %s represents the minimum number of selections required */
			'dropdown_min_selections'     => __( 'Minimum %s selections are required', 'sureforms' ),

			/* translators: %s represents the maximum number of selections allowed */
			'dropdown_max_selections'     => __( 'Maximum %s selections are allowed', 'sureforms' ),

			/* translators: %s represents the minimum number of characters required */
			'multi_choice_min_selections' => __( 'Minimum %s selections are required', 'sureforms' ),

			/* translators: %s represents the maximum number of characters allowed */
			'multi_choice_max_selections' => __( 'Maximum %s selections are allowed', 'sureforms' ),
		];

		return apply_filters( 'srfm_frontend_validation_messages', $translatable_array );
	}
}
