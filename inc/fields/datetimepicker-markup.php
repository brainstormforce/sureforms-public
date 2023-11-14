<?php
/**
 * Sureforms Datetimepicker Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;

/**
 * Sureforms Datetimepicker Markup Class.
 *
 * @since 0.0.1
 */
class Datetimepicker_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms Datetimepicker default styling block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function default_styling( $attributes ) {
			$required   = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$label      = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help       = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$field_type = isset( $attributes['fieldType'] ) ? $attributes['fieldType'] : '';
			$min        = isset( $attributes['min'] ) ? $attributes['min'] : '';
			$max        = isset( $attributes['max'] ) ? $attributes['max'] : '';
			$error_msg  = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$classname  = isset( $attributes['className'] ) ? $attributes['className'] : '';
			$block_id   = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';

			$output  = '';
			$output .= '
			<div class="srfm-input-date-container srfm-main-container srfm-frontend-inputs-holder ' . esc_attr( $classname ) . '" id="srfm-input-date-container-' . esc_attr( $block_id ) . '">
				<label for="srfm-input-date-' . esc_attr( $block_id ) . '" class="srfm-text-primary">' . esc_html( $label ) . ' ' . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '</label>
				<input type="hidden" id="srfm-full-date-time-' . esc_attr( $block_id ) . '" name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" value="">
				<div class="srfm-date-time-picker-holder" >';

		switch ( $field_type ) {
			case 'dateTime':
				$output .= '<input max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" id="srfm-input-date-' . esc_attr( $block_id ) . '" type="date" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" class="srfm-input-field">';
				$output .= '<input id="srfm-input-time-' . esc_attr( $block_id ) . '" type="time" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" class="srfm-input-field"/>';
				break;
			case 'date':
				$output .= '<input max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" id="srfm-input-date-' . esc_attr( $block_id ) . '" type="date" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" class="srfm-input-field">';
				break;
			case 'time':
				$output .= '<input id="srfm-input-time-' . esc_attr( $block_id ) . '" type="time" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" class="srfm-input-field"/>';
				break;
			default:
				$output .= '<input max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" id="srfm-input-date-' . esc_attr( $block_id ) . '" type="date" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" class="srfm-input-field">';
				$output .= '<input id="srfm-input-time-' . esc_attr( $block_id ) . '" type="time" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" class="srfm-input-field"/>';
				break;
		}
			$output .= '</div>
				' . ( '' !== $help ? '<label for="srfm-input-date" class="srfm-text-secondary srfm-helper-txt">' . esc_html( $help ) . '</label>' : '' ) . '
				<span style="display:none" class="srfm-error-message">' . esc_html( $error_msg ) . '</span>
			</div>			';
		return $output;
	}

	/**
	 * Render the sureforms Datetimepicker classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function classic_styling( $attributes ) {
			$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$field_width = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
			$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help        = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$field_type  = isset( $attributes['fieldType'] ) ? $attributes['fieldType'] : '';
			$min         = isset( $attributes['min'] ) ? $attributes['min'] : '';
			$max         = isset( $attributes['max'] ) ? $attributes['max'] : '';
			$error_msg   = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$classname   = isset( $attributes['className'] ) ? $attributes['className'] : '';
			$block_id    = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';

			$output  = '';
			$output .= '
			<div class="srfm-classic-inputs-holder srfm-main-container srfm-classic-date-time-container ' . esc_attr( $classname ) . '"  style="width:calc(' . esc_attr( $field_width ) . '% - 20px);" >
			<label for="srfm-input-date-' . esc_attr( $block_id ) . '" class="srfm-classic-label-text">
				' . esc_html( $label ) . ' ' . ( $required && $label ? '<span class="srfm-text-red-500"> *</span>' : '' ) . '
			</label>
				<input type="hidden" class="srfm-min-max-holder" min="' . esc_attr( $min ) . '" max="' . esc_attr( $max ) . '" >
				<input type="hidden" field-type="' . esc_attr( $field_type ) . '" class="srfm-classic-date-time-result" name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" value="">
				<div class="srfm-classic-date-time-picker srfm-relative srfm-mt-2 srfm-rounded-md srfm-shadow-sm datepicker-with-limits" data-te-input-wrapper-init ';

		$input_type = '';
		switch ( $field_type ) {
			case 'dateTime':
				$input_type = ' srfm-input-date-time';
				break;
			case 'date':
				$input_type = ' srfm-input-date';
				break;
			case 'time':
				$input_type = ' srfm-input-time';
				break;
			default:
				$input_type = ' srfm-input-time';
				break;
		}
			$input_icon = 'time' === $field_type ? '<i class="fa-solid fa-clock srfm-text-gray-400 srfm-text-[20px]"></i>' : '<i class="fa-regular fa-calendar srfm-text-gray-400 srfm-text-[20px]"></i>';
			$output    .= '>
			<div class="srfm-pointer-events-none srfm-absolute srfm-inset-y-0 srfm-right-0 srfm-flex srfm-items-center srfm-pr-3">
			' . $input_icon . '
			</div>
			<input type="text" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" class="srfm-input-data-time srfm-classic-datetime-picker' . $input_type . '" id="srfm-input-time-' . esc_attr( $block_id ) . '" />
			</div>
			' . ( '' !== $help ? '<p class="srfm-helper-txt" id="srfm-text-description">' . esc_html( $help ) . '</p>' : '' ) . '
			<p style="display:none" class="srfm-error-message ">' . esc_html( $error_msg ) . '</p>
			</div>
			';
		return $output;
	}

}
