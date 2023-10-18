<?php
/**
 * Sureforms Textarea Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;

/**
 * Sureforms Textarea Markup Class.
 *
 * @since 0.0.1
 */
class Textarea_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms textarea default styling block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function default_styling( $attributes ) {
		$id          = isset( $attributes['id'] ) ? Sureforms_Helper::get_string_value( $attributes['id'] ) : '';
		$default     = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
		$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help        = isset( $attributes['textAreaHelpText'] ) ? $attributes['textAreaHelpText'] : '';
		$max_length  = isset( $attributes['maxLength'] ) ? $attributes['maxLength'] : '';
		$rows        = isset( $attributes['rows'] ) ? $attributes['rows'] : '';
		$cols        = isset( $attributes['cols'] ) ? $attributes['cols'] : '';
		$error_msg   = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$classname   = isset( $attributes['className'] ) ? $attributes['className'] : '';

		return '<div class="sureforms-textarea-container main-container frontend-inputs-holder ' . esc_attr( $classname ) . '">' .
		'<label for="sureforms-textarea" class="sf-text-primary">' . esc_html( $label ) . ' ' .
		( $required && $label ? '<span style="color:red;"> *</span>' : '' ) .
		'</label>' .
		'<div style="position:relative">
		<div class="sureforms-text-area-counter">' . esc_attr( ( '' === $max_length ) ? '' : '0/' . esc_attr( $max_length ) ) . '</div>' .
		'<textarea style="width:100%" name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ) . '" id="sureforms-textarea" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" placeholder="' . esc_attr( $placeholder ) . '" maxLength="' . ( 0 === $max_length ? '' : esc_attr( $max_length ) ) . '" cols="' . esc_attr( $cols ) . '" rows="' . esc_attr( $rows ) . '" class="sureforms-textarea-field">' . esc_attr( $default ) . '</textarea></div>' .
		( '' !== $help ? '<label for="sureforms-textarea" class="sf-text-secondary sforms-helper-txt">' . esc_html( $help ) . '</label>' : '' ) .
		'<span style="display:none" class="error-message">' . esc_html( $error_msg ) . '</span>' .
		'</div>';

	}

	/**
	 * Render the sureforms textarea classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function classic_styling( $attributes ) {
		$id          = isset( $attributes['id'] ) ? Sureforms_Helper::get_string_value( $attributes['id'] ) : '';
		$default     = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
		$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help        = isset( $attributes['textAreaHelpText'] ) ? $attributes['textAreaHelpText'] : '';
		$max_length  = isset( $attributes['maxLength'] ) ? $attributes['maxLength'] : '';
		$rows        = isset( $attributes['rows'] ) ? $attributes['rows'] : '';
		$cols        = isset( $attributes['cols'] ) ? $attributes['cols'] : '';
		$error_msg   = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$classname   = isset( $attributes['className'] ) ? $attributes['className'] : '';

		return '
		<div class="frontend-inputs-holder main-container sureforms-textarea-container ' . esc_attr( $classname ) . '">
			<label for="sureforms-textarea" class="sf-classic-label-text">
				' . esc_html( $label ) . '
				' . ( $required && $label ? '<span class="text-red-500"> *</span>' : '' ) . '
			</label>
			<div class="mt-2 relative">
				<div class="sureforms-text-area-counter">
					' . esc_html( ( '' === $max_length ) ? '' : '0/' . esc_html( $max_length ) ) . '
				</div>
				<textarea name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ) . '"
					aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '"
					placeholder="' . esc_attr( $placeholder ) . '"
					maxLength="' . ( 0 === $max_length ? '' : esc_attr( $max_length ) ) . '"
					cols="' . esc_attr( $cols ) . '" rows="' . esc_attr( $rows ) . '"
					id="sureforms-textarea" class="sf-classic-textarea-element">' . esc_html( $default ) . '</textarea>
			</div>
			' . ( '' !== $help ? '<p class="sforms-helper-txt" id="text-description">' . esc_html( $help ) . '</p>' : '' ) . '
			<p style="display:none" class="error-message">' . esc_html( $error_msg ) . '</p>
		</div>';

	}

}
