<?php
/**
 * Sureforms Dropdown Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;

/**
 * Sureforms Dropdown Markup Class.
 *
 * @since 0.0.1
 */
class Dropdown_Markup extends Base {
	use Get_Instance;

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
		$field_width = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help        = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg   = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$classname   = isset( $attributes['className'] ) ? $attributes['className'] : '';
		$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$block_id    = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';

		$output  = '';
		$output .= '<div class="srfm-classic-dropdown-container srfm-main-container srfm-frontend-inputs-holder ' . esc_attr( $classname ) . '"  style="width:calc(' . esc_attr( $field_width ) . '% - 20px);" >
        <label for="srfm-classic-dropdown-button-' . esc_attr( $block_id ) . '" class="srfm-classic-label-text">' . esc_html( $label ) . ' 
            ' . ( $required && $label ? '<span class="!srfm-text-required_icon_color"> *</span>' : '' ) . '
        </label>
        <div class= "srfm-relative srfm-mt-2">
            <input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" type="hidden" id="srfm-classic-dropdown-' . esc_attr( $block_id ) . '" class="srfm-classic-dropdown-result" value="" />
            <button type="button" class="srfm-classic-dropdown-button srfm-classic-dropdown-btn" id="srfm-classic-dropdown-button-' . esc_attr( $block_id ) . '">
                <span class="srfm-dropdown-value srfm-font-normal srfm-text-gray-900 srfm-block srfm-truncate">' . esc_attr( '' !== $placeholder ? $placeholder : '&nbsp;' ) . '</span>
                <span class="srfm-classic-select-icon srfm-pointer-events-none srfm-absolute srfm-inset-y-0 srfm-right-0 srfm-flex srfm-items-center srfm-pr-2 srfm-duration-300 srfm-transition-all">
                    <i class="fa-solid fa-angle-down srfm-h-5 srfm-w-5 srfm-text-gray-500 srfm-mt-[10px]"></i>
                </span>
            </button>
            <ul class="srfm-classic-dropdown-box srfm-classic-dropdown-ul" tabindex="-1" value="value" style="display: none;">' . ( 0 === count( $options ) ? '<div class="srfm-text-gray-500 srfm-relative srfm-select-none srfm-py-2 srfm-pl-3 srfm-pr-9">' . esc_html__( 'No Options Found', 'sureforms' ) . '</div>' : '' );

		foreach ( $options as $option ) {
			$option_text = esc_html( $option );

			$output .= '<li class="srfm-classic-dropdown-option srfm-classic-dropdown-li" id="srfm-listbox-option-0" role="option">
                            <span class="srfm-font-normal srfm-block srfm-truncate">' . $option_text . '</span>
                        </li>';
		}

		$output .= '</ul>
                </div>
                ' . ( '' !== $help ? '<p class="srfm-text-secondary srfm-helper-txt">' . esc_html( $help ) . '</p>' : '' ) . '
                <p style="display:none" class="srfm-error-message">' . esc_html( $error_msg ) . '</p>
            </div>';
		return $output;
	}

}
