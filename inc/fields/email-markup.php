<?php
/**
 * Sureforms Email Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;

/**
 * SureForms Email Markup Class.
 *
 * @since 0.0.1
 */
class Email_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms email classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param int|string   $form_id form id.
	 * @return string|boolean
	 */
	public function default( $attributes, $form_id ) {
		$required         = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$default          = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
		$placeholder      = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$field_width      = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$label            = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help             = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$is_unique        = isset( $attributes['isUnique'] ) ? $attributes['isUnique'] : false;
		$duplicate_msg   = isset( $attributes['duplicateMsg'] ) && $attributes['duplicateMsg'] ? $attributes['duplicateMsg'] : Sureforms_Helper::get_default_dynamic_block_option( 'srfm_email_block_unique_text' );
		$error_msg   = isset( $attributes['errorMsg'] ) && $attributes['errorMsg'] ? $attributes['errorMsg'] : Sureforms_Helper::get_default_dynamic_block_option( 'srfm_email_block_required_text' );

		$is_confirm_email = isset( $attributes['isConfirmEmail'] ) ? $attributes['isConfirmEmail'] : false;
		$class_name       = isset( $attributes['className'] ) ? ' ' . $attributes['className'] : '';
		$block_id         = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';
		$slug             = 'email';

		$block_width          = $field_width ? ' srfm-block-width-' . str_replace( '.', '-', $field_width ) : '';
		$aria_require         = $required ? 'true' : 'false';
		$aria_unique          = $is_unique ? 'true' : 'false';
		$default_value_attr   = $default ? ' value="' . $default . '" ' : '';
		$placeholder_attr     = $placeholder ? ' placeholder="' . $placeholder . '" ' : '';
		$input_label_fallback = $label ? $label : __( 'Email', 'sureforms' );
		$input_label          = '-lbl-' . Sureforms_Helper::encrypt( $input_label_fallback );

		$input_confirm_label_fallback = 'Confirm ' . $input_label_fallback;
		$input_confirm_label          = '-lbl-' . Sureforms_Helper::encrypt( $input_confirm_label_fallback );

		ob_start(); ?>
			<div class="srfm-block-single srfm-<?php echo esc_attr( $slug ); ?>-block-wrap<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $class_name ); ?>">
				<div class="srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block srf-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-block">
					<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( $form_id, 'label', $label, $slug, $block_id, boolval( $required ) ) ); ?>
					<div class="srfm-block-wrap">
						<input class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>" type="email" name="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?><?php echo esc_attr( $input_label ); ?>" aria-required="<?php echo esc_attr( $aria_require ); ?>" aria-unique="<?php echo esc_attr( $aria_unique ); ?>" <?php echo wp_kses_post( $default_value_attr . ' ' . $placeholder_attr ); ?> >
						<?php echo Sureforms_Helper::fetch_svg( 'error', 'srfm-error-icon' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ignored to render svg ?>
					</div>
					<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( $form_id, 'help', '', '', '', false, $help ) ); ?>
					<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( $form_id, 'error', '', '', '', boolval( $required ), '', $error_msg, false, $duplicate_msg ) ); ?>
				</div>
				<?php if ( true === $is_confirm_email ) { ?>
					<div class="srfm-block srfm-<?php echo esc_attr( $slug ); ?>-confirm-block srf-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-confirm-block">
						<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( $form_id, 'label', 'Confirm ' . $label, $slug . '-confirm', $block_id, boolval( $required ) ) ); ?>
						<div class="srfm-block-wrap">
							<input class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>-confirm" type="email" name="srfm-<?php echo esc_attr( $slug ); ?>-confirm-<?php echo esc_attr( $block_id ); ?>" name="srfm-<?php echo esc_attr( $slug ); ?>-confirm-<?php echo esc_attr( $block_id ); ?><?php echo esc_attr( $input_confirm_label ); ?>" aria-required="<?php echo esc_attr( $aria_require ); ?>" <?php echo wp_kses_post( $default_value_attr . ' ' . $placeholder_attr ); ?> >
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
