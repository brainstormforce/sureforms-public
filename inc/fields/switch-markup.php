<?php
/**
 * Sureforms Switch Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;


/**
 * Sureforms Switch Markup Class.
 *
 * @since 0.0.1
 */
class Switch_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms checkbox default styling block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function default_styling( $attributes ) {
		$block_id      = isset( $attributes['block_id'] ) ? Sureforms_Helper::get_string_value( $attributes['block_id'] ) : '';
		$form_id       = isset( $attributes['formId'] ) ? Sureforms_Helper::get_integer_value( $attributes['formId'] ) : '';
		$required      = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$label         = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help          = isset( $attributes['switchHelpText'] ) ? $attributes['switchHelpText'] : '';
		$checked       = isset( $attributes['checked'] ) ? $attributes['checked'] : '';
		$error_msg     = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$classname     = isset( $attributes['className'] ) ? $attributes['className'] : '';
		$color_primary = get_post_meta( Sureforms_Helper::get_integer_value( $form_id ), '_srfm_color1', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $form_id ), '_srfm_color1', true ) ) : '';
		$checked_color = ! empty( $color_primary ) ? $color_primary : '#0084C7';

		return '<div class="srfm-switch-container srfm-main-container srfm-frontend-inputs-holder ' . esc_attr( $classname ) . '">
		<label class="srfm-switch-label" for="srfm-switch-' . esc_attr( $block_id ) . '">
			<div style="display: flex; align-items: flex-start; gap: 0.5rem;" class="srfm-text-primary">
				<div class="srfm-switch-background" style="background-color: ' . ( $checked ? '#007CBA' : '#dcdcdc' ) . ';">
					<input class="srfm-switch srfm-default-switch"
						name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '"
						id="srfm-switch-' . esc_attr( $block_id ) . '"
						' . ( $checked ? 'checked' : '' ) . ' type="checkbox"
						aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" />
					<div class="srfm-switch-toggle" style="left: ' . ( $checked ? '27px' : '2px' ) . ';"></div>
				</div>
				<span style="color: var(--srfm-primary-color)">' . esc_html( $label ) . '</span>
				' . ( $required && $label ? '<span style="color: red;"> *</span>' : '' ) . '
			</div>
		</label>
		' . ( '' !== $help ? '<label class="srfm-text-secondary srfm-helper-txt">' . esc_html( $help ) . '</label>' : '' ) . '
		<span style="margin-top: 5px; display: none;" class="srfm-error-message">' . esc_html( $error_msg ) . '</span>
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
		$block_id      = isset( $attributes['block_id'] ) ? Sureforms_Helper::get_string_value( $attributes['block_id'] ) : '';
		$form_id       = isset( $attributes['formId'] ) ? Sureforms_Helper::get_integer_value( $attributes['formId'] ) : '';
		$required      = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$label         = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help          = isset( $attributes['switchHelpText'] ) ? $attributes['switchHelpText'] : '';
		$checked       = isset( $attributes['checked'] ) ? $attributes['checked'] : '';
		$error_msg     = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$classname     = isset( $attributes['className'] ) ? $attributes['className'] : '';
		$color_primary = get_post_meta( Sureforms_Helper::get_integer_value( $form_id ), '_srfm_color1', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $form_id ), '_srfm_color1', true ) ) : '';
		$checked_color = ! empty( $color_primary ) ? $color_primary : '#0084C7';

		return '<div class="srfm-switch-container srfm-main-container srfm-frontend-inputs-holder srfm-classic-switch-container' . esc_attr( $classname ) . '">
		<label class="srfm-switch-label" for="srfm-switch-' . esc_attr( $block_id ) . '">
			<div class="srfm-text-primary !srfm-flex !srfm-items-start !srfm-gap-2 !srfm-mt-1">
				<div class="srfm-switch-background srfm-classic-toggle-bg srfm-w-[60px] srfm-mr-[3px] srfm-mt-[5px]" style="background-color: ' . ( $checked ? esc_attr( $checked_color ) : '#dcdcdc' ) . '">
					<input class="srfm-switch srfm-classic-switch-input !srfm-p-0"
						name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '"
						id="srfm-switch-' . esc_attr( $block_id ) . '"
						' . ( $checked ? 'checked' : '' ) . ' type="checkbox"
						aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" />
					<div class="srfm-switch-toggle !srfm-top-[3px] !srfm-shadow !srfm-border !srfm-border-gray-200 !srfm-h-5 !srfm-w-5" style="left: ' . ( $checked ? '24px' : '0' ) . ';"> 
						<span class="srfm-classic-toggle-icon-container srfm-classic-toggle-icon ' . esc_attr( $checked ? '!opacity-100' : '!opacity-0' ) . '" aria-hidden="true">
							<svg style="fill: ' . esc_attr( $checked_color ) . '" class="!srfm-h-3 !srfm-w-3 srfm-classic-toggle-icon" viewBox="0 0 12 12">
								<path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
							</svg>
						</span>
					</div>
				</div>
				<span class="srfm-classic-label-text">' . esc_html( $label ) . ' ' . ( $required && $label ? '<span class="!srfm-text-required_icon_color"> *</span>' : '' ) . '</span>
			</div>
		</label>
		' . ( '' !== $help ? '<p for="srfm-checkbox" class="srfm-helper-txt">' . esc_html( $help ) . '</p>' : '' ) . '
		<span style="display: none;" class="srfm-error-message">' . esc_html( $error_msg ) . '</span>
	</div>';

	}

}
