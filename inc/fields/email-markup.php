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

		return '<div class="srfm-input-email-container srfm-main-container srfm-frontend-inputs-holder ' . esc_attr( $classname ) . '">
        <label for="srfm-input-email-' . esc_attr( $block_id ) . '" class="srfm-text-primary">' . esc_html( $label ) . ' ' . ( $required && $label ? '<span class="!srfm-text-required_icon_color"> *</span>' : '' ) . '</label>
        <input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" id="srfm-input-email-' . esc_attr( $block_id ) . '" type="email" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" value="' . esc_attr( $default ) . '" placeholder="' . esc_attr( $placeholder ) . '" aria-unique="' . esc_attr( $is_unique ? 'true' : 'false' ) . '" class="srfm-input-email srfm-input-field">
        <span style="display:none" class="srfm-error-message">' . esc_html( $error_msg ) . '</span>
        <span style="display:none" class="srfm-error-message srfm-duplicate-message">' . esc_html( $dulicate_msg ) . '</span>' .
		( true === $is_confirm_email ?
			'<label for="srfm-input-confirm-email-' . esc_attr( $block_id ) . '" class="srfm-text-primary srfm-confirm-email-spl">' . esc_html( $confirm_label ) . ' ' . ( $required && $label ? '<span class="!srfm-text-required_icon_color"> *</span>' : '' ) . '</label>' .
			'<input id="srfm-input-confirm-email-' . esc_attr( $block_id ) . '" type="email" data-required="' . esc_attr( $required ? 'true' : 'false' ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="srfm-input-field srfm-input-confirm-email">'
		: '' ) .
		( '' !== $help ? '<label for="srfm-input-email" class="srfm-text-secondary srfm-helper-txt">' . esc_html( $help ) . '</label>' : '' ) .
		'<span style="display:none" class="srfm-error-message">' . esc_html( $error_msg ) . '</span>
        <span style="display:none" class="srfm-error-message srfm-confirm-email-error">' . esc_html( __( 'Email does not match', 'sureforms' ) ) . '</span>
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

		return '<div class="srfm-main-container srfm-input-email-container srfm-frontend-inputs-holder ' . esc_attr( $classname ) . '">
        <label for="srfm-input-email-' . esc_attr( $block_id ) . '" class="srfm-classic-label-text">
            ' . esc_html( $label ) . ' ' . ( $required && $label ? '<span class="srfm-text-red-500"> *</span>' : '' ) . '
        </label>
        <div class= "srfm-relative srfm-mt-2 srfm-rounded-md srfm-shadow-sm">
            <div class="srfm-pointer-events-none srfm-absolute srfm-inset-y-0 srfm-right-0 srfm-flex srfm-items-center srfm-pr-3">
                <i class="fa fa-envelope srfm-text-gray-400 srfm-text-[18px]" aria-hidden="true"></i>
            </div>
            <input type="email" name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" id="srfm-input-email-' . esc_attr( $block_id ) . '" class="srfm-input-email  srfm-classic-email-element" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" value="' . esc_attr( $default ) . '" placeholder="' . esc_attr( $placeholder ) . '" aria-unique="' . esc_attr( $is_unique ? 'true' : 'false' ) . '">
        </div>
        <p style="display:none" class="srfm-error-message">' . esc_html( $error_msg ) . '</p>
        <p style="display:none" class="srfm-error-message srfm-duplicate-message">' . esc_html( $dulicate_msg ) . '</p>
        ' . ( true === $is_confirm_email ? '
        <label for="srfm-input-email-' . esc_attr( $block_id ) . '" class="srfm-classic-label-text !srfm-mt-[24px]">
            ' . esc_html( $confirm_label ) . ( $required && $label ? '<span class="srfm-text-red-500"> *</span>' : '' ) . '
        </label>
        <div class= "srfm-relative srfm-mt-2 srfm-rounded-md srfm-shadow-sm">
            <div class="srfm-pointer-events-none srfm-absolute srfm-inset-y-0 srfm-right-0 srfm-flex srfm-items-center srfm-pr-3">
                <i class="fa fa-envelope srfm-text-gray-400 srfm-text-[18px]" aria-hidden="true"></i>
            </div>
            <input type="email" id="srfm-input-confirm-email-' . esc_attr( $block_id ) . '" class="srfm-input-confirm-email  srfm-classic-email-element" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" value="' . esc_attr( $default ) . '" placeholder="' . esc_attr( $placeholder ) . '">
        </div>' : '' ) . '
		' . ( '' !== $help ? '<p class="srfm-helper-txt" id="srfm-text-description">' . esc_html( $help ) . '</p>' : '' ) . '
        <p style="display:none" class="srfm-error-message srfm-cnf-email-required-message">' . esc_html( $error_msg ) . '</p>
        <p style="display:none" class="srfm-error-message srfm-confirm-email-error ">' . esc_html( __( 'Email does not match', 'sureforms' ) ) . '</p>
    </div>';

	}

}
