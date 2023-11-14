<?php
/**
 * Sureforms Phone Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;

/**
 * Sureforms_Phone_Markup Class.
 *
 * @since 0.0.1
 */
class Phone_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms phone default styling block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function default_styling( $attributes ) {
		$block_id     = isset( $attributes['block_id'] ) ? strval( $attributes['block_id'] ) : '';
		$required     = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$placeholder  = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$label        = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help         = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg    = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$is_unique    = isset( $attributes['isUnique'] ) ? $attributes['isUnique'] : false;
		$dulicate_msg = isset( $attributes['duplicateMsg'] ) ? $attributes['duplicateMsg'] : '';
		$classname    = isset( $attributes['className'] ) ? $attributes['className'] : '';

		$output  = '';
		$output .= '<div class="srfm-input-phone-container srfm-main-container srfm-frontend-inputs-holder ' . esc_attr( $classname ) . '" id="srfm-input-phone-' . esc_attr( $block_id ) . '">
                <label class="srfm-text-primary">' . esc_html( $label ) . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '</label>
                <div class="srfm-input-phone-holder">
                    <input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" type="hidden" aria-unique="' . esc_attr( $is_unique ? 'true' : 'false' ) . '" id="srfm-fullPhoneNumber-' . esc_attr( $block_id ) . '" value="" />
                    <input type="tel" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" aria-unique="' . esc_attr( $is_unique ? 'true' : 'false' ) . '" value="" placeholder="' . esc_attr( $placeholder ) . '"
                        id="srfm-phone-number-' . esc_attr( $block_id ) . '"
                        class="srfm-input-field" />
                </div>' .
			( '' !== $help ? '<label class="srfm-text-secondary srfm-helper-txt">' . esc_html( $help ) . '</label>' : '' ) .
			'<span style="display:none" class="srfm-error-message">' . esc_html( $error_msg ) . '</span>
            <span style="display:none" class="srfm-error-message srfm-duplicate-message">' . esc_html( $dulicate_msg ) . '</span>
            <p style="display:none" class="srfm-int-tel-error srfm-error-message"></p></div>';

		return $output;

	}

	/**
	 * Render the sureforms phone classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function classic_styling( $attributes ) {
		$block_id     = isset( $attributes['block_id'] ) ? strval( $attributes['block_id'] ) : '';
		$required     = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$placeholder  = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$field_width  = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$label        = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help         = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg    = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$is_unique    = isset( $attributes['isUnique'] ) ? $attributes['isUnique'] : false;
		$dulicate_msg = isset( $attributes['duplicateMsg'] ) ? $attributes['duplicateMsg'] : '';
		$classname    = isset( $attributes['className'] ) ? $attributes['className'] : '';
		$auto_country = isset( $attributes['autoCountry'] ) ? $attributes['autoCountry'] : '';

		$output  = '';
		$output .= '<div class="srfm-input-phone-container srfm-main-container srfm-frontend-inputs-holder ' . esc_attr( $classname ) . '" id="srfm-input-phone-' . esc_attr( $block_id ) . '" style="width:calc(' . esc_attr( $field_width ) . '% - 20px);">
            <label for="srfm-phone-number-' . esc_attr( $block_id ) . '" class="srfm-classic-label-text">' . esc_html( $label ) . ' ' . ( $required && $label ? '<span class="srfm-text-red-500"> *</span>' : '' ) . '</label>
            <div class="srfm-relative srfm-mt-2">
                <div id="srfm-phone-parent" class="srfm-group srfm-classic-phone-parent">
					<input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" type="hidden" aria-unique="' . esc_attr( $is_unique ? 'true' : 'false' ) . '" id="srfm-fullPhoneNumber-' . esc_attr( $block_id ) . '" value="" />
					<input type="tel" id="srfm-phone-number-' . esc_attr( $block_id ) . '" class="srfm-classic-phone-element" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" auto-country="' . esc_attr( $auto_country ? 'true' : 'false' ) . '" value="" placeholder="' . esc_attr( $placeholder ) . '">
				</div>
                </div>' . ( '' !== $help ? '<p class="srfm-helper-txt" id="srfm-text-description">' . esc_html( $help ) . '</p>' : '' ) . '<p style="display:none;" class="srfm-error-message">' . esc_html( $error_msg ) . '</p> <p style="display:none" class="srfm-duplicate-message">' . esc_html( $dulicate_msg ) . '</p>
            <p style="display:none" class="srfm-int-tel-error srfm-error-message">' . esc_html( __( 'Please enter a valid phone number.', 'sureforms' ) ) . '</p></div>';
		return $output;
	}

}
