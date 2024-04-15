<?php
/**
 * Sureforms GDPR Markup Class file.
 *
 * @package sureforms.
 * @since x.x.x
 */

namespace SRFM\Inc\Fields;

use SRFM\Inc\Traits\Get_Instance;
use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sureforms GDPR Markup Class.
 *
 * @since x.x.x
 */
class GDPR_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms GDPR classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function markup( $attributes ) {
		$form_id     = isset( $attributes['formId'] ) ? Helper::get_string_value( $attributes['formId'] ) : '';
		$required    = true;
		$field_width = isset( $attributes['fieldWidth'] ) ? Helper::get_string_value( $attributes['fieldWidth'] ) : '';
		$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help        = isset( $attributes['gdprHelpText'] ) ? $attributes['gdprHelpText'] : '';
		$checked     = isset( $attributes['checked'] ) ? $attributes['checked'] : '';
		$error_msg   = isset( $attributes['errorMsg'] ) && $attributes['errorMsg'] ? $attributes['errorMsg'] : Helper::get_default_dynamic_block_option( 'srfm_gdpr_block_required_text' );
		$class_name  = isset( $attributes['className'] ) ? ' ' . $attributes['className'] : '';
		$block_id    = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';
		$block_slug  = isset( $attributes['slug'] ) ? $attributes['slug'] : '';
		$slug        = 'gdpr';

		$block_width          = $field_width ? ' srfm-block-width-' . str_replace( '.', '-', $field_width ) : '';
		$input_label_fallback = $label ? $label : __( 'I consent to have this website store my submitted information so they can respond to my inquiry.', 'sureforms' );
		$input_label          = '-lbl-' . Helper::encrypt( $input_label_fallback );
		$field_name           = $input_label . '-' . $block_slug;
		$conditional_class    = apply_filters( 'srfm_conditional_logic_classes', $form_id, $block_id );

		// html attributes.
		$aria_require_attr = 'true';
		$checked_attr      = $checked ? 'checked' : '';
		$allowed_tags      = [
			'a' => [
				'href'   => [],
				'target' => [],
			],
		];

		ob_start(); ?>
			<div data-block-id="<?php echo esc_attr( $block_id ); ?>" class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block srf-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-block<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $class_name ); ?> <?php echo esc_attr( $conditional_class ); ?>">
				<div class="srfm-block-wrap">
					<input class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>" id="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>" name="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?><?php echo esc_attr( $field_name ); ?>" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>" type="checkbox" <?php echo esc_attr( $checked_attr ); ?>/>
					<label class="srfm-cbx" for="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>">
						<span class="srfm-span-wrap">
							<svg class="srfm-check-icon" width="12px" height="10px">
								<use xlink:href="#srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-check"></use>
							</svg>
						</span>
						<span class="srfm-block-text srfm-span-wrap"><?php echo wp_kses( $label, $allowed_tags ); ?>
							<span class="srfm-required"> *</span></span>
					</label>
					<svg class="srfm-inline-svg">
						<symbol id="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-check" viewbox="0 0 12 10">
						<polyline points="1.5 6 4.5 9 10.5 1"></polyline>
						</symbol>
					</svg>
				</div>
				<?php echo wp_kses_post( Helper::generate_common_form_markup( $form_id, 'help', '', '', '', false, $help ) ); ?>
				<?php echo wp_kses_post( Helper::generate_common_form_markup( $form_id, 'error', '', '', '', boolval( $required ), '', $error_msg ) ); ?>
			</div>
		<?php

		return ob_get_clean();

	}

}
