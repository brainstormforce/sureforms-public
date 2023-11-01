<?php
/**
 * Sureforms Number Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;

/**
 * Sureforms Number Field Markup Class.
 *
 * @since 0.0.1
 */
class Number_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms number default styling block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function default_styling( $attributes ) {
			$block_id    = isset( $attributes['block_id'] ) ? strval( $attributes['block_id'] ) : '';
			$default     = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
			$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$min_value   = isset( $attributes['minValue'] ) ? $attributes['minValue'] : '';
			$max_value   = isset( $attributes['maxValue'] ) ? $attributes['maxValue'] : '';
			$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
			$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help        = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$error_msg   = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$format_type = isset( $attributes['formatType'] ) ? $attributes['formatType'] : '';
			$classname   = isset( $attributes['className'] ) ? $attributes['className'] : '';

			return '<div class="sureforms-input-number-container main-container frontend-inputs-holder ' . esc_attr( $classname ) . ' ">
            <label for="sureforms-input-number-' . esc_attr( $block_id ) . '" class="sf-text-primary">' . esc_html( $label ) . ' ' . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '</label>
            <input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" ' . ( 'none' === $format_type ? 'step="any"' : '' ) . ' id="sureforms-input-number-' . esc_attr( $block_id ) . '" type="' . ( 'none' === $format_type ? 'number' : 'text' ) . '" value="' . esc_attr( $default ) . '" placeholder="' . esc_attr( $placeholder ) . '" format-type="' . esc_attr( $format_type ) . '" minimum="' . esc_attr( $min_value ) . '" maximum="' . esc_attr( $max_value ) . '" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" class="sureforms-input-field sf-number-field">
			' . ( '' !== $help ? '<label for="sureforms-input-number" class="sf-text-secondary">' . esc_html( $help ) . '</label>' : '' ) . '
            <span style="display:none" class="error-message">' . esc_html( $error_msg ) . '</span>
            <span style="display:none" class="min-max-validation-message error-message"></span>
         </div>';
	}

	/**
	 * Render the sureforms number classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function classic_styling( $attributes ) {
			$block_id    = isset( $attributes['block_id'] ) ? strval( $attributes['block_id'] ) : '';
			$default     = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
			$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$min_value   = isset( $attributes['minValue'] ) ? $attributes['minValue'] : '';
			$max_value   = isset( $attributes['maxValue'] ) ? $attributes['maxValue'] : '';
			$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
			$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help        = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$error_msg   = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$format_type = isset( $attributes['formatType'] ) ? $attributes['formatType'] : '';
			$classname   = isset( $attributes['className'] ) ? $attributes['className'] : '';

			return '<div class="sureforms-input-number-container main-container frontend-inputs-holder ' . esc_attr( $classname ) . '">
            <label for="sureforms-input-number-' . esc_attr( $block_id ) . '" class="sf-classic-label-text">' . esc_html( $label ) . ' ' . ( $required && $label ? '<span class="text-red-500"> *</span>' : '' ) . '</label>
            <div>
                <input type="' . ( 'none' === $format_type ? 'number' : 'text' ) . '" name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" id="sureforms-input-number-' . esc_attr( $block_id ) . '" class="sf-classic-number-element" placeholder="' . esc_attr( $placeholder ) . '" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" value="' . esc_attr( $default ) . '" format-type="' . esc_attr( $format_type ) . '" minimum="' . esc_attr( $min_value ) . '" maximum="' . esc_attr( $max_value ) . '">
            </div>' . ( '' !== $help ? '<p class="sforms-helper-txt" id="text-description">' . esc_html( $help ) . '</p>' : '' ) . '
            <p style="display:none" class="error-message">' . esc_html( $error_msg ) . '</p>
            <p style="display:none" class="min-max-validation-message error-message"></p>
        </div>';
	}

}
