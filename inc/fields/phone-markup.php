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
	 * Return Phone codes
	 *
	 * @return mixed|array<mixed|string> $data with phone codes
	 */
	public function getPhoneCodes() {
		$file_path = plugin_dir_url( __FILE__ ) . 'phone_codes.json';
		$response  = wp_remote_get( $file_path );
		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
			$json_string = wp_remote_retrieve_body( $response );
			$data        = json_decode( $json_string, true );
		} else {
			$data = array();
		}

		return $data;
	}

	/**
	 * Render the sureforms phone default styling block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function default_styling( $attributes ) {
		$block_id        = isset( $attributes['block_id'] ) ? strval( $attributes['block_id'] ) : '';
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

		$data = $this->getPhoneCodes();

		$output  = '';
		$output .= '<div class="srfm-input-phone-container srfm-main-container srfm-frontend-inputs-holder ' . esc_attr( $classname ) . '" id="srfm-input-phone-' . esc_attr( $block_id ) . '">
                <label class="srfm-text-primary">' . esc_html( $label ) . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '</label>
                <div class="srfm-input-phone-holder">
                    <input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" type="hidden" aria-unique="' . esc_attr( $is_unique ? 'true' : 'false' ) . '" id="srfm-fullPhoneNumber-' . esc_attr( $block_id ) . '" value="' . esc_attr( ! empty( $default ) ? "($default_country)$default" : '' ) . '" />
                    <select id="srfm-country-code-' . esc_attr( $block_id ) . '" ' . esc_attr( $required ? 'required' : '' ) . '>';
		if ( is_array( $data ) ) {
			foreach ( $data as $country ) {
				if ( isset( $country['code'] ) && isset( $country['dial_code'] ) ) {
					$output .= '<option value="' . esc_attr( $country['dial_code'] ) . '" ' . ( $country['dial_code'] === $default_country ? 'selected' : '' ) . '>' . esc_html( $country['code'] . ' ' . $country['dial_code'] ) . '</option>';
				}
			}
		}
			$output .= '</select>
                    <input type="tel" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" aria-unique="' . esc_attr( $is_unique ? 'true' : 'false' ) . '" value="' . esc_attr( $default ) . '" placeholder="' . esc_attr( $placeholder ) . '"
                        id="srfm-phone-number-' . esc_attr( $block_id ) . '"
                        class="srfm-input-field" />
                </div>' .
			( '' !== $help ? '<label class="srfm-text-secondary srfm-helper-txt">' . esc_html( $help ) . '</label>' : '' ) .
			'<span style="display:none" class="srfm-error-message">' . esc_html( $error_msg ) . '</span>
            <span style="display:none" class="srfm-error-message srfm-duplicate-message">' . esc_html( $dulicate_msg ) . '</span>
            </div>';

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
		$block_id        = isset( $attributes['block_id'] ) ? strval( $attributes['block_id'] ) : '';
		$default         = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
		$default_country = isset( $attributes['defaultCountryCode'] ) ? $attributes['defaultCountryCode'] : '';
		$required        = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$placeholder     = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$field_width     = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$label           = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help            = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg       = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$is_unique       = isset( $attributes['isUnique'] ) ? $attributes['isUnique'] : false;
		$dulicate_msg    = isset( $attributes['duplicateMsg'] ) ? $attributes['duplicateMsg'] : '';
		$classname       = isset( $attributes['className'] ) ? $attributes['className'] : '';

		$data = $this->getPhoneCodes();

		$output  = '';
		$output .= '<div class="srfm-input-phone-container srfm-main-container srfm-frontend-inputs-holder ' . esc_attr( $classname ) . '" id="srfm-input-phone-' . esc_attr( $block_id ) . '" style="width:calc(' . esc_attr( $field_width ) . '% - 20px);">
            <label for="srfm-phone-number-' . esc_attr( $block_id ) . '" class="srfm-classic-label-text">' . esc_html( $label ) . ' ' . ( $required && $label ? '<span class="srfm-text-red-500"> *</span>' : '' ) . '</label>
            <div class= "srfm-relative srfm-mt-2">
                <div id="srfm-phone-parent" class="group srfm-classic-phone-parent">
                    <div class="srfm-absolute srfm-inset-y-0 srfm-left-0 srfm-flex srfm-items-center">
                        <input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" type="hidden" aria-unique="' . esc_attr( $is_unique ? 'true' : 'false' ) . '" id="srfm-fullPhoneNumber-' . esc_attr( $block_id ) . '" value="' . esc_attr( ! empty( $default ) ? "($default_country)$default" : '' ) . '" />
                        <select class="srfm-classic-phone-select" id="srfm-country-code-' . esc_attr( $block_id ) . '">';
		if ( is_array( $data ) ) {
			foreach ( $data as $country ) {
				if ( isset( $country['code'] ) && isset( $country['dial_code'] ) ) {
					$output .= '<option value="' . esc_attr( $country['dial_code'] ) . '" ' . ( $country['dial_code'] === $default_country ? 'selected' : '' ) . '>' . esc_html( $country['code'] ) . '</option>';
				}
			}
		}
			$output .= '</select>
                        </div>
                        <input type="tel" id="srfm-phone-number-' . esc_attr( $block_id ) . '" class="srfm-classic-phone-element" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" value="' . esc_attr( $default ) . '" placeholder="' . esc_attr( $placeholder ) . '">
                    </div>
                </div>' . ( '' !== $help ? '<p class="srfm-helper-txt" id="srfm-text-description">' . esc_html( $help ) . '</p>' : '' ) . '<p style="display:none;" class="srfm-error-message">' . esc_html( $error_msg ) . '</p> <p style="display:none" class="srfm-duplicate-message">' . esc_html( $dulicate_msg ) . '</p>
            </div>';
		return $output;
	}

}
