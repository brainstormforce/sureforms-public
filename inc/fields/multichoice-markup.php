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
	 * Render the sureforms Multichoice default styling block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function default_styling( $attributes ) {
			$required         = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$single_selection = isset( $attributes['singleSelection'] ) ? $attributes['singleSelection'] : false;
			$options          = isset( $attributes['options'] ) ? $attributes['options'] : array();
			$label            = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help             = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$style            = isset( $attributes['style'] ) ? $attributes['style'] : '';
			$error_msg        = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$classname        = isset( $attributes['className'] ) ? $attributes['className'] : '';
			$block_id         = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';

			$output  = '';
			$output .= '
		<div class="srfm-multi-choice-container srfm-main-container ' . esc_attr( $classname ) . '" id="srfm-multi-choice-container-' . esc_attr( $block_id ) . '">
		<label class="srfm-text-primary">' . esc_html( $label ) . ' ' . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '</label>
		<input type="hidden" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" value="' . esc_attr( $single_selection ) . '" id="srfm-multi-choice-selection-' . esc_attr( $block_id ) . '" />
		<input type="hidden" value="' . esc_attr( $style ) . '" id="srfm-multi-choice-style-' . esc_attr( $block_id ) . '" />
		<input class="srfm-multi-choice-' . esc_attr( $block_id ) . '" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" name="' . esc_attr( $single_selection ? str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) : str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" type="hidden" value="">';
		if ( is_array( $options ) ) {
			foreach ( $options as $i => $option ) {
				$output .= '	<div style="display: flex; align-items: center; gap:4px">
					<input
						style="display: ' . esc_attr( 'buttons' === $style ? 'none' : 'inherit' ) . '"
						class="srfm-multi-choice"
						id="srfm-multi-choice-' . esc_attr( $block_id . '-' . $i ) . '"
						type="' . esc_attr( $single_selection ? 'radio' : 'checkbox' ) . '"
						' . esc_attr( $single_selection ? 'name="' . esc_attr( 'sf-radio-' . $block_id ) . '"' : '' ) . '
					/>
					<label
						class="srfm-multi-choice-label-' . esc_attr( $block_id ) . ' srfm-multi-choice-label-button"
						for="srfm-multi-choice-' . esc_attr( $block_id . '-' . $i ) . '"
						style="' . esc_attr( 'buttons' === $style ? 'background-color: white; cursor: pointer; border: 2px solid black; border-radius: 10px; padding: .5rem 1rem .5rem 1rem; width: 100%; color: black;' : '' ) . '"
					>
						<span
							class="multi-choice-option"
							id="srfm-multi-choice-option-' . esc_attr( $block_id . '-' . $i ) . '"
						>
							' . esc_html( $option ) . '
						</span>
					</label>
					<span></span>
				</div>';
			}
		}

		$output .= '' . ( '' !== $help ? '<label class="srfm-text-secondary srfm-helper-txt">' . esc_html( $help ) . '</label>' : '' ) . '
			<span style="display:none" class="srfm-error-message">' . esc_html( $error_msg ) . '</span>
	</div>
		';
		return $output;

	}

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

			$output  = '';
			$output .= '
			<div class="srfm-multi-choice-container srfm-main-container srfm-frontend-inputs-holder ' . esc_attr( $classname ) . '" id="srfm-multi-choice-container-' . esc_attr( $block_id ) . '">
				<input type="hidden" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" value="' . esc_attr( $single_selection ) . '" id="srfm-multi-choice-selection-' . esc_attr( $block_id ) . '" />
				<input type="hidden" value="' . esc_attr( $style ) . '" id="srfm-multi-choice-style-' . esc_attr( $block_id ) . '" />
				<input id="srfm-multichoice-result-' . esc_attr( $block_id ) . '" class="srfm-multi-choice-' . esc_attr( $block_id ) . '" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" name="' . esc_attr( $single_selection ? str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) : str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" type="hidden" value="">
				<label class="srfm-classic-label-text">' . esc_html( $label ) . ' ' . ( $required && $label ? '<span class="srfm-text-red-500"> *</span>' : '' ) . '</label>
				<div class="srfm-radio-buttons srfm-flex srfm-flex-wrap srfm-mt-2">';
		if ( is_array( $options ) ) {
			foreach ( $options as $i => $option ) {
						$output .= ' <label class="srfm-classic-radio">
						<input type="' . esc_attr( $single_selection ? 'radio' : 'checkbox' ) . '" ' . esc_attr( $single_selection ? 'name="' . esc_attr( "sf-radio-$block_id" ) . '"' : '' ) . ' id="srfm-multi-choice-' . esc_attr( $block_id . '-' . $i ) . '" class="srfm-multi-choice">
						<div class="srfm-flex srfm-items-start srfm-classic-radio-btn srfm-classic-multi-choice">
							<div class="srfm-pr-[5px] srfm-relative srfm-flex">
								<i class="fa fa-check-circle srfm-text-base" aria-hidden="true"></i>
								<i class="fa-regular fa-circle srfm-text-sm srfm-absolute srfm-text-gray-300" aria-hidden="true"></i>
							</div>
							<div> 
								<article id="srfm-multi-choice-option-' . esc_attr( $block_id . '-' . $i ) . '" class="srfm-text-sm srfm-font-medium srfm-leading-6 srfm-text-primary_color srfm-mt-[-0.5px]">' . esc_html( $option ) . '</article>
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
