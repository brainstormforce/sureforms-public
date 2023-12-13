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
	public function classic_styling( $attributes ) {
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

		$block_width = $field_width ? ' srfm-block-width-' . str_replace(".","-",$field_width) : '';

		ob_start(); ?>		
		<div class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block srfm-block-width-<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $classname ) ?>">
		<div class="srfm-block-wrap" style='--min:0; --max:100; --step:1; --value:1; --text-value:"1";'>
		<input type="range" min="0" max="100" value="1" oninput="this.parentNode.style.setProperty('--value',this.value); this.parentNode.style.setProperty('--text-value', JSON.stringify(this.value))">
		<output></output>
		<div class='srfm-block-wrap__progress'></div>
		</div>
		</div>
		<?php
		return ob_get_clean();
	}

}
