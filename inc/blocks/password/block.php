<?php
/**
 * PHP render form Password Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Password;

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
			$id                  = isset( $attributes['id'] ) ? $sureforms_helper_instance->get_string_value( $attributes['id'] ) : '';
			$required            = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$placeholder         = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
			$label               = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help                = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$error_msg           = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$is_confirm_password = isset( $attributes['isConfirmPassword'] ) ? $attributes['isConfirmPassword'] : false;
			$confirm_label       = isset( $attributes['confirmLabel'] ) ? $attributes['confirmLabel'] : '';
			$classname           = isset( $attributes['className'] ) ? $attributes['className'] : '';
			ob_start(); ?>
		<div class="sureforms-input-password-container main-container frontend-inputs-holder <?php echo esc_attr( $classname ); ?>">
			<label for="sureforms-input-password-<?php echo esc_attr( $id ); ?>" class="text-primary"><?php echo esc_html( $label ); ?> 
				<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
			</label>
			<input name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" id="sureforms-input-password-<?php echo esc_attr( $id ); ?>" type="password" area-required="<?php echo esc_attr( $required ? 'true' : 'false' ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" 
			class="sureforms-input-field
			">
			<span class="info-icon" data-tooltip="A stronger password is required minimum 8 character using upper and lower case letters, numbers, and symbols.">&#9432; <span class="password-strength-message"></span></span>
			<span style="display:none" class="error-message"><?php echo esc_html( $error_msg ); ?></span>
			<?php
			echo true === $is_confirm_password ? '
			<label for="sureforms-confirm-input-password-' . esc_attr( $id ) . '" class="text-primary sureforms-confirm-pwd-spl">' . esc_html( $confirm_label ) . ' ' . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '</label>
			<input id="sureforms-confirm-input-password-' . esc_attr( $id ) . '" type="password" area-required="' . esc_attr( $required ? 'true' : 'false' ) . '" placeholder="' . esc_attr( $placeholder ) . '" 
			class="sureforms-input-field sureforms-confirm-input-password">' : '';
			?>
			<?php echo '' !== $help ? '<label for="sureforms-input-password" class="text-secondary sforms-helper-txt">' . esc_attr( $help ) . '</label>' : ''; ?>
			<span style="display:none" class="error-message"><?php echo esc_attr( $error_msg ); ?></span>
			<span style="display:none" class="error-message confirm-password-error"><?php echo 'Field values do not match.'; ?></span>
		</div>
			<?php
		}
			return ob_get_clean();
	}
}
