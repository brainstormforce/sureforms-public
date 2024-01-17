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
		$class_name  = isset( $attributes['className'] ) ? ' ' . $attributes['className'] : '';
		$block_id    = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';
		$slug        = 'checkbox';

		$block_width = $field_width ? ' srfm-block-width-' . str_replace( '.', '-', $field_width ) : '';

		// html attributes.
		$aria_require_attr    = $required ? 'true' : 'false';
		$checked_attr         = $checked ? 'checked' : '';
		$input_label_fallback = $label ? $label : 'Checkbox';
		$input_label          = '-lbl-' . Sureforms_Helper::encrypt( $input_label_fallback );

		ob_start(); ?>
			<div class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block srf-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-block<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $class_name ); ?>">
					<div class="srfm-block-wrap">
						<input class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>" id="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>" name="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?><?php echo esc_attr( $input_label ); ?>" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>" type="checkbox" <?php echo esc_attr( $checked_attr ); ?>/>
						<label class="srfm-cbx" for="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>">
							<span class="srfm-span-wrap">
								<svg class="srfm-check-icon" width="12px" height="10px">
									<use xlink:href="#srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-check"></use>
								</svg>
							</span>
							<span class="srfm-block-text srfm-span-wrap"><?php echo $label_url ? '<a class="srfm-block-url" target="_blank" href="' . esc_url( $label_url ) . '">' . esc_html( $label ) . '</a>' : esc_html( $label ); ?>
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
				<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( 'help', '', '', '', false, $help ) ); ?>
				<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( 'error', '', '', '', boolval( $required ), '', $error_msg ) ); ?>
			</div>
		<?php

		return ob_get_clean();

	}

}
