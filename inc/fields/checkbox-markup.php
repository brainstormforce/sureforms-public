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
	 * Render the sureforms checkbox classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function classic_styling( $attributes ) {
		$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$field_width = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help        = isset( $attributes['checkboxHelpText'] ) ? $attributes['checkboxHelpText'] : '';
		$label_url   = isset( $attributes['labelUrl'] ) ? $attributes['labelUrl'] : '';
		$checked     = isset( $attributes['checked'] ) ? $attributes['checked'] : '';
		$error_msg   = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$classname   = isset( $attributes['className'] ) ? $attributes['className'] : '';
		$block_id    = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';

		$slug = 'datepicker';

		$inline_style = '';

		// Append Dynamic styles here.
		$inline_style .= $field_width ? 'width:' . $field_width . '%;' : '';
		$style =  $inline_style ? 'style="'. $inline_style .'"' : '';

		// html attributes
		$aria_require_attr = $required ? 'true' : 'false';



		return '<div class="srfm-checkbox-container srfm-main-container srfm-classic-inputs-holder" style="width:calc(' . esc_attr( $field_width ) . '% - 20px);" >
			<div class= "srfm-relative srfm-flex srfm-items-start srfm-flex-row srfm-gap-2">
				<div class="srfm-flex srfm-h-6 srfm-items-center">
					<input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" id="srfm-checkbox-' . esc_attr( $block_id ) . '" ' . esc_attr( $checked ? 'checked' : '' ) . ' type="checkbox" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" class="srfm-h-4 srfm-w-4 srfm-rounded srfm-border-[#d1d5db] srfm-classic-checkbox-input">
				</div>
				<div class="srfm-text-sm srfm-leading-6">
					<label for="srfm-checkbox-' . esc_attr( $block_id ) . '" class="srfm-classic-label-text">' .
						( $label_url
							? '<a target="_blank" href="' . esc_url( $label_url ) . '" class="srfm-underline">' . esc_html( $label ) . '</a>'
							: esc_html( $label )
						) .
						( $required && $label ? '<span style="color:red;"> *</span>' : '' ) .
					'</label>
				</div>
			</div>
			' . ( '' !== $help ? '<p for="srfm-checkbox" class="srfm-helper-txt">' . esc_html( $help ) . '</p>' : '' ) . '
			<span style="display:none" class="srfm-error-message">' . esc_html( $error_msg ) . '</span>
		</div>';

		ob_start(); ?>
			<div class="srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block <?php echo esc_attr( $classname ) ?>" <?php echo wp_kses_post( $style ) ?>>
				<?php echo wp_kses_post(Sureforms_Helper::GenerateCommonFormMarkup('label', $label, $slug, $block_id, $required )); ?>
					

				<?php echo wp_kses_post( Sureforms_Helper::GenerateCommonFormMarkup('help', '', '', '', '', $help ) ); ?>
				<?php echo wp_kses_post(Sureforms_Helper::GenerateCommonFormMarkup('error', '', '', '', $required, '', $error_msg )); ?>
			</div>
		<?php

		return ob_get_clean();

	}

}
