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
		$block_id           = isset( $attributes['block_id'] ) ? strval( $attributes['block_id'] ) : '';
		$label              = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help               = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$min                = isset( $attributes['min'] ) ? $attributes['min'] : 0;
		$max                = isset( $attributes['max'] ) ? $attributes['max'] : 100;
		$step               = isset( $attributes['step'] ) ? $attributes['step'] : 1;
		$value_display_text = isset( $attributes['valueDisplayText'] ) ? $attributes['valueDisplayText'] : '';
		$error_msg          = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$classname          = isset( $attributes['className'] ) ? $attributes['className'] : '';

		return '<div class="srfm-number-slider-container srfm-main-container srfm-frontend-inputs-holder ' . esc_attr( $classname ) . '">
        <label class="srfm-text-primary" for="srfm-number-slider-' . esc_attr( $block_id ) . '">' . esc_html( $label ) . '</label>
        <input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" id="srfm-number-slider-' . esc_attr( $block_id ) . '" type="range"
        min="' . intval( $min ) . '" max="' . intval( $max ) . '" step="' . intval( $step ) . '" value="0"
        class="srfm-number-slider-input"
        >
        <div style="font-size:14px; font-weight:600;">' . esc_html( $value_display_text ) . '<span id="srfm-number-slider-value-' . esc_attr( $block_id ) . '">0</span></div>' .
		( '' !== $help ? '<label class="srfm-text-secondary srfm-helper-txt" for="srfm-number-slider-' . esc_attr( $block_id ) . '">' . esc_html( $help ) . '</label>' : '' ) .
		'<span style="display:none" class="srfm-error-message">' . esc_html( $error_msg ) . '</span>
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
		$block_id           = isset( $attributes['block_id'] ) ? strval( $attributes['block_id'] ) : '';
		$required           = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$field_width        = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$label              = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help               = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$min                = isset( $attributes['min'] ) ? $attributes['min'] : 0;
		$max                = isset( $attributes['max'] ) ? $attributes['max'] : 100;
		$step               = isset( $attributes['step'] ) ? $attributes['step'] : 1;
		$value_display_text = isset( $attributes['valueDisplayText'] ) ? $attributes['valueDisplayText'] : '';
		$error_msg          = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$classname          = isset( $attributes['className'] ) ? $attributes['className'] : '';

		return '<div class="srfm-number-slider-container srfm-classic-number-slider srfm-classic-inputs-holder ' . esc_attr( $classname ) . '"  style="width:calc(' . esc_attr( $field_width ) . '% - 20px);" >
        <div class="range-slider-container">
            <div class="range-slider-block">
                <div id="srfm-range-sliders" class="srfm-range-sliders srfm-w-full">
                    <div class="range-slider-group range-slider-group-sf">
                        <label for="srfm-range-slider" class="srfm-classic-label-text">' . esc_html( $label ) . '</label>
                        <div class="srfm-flex srfm-justify-between srfm-items-center">
                            <input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" type="range" min="' .
							intval( $min ) . '" max="' . intval( $max ) . '" value="' . intval( $min ) . '" data-color="#0284c7"
                            step="' . intval( $step ) . '" class="range-slider srfm-range-slider !srfm-border-solid !srfm-border !srfm-border-[#d1d5db]" id="srfm-range-slider" />
                            <input type="number" min="' . intval( $min ) . '" max="' . intval( $max ) . '" value="' . intval( $min ) . '" class="input-slider srfm-number-input-slider !srfm-w-[60px] !srfm-border-solid !srfm-border-[1px] !srfm-border-[#D1D5DB] !srfm-rounded-md !srfm-px-2 !srfm-py-1 !srfm-text-center !srfm-bg-white focus:!srfm-border-srfm_primary_color focus:!srfm-ring-srfm_primary_color focus:!srfm-outline-0 focus:!srfm-bg-white sm:srfm-text-sm sm:srfm-leading-6" id="srfm-input-slider" />
                        </div>
                    </div>
                </div>
            </div>
        </div>' .
		( '' !== $help ? '<p class="srfm-text-sm srfm-text-gray-500" id="srfm-text-description">' . esc_html( $help ) . '</p>' : '' ) .
		'<p style="display:none" class="srfm-error-message">' . esc_html( $error_msg ) . '</p>
    </div>';
	}

}
