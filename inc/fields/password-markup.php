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
		$required            = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$placeholder         = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$label               = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help                = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg           = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$is_confirm_password = isset( $attributes['isConfirmPassword'] ) ? $attributes['isConfirmPassword'] : false;
		$confirm_label       = isset( $attributes['confirmLabel'] ) ? $attributes['confirmLabel'] : '';
		$classname           = isset( $attributes['className'] ) ? $attributes['className'] : '';
		$block_id            = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';

		return '<div class="srfm-input-password-container srfm-main-container srfm-frontend-inputs-holder ' . esc_attr( $classname ) . '">' .
		'<label for="srfm-input-password-' . esc_attr( $block_id ) . '" class="srfm-text-primary">' . esc_html( $label ) . ' ' . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '</label>' .
		'<input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" id="srfm-input-password-' . esc_attr( $block_id ) . '" type="password" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="srfm-input-field">' .
		'<span class="srfm-info-icon" data-tooltip="A stronger password is required minimum 8 characters using upper and lower case letters, numbers, and symbols.">&#9432; <span class="srfm-password-strength-message"></span></span>' .
		'<span style="display:none" class="srfm-error-message">' . esc_html( $error_msg ) . '</span>' .
		( true === $is_confirm_password ?
			'<label for="srfm-confirm-input-password-' . esc_attr( $block_id ) . '" class="srfm-text-primary srfm-confirm-pwd-spl">' . esc_html( $confirm_label ) . ' ' . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '</label>' .
			'<input id="srfm-confirm-input-password-' . esc_attr( $block_id ) . '" type="password" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="srfm-input-field srfm-confirm-input-password">' : ''
		) .
		( '' !== $help ? '<p class="srfm-text-secondary srfm-helper-txt">' . esc_html( $help ) . '</p>' : '' ) .
		'<p style="display:none" class="srfm-error-message">' . esc_attr( $error_msg ) . '</p>' .
			'<p style="display:none" class="srfm-error-message srfm-confirm-password-error">Field values do not match.</p>
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
		$required            = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$placeholder         = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$field_width         = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$label               = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help                = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg           = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$is_confirm_password = isset( $attributes['isConfirmPassword'] ) ? $attributes['isConfirmPassword'] : false;
		$confirm_label       = isset( $attributes['confirmLabel'] ) ? $attributes['confirmLabel'] : '';
		$classname           = isset( $attributes['className'] ) ? $attributes['className'] : '';
		$block_id            = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';

		return '<div class="srfm-input-password-container srfm-frontend-inputs-holder srfm-main-container srfm-classic-inputs-holder' . esc_attr( $classname ) . '"  style="width:calc(' . esc_attr( $field_width ) . '% - 20px);" >
    <label for="srfm-input-password-' . esc_attr( $block_id ) . '" class="srfm-classic-label-text">' . esc_html( $label ) . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '</label>
    <div class= "srfm-relative srfm-mt-2">
        <input type="password" name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" id="srfm-input-password-' . esc_attr( $block_id ) . '" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="srfm-classic-pwd-element">
        <div class="srfm-pointer-events-none srfm-absolute srfm-inset-y-0 srfm-right-0 srfm-flex srfm-items-center srfm-pr-3">
		<i class="fa fa-lock srfm-text-gray-400 srfm-text-[18px]" aria-hidden="true"></i>
        </div>
    </div>' .
		'<p style="display:none" class="srfm-error-message" id="srfm-email-error">' . esc_html( $error_msg ) . '</p>' .
		( true === $is_confirm_password
		? '<label for="srfm-input-password-' . esc_attr( $block_id ) . '" class="srfm-classic-label-text !srfm-mt-[24px]">' . esc_html( $confirm_label ) . ( $required && $confirm_label ? '<span style="color:red;"> *</span>' : '' ) . '</label>
        <div class= "srfm-relative srfm-mt-2 srfm-rounded-md srfm-shadow-sm">
            <input type="password" id="srfm-confirm-input-password-' . esc_attr( $block_id ) . '" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="srfm-confirm-input-password srfm-classic-pwd-element">
            <div class="srfm-pointer-events-none srfm-absolute srfm-inset-y-0 srfm-right-0 srfm-flex srfm-items-center srfm-pr-3">
			<i class="fa fa-lock srfm-text-gray-400 srfm-text-[18px]" aria-hidden="true"></i>
            </div>
        </div>'
		: '' ) .
		( '' !== $help ? '<p class="srfm-helper-txt">' . esc_attr( $help ) . '</p>' : '' ) .
		'<p style="display:none" class="srfm-error-message " id="srfm-email-error">' . esc_html( $error_msg ) . '</p>
    <p style="display:none" class="srfm-error-message srfm-confirm-password-error ">' . esc_html( __( 'Password does not match', 'sureforms' ) ) . '</p>
</div>';
	}

}
