<?php
/**
 * PHP render form dropdown Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Dropdown;

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
			$options  = isset( $attributes['options'] ) ? $attributes['options'] : '';
			$label    = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help     = isset( $attributes['help'] ) ? $attributes['help'] : '';
			ob_start(); ?>
		<div class="sureforms-dropdown-container main-container" style="display:flex; flex-direction:column; gap:0.5rem;">
			<label class="text-primary"><?php echo esc_html( $label ); ?> 
				<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
			</label>
			<select 
			name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>"
			style="padding: 5px; 
			min-height: 43px; 
			box-shadow: 0 0 0 transparent; 
			border-radius: 4px; 
			border: 2px solid #8c8f94; 
			background-color: #fff; 
			color: #2c3338;">
			<?php foreach ( $options as $option ) : ?>
				<option value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $option ); ?></option>
			<?php endforeach; ?>
			</select>
			<?php echo '' !== $help ? '<label class="text-secondary">' . esc_attr( $help ) . '</label>' : ''; ?>
		</div>
			<?php
		}
			return ob_get_clean();
	}
}
