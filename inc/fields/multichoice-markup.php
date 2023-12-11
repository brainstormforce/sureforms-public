<?php
/**
 * Sureforms Multichoice Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;

/**
 * SureForms Multichoice Markup Class.
 *
 * @since 0.0.1
 */
class Multichoice_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms Multichoice classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function classic_styling( $attributes ) {
			$required         = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$single_selection = isset( $attributes['singleSelection'] ) ? $attributes['singleSelection'] : false;
			$options          = isset( $attributes['options'] ) ? $attributes['options'] : array();
			$label            = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help             = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$style            = isset( $attributes['style'] ) ? $attributes['style'] : '';
			$error_msg        = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$classname        = isset( $attributes['className'] ) ? $attributes['className'] : '';
			$block_id         = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';
			$field_width      = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
			$output           = '';
			$output          .= '
			<div class="srfm-multi-choice-container srfm-main-container srfm-frontend-inputs-holder ' . esc_attr( $classname ) . '" id="srfm-multi-choice-container-' . esc_attr( $block_id ) . '"  style="width:calc(' . esc_attr( $field_width ) . '% - 20px);" >
				<input type="hidden" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" value="' . esc_attr( $single_selection ) . '" id="srfm-multi-choice-selection-' . esc_attr( $block_id ) . '" />
				<input type="hidden" value="' . esc_attr( $style ) . '" id="srfm-multi-choice-style-' . esc_attr( $block_id ) . '" />
				<input class="srfm-multi-choice-' . esc_attr( $block_id ) . '" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" name="' . esc_attr( $single_selection ? str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) : str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" type="hidden" value="">
				<label class="srfm-classic-label-text">' . esc_html( $label ) . ' ' . ( $required && $label ? '<span class="srfm-text-red-500"> *</span>' : '' ) . '</label>
				<div class="srfm-radio-buttons srfm-flex srfm-flex-wrap srfm-mt-2 srfm-justify-between">';
		if ( is_array( $options ) ) {
			foreach ( $options as $i => $option ) {
						$output .= ' <label class="srfm-classic-radio">
						<input type="' . esc_attr( $single_selection ? 'radio' : 'checkbox' ) . '" ' . esc_attr( $single_selection ? 'name="' . esc_attr( "sf-radio-$block_id" ) . '"' : '' ) . ' id="srfm-multi-choice-' . esc_attr( $block_id . '-' . $i ) . '" class="srfm-multi-choice">
						<div class="srfm-flex srfm-items-center srfm-classic-radio-btn srfm-classic-multi-choice">
							<div class="srfm-pr-[5px] rtl:srfm-pl-[5px] rtl:srfm-pr-[0] srfm-relative srfm-flex">
								<i class="fa fa-check-circle srfm-text-base" aria-hidden="true"></i>
								<i class="fa-regular fa-circle srfm-text-sm srfm-absolute srfm-text-gray-300" aria-hidden="true"></i>
							</div>
							<div> 
								<article id="srfm-multi-choice-option-' . esc_attr( $block_id . '-' . $i ) . '" class="srfm-text-sm srfm-font-medium srfm-leading-6 srfm-text-primary_color srfm-mt-[-0.5px]">' . esc_html( $option['optiontitle'] ) . '</article>
							</div>
						</div>
						</label>';
			}
		}
					$output .= '
					</div>
					' . ( '' !== $help ? '<p class="srfm-helper-txt" >' . esc_html( $help ) . '</p>' : '' ) . '
					<p style="display:none" class="srfm-error-message">' . esc_html( $error_msg ) . '</p>
				</div>
			';
		return $output;

	}

}
