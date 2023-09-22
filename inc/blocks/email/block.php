<?php
/**
 * PHP render form Email Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Email;

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
			$id               = isset( $attributes['id'] ) ? $sureforms_helper_instance->get_string_value( $attributes['id'] ) : '';
			$required         = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$default          = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
			$placeholder      = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
			$label            = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help             = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$is_unique        = isset( $attributes['isUnique'] ) ? $attributes['isUnique'] : false;
			$dulicate_msg     = isset( $attributes['duplicateMsg'] ) ? $attributes['duplicateMsg'] : '';
			$error_msg        = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$is_confirm_email = isset( $attributes['isConfirmEmail'] ) ? $attributes['isConfirmEmail'] : false;
			$confirm_label    = isset( $attributes['confirmLabel'] ) ? $attributes['confirmLabel'] : '';
			$classname        = isset( $attributes['className'] ) ? $attributes['className'] : '';
			ob_start(); ?>
		<div class="sureforms-input-email-container main-container frontend-inputs-holder <?php echo esc_attr( $classname ); ?>">
			<label for="sureforms-input-email-<?php echo esc_attr( $id ); ?>" class="text-primary"><?php echo esc_html( $label ); ?> 
				<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
			</label>
			<input name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" id="sureforms-input-email-<?php echo esc_attr( $id ); ?>" type="email" area-required="<?php echo esc_attr( $required ? 'true' : 'false' ); ?>" value="<?php echo esc_attr( $default ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" 
			area-unique="<?php echo esc_attr( $is_unique ? 'true' : 'false' ); ?>" class="sureforms-input-email sureforms-input-field">
			<span style="display:none" class="error-message"><?php echo esc_html( $error_msg ); ?></span>
			<span style="display:none" class="error-message duplicate-message"><?php echo esc_html( $dulicate_msg ); ?></span>
			<?php
			echo true === $is_confirm_email ?
			'<label for="sureforms-input-confirm-email-' . esc_attr( $id ) . '" class="text-primary sureforms-confirm-email-spl">' . esc_html( $confirm_label ) . ' ' . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '</label>' .
			'<input id="sureforms-input-confirm-email-' . esc_attr( $id ) . '" type="email" data-required="' . esc_attr( $required ? 'true' : 'false' ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="sureforms-input-field sureforms-input-confirm-email">' : '';
			?>
			<?php echo '' !== $help ? '<label for="sureforms-input-email" class="text-secondary sforms-helper-txt">' . esc_html( $help ) . '</label>' : ''; ?>
			<span style="display:none" class="error-message"><?php echo esc_html( $error_msg ); ?></span>
			<span style="display:none" class="error-message confirm-email-error"><?php echo 'Field values do not match.'; ?></span>
		</div>
			<?php
		}
			return ob_get_clean();
	}
}
