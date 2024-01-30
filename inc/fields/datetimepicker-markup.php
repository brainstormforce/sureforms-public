<?php
/**
 * Sureforms Datetimepicker Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;

/**
 * Sureforms Datetimepicker Markup Class.
 *
 * @since 0.0.1
 */
class Datetimepicker_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms Datetimepicker classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function classic_styling( $attributes, $form_id ) {
			$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$field_width = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
			$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help        = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$field_type  = isset( $attributes['fieldType'] ) ? $attributes['fieldType'] : '';
			$min         = isset( $attributes['min'] ) ? $attributes['min'] : '';
			$max         = isset( $attributes['max'] ) ? $attributes['max'] : '';
			$error_msg   = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$class_name  = isset( $attributes['className'] ) ? ' ' . $attributes['className'] : '';
			$block_id    = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';
			$slug        = 'datepicker';

			$block_width = $field_width ? ' srfm-block-width-' . str_replace( '.', '-', $field_width ) : '';

			// html attributes.
			$aria_require_attr    = $required ? 'true' : 'false';
			$min_attr             = $min ? ' min="' . esc_attr( $min ) . '" ' : '';
			$max_attr             = $max ? ' max="' . esc_attr( $max ) . '" ' : '';
			$input_label_fallback = $label ? $label : 'Date & Time';
			$input_label          = '-lbl-' . Sureforms_Helper::encrypt( $input_label_fallback );
			$input_type           = '';
			$input_icon           = 'time' === $field_type ? '<i class="fa-solid fa-clock></i>' : '<i class="fa-regular fa-calendar"></i>';

			$input_type_class = '';
		if ( 'dateTime' === $field_type ) {
			$input_type = 'date-time';
		}

		if ( 'date' === $field_type ) {
			$input_type = 'date';
		}

		if ( 'time' === $field_type ) {
			$input_type = 'time';
		}

		ob_start(); ?>
			<div class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $class_name ); ?>">
				<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( $form_id, 'label', $label, $slug, $block_id, boolval( $required ) ) ); ?>
				<div class="srfm-block-wrap srfm-with-icon">
					<?php echo Sureforms_Helper::fetch_svg( 'time' === $field_type ? 'clock' : 'calender', 'srfm-' . esc_attr( $slug ) . '-icon srfm-input-icon' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ignored to render svg ?>
					<input type="text" id="srfm-input-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>" class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?> srfm-input-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $input_type ); ?>" name="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?><?php echo esc_attr( $input_label ); ?>" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>" value="" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>">
					<?php echo Sureforms_Helper::fetch_svg( 'error', 'srfm-error-icon' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ignored to render svg ?>
				</div>
				<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( 'help', '', '', '', false, $help ) ); ?>
				<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( 'error', '', '', '', boolval( $required ), '', $error_msg ) ); ?>
			</div>
		<?php

		return ob_get_clean();

	}

}
