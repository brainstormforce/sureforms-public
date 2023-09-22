<?php
/**
 * PHP render form dropdown Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Dropdown;

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
			$id        = isset( $attributes['id'] ) ? $sureforms_helper_instance->get_string_value( $attributes['id'] ) : '';
			$required  = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$options   = isset( $attributes['options'] ) ? $attributes['options'] : '';
			$label     = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help      = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$error_msg = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$classname = isset( $attributes['className'] ) ? $attributes['className'] : '';
			ob_start(); ?>
		<div class="sureforms-dropdown-container main-container frontend-inputs-holder <?php echo esc_attr( $classname ); ?>">
			<label class="text-primary"><?php echo esc_html( $label ); ?> 
				<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
			</label>
			<select 
			name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>"
			area-required=<?php echo esc_attr( $required ? 'true' : 'fasle' ); ?>
			class="sureforms-input-field"
			>
			<?php foreach ( $options as $option ) : ?>
				<option value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $option ); ?></option>
			<?php endforeach; ?>
			</select>
			<?php echo '' !== $help ? '<label class="text-secondary sforms-helper-txt">' . esc_html( $help ) . '</label>' : ''; ?>
			<span style="display:none" class="error-message"><?php echo esc_html( $error_msg ); ?></span>
		</div>
			<?php
		}
			return ob_get_clean();
	}
}
