<?php
/**
 * Sureforms Number Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;

/**
 * Sureforms Number Field Markup Class.
 *
 * @since 0.0.1
 */
class Number_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms number classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function classic_styling( $attributes ) {
			$block_id    = isset( $attributes['block_id'] ) ? strval( $attributes['block_id'] ) : '';
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
			$input_label_fallback = $label ? $label : 'Number';
			$input_label          = '-lbl-' . Sureforms_Helper::encrypt( $input_label_fallback );

			$type = 'none' === $format_type ? 'number' : 'text';

		ob_start(); ?>
			<div class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $classname ); ?>">
				<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( 'label', $label, $slug, $block_id, $required ) ); ?>
				<div class="srfm-block-wrap">
					<input class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>" type="<?php echo esc_attr( $type ); ?>" name="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?><?php echo esc_attr( $input_label ); ?>" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>"  <?php echo wp_kses_post( $placeholder_attr . '' . $default_value_attr . '' . $format_attr . '' . $min_value_attr . '' . $max_value_attr ); ?> /> 
					<?php echo Sureforms_Helper::fetch_svg( 'error', 'srfm-error-icon' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ignored to render svg ?>
				</div>
				<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( 'help', '', '', '', '', $help ) ); ?>
				<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( 'error', '', '', '', $required, '', $error_msg ) ); ?>
			</div>
		<?php
		return ob_get_clean();
	}

}
