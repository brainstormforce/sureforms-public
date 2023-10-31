<?php
/**
 * Sureforms Email Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;

/**
 * SureForms Email Markup Class.
 *
 * @since 0.0.1
 */
class Email_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms email default styling block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function default_styling( $attributes ) {
		$required         = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$default          = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
		$placeholder      = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$label            = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help             = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$is_unique        = isset( $attributes['isUnique'] ) ? $attributes['isUnique'] : false;
		$dulicate_msg     = isset( $attributes['duplicateMsg'] ) ? $attributes['duplicateMsg'] : '';
		$error_msg        = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$is_confirm_email = isset( $attributes['isConfirmEmail'] ) ? $attributes['isConfirmEmail'] : false;
		$confirm_label    = isset( $attributes['confirmLabel'] ) ? $attributes['confirmLabel'] : '';
		$classname        = isset( $attributes['className'] ) ? $attributes['className'] : '';
		$block_id         = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';

		return '<div class="sureforms-input-email-container main-container frontend-inputs-holder ' . esc_attr( $classname ) . '">
        <label for="sureforms-input-email-' . esc_attr( $block_id ) . '" class="sf-text-primary">' . esc_html( $label ) . ' ' . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '</label>
        <input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" id="sureforms-input-email-' . esc_attr( $block_id ) . '" type="email" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" value="' . esc_attr( $default ) . '" placeholder="' . esc_attr( $placeholder ) . '" aria-unique="' . esc_attr( $is_unique ? 'true' : 'false' ) . '" class="sureforms-input-email sureforms-input-field">
        <span style="display:none" class="error-message">' . esc_html( $error_msg ) . '</span>
        <span style="display:none" class="error-message duplicate-message">' . esc_html( $dulicate_msg ) . '</span>' .
		( true === $is_confirm_email ?
			'<label for="sureforms-input-confirm-email-' . esc_attr( $block_id ) . '" class="sf-text-primary sureforms-confirm-email-spl">' . esc_html( $confirm_label ) . ' ' . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '</label>' .
			'<input id="sureforms-input-confirm-email-' . esc_attr( $block_id ) . '" type="email" data-required="' . esc_attr( $required ? 'true' : 'false' ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="sureforms-input-field sureforms-input-confirm-email">'
		: '' ) .
		( '' !== $help ? '<label for="sureforms-input-email" class="sf-text-secondary sforms-helper-txt">' . esc_html( $help ) . '</label>' : '' ) .
		'<span style="display:none" class="error-message">' . esc_html( $error_msg ) . '</span>
        <span style="display:none" class="error-message confirm-email-error">' . esc_html( __( 'Email does not match', 'sureforms' ) ) . '</span>
    </div>';

	}

	/**
	 * Render the sureforms email classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function classic_styling( $attributes ) {
		$required         = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$default          = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
		$placeholder      = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$label            = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help             = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$is_unique        = isset( $attributes['isUnique'] ) ? $attributes['isUnique'] : false;
		$dulicate_msg     = isset( $attributes['duplicateMsg'] ) ? $attributes['duplicateMsg'] : '';
		$error_msg        = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$is_confirm_email = isset( $attributes['isConfirmEmail'] ) ? $attributes['isConfirmEmail'] : false;
		$confirm_label    = isset( $attributes['confirmLabel'] ) ? $attributes['confirmLabel'] : '';
		$classname        = isset( $attributes['className'] ) ? $attributes['className'] : '';
		$block_id         = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';

		return '<div class="main-container sureforms-input-email-container frontend-inputs-holder ' . esc_attr( $classname ) . '">
        <label for="sureforms-input-email-' . esc_attr( $block_id ) . '" class="sf-classic-label-text">
            ' . esc_html( $label ) . ' ' . ( $required && $label ? '<span class="text-red-500"> *</span>' : '' ) . '
        </label>
        <div class="relative mt-2 rounded-md shadow-sm">
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M3 4a2 2 0 00-2 2v1.161l8.441 4.221a1.25 1.25 0 001.118 0L19 7.162V6a2 2 0 00-2-2H3z" />
                    <path d="M19 8.839l-7.77 3.885a2.75 2.75 0 01-2.46 0L1 8.839V14a2 2 0 002 2h14a2 2 0 002-2V8.839z" />
                </svg>
            </div>
            <input type="email" name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" id="sureforms-input-email-' . esc_attr( $block_id ) . '" class="sureforms-input-email  sf-classic-email-element" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" value="' . esc_attr( $default ) . '" placeholder="' . esc_attr( $placeholder ) . '" aria-unique="' . esc_attr( $is_unique ? 'true' : 'false' ) . '">
        </div>
        <p style="display:none" class="error-message">' . esc_html( $error_msg ) . '</p>
        <p style="display:none" class="error-message duplicate-message">' . esc_html( $dulicate_msg ) . '</p>
        ' . ( true === $is_confirm_email ? '
        <label for="sureforms-input-email-' . esc_attr( $block_id ) . '" class="sf-classic-label-text !mt-[24px]">
            ' . esc_html( $confirm_label ) . ( $required && $label ? '<span class="text-red-500"> *</span>' : '' ) . '
        </label>
        <div class="relative mt-2 rounded-md shadow-sm">
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M3 4a2 2 0 00-2 2v1.161l8.441 4.221a1.25 1.25 0 001.118 0L19 7.162V6a2 2 0 00-2-2H3z" />
                    <path d="M19 8.839l-7.77 3.885a2.75 2.75 0 01-2.46 0L1 8.839V14a2 2 0 002 2h14a2 2 0 002-2V8.839z" />
                </svg>
            </div>
            <input type="email" id="sureforms-input-confirm-email-' . esc_attr( $block_id ) . '" class="sureforms-input-confirm-email  sf-classic-email-element" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" value="' . esc_attr( $default ) . '" placeholder="' . esc_attr( $placeholder ) . '">
        </div>' : '' ) . '
		' . ( '' !== $help ? '<p class="sforms-helper-txt" id="text-description">' . esc_html( $help ) . '</p>' : '' ) . '
        <p style="display:none" class="error-message srfm-cnf-email-required-message">' . esc_html( $error_msg ) . '</p>
        <p style="display:none" class="error-message confirm-email-error ">' . esc_html( __( 'Email does not match', 'sureforms' ) ) . '</p>
    </div>';

	}

}
