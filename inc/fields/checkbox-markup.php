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
	 * @param int|string   $form_id form id.
	 *
	 * @return string|boolean
	 */
	public function default( $attributes, $form_id ) {
		$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$field_width = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help        = isset( $attributes['checkboxHelpText'] ) ? $attributes['checkboxHelpText'] : '';
		$checked     = isset( $attributes['checked'] ) ? $attributes['checked'] : '';
		$error_msg   = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$class_name  = isset( $attributes['className'] ) ? ' ' . $attributes['className'] : '';
		$block_id    = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';
		$form_id    = isset( $attributes['formId'] ) ? $attributes['formId'] : '';
		$slug        = 'checkbox';

		$block_width = $field_width ? ' srfm-block-width-' . str_replace( '.', '-', $field_width ) : '';

		// html attributes.
		$aria_require_attr    = $required ? 'true' : 'false';
		$checked_attr         = $checked ? 'checked' : '';
		$input_label_fallback = $label ? $label : __( 'Checkbox', 'sureforms' );
		$input_label          = '-lbl-' . Sureforms_Helper::encrypt( $input_label_fallback );
		$allowed_tags         = array(
			'a' => array(
				'href'   => array(),
				'target' => array(),
			),
		);
		$conditional_class    = apply_filters( 'sureforms_conditional_logic_classes', $form_id, $block_id );

		ob_start(); ?>
			<div data-block-id="<?php echo esc_attr( $block_id ); ?>" class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block srf-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-block<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $class_name ); ?> <?php echo esc_attr( $conditional_class ); ?>">
					<div class="srfm-block-wrap">
						<input class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>" id="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>" name="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?><?php echo esc_attr( $input_label ); ?>" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>" type="checkbox" <?php echo esc_attr( $checked_attr ); ?>/>
						<label class="srfm-cbx" for="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>">
							<span class="srfm-span-wrap">
								<svg class="srfm-check-icon" width="12px" height="10px">
									<use xlink:href="#srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-check"></use>
								</svg>
							</span>
							<span class="srfm-block-text srfm-span-wrap"><?php echo wp_kses( $label, $allowed_tags ); ?>
																					<?php
																					if ( $required ) {
																						?>
								<span class="srfm-required"> *</span><?php } ?></span>
						</label>
						<svg class="srfm-inline-svg">
							<symbol id="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-check" viewbox="0 0 12 10">
							<polyline points="1.5 6 4.5 9 10.5 1"></polyline>
							</symbol>
						</svg>
					</div>
				<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( $form_id, 'help', '', '', '', false, $help ) ); ?>
				<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( $form_id, 'error', '', '', '', boolval( $required ), '', $error_msg ) ); ?>
			</div>
		<?php

		return ob_get_clean();

	}

}
