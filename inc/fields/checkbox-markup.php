<?php
/**
 * Sureforms Checkbox Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;


/**
 * Sureforms Checkbox Markup Class.
 *
 * @since 0.0.1
 */
class Checkbox_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms checkbox default styling block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function default_styling( $attributes ) {
		$required  = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$label     = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help      = isset( $attributes['checkboxHelpText'] ) ? $attributes['checkboxHelpText'] : '';
		$label_url = isset( $attributes['labelUrl'] ) ? $attributes['labelUrl'] : '';
		$checked   = isset( $attributes['checked'] ) ? $attributes['checked'] : '';
		$error_msg = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$classname = isset( $attributes['className'] ) ? $attributes['className'] : '';
		$block_id  = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';

		return '<div class="srfm-checkbox-container srfm-main-container' . esc_attr( $classname ) . '">
		<div>
			<input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" id="srfm-checkbox-' . esc_attr( $block_id ) . '" ' . esc_attr( $checked ? 'checked' : '' ) . ' type="checkbox" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '">
			<span class="srfm-text-primary">
				<label for="srfm-checkbox-' . esc_attr( $block_id ) . '" class="srfm-text-primary">' .
					( $label_url
						? '<a target="_blank" href="' . esc_url( $label_url ) . '" style="text-decoration:none;">' . esc_html( $label ) . '</a>'
						: esc_html( $label )
					) .
					( $required && $label ? '<span style="color:red;"> *</span>' : '' ) .
				'</label>
			</span>
		</div>' .
		( '' !== $help ? '<label for="srfm-checkbox" class="srfm-text-secondary srfm-helper-txt">' . esc_html( $help ) . '</label>' : '' ) .
		'<span style="display:none" class="srfm-error-message">' . esc_html( $error_msg ) . '</span>
	</div>';

	}

	/**
	 * Render the sureforms checkbox classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function classic_styling( $attributes ) {
		$required  = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$label     = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help      = isset( $attributes['checkboxHelpText'] ) ? $attributes['checkboxHelpText'] : '';
		$label_url = isset( $attributes['labelUrl'] ) ? $attributes['labelUrl'] : '';
		$checked   = isset( $attributes['checked'] ) ? $attributes['checked'] : '';
		$error_msg = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$classname = isset( $attributes['className'] ) ? $attributes['className'] : '';
		$block_id  = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';

		return '<div class="srfm-checkbox-container srfm-main-container srfm-classic-inputs-holder">
			<div class="relative flex items-start flex-row gap-2">
				<div class="flex h-6 items-center">
					<input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" id="srfm-checkbox-' . esc_attr( $block_id ) . '" ' . esc_attr( $checked ? 'checked' : '' ) . ' type="checkbox" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" class="h-4 w-4 rounded border-[#d1d5db] srfm-classic-checkbox-input">
				</div>
				<div class="text-sm leading-6">
					<label for="srfm-checkbox-' . esc_attr( $block_id ) . '" class="srfm-classic-label-text">' .
						( $label_url
							? '<a target="_blank" href="' . esc_url( $label_url ) . '" style="text-decoration:none;" class="underline">' . esc_html( $label ) . '</a>'
							: esc_html( $label )
						) .
						( $required && $label ? '<span style="color:red;"> *</span>' : '' ) .
					'</label>
				</div>
			</div>
			' . ( '' !== $help ? '<p for="srfm-checkbox" class="srfm-helper-txt">' . esc_html( $help ) . '</p>' : '' ) . '
			<span style="display:none" class="srfm-error-message">' . esc_html( $error_msg ) . '</span>
		</div>';

	}

}
