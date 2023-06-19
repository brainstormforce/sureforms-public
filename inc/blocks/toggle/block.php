<?php
/**
 * PHP render form Toggle Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Toggle;

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
			$id       = isset( $attributes['id'] ) ? strval( $attributes['id'] ) : '';
			$required = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$label    = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help     = isset( $attributes['switchHelpText'] ) ? $attributes['switchHelpText'] : '';
			$checked  = isset( $attributes['checked'] ) ? $attributes['checked'] : '';
			ob_start(); ?>
		<div class="sureforms-switch-container main-container" style="display:flex; flex-direction:column; gap:0.5rem; ">
		<label for="sureforms-switch-<?php echo esc_attr( $id ); ?>">
		<div style="display:flex; align-items:center; gap:0.5rem;">
				<div class="switch-background" style="display: inline-block; position: relative; width: 50px; height: 25px; border-radius: 25px; background-color: <?php echo $checked ? '#007CBA' : '#dcdcdc'; ?>; transition: background-color 0.2s; cursor: pointer;">
					<input class="sureforms-switch" name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" id="sureforms-switch-<?php echo esc_attr( $id ); ?>"
					<?php echo esc_attr( $checked ? 'checked' : '' ); ?>
					type="checkbox" <?php echo esc_attr( $required ? 'required' : '' ); ?> style="position: absolute; opacity: 0; width: 0; height: 0;">
					<div class="switch-toggle" style="display: inline-block; position: absolute; width: 21px; height: 21px; border-radius: 50%; background-color: #fff; top: 2px; left: <?php echo $checked ? '27px' : '2px'; ?>; transition: left 0.2s;">
					</div>
				</div>
				</label>
				<span style="color: var(--primary-color)"><?php echo esc_html( $label ); ?></span><?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
			</div>
			<?php echo '' !== $help ? '<label>' . esc_html( $help ) . '</label>' : ''; ?>
		</div>
			<?php
		}
			return ob_get_clean();
	}
}
