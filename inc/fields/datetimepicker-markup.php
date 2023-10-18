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
			$id         = isset( $attributes['id'] ) ? strval( $attributes['id'] ) : '';
			$required   = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$label      = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help       = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$field_type = isset( $attributes['fieldType'] ) ? $attributes['fieldType'] : '';
			$min        = isset( $attributes['min'] ) ? $attributes['min'] : '';
			$max        = isset( $attributes['max'] ) ? $attributes['max'] : '';
			$error_msg  = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$classname  = isset( $attributes['className'] ) ? $attributes['className'] : '';

			$output  = '';
			$output .= '
			<div class="sureforms-input-date-container main-container frontend-inputs-holder ' . esc_attr( $classname ) . '" id="sureforms-input-date-container-' . esc_attr( $id ) . '">
				<label for="sureforms-input-date-' . esc_attr( $id ) . '" class="sf-text-primary">' . esc_html( $label ) . ' ' . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '</label>
				<input type="hidden" id="sureforms-full-date-time-' . esc_attr( $id ) . '" name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ) . '" value="">
				<div class="sureforms-date-time-picker-holder" >';

		switch ( $field_type ) {
			case 'dateTime':
				$output .= '<input max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" id="sureforms-input-date-' . esc_attr( $id ) . '" type="date" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" class="sureforms-input-field">';
				$output .= '<input id="sureforms-input-time-' . esc_attr( $id ) . '" type="time" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" class="sureforms-input-field"/>';
				break;
			case 'date':
				$output .= '<input max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" id="sureforms-input-date-' . esc_attr( $id ) . '" type="date" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" class="sureforms-input-field">';
				break;
			case 'time':
				$output .= '<input id="sureforms-input-time-' . esc_attr( $id ) . '" type="time" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" class="sureforms-input-field"/>';
				break;
			default:
				$output .= '<input max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" id="sureforms-input-date-' . esc_attr( $id ) . '" type="date" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" class="sureforms-input-field">';
				$output .= '<input id="sureforms-input-time-' . esc_attr( $id ) . '" type="time" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" class="sureforms-input-field"/>';
				break;
		}
			$output .= '</div>
				' . ( '' !== $help ? '<label for="sureforms-input-date" class="sf-text-secondary sforms-helper-txt">' . esc_html( $help ) . '</label>' : '' ) . '
				<span style="display:none" class="error-message">' . esc_html( $error_msg ) . '</span>
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
			$id         = isset( $attributes['id'] ) ? strval( $attributes['id'] ) : '';
			$required   = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$label      = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help       = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$field_type = isset( $attributes['fieldType'] ) ? $attributes['fieldType'] : '';
			$min        = isset( $attributes['min'] ) ? $attributes['min'] : '';
			$max        = isset( $attributes['max'] ) ? $attributes['max'] : '';
			$error_msg  = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$classname  = isset( $attributes['className'] ) ? $attributes['className'] : '';

			$output  = '';
			$output .= '
			<div class="sf-classic-inputs-holder main-container sf-classic-date-time-container ' . esc_attr( $classname ) . '">
			<label for="sureforms-input-date-' . esc_attr( $id ) . '" class="sf-classic-label-text">
				' . esc_html( $label ) . ' ' . ( $required && $label ? '<span class="text-red-500"> *</span>' : '' ) . '
			</label>
				<input type="hidden" class="sf-min-max-holder" min="' . esc_attr( $min ) . '" max="' . esc_attr( $max ) . '" >
				<input type="hidden" field-type="' . esc_attr( $field_type ) . '" class="sf-classic-date-time-result" name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ) . '" value="">
				<div class="sf-classic-date-time-picker relative mt-2 rounded-md shadow-sm datepicker-with-limits" data-te-input-wrapper-init ';

		switch ( $field_type ) {
			case 'dateTime':
				$output .= esc_attr( 'data-te-date-timepicker-init' );
				break;
			case 'date':
				$output .= esc_attr( 'data-te-datepicker-init' );
				break;
			case 'time':
				$output .= esc_attr( 'data-te-timepicker-init' );
				break;
			default:
				$output .= esc_attr( 'data-te-date-timepicker-init' );
				break;
		}
				$output .= '>
				<input type="text" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" class="sureforms-input-data-time sf-classic-datetime-picker" id="sureforms-input-time-' . esc_attr( $id ) . '" />
				</div>
				' . ( '' !== $help ? '<p class="sforms-helper-txt" id="text-description">' . esc_html( $help ) . '</p>' : '' ) . '
				<p style="display:none" class="error-message ">' . esc_html( $error_msg ) . '</p>
				</div>
			';
		return $output;
	}

}
