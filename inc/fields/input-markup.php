<?php
/**
 * Sureforms Input Markup Class file.
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
class Input_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms input default styling block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function default_styling( $attributes ) {
		$block_id      = isset( $attributes['block_id'] ) ? strval( $attributes['block_id'] ) : '';
		$default       = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
		$required      = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$is_unique     = isset( $attributes['isUnique'] ) ? $attributes['isUnique'] : false;
		$duplicate_msg = isset( $attributes['duplicateMsg'] ) ? $attributes['duplicateMsg'] : '';
		$placeholder   = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$label         = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help          = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg     = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$max_text_len  = isset( $attributes['textLength'] ) ? $attributes['textLength'] : '';
		$classname     = isset( $attributes['className'] ) ? $attributes['className'] : '';

		return '<div class="sureforms-input-text-container main-container frontend-inputs-holder ' . esc_attr( $classname ) . '">
			<label class="sf-text-primary">' . esc_html( $label ) . ' ' . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '</label>
			<input id="sureforms-input-text-' . esc_attr( $block_id ) . '" name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" type="text" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" value="' . esc_attr( $default ) . '" placeholder="' . esc_attr( $placeholder ) . '" 
			aria-unique="' . esc_attr( $is_unique ? 'true' : 'false' ) . '" maxlength="' . esc_attr( $max_text_len ) . '" class="sureforms-input-field">
			' . ( '' !== $help ? '<label for="sureforms-input-text" class="sf-text-secondary sforms-helper-txt">' . esc_html( $help ) . '</label>' : '' ) . '
			<span style="display:none" class="error-message">' . esc_html( $error_msg ) . '</span>
			<span style="display:none" class="error-message duplicate-message">' . esc_html( $duplicate_msg ) . '</span>
		</div>';
	}

	/**
	 * Render the sureforms input classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function classic_styling( $attributes ) {
		$block_id      = isset( $attributes['block_id'] ) ? strval( $attributes['block_id'] ) : '';
		$default       = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
		$required      = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$is_unique     = isset( $attributes['isUnique'] ) ? $attributes['isUnique'] : false;
		$duplicate_msg = isset( $attributes['duplicateMsg'] ) ? $attributes['duplicateMsg'] : '';
		$placeholder   = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$label         = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help          = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg     = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$max_text_len  = isset( $attributes['textLength'] ) ? $attributes['textLength'] : '';
		$classname     = isset( $attributes['className'] ) ? $attributes['className'] : '';

		return '<div class="main-container frontend-inputs-holder  ' . esc_attr( $classname ) . '">
			<label for="text" class="sf-classic-label-text">' . esc_html( $label ) . ' ' . ( $required && $label ? '<span class="text-red-500"> *</span>' : '' ) . '</label>
			<div class="">
				<input type="text" name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" id="text" class="sf-classic-input-element" 
				placeholder="' . esc_attr( $placeholder ) . '" aria-unique="' . esc_attr( $is_unique ? 'true' : 'false' ) . '" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" value="' . esc_attr( $default ) . '" placeholder="' . esc_attr( $placeholder ) . '" maxlength="' . esc_attr( $max_text_len ) . '">
			</div>
			' . ( '' !== $help ? '<p class="sforms-helper-txt" id="text-description">' . esc_html( $help ) . '</p>' : '' ) . '
			<p style="display:none" class="error-message">' . esc_html( $error_msg ) . '</p>
			<p style="display:none" class="duplicate-message">' . esc_html( $duplicate_msg ) . '</p>
		</div>';
	}

}
