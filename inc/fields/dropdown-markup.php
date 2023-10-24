<?php
/**
 * Sureforms Dropdown Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;

/**
 * Sureforms Dropdown Markup Class.
 *
 * @since 0.0.1
 */
class Dropdown_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms dropdown default styling block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function default_styling( $attributes ) {
		$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$options     = isset( $attributes['options'] ) ? $attributes['options'] : '';
		$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help        = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg   = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$classname   = isset( $attributes['className'] ) ? $attributes['className'] : '';
		$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$block_id    = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';

		$output  = '';
		$output .= '<div class="sureforms-dropdown-container main-container frontend-inputs-holder ' . esc_attr( $classname ) . '">
        <label class="sf-text-primary">' . esc_html( $label ) . ' 
            ' . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '
        </label>
        <select 
        name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '"
        aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '"
        class="sureforms-input-field"
        >';
		if ( ! empty( $placeholder ) ) {
			$output .= '<option value="">' . $placeholder . '</option>';
		}
		foreach ( $options as $option ) {
			$option      = esc_attr( $option );
			$option_text = esc_html( $option );

			$output .= '<option value="' . $option . '">' . $option_text . '</option>';
		}

		$output .= '</select>' . ( '' !== $help ? '<label class="sf-text-secondary sforms-helper-txt">' . esc_html( $help ) . '</label>' : '' ) . '
                <span style="display:none" class="error-message">' . esc_html( $error_msg ) . '</span>
            </div>';
		return $output;
	}

	/**
	 * Render the sureforms dropdown classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function classic_styling( $attributes ) {
		$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$options     = isset( $attributes['options'] ) ? $attributes['options'] : '';
		$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help        = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg   = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$classname   = isset( $attributes['className'] ) ? $attributes['className'] : '';
		$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$block_id    = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';

		$output  = '';
		$output .= '<div class="sureforms-classic-dropdown-container main-container frontend-inputs-holder ' . esc_attr( $classname ) . '">
        <label id="listbox-label" class="sf-classic-label-text">' . esc_html( $label ) . ' 
            ' . ( $required && $label ? '<span class="text-required_icon_color"> *</span>' : '' ) . '
        </label>
        <div class="relative mt-2">
            <input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" type="hidden" class="sf-classic-dropdown-result" value="" />
            <button type="button" class="sureforms-classic-dropdown-button sf-classic-dropdown-btn" id="sureforms-classic-dropdown-button-' . esc_attr( $block_id ) . '">
                <span class="sf-dropdown-value block truncate">' . esc_attr( '' !== $placeholder ? $placeholder : '&nbsp;' ) . '</span>
                <span class="sf-classic-select-icon pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2 duration-300 transition-all">
                    <i class="fa-solid fa-angle-down h-5 w-5 text-gray-400 mt-[10px]"></i>
                </span>
            </button>
            <ul class="sf-classic-dropdown-box sf-classic-dropdown-ul" tabindex="-1" value="value" style="display: none;">' . ( 0 === count( $options ) ? '<div class="text-gray-500 relative select-none py-2 pl-3 pr-9">' . esc_html__( 'No Options Found', 'sureforms' ) . '</div>' : '' );

		foreach ( $options as $option ) {
			$option_text = esc_html( $option );

			$output .= '<li class="sf-classic-dropdown-option sf-classic-dropdown-li" id="listbox-option-0" role="option">
                            <span class="font-normal block truncate">' . $option_text . '</span>
                        </li>';
		}

		$output .= '</ul>
                </div>
                ' . ( '' !== $help ? '<label for="sureforms-input-text" class="sf-text-secondary sforms-helper-txt">' . esc_html( $help ) . '</label>' : '' ) . '
                <span style="display:none" class="error-message">' . esc_html( $error_msg ) . '</span>
            </div>';
		return $output;
	}

}
