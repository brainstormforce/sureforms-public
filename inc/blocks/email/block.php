<?php
/**
 * PHP render form Email Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Email;

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
			$id          = isset( $attributes['id'] ) ? strval( $attributes['id'] ) : '';
			$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
			$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help        = isset( $attributes['help'] ) ? $attributes['help'] : '';
			ob_start(); ?>
		<div class="sureforms-input-email-container main-container" style="display:flex; flex-direction:column; gap:0.5rem;">
			<label for="sureforms-input-email"><?php echo esc_attr( $label ); ?> 
				<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
			</label>
			<input name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" id="sureforms-input-email" type="email" <?php echo esc_attr( $required ? 'required' : '' ); ?> placeholder="<?php echo esc_attr( $placeholder ); ?>" 
			style="padding: 5px; line-height: 2; min-height: 30px; box-shadow: 0 0 0 transparent; border-radius: 4px; border: 2px solid #8c8f94; background-color: #fff; color: #2c3338;
			">
			<?php echo '' !== $help ? '<label for="sureforms-input-email" style="color:#ddd;">' . esc_attr( $help ) . '</label>' : ''; ?>
		</div>
			<?php
		}
			return ob_get_clean();
	}
}
