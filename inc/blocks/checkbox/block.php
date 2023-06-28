<?php
/**
 * PHP render for Text Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Checkbox;

use SureForms\Inc\Blocks\Base;

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
		if ( ! empty( $attributes ) ) {
			$id          = isset( $attributes['id'] ) ? strval( $attributes['id'] ) : '';
			$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
			$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help        = isset( $attributes['checkboxHelpText'] ) ? $attributes['checkboxHelpText'] : '';
			$label_url   = isset( $attributes['labelUrl'] ) ? $attributes['labelUrl'] : '';
			$checked     = isset( $attributes['checked'] ) ? $attributes['checked'] : '';
			ob_start(); ?>
		<div class="sureforms-checkbox-container main-container" style="display:flex; flex-direction:column; gap:0.5rem;">
			<div>
				<input name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" id="sureforms-checkbox" <?php echo esc_attr( $checked ? 'checked' : '' ); ?> type="checkbox" <?php echo esc_attr( $required ? 'required' : '' ); ?> placeholder="<?php echo esc_attr( $placeholder ); ?>">
				<span class="text-primary">
					<label for="sureforms-checkbox" class="text-primary">
						<?php
							echo $label_url
							? '<a target="_blank" href="' . esc_attr( $label_url ) . '" style="text-decoration:none;">' . esc_html( $label ) . '</a>'
							: esc_html( $label );
						?>
						<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
					</label>
				</span>
			</div>
			<?php echo '' !== $help ? '<label for="sureforms-checkbox" class="text-secondary">' . esc_attr( $help ) . '</label>' : ''; ?>
		</div>
			<?php
		}
			return ob_get_clean();
	}
}
