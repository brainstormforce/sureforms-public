<?php
/**
 * PHP render for Text Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Checkbox;

use SureForms\Inc\Blocks\Base;
use SureForms\Inc\Sureforms_Helper;

/**
 * Address Block.
 */
class Block extends Base {
	/**
	 * Render form checkbox block
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
			$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
			$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help        = isset( $attributes['checkboxHelpText'] ) ? $attributes['checkboxHelpText'] : '';
			$label_url   = isset( $attributes['labelUrl'] ) ? $attributes['labelUrl'] : '';
			$checked     = isset( $attributes['checked'] ) ? $attributes['checked'] : '';
			$error_msg   = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$classname   = isset( $attributes['className'] ) ? $attributes['className'] : '';
			ob_start(); ?>
		<div class="sureforms-checkbox-container main-container frontend-inputs-holder <?php echo esc_attr( $classname ); ?>">
			<div>
				<input name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" id="sureforms-checkbox-<?php echo esc_attr( $id ); ?>" <?php echo esc_attr( $checked ? 'checked' : '' ); ?> type="checkbox" area-required=<?php echo esc_attr( $required ? 'true' : 'false' ); ?> placeholder="<?php echo esc_attr( $placeholder ); ?>">
				<span class="text-primary">
					<label for="sureforms-checkbox-<?php echo esc_attr( $id ); ?>" class="text-primary">
						<?php
							echo $label_url
							? '<a target="_blank" href="' . esc_attr( $label_url ) . '" style="text-decoration:none;">' . esc_html( $label ) . '</a>'
							: esc_html( $label );
						?>
						<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
					</label>
				</span>
			</div>
			<?php echo '' !== $help ? '<label for="sureforms-checkbox" class="text-secondary sforms-helper-txt">' . esc_html( $help ) . '</label>' : ''; ?>
			<span style="display:none" class="error-message"><?php echo esc_html( $error_msg ); ?></span>
		</div>
			<?php
		}
			return ob_get_clean();
	}
}
