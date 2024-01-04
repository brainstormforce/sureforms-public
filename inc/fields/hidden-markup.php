<?php
/**
 * Sureforms Hidden Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;

/**
 * SureForms Hidden Markup Class.
 *
 * @since 0.0.1
 */
class Hidden_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms hidden default styling block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function default_styling( $attributes ) {
		$block_id             = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';
		$label                = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$input_label_fallback = $label ? $label : 'Hidden Field';
		$input_label          = '-lbl-' . Sureforms_Helper::encrypt( $input_label_fallback );
		$default              = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
		$slug                 = 'hidden';
		ob_start(); ?>
		<div class="srfm-<?php echo esc_attr( $slug ); ?>-block">
			<input name="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?><?php echo esc_attr( $input_label ); ?>" value="<?php echo esc_attr( $default ); ?>" type="hidden" class="srfm-<?php echo esc_attr( $slug ); ?>-input">     
		</div>
		<?php
		return ob_get_clean();
	}
}
