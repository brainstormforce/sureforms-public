<?php
/**
 * PHP render form Textarea Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Textarea;

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
			$help        = isset( $attributes['textAreaHelpText'] ) ? $attributes['textAreaHelpText'] : '';
			$max_length  = isset( $attributes['maxLength'] ) ? $attributes['maxLength'] : '';
			ob_start(); ?>
			<div class="sureform-textarea-container" style="display:flex; flex-direction:column; gap:0.5rem;">
				<label for="sureform-textarea"><?php echo esc_attr( $label ); ?> 
					<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
				</label>
				<textarea name="<?php echo esc_attr( str_replace( ' ', '_', $label ) ); ?>" id="sureform-textarea" required="<?php echo esc_attr( $required ? 'true' : 'false' ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" rows="<?php echo esc_attr( $max_length ); ?>"></textarea>
				<?php echo '' !== $help ? '<label for="sureform-textarea" style="color:#ddd;">' . esc_attr( $help ) . '</label>' : ''; ?>
			</div>
			<?php
		}
			return ob_get_clean();
	}
}
