<?php
/**
 * Sureforms Password Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;
/**
 * Sureforms_Password_Markup Class.
 *
 * @since 0.0.1
 */
class Password_Markup extends Base {
	use Get_Instance;


	/**
	 * Render the sureforms password classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function classic_styling( $attributes ) {
		$required            = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$placeholder         = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$field_width         = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$label               = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help                = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg           = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$is_confirm_password = isset( $attributes['isConfirmPassword'] ) ? $attributes['isConfirmPassword'] : false;
		$confirm_label       = isset( $attributes['confirmLabel'] ) ? $attributes['confirmLabel'] : '';
		$classname           = isset( $attributes['className'] ) ? $attributes['className'] : '';
		$block_id            = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';


		$slug = 'password';

		$inline_style = '';

		// Append Dynamic styles here.
		$inline_style .= $field_width ? 'width:' . $field_width . '%;' : '';
		$style =  $inline_style ? 'style="'. $inline_style .'"' : '';

		// html attributes
		$aria_require_attr = $required ? 'true' : 'false';
		$placeholder_attr = $placeholder ? 'placeholder="'. $placeholder .'" ' : '';

		$input_icon = '<i class="fa fa-lock" aria-hidden="true"></i>';


	ob_start(); ?>
		<div class="srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block <?php echo esc_attr( $classname ) ?>" <?php echo wp_kses_post( $style ) ?>>
		<?php echo wp_kses_post(Sureforms_Helper::GenerateCommonFormMarkup('label', $label, $slug, $block_id, $required )); ?>
		<div class="srfm-block-wrap">
			<?php echo wp_kses_post( $input_icon ); ?>
			<input type="password" class="srfm-input-common" name="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>" <?php echo wp_kses_post(  $placeholder_attr ); ?>/>
		</div>

		<?php if( true === $is_confirm_password ) { ?>
			<?php echo wp_kses_post(Sureforms_Helper::GenerateCommonFormMarkup('label', $confirm_label, $slug, $block_id, $required )); ?>
			<div class="srfm-block-wrap">
				<?php echo wp_kses_post( $input_icon ); ?>
				<input type="password" class="srfm-input-common" name="srfm-confirm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>" <?php echo wp_kses_post(  $placeholder_attr ); ?>/>
			</div>
		<?php } ?>

		<?php echo wp_kses_post( Sureforms_Helper::GenerateCommonFormMarkup('help', '', '', '', '', $help ) ); ?>
		<?php echo wp_kses_post(Sureforms_Helper::GenerateCommonFormMarkup('error', '', '', '', $required, '', $error_msg )); ?>
		</div>
	<?php

	return ob_get_clean();
	}
}
