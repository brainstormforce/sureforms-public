<?php
/**
 * Sureforms Url Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;

/**
 * Sureforms Url Field Markup Class.
 *
 * @since 0.0.1
 */
class Url_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms url default styling block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function default_styling( $attributes ) {
			$block_id    = isset( $attributes['block_id'] ) ? strval( $attributes['block_id'] ) : '';
			$default     = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
			$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
			$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help        = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$error_msg   = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$classname   = isset( $attributes['className'] ) ? $attributes['className'] : '';

			return '<div class="srfm-input-url-container srfm-main-container srfm-frontend-inputs-holder ' . esc_attr( $classname ) . '">
    <label for="srfm-input-url-' . esc_attr( $block_id ) . '" class="srfm-text-primary">' . esc_html( $label ) . ' ' . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '</label>
    <input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" id="srfm-input-url-' . esc_attr( $block_id ) . '" type="url" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" value="' . esc_attr( $default ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="srfm-url-input">
    ' . ( '' !== $help ? '<label for="srfm-input-url-' . esc_attr( $block_id ) . '" class="srfm-text-secondary srfm-helper-txt">' . esc_html( $help ) . '</label>' : '' ) . '
    <span style="display:none" class="srfm-error-message">' . esc_html( $error_msg ) . '</span>
</div>';
	}

	/**
	 * Render the sureforms url classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function classic_styling( $attributes ) {
			$block_id    = isset( $attributes['block_id'] ) ? strval( $attributes['block_id'] ) : '';
			$default     = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
			$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
			$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help        = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$error_msg   = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$classname   = isset( $attributes['className'] ) ? $attributes['className'] : '';

		return '<div class="srfm-main-container srfm-frontend-inputs-holder srfm-input-url-container srfm-classic-input-url-container' . esc_attr( $classname ) . '">
			<label for="srfm-input-url-' . esc_attr( $block_id ) . '" class="srfm-classic-label-text">
				' . esc_html( $label ) . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '
			</label>
			<div class="mt-2 flex rounded-md shadow-sm">
				<span class="srfm-classic-url-prefix">https://</span>
				<input type="text" name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-url' . $block_id ) ) . '" id="srfm-input-url-' . esc_attr( $block_id ) . '" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" value="' . esc_attr( $default ) . '"
				class="srfm-classic-url-element srfm-url-input" placeholder="' . esc_attr( $placeholder ) . '">
			</div>
			' . ( '' !== $help ? '<label for="srfm-input-url-' . esc_attr( $block_id ) . '" class="srfm-helper-txt">' . esc_html( $help ) . '</label>' : '' ) . '
			<p style="display:none" class="srfm-error-message">' . esc_html( $error_msg ) . '</p>
			<p style="display:none" class="srfm-validation-url-message mt-2 text-sm text-red-600">' . esc_html( __( 'Please enter a valid URL.', 'sureforms' ) ) . '</p>
		</div>';

	}

}
