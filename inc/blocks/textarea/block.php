<?php
/**
 * PHP render form Textarea Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Textarea;

use SureForms\Inc\Blocks\Base;
use SureForms\Inc\Sureforms_Helper;

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
		$sureforms_helper_instance = new Sureforms_Helper();

		if ( ! empty( $attributes ) ) {
			$id          = isset( $attributes['id'] ) ? $sureforms_helper_instance->get_string_value( $attributes['id'] ) : '';
			$default     = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
			$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
			$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help        = isset( $attributes['textAreaHelpText'] ) ? $attributes['textAreaHelpText'] : '';
			$max_length  = isset( $attributes['maxLength'] ) ? $attributes['maxLength'] : '';
			$rows        = isset( $attributes['rows'] ) ? $attributes['rows'] : '';
			$cols        = isset( $attributes['cols'] ) ? $attributes['cols'] : '';
			$error_msg   = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$classname   = isset( $attributes['className'] ) ? $attributes['className'] : '';
			ob_start(); ?>
			<div class="sureforms-textarea-container main-container frontend-inputs-holder <?php echo esc_attr( $classname ); ?>" style="position:relative">
				<label for="sureforms-textarea" class="text-primary"><?php echo esc_html( $label ); ?> 
					<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
				</label>
				<div class="sureforms-text-area-counter"><?php echo esc_attr( ( '' === $max_length ) ? '' : '0/' . esc_attr( $max_length ) ); ?></div>
				<textarea name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" id="sureforms-textarea" area-required="<?php echo esc_attr( $required ? 'true' : 'false' ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" maxLength="<?php echo 0 === $max_length ? '' : esc_attr( $max_length ); ?>" cols="<?php echo esc_attr( $cols ); ?>" rows="<?php echo esc_attr( $rows ); ?>" class="sureforms-textarea-field"><?php echo esc_attr( $default ); ?></textarea>
				<?php echo '' !== $help ? '<label for="sureforms-textarea" class="text-secondary sforms-helper-txt">' . esc_html( $help ) . '</label>' : ''; ?>
				<span style="display:none" class="error-message"><?php echo esc_html( $error_msg ); ?></span>
			</div>
			<?php
		}
			return ob_get_clean();
	}
}
