<?php
/**
 * Sureforms Password Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;

/**
 * Sureforms_Password_Markup Class.
 *
 * @since 0.0.1
 */
class Password_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms password default styling block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function default_styling( $attributes ) {
		$id                  = isset( $attributes['id'] ) ? strval( $attributes['id'] ) : '';
		$required            = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$placeholder         = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$label               = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help                = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg           = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$is_confirm_password = isset( $attributes['isConfirmPassword'] ) ? $attributes['isConfirmPassword'] : false;
		$confirm_label       = isset( $attributes['confirmLabel'] ) ? $attributes['confirmLabel'] : '';
		$classname           = isset( $attributes['className'] ) ? $attributes['className'] : '';

		return '<div class="sureforms-input-password-container main-container frontend-inputs-holder ' . esc_attr( $classname ) . '">' .
		'<label for="sureforms-input-password-' . esc_attr( $id ) . '" class="sf-text-primary">' . esc_html( $label ) . ' ' . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '</label>' .
		'<input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ) . '" id="sureforms-input-password-' . esc_attr( $id ) . '" type="password" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="sureforms-input-field">' .
		'<span class="info-icon" data-tooltip="A stronger password is required minimum 8 characters using upper and lower case letters, numbers, and symbols.">&#9432; <span class="password-strength-message"></span></span>' .
		'<span style="display:none" class="error-message">' . esc_html( $error_msg ) . '</span>' .
		( true === $is_confirm_password ?
			'<label for="sureforms-confirm-input-password-' . esc_attr( $id ) . '" class="sf-text-primary sureforms-confirm-pwd-spl">' . esc_html( $confirm_label ) . ' ' . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '</label>' .
			'<input id="sureforms-confirm-input-password-' . esc_attr( $id ) . '" type="password" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="sureforms-input-field sureforms-confirm-input-password">' : ''
		) .
		( '' !== $help ? '<label for="sureforms-input-password" class="sf-text-secondary sforms-helper-txt">' . esc_html( $help ) . '</label>' : '' ) .
		'<span style="display:none" class="error-message">' . esc_attr( $error_msg ) . '</span>' .
			'<span style="display:none" class="error-message confirm-password-error">Field values do not match.</span>
        </div>';
	}

	/**
	 * Render the sureforms password classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function classic_styling( $attributes ) {
		$id                  = isset( $attributes['id'] ) ? strval( $attributes['id'] ) : '';
		$required            = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$placeholder         = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$label               = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help                = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg           = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$is_confirm_password = isset( $attributes['isConfirmPassword'] ) ? $attributes['isConfirmPassword'] : false;
		$confirm_label       = isset( $attributes['confirmLabel'] ) ? $attributes['confirmLabel'] : '';
		$classname           = isset( $attributes['className'] ) ? $attributes['className'] : '';

		return '<div class="sureforms-input-password-container frontend-inputs-holder main-container ' . esc_attr( $classname ) . '">
    <label for="sureforms-input-password-' . esc_attr( $id ) . '" class="sf-classic-label-text">' . esc_html( $label ) . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '</label>
    <div class="relative mt-2">
        <input type="password" name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ) . '" id="sureforms-input-password-' . esc_attr( $id ) . '" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="sf-classic-pwd-element">
        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
            <svg  class="h-5 w-5 text-gray-400" viewBox="0 0 30 30" fill="currentColor" aria-hidden="true">
                <path d="M 15 2 C 11.145666 2 8 5.1456661 8 9 L 8 11 L 6 11 C 4.895 11 4 11.895 4 13 L 4 25 C 4 26.105 4.895 27 6 27 L 24 27 C 25.105 27 26 26.105 26 25 L 26 13 C 26 11.895 25.105 11 24 11 L 22 11 L 22 9 C 22 5.2715823 19.036581 2.2685653 15.355469 2.0722656 A 1.0001 1.0001 0 0 0 15 2 z M 15 4 C 17.773666 4 20 6.2263339 20 9 L 20 11 L 10 11 L 10 9 C 10 6.2263339 12.226334 4 15 4 z"></path>
            </svg>
        </div>
    </div>' .
		'<p style="display:none" class="error-message" id="email-error">' . esc_html( $error_msg ) . '</p>' .
		( true === $is_confirm_password
		? '<label for="sureforms-input-password-' . esc_attr( $id ) . '" class="sf-classic-label-text !mt-[24px]">' . esc_html( $confirm_label ) . ( $required && $confirm_label ? '<span style="color:red;"> *</span>' : '' ) . '</label>
        <div class="relative mt-2 rounded-md shadow-sm">
            <input type="password" id="sureforms-confirm-input-password-' . esc_attr( $id ) . '" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="sureforms-confirm-input-password sf-classic-pwd-element">
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                <svg  class="h-5 w-5 text-gray-400" viewBox="0 0 30 30" fill="currentColor" aria-hidden="true">
                    <path d="M 15 2 C 11.145666 2 8 5.1456661 8 9 L 8 11 L 6 11 C 4.895 11 4 11.895 4 13 L 4 25 C 4 26.105 4.895 27 6 27 L 24 27 C 25.105 27 26 26.105 26 25 L 26 13 C 26 11.895 25.105 11 24 11 L 22 11 L 22 9 C 22 5.2715823 19.036581 2.2685653 15.355469 2.0722656 A 1.0001 1.0001 0 0 0 15 2 z M 15 4 C 17.773666 4 20 6.2263339 20 9 L 20 11 L 10 11 L 10 9 C 10 6.2263339 12.226334 4 15 4 z"></path>
                </svg>
            </div>
        </div>'
		: '' ) .
		( '' !== $help ? '<label for="sureforms-input-password" class="sforms-helper-txt">' . esc_attr( $help ) . '</label>' : '' ) .
		'<p style="display:none" class="error-message " id="email-error">' . esc_html( $error_msg ) . '</p>
    <p style="display:none" class="error-message confirm-password-error ">' . esc_html( __( 'Password does not match', 'sureforms' ) ) . '</p>
</div>';
	}

}
