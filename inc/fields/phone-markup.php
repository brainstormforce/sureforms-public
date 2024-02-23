<?php
/**
 * Sureforms Phone Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;

/**
 * Sureforms_Phone_Markup Class.
 *
 * @since 0.0.1
 */
class Phone_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms phone classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param int|string   $form_id form id.
	 *
	 * @return string|boolean
	 */
	public function default( $attributes, $form_id ) {
		$block_id     = isset( $attributes['block_id'] ) ? strval( $attributes['block_id'] ) : '';
		$required     = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$placeholder  = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$field_width  = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$label        = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help         = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg    = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : Sureforms_Helper::get_default_dynamic_block_option( 'srfm_phone_block_required_text' );
		$is_unique    = isset( $attributes['isUnique'] ) ? $attributes['isUnique'] : false;
		$dulicate_msg = isset( $attributes['duplicateMsg'] ) ? $attributes['duplicateMsg'] : '';
		$classname    = isset( $attributes['className'] ) ? ' ' . $attributes['className'] : '';
		$auto_country = isset( $attributes['autoCountry'] ) ? $attributes['autoCountry'] : '';
		$slug         = 'phone';

		$block_width = $field_width ? ' srfm-block-width-' . str_replace( '.', '-', $field_width ) : '';

		$aria_require_attr    = $required ? 'true' : 'false';
		$placeholder_attr     = $placeholder ? 'placeholder="' . $placeholder . '" ' : '';
		$input_label_fallback = $label ? $label : __( 'Phone', 'sureforms' );
		$input_label          = '-lbl-' . Sureforms_Helper::encrypt( $input_label_fallback );

		ob_start(); ?>
		<div class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block srf-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-block<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $classname ); ?>">
				<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( $form_id, 'label', $label, $slug, $block_id, boolval( $required ) ) ); ?>
				<div class="srfm-block-wrap">
					<input type="tel" class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>" name="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?><?php echo esc_attr( $input_label ); ?>" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>" auto-country="<?php echo esc_attr( $auto_country ? 'true' : 'false' ); ?>" value="" <?php echo wp_kses_post( $placeholder_attr ); ?>>
				</div>
				<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( $form_id, 'help', '', '', '', false, $help ) ); ?>
				<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( $form_id, 'error', '', '', '', boolval( $required ), '', $error_msg, false, '', true ) ); ?>
			</div>
		<?php
		return ob_get_clean();
	}
}
