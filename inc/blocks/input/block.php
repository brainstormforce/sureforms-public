<?php
/**
 * PHP render form Text Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Input;

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
			$id           = isset( $attributes['id'] ) ? strval( $attributes['id'] ) : '';
			$default      = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
			$required     = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$is_unique    = isset( $attributes['isUnique'] ) ? $attributes['isUnique'] : false;
			$dulicate_msg = isset( $attributes['duplicateMsg'] ) ? $attributes['duplicateMsg'] : '';
			$placeholder  = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
			$label        = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help         = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$error_msg    = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$max_text_len = isset( $attributes['textLength'] ) ? $attributes['textLength'] : '';
			$classname    = isset( $attributes['className'] ) ? $attributes['className'] : '';
			ob_start(); ?>
		<!-- <div class="sureforms-input-text-container main-container frontend-inputs-holder <?php echo esc_attr( $classname ); ?>">
			<label class="text-primary"><?php echo esc_html( $label ); ?> 
				<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
			</label>
			<input id="sureforms-input-text-<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" type="text" area-required="<?php echo esc_attr( $required ? 'true' : 'false' ); ?>" value="<?php echo esc_attr( $default ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" 
			area-unique="<?php echo esc_attr( $is_unique ? 'true' : 'false' ); ?>" maxlength="<?php echo esc_attr( $max_text_len ); ?>" class="sureforms-input-field">
			<?php echo '' !== $help ? '<label for="sureforms-input-text" class="text-secondary sforms-helper-txt">' . esc_html( $help ) . '</label>' : ''; ?>
			<span style="display:none" class="error-message"><?php echo esc_html( $error_msg ); ?></span>
			<span style="display:none" class="error-message duplicate-message"><?php echo esc_html( $dulicate_msg ); ?></span>
		</div> -->
		<div class="main-container sf-classic-inputs-holder  <?php echo esc_attr( $classname ); ?>">
			<label for="text" class="block text-sm font-medium leading-6 text-primary_color"><?php echo esc_html( $label ); ?> <?php echo $required && $label ? '<span class="text-red-500"> *</span>' : ''; ?></label>
			<div class="mt-2">
				<input type="text" name="text" id="text" class="block !w-full !border-0 !border-[#d1d5db] !rounded-md !py-1.5 !text-gray-900 !shadow-sm !ring-1 !ring-inset !ring-gray-300 !bg-white placeholder:!text-gray-500 focus:!ring-2 focus:!border-solid focus:!border-primary_color focus:!ring-primary_color focus:!outline-0 focus:!bg-white sm:text-sm sm:leading-6" 
				placeholder="<?php echo esc_attr( $placeholder ); ?>" area-unique="<?php echo esc_attr( $is_unique ? 'true' : 'false' ); ?>" area-required="<?php echo esc_attr( $required ? 'true' : 'false' ); ?>" value="<?php echo esc_attr( $default ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>">
			</div>
			<?php echo '' !== $help ? '<p class="mt-2 text-sm text-gray-500" id="text-description">' . esc_html( $help ) . '</p>' : ''; ?>
			<p style="display:none" class="error-message mt-2 text-sm text-red-600"><?php echo esc_html( $error_msg ); ?></p>
			<p style="display:none" class="duplicate-message mt-2 text-sm text-red-600"><?php echo esc_html( $dulicate_msg ); ?></p>
		</div>
			<?php
		}
			return ob_get_clean();
	}
}
