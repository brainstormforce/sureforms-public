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
	public function classic_styling( $attributes ) {
			$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$field_width = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
			$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help        = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$field_type  = isset( $attributes['fieldType'] ) ? $attributes['fieldType'] : '';
			$min         = isset( $attributes['min'] ) ? $attributes['min'] : '';
			$max         = isset( $attributes['max'] ) ? $attributes['max'] : '';
			$error_msg   = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$classname   = isset( $attributes['className'] ) ? $attributes['className'] : '';
			$block_id    = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';

			$slug = 'datepicker';

			$inline_style = '';

			// Append Dynamic styles here.
			$inline_style .= $field_width ? 'width:' . $field_width . '%;' : '';
			$style =  $inline_style ? 'style="'. $inline_style .'"' : '';
	
			// html attributes
			$aria_require_attr = $required ? 'true' : 'false';

			$input_icon = 'time' === $field_type ? '<i class="fa-solid fa-clock></i>' : '<i class="fa-regular fa-calendar"></i>';

			$input_type_class = '';
			if( 'dateTime' === $field_type ) {
				$input_type = ' srfm-input-date-time';
			}

			if( 'date' === $field_type ) {
				$input_type = ' srfm-input-date';
			}

			if( 'time' === $field_type ) {
				$input_type = ' srfm-input-time';
			}

		ob_start(); ?>
			<div class="srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block <?php echo esc_attr( $classname ) ?>" <?php echo wp_kses_post( $style ) ?>>
			<?php echo wp_kses_post(Sureforms_Helper::GenerateCommonFormMarkup('label', $label, $slug, $block_id, $required )); ?>
				<input type="hidden" class="srfm-min-max-holder" min="<?php esc_attr($min); ?>" max="<?php esc_attr($max); ?>" >
				<input type="hidden" field-type="<?php echo esc_attr( $field_type ); ?>" class="srfm-classic-date-time-result" name="srfm-hidden<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>" value="">
				<div class="srfm-block-wrap">
					<?php echo wp_kses_post( $input_icon ); ?>
					<input type="text" class="srfm-input-common" name="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>" >
				</div>

				<?php echo wp_kses_post( Sureforms_Helper::GenerateCommonFormMarkup('help', '', '', '', '', $help ) ); ?>
				<?php echo wp_kses_post(Sureforms_Helper::GenerateCommonFormMarkup('error', '', '', '', $required, '', $error_msg )); ?>
			</div>
		<?php

		return ob_get_clean();

	}

}
