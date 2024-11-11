<?php
/**
 * Text Helper Class file for Sureforms.
 *
 * @package Sureforms
 * @since x.x.x
 */

namespace SRFM\Inc;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sureforms Text Helper Class
 * Provides helper functions for handling text, including translations.
 *
 * @since x.x.x
 */
class Translatable {

    public static function get_frontend_validation_messages() {
        return [
            'valid_phone_number' => __( 'Please enter a valid phone number.', 'sureforms' ),
            'valid_url'          => __( 'Please enter a valid URL.', 'sureforms' ),
            'confirm_email_same' => __( 'Confirmation email is not the same.', 'sureforms' ),
            'valid_email'        => __( 'Please enter a valid email address.', 'sureforms' ),
            'confirm_password_same' => __( 'Confirmation password is not the same.', 'sureforms' ),
            'min_value'          => __( 'Minimum value is %s', 'sureforms' ),
            'min_selections'     => __( 'Minimum %s selections are required', 'sureforms' ),
            'max_selections'     => __( 'Maximum %s selections are allowed', 'sureforms' ),
        ];
    }
}
