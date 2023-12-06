<?php
/**
 * Sureforms Input Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;

/**
 * Sureforms Input Markup Class.
 *
 * @since 0.0.1
 */
class Input_Markup extends Base {
	use Get_Instance;

	/**
	 * Render input markup
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function classic_styling( $attributes ) {
		$block_id      = isset( $attributes['block_id'] ) ? strval( $attributes['block_id'] ) : '';
		$default       = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
		$required      = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$is_unique     = isset( $attributes['isUnique'] ) ? $attributes['isUnique'] : false;
		$duplicate_msg = isset( $attributes['duplicateMsg'] ) ? $attributes['duplicateMsg'] : '';
		$placeholder   = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$label         = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$field_width   = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$help          = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg     = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$max_text_length  = isset( $attributes['textLength'] ) ? $attributes['textLength'] : '';
		$classname     = isset( $attributes['className'] ) ? $attributes['className'] : '';

		$inline_style = '';

		// Append Dynamic styles here.
		$inline_style .= $field_width ? 'width:' . $field_width . 'px;' : '';
		$style =  $inline_style ? 'style="'. $inline_style .'"' : '';


		// html attributes
		$placeholder_attr = $placeholder ? 'placeholder="'. $placeholder .'" ' : '';
		$max_length_attr = $max_text_length ? 'maxlength="'. $max_text_length .'" ' : '';
		$aria_require_attr = $required ? 'true' : 'false';
		$aria_unique_attr = $is_unique ? 'true' : 'false';
		$default_value_attr = $default ? 'value="'. $default .'" ' : '';

		ob_start(); ?>

			<div class="srfm-block srfm-text-input-block <?php echo esc_attr( $classname ) ?>" <?php esc_attr( $style ) ?>>
				<?php if( $label ) { ?>
					<label for="srfm-text-input-<?php echo esc_attr( $block_id ) ?>" class="srfm-block-label"><?php echo esc_html( $label ) ?><?php if( $required ) { ?><span class="srfm-requred"> *</span><?php } ?></label>
				<?php } ?>
				<input class="srfm-block-input" type="text" name="srfm-text-input-<?php echo esc_attr( $block_id ); ?>" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>" aria-unique="<?php echo esc_attr( $aria_unique_attr ); ?>" <?php echo esc_attr(  $placeholder_attr .''. $max_length_attr .''. $default_value_attr ); ?> />
				<?php if( $help ) { ?>
					<div class="srfm-description"><?php echo esc_html( $help ); ?></div>
				<?php } ?>
				<?php if( $required ) { ?>
					<div class="srfm-error srfm-hide"><?php echo esc_html( $error_msg ); ?></div>
				<?php } ?>
				<?php if( $is_unique ) { ?>
					<div class="srfm-error srfm-hide"><?php echo esc_html( $duplicate_msg ); ?></div>
				<?php } ?>
			</div>
		<?php 
		return ob_get_clean();
	}

}
