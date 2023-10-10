<?php
/**
 * Sureforms Number Slider Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;

/**
 * Sureforms Input Markup Class.
 *
 * @since 0.0.1
 */
class Number_Slider_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms number slider default styling block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function default_styling( $attributes ) {
		$id            = isset( $attributes['id'] ) ? strval( $attributes['id'] ) : '';
		$required           = isset( $attributes['required'] ) ? $attributes['required'] : false;
        $label              = isset( $attributes['label'] ) ? $attributes['label'] : '';
        $help               = isset( $attributes['help'] ) ? $attributes['help'] : '';
        $min                = isset( $attributes['min'] ) ? $attributes['min'] : 0;
        $max                = isset( $attributes['max'] ) ? $attributes['max'] : 100;
        $step               = isset( $attributes['step'] ) ? $attributes['step'] : 1;
        $value_display_text = isset( $attributes['valueDisplayText'] ) ? $attributes['valueDisplayText'] : '';
        $error_msg          = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
        $classname          = isset( $attributes['className'] ) ? $attributes['className'] : '';

		return '<div class="sureforms-number-slider-container main-container frontend-inputs-holder ' . esc_attr($classname) . '">
        <label class="sf-text-primary" for="sureforms-number-slider-' . esc_attr($id) . '">' . esc_html($label) . ' ' .
        ($required && $label ? '<span style="color:red;"> *</span>' : '') . '</label>
        <input name="' . esc_attr(str_replace(' ', '_', $label . 'SF-divider' . $id)) . '" id="sureforms-number-slider-' . esc_attr($id) . '" type="range" aria-required="' .
        esc_attr($required ? 'true' : 'false') . '" 
        min="' . intval($min) . '" max="' . intval($max) . '" step="' . intval($step) . '" value="0"
        class="sureforms-number-slider-input"
        >
        <div style="font-size:14px; font-weight:600;">' . esc_html($value_display_text) . '<span id="sureforms-number-slider-value-' . esc_attr($id) . '">0</span></div>' .
        ('' !== $help ? '<label class="sf-text-secondary sforms-helper-txt" for="sureforms-number-slider-' . esc_attr($id) . '">' . esc_html($help) . '</label>' : '') .
        '<span style="display:none" class="error-message">' . esc_html($error_msg) . '</span>
        </div>';
	}

	/**
	 * Render the sureforms number slider classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function classic_styling( $attributes ) {
		$id            = isset( $attributes['id'] ) ? strval( $attributes['id'] ) : '';
		$required           = isset( $attributes['required'] ) ? $attributes['required'] : false;
        $label              = isset( $attributes['label'] ) ? $attributes['label'] : '';
        $help               = isset( $attributes['help'] ) ? $attributes['help'] : '';
        $min                = isset( $attributes['min'] ) ? $attributes['min'] : 0;
        $max                = isset( $attributes['max'] ) ? $attributes['max'] : 100;
        $step               = isset( $attributes['step'] ) ? $attributes['step'] : 1;
        $value_display_text = isset( $attributes['valueDisplayText'] ) ? $attributes['valueDisplayText'] : '';
        $error_msg          = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
        $classname          = isset( $attributes['className'] ) ? $attributes['className'] : '';

		return '<div class="sureforms-number-slider-container sf-classic-number-slider sf-classic-inputs-holder ' . esc_attr( $classname ) . '">
        <div class="range-slider-container">
            <div class="range-slider-block">
                <div id="range-sliders" class="range-sliders w-full">
                    <div class="range-slider-group range-slider-group-sf">
                        <label for="range-slider-sf" class="sf-classic-label-text">' . esc_html($label) . ' ' .
                        ($required && $label ? '<span class="text-red-500"> *</span>' : '') . '</label>
                        <div class="flex justify-between items-center">
                            <input name="' . esc_attr(str_replace(' ', '_', $label . 'SF-divider' . $id)) . '" type="range" min="' .
                            intval($min) . '" max="' . intval($max) . '" value="' . intval($min) . '" data-color="#0284c7"
                            step="' . intval($step) . '" aria-required="' . esc_attr($required ? 'true' : 'false') . '" class="range-slider range-slider-sf !border-solid !border !border-[#d1d5db]" id="range-slider-sf" />
                            <input type="number" min="' . intval($min) . '" max="' . intval($max) . '" value="' . intval($min) . '" class="input-slider number-input-slider-sf !w-[60px] !border-solid !border-[1px] !border-[#D1D5DB] !rounded-md !px-2 !py-1 !text-center !bg-white focus:!border-sf_primary_color focus:!ring-sf_primary_color focus:!outline-0 focus:!bg-white sm:text-sm sm:leading-6" id="input-slider-sf" />
                        </div>
                    </div>
                </div>
            </div>
        </div>' . 
        ('' !== $help ? '<p class="text-sm text-gray-500" id="text-description">' . esc_html($help) . '</p>' : '') . 
        '<p style="display:none" class="error-message">' . esc_html($error_msg) . '</p>
    </div>';
	}

}
