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
			$required = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$label    = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help     = isset( $attributes['switchHelpText'] ) ? $attributes['switchHelpText'] : '';
			$checked  = isset( $attributes['checked'] ) ? $attributes['checked'] : '';
			ob_start(); ?>
		<div class="sureform-switch-container" style="display:flex; flex-direction:column; gap:0.5rem; ">
			<div style="display:flex; align-items:center; gap:0.5rem;">
				<div style="display: inline-block;
						position: relative;
						width: 50px;
						height: 25px;
						border-radius: 25px;
						background-color: <?php echo $checked ? '#007CBA' : '#dcdcdc'; ?>;
						transition: background-color 0.2s;
						cursor: pointer;">
					<input name="<?php echo esc_attr( str_replace( ' ', '_', $label ) ); ?>" id="sureform-switch" checked="<?php echo $checked ? 'true' : 'false'; ?>" type="checkbox" required="<?php echo $required ? 'true' : 'false'; ?>" style="position: absolute;
					opacity: 0;
					width: 0;
					height: 0;">
					<div style="display: inline-block;
						position: absolute;
						width: 21px;
						height: 21px;
						border-radius: 50%;
						background-color: #fff;
						top: 2px;
						left: <?php echo $checked ? '27px' : '2px'; ?>;
						transition: left 0.2s;">
					</div>
				</div>
				<label for="sureform-switch">
					<?php echo esc_html( $label ); ?><?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
				</label>
			</div>
			<?php echo '' !== $help ? '<label for="sureform-switch" style="color:#ddd;">' . esc_html( $help ) . '</label>' : ''; ?>
		</div>
			<?php
		}
			return ob_get_clean();
	}
}
