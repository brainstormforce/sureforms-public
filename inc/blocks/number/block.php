<?php
/**
 * PHP render form Number Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Number;

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
			$min_value   = isset( $attributes['minValue'] ) ? $attributes['minValue'] : '';
			$max_value   = isset( $attributes['maxValue'] ) ? $attributes['maxValue'] : '';
			$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
			$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help        = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$error_msg   = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$format_type = isset( $attributes['formatType'] ) ? $attributes['formatType'] : '';
			$classname   = isset( $attributes['className'] ) ? $attributes['className'] : '';
			ob_start(); ?>
		<div class="sureforms-input-number-container main-container frontend-inputs-holder <?php echo esc_attr( $classname ); ?>">
			<label for="sureforms-input-number-<?php echo esc_attr( $id ); ?>" class="text-primary"><?php echo esc_html( $label ); ?> 
				<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
			</label>
			<input name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" <?php echo 'none' === $format_type ? 'step="any"' : ''; ?> id="sureforms-input-number-<?php echo esc_attr( $id ); ?>" type="<?php echo 'none' === $format_type ? 'number' : 'text'; ?>" value="<?php echo esc_attr( $default ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" format-type="<?php echo esc_attr( $format_type ); ?>" min="<?php echo esc_attr( $min_value ); ?>" max="<?php echo esc_attr( $max_value ); ?>"
			area-required="<?php echo esc_attr( $required ? 'true' : 'false' ); ?>" class="sureforms-input-field sf-number-field">
			<span style="display:none" class="error-message"><?php echo esc_html( $error_msg ); ?></span>
			<span class="min-max-validation-message error-message"></span>
			<?php echo '' !== $help ? '<label for="sureforms-input-number" class="text-secondary">' . esc_html( $help ) . '</label>' : ''; ?>
		</div>
			<?php
		}
			return ob_get_clean();
	}
}
