<?php
/**
 * Sureforms Number Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc;

use SureForms\Inc\Traits\Get_Instance;

/**
 * Sureforms Number Field Markup Class.
 *
 * @since 0.0.1
 */
class SureForms_Number_Field_Markup {
	use Get_Instance;

	/**
	 * Render the sureforms number default styling block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public static function number_field_default_styling( $attributes ) {
			$id          = isset( $attributes['id'] ) ? strval( $attributes['id'] ) : '';
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
            <label for="sureforms-input-number-' . esc_attr( $id ) . '" class="sf-text-primary">' . esc_html( $label ) . ' ' . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '</label>
            <input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ) . '" ' . ( 'none' === $format_type ? 'step="any"' : '' ) . ' id="sureforms-input-number-' . esc_attr( $id ) . '" type="' . ( 'none' === $format_type ? 'number' : 'text' ) . '" value="' . esc_attr( $default ) . '" placeholder="' . esc_attr( $placeholder ) . '" format-type="' . esc_attr( $format_type ) . '" min="' . esc_attr( $min_value ) . '" max="' . esc_attr( $max_value ) . '" area-required="' . esc_attr( $required ? 'true' : 'false' ) . '" class="sureforms-input-field sf-number-field">
            <span style="display:none" class="error-message">' . esc_html( $error_msg ) . '</span>
            <span class="min-max-validation-message error-message"></span>' . ( '' !== $help ? '<label for="sureforms-input-number" class="sf-text-secondary">' . esc_html( $help ) . '</label>' : '' ) . '
         </div>';
	}

	/**
	 * Render the sureforms number classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public static function number_field_classic_styling( $attributes ) {
			$id          = isset( $attributes['id'] ) ? strval( $attributes['id'] ) : '';
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
            <label for="sureforms-input-number-' . esc_attr( $id ) . '" class="sf-classic-label-text">' . esc_html( $label ) . ' ' . ( $required && $label ? '<span class="text-red-500"> *</span>' : '' ) . '</label>
            <div>
                <input type="' . ( 'none' === $format_type ? 'number' : 'text' ) . '" name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ) . '" id="sureforms-input-number-' . esc_attr( $id ) . '" class="sf-classic-number-element" placeholder="' . esc_attr( $placeholder ) . '" area-required="' . esc_attr( $required ? 'true' : 'false' ) . '" value="' . esc_attr( $default ) . '" format-type="' . esc_attr( $format_type ) . '" min="' . esc_attr( $min_value ) . '" max="' . esc_attr( $max_value ) . '">
            </div>' . ( '' !== $help ? '<p class="sforms-helper-txt" id="text-description">' . esc_html( $help ) . '</p>' : '' ) . '
            <p style="display:none" class="error-message">' . esc_html( $error_msg ) . '</p>
            <p class="min-max-validation-message error-message"></p>
        </div>';
	}

}
