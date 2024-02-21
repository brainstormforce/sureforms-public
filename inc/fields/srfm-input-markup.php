<?php
/**
 * Sureforms Input Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc\Fields;

use SRFM\Inc\Traits\Get_Instance;
use SRFM\Inc\SRFM_Helper;

/**
 * Sureforms Input Markup Class.
 *
 * @since 0.0.1
 */
class SRFM_Input_Markup extends SRFM_Base {
	use Get_Instance;

	/**
	 * Render input markup
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param int|string   $form_id form id.
	 *
	 * @return string|boolean
	 */
	public function markup( $attributes, $form_id ) {
		$block_id        = isset( $attributes['block_id'] ) ? SRFM_Helper::get_string_value( $attributes['block_id'] ) : '';
		$default         = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
		$required        = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$is_unique       = isset( $attributes['isUnique'] ) ? $attributes['isUnique'] : false;
		$duplicate_msg   = isset( $attributes['duplicateMsg'] ) ? $attributes['duplicateMsg'] : '';
		$placeholder     = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$label           = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$field_width     = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$help            = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg       = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$max_text_length = isset( $attributes['textLength'] ) ? $attributes['textLength'] : '';
		$class_name      = isset( $attributes['className'] ) ? ' ' . $attributes['className'] : '';
		$slug            = 'input';

		$block_width = $field_width ? ' srfm-block-width-' . str_replace( '.', '-', $field_width ) : '';

		// Attributes.
		$placeholder          = $placeholder ? $placeholder : '';
		$max_length           = $max_text_length ? $max_text_length : '';
		$aria_require         = $required ? 'true' : 'false';
		$aria_unique          = $is_unique ? 'true' : 'false';
		$default_value        = $default ? $default : '';
		$input_label_fallback = $label ? $label : __( 'Text Field', 'sureforms' );
		$input_label          = '-lbl-' . SRFM_Helper::encrypt( $input_label_fallback );

		$unique_slug = 'srfm-' . $slug . '-' . $block_id . $input_label;

		ob_start(); ?>

			<div class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block srf-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-block<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $class_name ); ?>">
			<?php echo wp_kses_post( SRFM_Helper::generate_common_form_markup( $form_id, 'label', $label, $slug, $block_id . $input_label, boolval( $required ) ) ); ?>
				<div class="srfm-block-wrap">
					<input class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>" type="text" name="<?php echo esc_attr( $unique_slug ); ?>" id="<?php echo esc_attr( $unique_slug ); ?>" aria-required="<?php echo esc_attr( $aria_require ); ?>" data-unique="<?php echo esc_attr( $aria_unique ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" maxlength="<?php echo esc_attr( $max_length ); ?>" value="<?php echo esc_attr( $default_value ); ?>" />
					<?php echo SRFM_Helper::fetch_svg( 'error', 'srfm-error-icon' );  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ignored to render svg ?>
				</div>
				<?php echo wp_kses_post( SRFM_Helper::generate_common_form_markup( $form_id, 'help', '', '', '', false, $help ) ); ?>
				<?php echo wp_kses_post( SRFM_Helper::generate_common_form_markup( $form_id, 'error', '', '', '', boolval( $required ), '', $error_msg, false, $duplicate_msg, $is_unique ) ); ?>
			</div>
		<?php
		return ob_get_clean();
	}

}
