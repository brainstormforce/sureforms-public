<?php
/**
 * PHP render form URL Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Url;

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
			$help        = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$error_msg   = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$classname   = isset( $attributes['className'] ) ? $attributes['className'] : '';
			ob_start(); ?>
		<!-- <div class="sureforms-input-url-container main-container frontend-inputs-holder <?php echo esc_attr( $classname ); ?>">
			<label for="sureforms-input-url-<?php echo esc_attr( $id ); ?>" class="text-primary"><?php echo esc_html( $label ); ?> 
				<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
			</label>
			<input name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" id="sureforms-input-url-<?php echo esc_attr( $id ); ?>" type="url" area-required=<?php echo esc_attr( $required ? 'true' : 'false' ); ?> value="<?php echo esc_attr( $default ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" 
			class="sureforms-url-input
			">
			<?php echo '' !== $help ? '<label for="sureforms-input-url-' . esc_attr( $id ) . '" class="text-secondary sforms-helper-txt">' . esc_html( $help ) . '</label>' : ''; ?>
			<span style="display:none" class="error-message"><?php echo esc_html( $error_msg ); ?></span>
		</div> -->
		<div class="main-container frontend-inputs-holder sureforms-input-url-container <?php echo esc_attr( $classname ); ?>">
			<label for="sureforms-input-url-<?php echo esc_attr( $id ); ?>" class="sf-classic-label-text">
				<?php echo esc_html( $label ); ?><?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
			</label>
			<div class="mt-2 flex rounded-md shadow-sm">
				<span class="sf-classic-url-prefix">https://</span>
				<input type="text" name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-url' . $id ) ); ?>" id="sureforms-input-url-<?php echo esc_attr( $id ); ?>" area-required=<?php echo esc_attr( $required ? 'true' : 'false' ); ?> value="<?php echo esc_attr( $default ); ?>"
				class="sf-classic-url-element sureforms-url-input" placeholder="<?php echo esc_attr( $placeholder ); ?>">
			</div>
			<?php echo '' !== $help ? '<label for="sureforms-input-url-' . esc_attr( $id ) . '" class="sforms-helper-txt">' . esc_html( $help ) . '</label>' : ''; ?>
			<p style="display:none" class="error-message"><?php echo esc_html( $error_msg ); ?></p>
			<p style="display:none" class="validation-url-message mt-2 text-sm text-red-600"><?php echo esc_html( __( 'Please enter a valid URL.', 'sureforms' ) ); ?></p>
		</div>
			<?php
		}
			return ob_get_clean();
	}
}
