<?php
/**
 * PHP render form Number Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Number;

use SureForms\Inc\Blocks\Base;

/**
 * Address Block.
 */
class Block extends Base {
	/**
	 * Render the block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param string       $content Post content.
	 *
	 * @return string|boolean
	 */
	public function render( $attributes, $content = '' ) {
		if ( ! empty( $attributes ) ) {
			$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
			$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help        = isset( $attributes['help'] ) ? $attributes['help'] : '';
			ob_start(); ?>
		<div class="sureform-input-number-container" style="display:flex; flex-direction:column; gap:0.5rem;">
			<label for="sureform-input-number"><?php echo esc_attr( $label ); ?> 
				<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
			</label>
			<input name="<?php echo esc_attr( str_replace( ' ', '_', $label ) ); ?>" id="sureform-input-number" type="number" required="<?php echo esc_attr( $required ? 'true' : 'false' ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" 
			style="padding: 0 8px; 
				line-height: 2; 
				min-height: 30px;
				box-shadow: 0 0 0 transparent;
				border-radius: 4px;
				border: 1px solid #8c8f94;
				background-color: #fff;
				color: #2c3338;
			">
			<?php echo '' !== $help ? '<label for="sureform-input-number" style="color:#ddd;">' . esc_attr( $help ) . '</label>' : ''; ?>
		</div>
			<?php
		}
			return ob_get_clean();
	}
}
