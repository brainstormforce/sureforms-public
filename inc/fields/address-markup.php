<?php
/**
 * Sureforms Address Markup Class file.
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
 * Sureforms Address Markup Class.
 *
 * @since 0.0.1
 */
class Address_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms address classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param string       $content inner block content.
	 *
	 * @return string|boolean
	 */
	public function markup( $attributes, $content = '' ) {
		$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$field_width = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help        = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$class_name  = isset( $attributes['className'] ) ? ' ' . $attributes['className'] : '';
		$block_id    = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';
		$form_id     = isset( $attributes['formId'] ) ? $attributes['formId'] : '';
		$help        = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$slug        = 'address';

		$block_width          = $field_width ? ' srfm-block-width-' . str_replace( '.', '-', $field_width ) : '';
		$input_label_fallback = $label ? $label : __( 'Address', 'sureforms' );
		$input_label          = '-lbl-' . Helper::encrypt( $input_label_fallback );

		$conditional_class = apply_filters( 'srfm_conditional_logic_classes', $form_id, $block_id );

		ob_start(); ?>
			<div data-block-id="<?php echo esc_attr( $block_id ); ?>" class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block srf-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-block<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $class_name ); ?> <?php echo esc_attr( $conditional_class ); ?>">
				<div class="srfm-address-label-ctn">
					<?php echo wp_kses_post( Helper::generate_common_form_markup( $form_id, 'label', $label, $slug, $block_id, boolval( $required ) ) ); ?>
				</div>
				<input class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>-hidden" type="hidden" name="srfm-<?php echo esc_attr( $slug ); ?>-hidden-<?php echo esc_attr( $block_id ); ?><?php echo esc_attr( $input_label ); ?>"/>
				<div class="srfm-block-wrap">
					<?php
                        // phpcs:ignore
                        echo $content;
                        // phpcs:ignoreEnd
					?>
				</div>
				<div class="srfm-address-help-ctn">
					<?php echo wp_kses_post( Helper::generate_common_form_markup( $form_id, 'help', '', '', '', false, $help ) ); ?>
				</div>
			</div>
		<?php

		return ob_get_clean();

	}

}
