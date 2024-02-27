<?php
/**
 * Sureforms Dropdown Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc\Fields;

use SRFM\Inc\Traits\SRFM_Get_Instance;
use SRFM\Inc\SRFM_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sureforms Dropdown Markup Class.
 *
 * @since 0.0.1
 */
class SRFM_Dropdown_Markup extends SRFM_Base {
	use SRFM_Get_Instance;

	/**
	 * Render the sureforms dropdown classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param int|string   $form_id form id.
	 *
	 * @return string|boolean
	 */
	public function markup( $attributes, $form_id ) {
		$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$options     = isset( $attributes['options'] ) ? $attributes['options'] : '';
		$field_width = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help        = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg   = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$class_name  = isset( $attributes['className'] ) ? ' ' . $attributes['className'] : '';
		$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$block_id    = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';
		$slug        = 'dropdown';

		$block_width          = $field_width ? ' srfm-block-width-' . str_replace( '.', '-', $field_width ) : '';
		$aria_require         = $required ? 'true' : 'false';
		$placeholder_html     = $placeholder ? $placeholder : 'Select option';
		$input_label_fallback = $label ? $label : __( 'Dropdown', 'sureforms' );
		$input_label          = '-lbl-' . SRFM_Helper::encrypt( $input_label_fallback );

		ob_start(); ?>
			<div class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block srf-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-block<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $class_name ); ?>">
				<?php echo wp_kses_post( SRFM_Helper::generate_common_form_markup( $form_id, 'label', $label, $slug, $block_id, boolval( $required ) ) ); ?>
				<div class="srfm-block-wrap srfm-dropdown-common-wrap">
				<?php

				if ( is_array( $options ) ) {
					?>
				<select class="srfm-dropdown-common srfm-<?php echo esc_attr( $slug ); ?>-input" aria-required="<?php echo esc_attr( $aria_require ); ?>" name="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?><?php echo esc_attr( $input_label ); ?>" tabindex="0" aria-hidden="true">
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
				<?php echo wp_kses_post( SRFM_Helper::generate_common_form_markup( $form_id, 'help', '', '', '', false, $help ) ); ?>
				<?php echo wp_kses_post( SRFM_Helper::generate_common_form_markup( $form_id, 'error', '', '', '', boolval( $required ), '', $error_msg ) ); ?>
			</div>

		<?php
		return ob_get_clean();

	}

}
