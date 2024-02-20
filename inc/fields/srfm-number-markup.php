<?php
/**
 * Sureforms Number Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc\Fields;

use SRFM\Inc\Traits\Get_Instance;
use SRFM\Inc\SRFM_Helper;

/**
 * Sureforms Number Field Markup Class.
 *
 * @since 0.0.1
 */
class SRFM_Number_Markup extends SRFM_Base {
	use Get_Instance;

	/**
	 * Render the sureforms number classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param int|string   $form_id form id.
	 *
	 * @return string|boolean
	 */
	public function default( $attributes, $form_id ) {
			$block_id    = isset( $attributes['block_id'] ) ? SRFM_Helper::get_string_value( $attributes['block_id'] ) : '';
			$default     = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
			$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$min_value   = isset( $attributes['minValue'] ) ? $attributes['minValue'] : '';
			$max_value   = isset( $attributes['maxValue'] ) ? $attributes['maxValue'] : '';
			$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
			$field_width = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
			$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help        = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$error_msg   = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$format_type = isset( $attributes['formatType'] ) ? $attributes['formatType'] : '';
			$classname   = isset( $attributes['className'] ) ? ' ' . $attributes['className'] : '';
			$slug        = 'number';

			$block_width = $field_width ? ' srfm-block-width-' . str_replace( '.', '-', $field_width ) : '';

			// html attributes.
			$placeholder_attr     = $placeholder ? ' placeholder="' . $placeholder . '" ' : '';
			$aria_require_attr    = $required ? 'true' : 'false';
			$default_value_attr   = $default ? ' value="' . $default . '" ' : '';
			$format_attr          = $format_type ? ' format-type="' . $format_type . '" ' : '';
			$min_value_attr       = $min_value ? ' min="' . $min_value . '" ' : '';
			$max_value_attr       = $max_value ? ' max="' . $max_value . '" ' : '';
			$input_label_fallback = $label ? $label : __( 'Number', 'sureforms' );
			$input_label          = '-lbl-' . SRFM_Helper::encrypt( $input_label_fallback );

			$unique_slug = 'srfm-' . $slug . '-' . $block_id . $input_label;

		ob_start(); ?>
			<div class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $classname ); ?>">
				<?php echo wp_kses_post( SRFM_Helper::generate_common_form_markup( $form_id, 'label', $label, $slug, $block_id . $input_label, boolval( $required ) ) ); ?>
				<div class="srfm-block-wrap">
					<input class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>" type="number" name="<?php echo esc_attr( $unique_slug ); ?>" id="<?php echo esc_attr( $unique_slug ); ?>" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>" pattern="[0-9]*" inputmode="numeric"  <?php echo wp_kses_post( $placeholder_attr . '' . $default_value_attr . '' . $format_attr . '' . $min_value_attr . '' . $max_value_attr ); ?> /> 
					<?php echo SRFM_Helper::fetch_svg( 'error', 'srfm-error-icon' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ignored to render svg ?>
				</div>
				<?php echo wp_kses_post( SRFM_Helper::generate_common_form_markup( $form_id, 'help', '', '', '', false, $help ) ); ?>
				<?php echo wp_kses_post( SRFM_Helper::generate_common_form_markup( $form_id, 'error', '', '', '', boolval( $required ), '', $error_msg ) ); ?>
			</div>
		<?php
		return ob_get_clean();
	}

}
