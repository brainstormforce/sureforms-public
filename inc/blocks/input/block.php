<?php
/**
 * PHP render form Text Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Input;

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
			$id           = isset( $attributes['id'] ) ? strval( $attributes['id'] ) : '';
			$default      = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
			$required     = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$is_unique    = isset( $attributes['isUnique'] ) ? $attributes['isUnique'] : false;
			$dulicate_msg = isset( $attributes['duplicateMsg'] ) ? $attributes['duplicateMsg'] : '';
			$placeholder  = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
			$label        = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help         = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$error_msg    = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$max_text_len = isset( $attributes['textLength'] ) ? $attributes['textLength'] : '';
			ob_start(); ?>
		<div class="sureforms-input-text-container main-container" style="display:flex; flex-direction:column; gap:0.5rem;">
			<label class="text-primary"><?php echo esc_attr( $label ); ?> 
				<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
			</label>
			<input id="sureforms-input-text" name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" type="text" area-required="<?php echo esc_attr( $required ? 'true' : 'false' ); ?>" value="<?php echo esc_attr( $default ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" 
			area-unique="<?php echo esc_attr( $is_unique ? 'true' : 'false' ); ?>" maxlength="<?php echo esc_attr( $max_text_len ); ?>"style="padding: 5px; line-height: 2; min-height: 30px; box-shadow: 0 0 0 transparent; border-radius: 4px; border: 2px solid #8c8f94; background-color: #fff; color: #2c3338;">
			<span style="display:none" class="error-message"><?php echo esc_attr( $error_msg ); ?></span>
			<span style="display:none" class="error-message duplicate-message"><?php echo esc_attr( $dulicate_msg ); ?></span>
			<?php echo '' !== $help ? '<label for="sureforms-input-text" class="text-secondary">' . esc_attr( $help ) . '</label>' : ''; ?>
		</div>
			<?php
		}
			return ob_get_clean();
	}
}
