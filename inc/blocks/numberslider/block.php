<?php
/**
 * PHP render form Number Slider Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Numberslider;

use SureForms\Inc\Blocks\Base;

/**
 * Number Slider Block.
 */
class Block extends Base {
	/**
	 * Render the block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param string       $content Post content.
	 * @since X.X.X
	 * @return string|boolean
	 */
	public function render( $attributes, $content = '' ) {
		if ( ! empty( $attributes ) ) {
			$id                 = isset( $attributes['id'] ) ? strval( $attributes['id'] ) : '';
			$required           = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$label              = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help               = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$min                = isset( $attributes['min'] ) ? $attributes['min'] : 0;
			$max                = isset( $attributes['max'] ) ? $attributes['max'] : 100;
			$step               = isset( $attributes['step'] ) ? $attributes['step'] : 1;
			$value_display_text = isset( $attributes['valueDisplayText'] ) ? $attributes['valueDisplayText'] : '';
			ob_start(); ?>
		<div class="sureforms-number-slider-container main-container" style="display:flex; flex-direction:column; gap:0.5rem;">
			<label for="sureforms-number-slider"><?php echo esc_attr( $label ); ?> 
				<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
			</label>
			<input name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" id="sureforms-number-slider-<?php echo esc_attr( $id ); ?>" type="range" <?php echo esc_attr( $required ? 'required' : '' ); ?> 
			min="<?php echo ( intval( $min ) ); ?>" max="<?php echo ( intval( $max ) ); ?>" step="<?php echo ( intval( $step ) ); ?>" value="0"
			class="sureforms-number-slider-input"
			>
			<div style="font-size:14px; font-weight:600;"><?php echo esc_attr( $value_display_text ); ?><span id="sureforms-number-slider-value-<?php echo esc_attr( $id ); ?>">0</span></div>
			<?php echo '' !== $help ? '<label for="sureforms-number-slider" ">' . esc_attr( $help ) . '</label>' : ''; ?>
		</div>
			<?php
		}
			return ob_get_clean();
	}
}
