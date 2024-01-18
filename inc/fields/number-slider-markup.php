<?php
/**
 * Sureforms Number Slider Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;

/**
 * Sureforms Input Markup Class.
 *
 * @since 0.0.1
 */
class Number_Slider_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms number slider classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function default( $attributes ) {
		$block_id           = isset( $attributes['block_id'] ) ? strval( $attributes['block_id'] ) : '';
		$required           = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$field_width        = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$label              = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help               = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$min                = isset( $attributes['min'] ) ? $attributes['min'] : 0;
		$max                = isset( $attributes['max'] ) ? $attributes['max'] : 100;
		$step               = isset( $attributes['step'] ) ? $attributes['step'] : 1;
		$value_display_text = isset( $attributes['valueDisplayText'] ) ? $attributes['valueDisplayText'] : '';
		$error_msg          = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$classname          = isset( $attributes['className'] ) ? $attributes['className'] : '';

		$slug = 'number-slider';

		$block_width          = $field_width ? ' srfm-block-width-' . str_replace( '.', '-', $field_width ) : '';
		$inverse_value        = $max - $min;
		$input_label_fallback = $label ? $label : 'Number Slider';
		$input_label          = '-lbl-' . Sureforms_Helper::encrypt( $input_label_fallback );

		ob_start(); ?>


<div class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block srf-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-block<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $classname ); ?>">
		<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( 'label', $label, $slug, $block_id, boolval( $required ) ) ); ?>
		<div class="srfm-block-wrap">
		<div class="srfm-<?php echo esc_attr( $slug ); ?>-wrap" style="--min:<?php echo esc_attr( $min ); ?>%; --max:<?php echo esc_attr( $max ); ?>%; --value:<?php echo esc_attr( $min ); ?>%;">
			<div class="srfm-<?php echo esc_attr( $slug ); ?>"></div>
			<span class="srfm-<?php echo esc_attr( $slug ); ?>-thumb"></span>
			<div class="srfm-<?php echo esc_attr( $slug ); ?>-sign">
				<span><?php echo esc_attr( $min ); ?></span>
			</div>
		</div>
		<input class="srfm-input-<?php echo esc_attr( $slug ); ?>" name="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?><?php echo esc_attr( $input_label ); ?>" type="range" tabindex="0" value="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" min="<?php echo esc_attr( $min ); ?>" step="<?php echo esc_attr( $step ); ?>">
		</div>
		<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( 'help', '', '', '', false, $help ) ); ?>
		</div>
		<?php
		return ob_get_clean();
	}

}
