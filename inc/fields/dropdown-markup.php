<?php
/**
 * Sureforms Dropdown Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc\Fields;

use SRFM\Inc\Traits\Get_Instance;
use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sureforms Dropdown Markup Class.
 *
 * @since 0.0.1
 */
class Dropdown_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms dropdown classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function markup( $attributes ) {
		$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$options     = isset( $attributes['options'] ) ? $attributes['options'] : '';
		$field_width = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help        = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg   = isset( $attributes['errorMsg'] ) && $attributes['errorMsg'] ? $attributes['errorMsg'] : Helper::get_default_dynamic_block_option( 'srfm_dropdown_block_required_text' );
		$class_name  = isset( $attributes['className'] ) ? ' ' . $attributes['className'] : '';
		$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$block_id    = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';
		$form_id     = isset( $attributes['formId'] ) ? $attributes['formId'] : '';
		$block_slug  = isset( $attributes['slug'] ) ? $attributes['slug'] : '';
		$slug        = 'dropdown';

		$block_width          = $field_width ? ' srfm-block-width-' . str_replace( '.', '-', $field_width ) : '';
		$aria_require         = $required ? 'true' : 'false';
		$placeholder_html     = $placeholder ? $placeholder : 'Select option';
		$input_label_fallback = $label ? $label : __( 'Dropdown', 'sureforms' );
		$input_label          = '-lbl-' . Helper::encrypt( $input_label_fallback );
		$field_name           = $input_label . '-' . $block_slug;
		$conditional_class    = apply_filters( 'srfm_conditional_logic_classes', $form_id, $block_id );

		ob_start(); ?>
			<div data-block-id="<?php echo esc_attr( $block_id ); ?>" class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block srf-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-block<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $class_name ); ?> <?php echo esc_attr( $conditional_class ); ?>">
				<?php echo wp_kses_post( Helper::generate_common_form_markup( $form_id, 'label', $label, $slug, $block_id, boolval( $required ) ) ); ?>
				<div class="srfm-block-wrap srfm-dropdown-common-wrap">
				<?php

				if ( is_array( $options ) ) {
					?>
				<select class="srfm-dropdown-common srfm-<?php echo esc_attr( $slug ); ?>-input" aria-required="<?php echo esc_attr( $aria_require ); ?>" name="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?><?php echo esc_attr( $field_name ); ?>" tabindex="0" aria-hidden="true">
				<option value="" disabled selected><?php echo esc_html( $placeholder_html ); ?></option>
					<?php
					foreach ( $options as $option ) {
						$option_text = esc_html( $option );
						?>
					<option value="<?php echo esc_html( $option ); ?>"><?php echo esc_html( $option ); ?></option>
				<?php } ?>
				</select>
				<?php } ?>
				</div>
				<?php echo wp_kses_post( Helper::generate_common_form_markup( $form_id, 'help', '', '', '', false, $help ) ); ?>
				<?php echo wp_kses_post( Helper::generate_common_form_markup( $form_id, 'error', '', '', '', boolval( $required ), '', $error_msg ) ); ?>
			</div>

		<?php
		return ob_get_clean();

	}

}
