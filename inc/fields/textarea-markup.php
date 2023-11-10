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
		$block_id    = isset( $attributes['block_id'] ) ? Sureforms_Helper::get_string_value( $attributes['block_id'] ) : '';
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

		return '<div class="srfm-textarea-container srfm-main-container srfm-frontend-inputs-holder ' . esc_attr( $classname ) . '">' .
		'<label for="srfm-textarea" class="srfm-text-primary">' . esc_html( $label ) . ' ' .
		( $required && $label ? '<span style="color:red;"> *</span>' : '' ) .
		'</label>' .
		'<div style="position:relative">
		<div class="srfm-text-area-counter">' . esc_attr( ( '' === $max_length ) ? '' : '0/' . esc_attr( $max_length ) ) . '</div>' .
		'<textarea style="width:100%" name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" id="srfm-textarea" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" placeholder="' . esc_attr( $placeholder ) . '" maxLength="' . ( 0 === $max_length ? '' : esc_attr( $max_length ) ) . '" cols="' . esc_attr( $cols ) . '" rows="' . esc_attr( $rows ) . '" class="srfm-textarea-field">' . esc_attr( $default ) . '</textarea></div>' .
		( '' !== $help ? '<label for="srfm-textarea" class="srfm-text-secondary srfm-helper-txt">' . esc_html( $help ) . '</label>' : '' ) .
		'<span style="display:none" class="srfm-error-message">' . esc_html( $error_msg ) . '</span>' .
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
		$block_id    = isset( $attributes['block_id'] ) ? Sureforms_Helper::get_string_value( $attributes['block_id'] ) : '';
		$default     = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
		$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$field_width = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help        = isset( $attributes['textAreaHelpText'] ) ? $attributes['textAreaHelpText'] : '';
		$max_length  = isset( $attributes['maxLength'] ) ? $attributes['maxLength'] : '';
		$rows        = isset( $attributes['rows'] ) ? $attributes['rows'] : '';
		$cols        = isset( $attributes['cols'] ) ? $attributes['cols'] : '';
		$error_msg   = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$classname   = isset( $attributes['className'] ) ? $attributes['className'] : '';

		return '
		<div class="srfm-frontend-inputs-holder srfm-main-container srfm-textarea-container ' . esc_attr( $classname ) . '" style="width:calc(' . esc_attr( $field_width ) . '% - 20px);">
			<label for="srfm-textarea" class="srfm-classic-label-text">
				' . esc_html( $label ) . '
				' . ( $required && $label ? '<span class="srfm-text-red-500"> *</span>' : '' ) . '
			</label>
			<div class="srfm-mt-2 srfm-relative">
				<div class="srfm-text-area-counter">
					' . esc_html( ( '' === $max_length ) ? '' : '0/' . esc_html( $max_length ) ) . '
				</div>
				<textarea name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '"
					aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '"
					placeholder="' . esc_attr( $placeholder ) . '"
					maxLength="' . ( 0 === $max_length ? '' : esc_attr( $max_length ) ) . '"
					cols="' . esc_attr( $cols ) . '" rows="' . esc_attr( $rows ) . '"
					id="srfm-textarea" class="srfm-classic-textarea-element">' . esc_html( $default ) . '</textarea>
			</div>
			' . ( '' !== $help ? '<p class="srfm-helper-txt" id="srfm-text-description">' . esc_html( $help ) . '</p>' : '' ) . '
			<p style="display:none" class="srfm-error-message">' . esc_html( $error_msg ) . '</p>
		</div>';

	}

}
