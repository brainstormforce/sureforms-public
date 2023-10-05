<?php
/**
 * Sureforms Phone Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc;

use SureForms\Inc\Traits\Get_Instance;

/**
 * Sureforms_Phone_Markup Class.
 *
 * @since 0.0.1
 */
class SureForms_Phone_Markup {
	use Get_Instance;

	/**
	 * Render the sureforms phone default styling block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param array<mixed> $data Block attributes.
	 *
	 * @return string|boolean
	 */
	public static function phone_default_styling( $attributes, $data ) {
		$id              = isset( $attributes['id'] ) ? strval( $attributes['id'] ) : '';
		$default         = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
		$default_country = isset( $attributes['defaultCountryCode'] ) ? $attributes['defaultCountryCode'] : '';
		$required        = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$placeholder     = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$label           = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help            = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg       = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$is_unique       = isset( $attributes['isUnique'] ) ? $attributes['isUnique'] : false;
		$dulicate_msg    = isset( $attributes['duplicateMsg'] ) ? $attributes['duplicateMsg'] : '';
		$classname       = isset( $attributes['className'] ) ? $attributes['className'] : '';

		$output  = '';
		$output .= '<div class="sureforms-input-phone-container main-container frontend-inputs-holder ' . esc_attr( $classname ) . '" id="sureforms-input-phone-' . esc_attr( $id ) . '">
                <label class="sf-text-primary">' . esc_html( $label ) . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '</label>
                <div class="sureforms-input-phone-holder">
                    <input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ) . '" type="hidden" area-unique="' . esc_attr( $is_unique ? 'true' : 'false' ) . '" id="fullPhoneNumber-' . esc_attr( $id ) . '" value="' . esc_attr( ! empty( $default ) ? "($default_country)$default" : '' ) . '" />
                    <select id="sureforms-country-code-' . esc_attr( $id ) . '" ' . esc_attr( $required ? 'required' : '' ) . '>';
		if ( $default_country ) {
			$output .= '<option value="' . esc_attr( $default_country ) . '">' . esc_html( $default_country ) . '</option>';
		}
		if ( is_array( $data ) ) {
			foreach ( $data as $country ) {
				if ( isset( $country['code'] ) && isset( $country['dial_code'] ) ) {
					$output .= '<option value="' . esc_attr( $country['dial_code'] ) . '">' . esc_html( $country['code'] . ' ' . $country['dial_code'] ) . '</option>';
				}
			}
		}
			$output .= '</select>
                    <input type="tel" area-required="' . esc_attr( $required ? 'true' : 'false' ) . '" area-unique="' . esc_attr( $is_unique ? 'true' : 'false' ) . '" value="' . esc_attr( $default ) . '" placeholder="' . esc_attr( $placeholder ) . '"
                        id="sureforms-phone-number-' . esc_attr( $id ) . '"
                        class="sureforms-input-field" />
                </div>' .
			( '' !== $help ? '<label class="sf-text-secondary sforms-helper-txt">' . esc_html( $help ) . '</label>' : '' ) .
			'<span style="display:none" class="error-message">' . esc_html( $error_msg ) . '</span>
            <span style="display:none" class="error-message duplicate-message">' . esc_html( $dulicate_msg ) . '</span>
            </div>';

		return $output;

	}

	/**
	 * Render the sureforms phone classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param array<mixed> $data Block attributes.
	 *
	 * @return string|boolean
	 */
	public static function phone_classic_styling( $attributes, $data ) {
		$id              = isset( $attributes['id'] ) ? strval( $attributes['id'] ) : '';
		$default         = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
		$default_country = isset( $attributes['defaultCountryCode'] ) ? $attributes['defaultCountryCode'] : '';
		$required        = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$placeholder     = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$label           = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help            = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg       = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$is_unique       = isset( $attributes['isUnique'] ) ? $attributes['isUnique'] : false;
		$dulicate_msg    = isset( $attributes['duplicateMsg'] ) ? $attributes['duplicateMsg'] : '';
		$classname       = isset( $attributes['className'] ) ? $attributes['className'] : '';

		$output  = '';
		$output .= '<div class="sureforms-input-phone-container main-container frontend-inputs-holder ' . esc_attr( $classname ) . '" id="sureforms-input-phone-' . esc_attr( $id ) . '">
            <label for="sureforms-phone-number-' . esc_attr( $id ) . '" class="block text-sm font-medium leading-6 sf-text-sf_primary_color">' . esc_html( $label ) . ' ' . ( $required && $label ? '<span class="text-required_icon_color"> *</span>' : '' ) . '</label>
            <div class="relative mt-2">
                <div id="sureforms-phone-parent" class="group sf-classic-phone-parent">
                    <div class="absolute inset-y-0 left-0 flex items-center">
                        <input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ) . '" type="hidden" area-unique="' . esc_attr( $is_unique ? 'true' : 'false' ) . '" id="fullPhoneNumber-' . esc_attr( $id ) . '" value="' . esc_attr( ! empty( $default ) ? "($default_country)$default" : '' ) . '" />
                        <select class="sf-classic-phone-select" id="sureforms-country-code-' . esc_attr( $id ) . '">';
		if ( is_array( $data ) ) {
			foreach ( $data as $country ) {
				if ( isset( $country['code'] ) && isset( $country['dial_code'] ) ) {
					$output .= '<option value="' . esc_attr( $country['dial_code'] ) . '" ' . ( $country['dial_code'] === $default_country ? 'selected' : '' ) . '>' . esc_html( $country['code'] ) . '</option>';
				}
			}
		}
			$output .= '</select>
                        </div>
                        <input type="tel" id="sureforms-phone-number-' . esc_attr( $id ) . '" class="sf-classic-phone-element" area-required="' . esc_attr( $required ? 'true' : 'false' ) . '" value="' . esc_attr( $default ) . '" placeholder="' . esc_attr( $placeholder ) . '">
                    </div>
                </div>' . ( '' !== $help ? '<p class="sforms-helper-txt" id="text-description">' . esc_html( $help ) . '</p>' : '' ) . '<p style="display:none;" class="error-message">' . esc_html( $error_msg ) . '</p> <p style="display:none" class="duplicate-message">' . esc_html( $dulicate_msg ) . '</p>
            </div>';
		return $output;
	}

}
