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
	 * @param int|string   $form_id form id.
	 *
	 * @return string|boolean
	 */
	public function classic_styling( $attributes, $form_id ) {
		$required            = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$placeholder         = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$field_width         = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$label               = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help                = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg           = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$is_confirm_password = isset( $attributes['isConfirmPassword'] ) ? $attributes['isConfirmPassword'] : false;
		$confirm_label       = isset( $attributes['confirmLabel'] ) ? $attributes['confirmLabel'] : '';
		$class_name          = isset( $attributes['className'] ) ? ' ' . $attributes['className'] : '';
		$block_id            = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';
		$slug                = 'password';

		$block_width = $field_width ? ' srfm-block-width-' . str_replace( '.', '-', $field_width ) : '';

		// html attributes.
		$aria_require_attr    = $required ? 'true' : 'false';
		$placeholder_attr     = $placeholder ? ' placeholder="' . $placeholder . '" ' : '';
		$input_label_fallback = $label ? $label : 'Password';
		$input_label          = '-lbl-' . Sureforms_Helper::encrypt( $input_label_fallback );

		$input_confirm_label_fallback = 'Confirm ' . $input_label_fallback;
		$input_confirm_label          = '-lbl-' . Sureforms_Helper::encrypt( $input_confirm_label_fallback );

		ob_start(); ?>
		<div class="srfm-block-single srfm-<?php echo esc_attr( $slug ); ?>-block-wrap<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $class_name ); ?>">
		<div class="srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-block">
			<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( $form_id, 'label', $label, $slug, $block_id, boolval( $required ) ) ); ?>
			<div class="srfm-block-wrap">
				<input class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>" type="password" name="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?><?php echo esc_attr( $input_label ); ?>" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>" <?php echo wp_kses_post( $placeholder_attr ); ?>/>
				<?php echo Sureforms_Helper::fetch_svg( 'error', 'srfm-error-icon' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ignored to render svg ?>
			</div>
			<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( $form_id, 'help', '', '', '', false, $help ) ); ?>
			<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( $form_id, 'error', '', '', '', boolval( $required ), '', $error_msg, false, '', true ) ); ?>
		</div>
		<?php if ( true === $is_confirm_password ) { ?>
			<div class="srfm-block srfm-<?php echo esc_attr( $slug ); ?>-confirm-block srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-confirm-block">
				<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( $form_id, 'label', 'Confirm ' . $label, $slug . '-confirm', $block_id, boolval( $required ) ) ); ?>
				<div class="srfm-block-wrap">
					<input class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>-confirm" type="password" name="srfm-<?php echo esc_attr( $slug ); ?>-confirm-<?php echo esc_attr( $block_id ); ?><?php echo esc_attr( $input_confirm_label ); ?>" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>" <?php echo wp_kses_post( $placeholder_attr ); ?>/>
					<?php echo Sureforms_Helper::fetch_svg( 'error', 'srfm-error-icon' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ignored to render svg ?>
				</div>
				<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( $form_id, 'help', '', '', '', false, $help ) ); ?>
				<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( $form_id, 'error', '', '', '', boolval( $required ), '', $error_msg, false, '', true ) ); ?>
			</div>
		<?php } ?>
		</div>
		<?php

		return ob_get_clean();
	}
}
