<?php
/**
 * Sureforms Switch Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;


/**
 * Sureforms Switch Markup Class.
 *
 * @since 0.0.1
 */
class Switch_Markup extends Base {
	use Get_Instance;


	/**
	 * Render the sureforms checkbox classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function classic_styling( $attributes ) {
		$block_id      = isset( $attributes['block_id'] ) ? Sureforms_Helper::get_string_value( $attributes['block_id'] ) : '';
		$form_id       = isset( $attributes['formId'] ) ? Sureforms_Helper::get_integer_value( $attributes['formId'] ) : '';
		$required      = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$field_width   = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$label         = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help          = isset( $attributes['switchHelpText'] ) ? $attributes['switchHelpText'] : '';
		$checked       = isset( $attributes['checked'] ) ? $attributes['checked'] : '';
		$error_msg     = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$classname     = isset( $attributes['className'] ) ? $attributes['className'] : '';
		$color_primary = get_post_meta( Sureforms_Helper::get_integer_value( $form_id ), '_srfm_color1', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $form_id ), '_srfm_color1', true ) ) : '';
		$checked_color = ! empty( $color_primary ) ? $color_primary : '#0084C7';
		$direction     = is_rtl() ? 'right' : 'left';

		$slug        = 'rating';
        $block_width = $field_width ? ' srfm-block-width-' . str_replace(".","-",$field_width) : '';

        $aria_require_attr = $required ? 'true' : 'false';

		ob_start(); ?>
			 <div class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block srfm-block-width-<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $classname ) ?>">
				<?php echo wp_kses_post( Sureforms_Helper::GenerateCommonFormMarkup( 'label', $label, $slug, $block_id, $required ) ); ?>
				<div class="srfm-block-wrap">
				<label class="switch">
					<input type="checkbox">
					<span class="slider-green round"></span>
				</label>
				</div>
				<?php echo wp_kses_post(Sureforms_Helper::GenerateCommonFormMarkup('error', '', '', '', $required, '', $error_msg )); ?>
			</div>

		<?php
		return ob_get_clean();

	}

}