<?php
/**
 * Sureforms Input Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;

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
	public function classic_styling( $attributes, $form_id ) {
		$block_id        = isset( $attributes['block_id'] ) ? strval( $attributes['block_id'] ) : '';
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
		$input_label_fallback = $label ? $label : 'Text Field';
		$input_label          = '-lbl-' . Sureforms_Helper::encrypt( $input_label_fallback );

		ob_start(); ?>

			<div class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block srf-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-block<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $class_name ); ?>">
			<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( $form_id, 'label', $label, $slug, $block_id, boolval( $required ) ) ); ?>
				<div class="srfm-block-wrap">
					<input class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>" type="text" name="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?><?php echo esc_attr( $input_label ); ?>" aria-required="<?php echo esc_attr( $aria_require ); ?>" aria-unique="<?php echo esc_attr( $aria_unique ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" maxlength="<?php echo esc_attr( $max_length ); ?>" value="<?php echo esc_attr( $default_value ); ?>" />
					<?php echo Sureforms_Helper::fetch_svg( 'error', 'srfm-error-icon' );  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ignored to render svg ?>
				</div>
				<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( $form_id, 'help', '', '', '', false, $help ) ); ?>
				<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( $form_id, 'error', '', '', '', boolval( $required ), '', $error_msg, false, $duplicate_msg ) ); ?>
			</div>
		<?php
		return ob_get_clean();
	}

}
