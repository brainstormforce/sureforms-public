<?php
/**
 * Sureforms Phone Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc\Fields;

use SRFM\Inc\Traits\SRFM_Get_Instance;
use SRFM\Inc\SRFM_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sureforms_Phone_Markup Class.
 *
 * @since 0.0.1
 */
class SRFM_Phone_Markup extends SRFM_Base {
	use SRFM_Get_Instance;

	/**
	 * Render the sureforms phone classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function markup( $attributes ) {
		$block_id     = isset( $attributes['block_id'] ) ? SRFM_Helper::get_string_value( $attributes['block_id'] ) : '';
		$form_id      = isset( $attributes['formId'] ) ? SRFM_Helper::get_string_value( $attributes['formId'] ) : '';
		$required     = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$placeholder  = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$field_width  = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$label        = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help         = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg    = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$is_unique    = isset( $attributes['isUnique'] ) ? $attributes['isUnique'] : false;
		$dulicate_msg = isset( $attributes['duplicateMsg'] ) ? $attributes['duplicateMsg'] : '';
		$classname    = isset( $attributes['className'] ) ? ' ' . $attributes['className'] : '';
		$auto_country = isset( $attributes['autoCountry'] ) ? $attributes['autoCountry'] : '';
		$slug         = 'phone';

		$block_width = $field_width ? ' srfm-block-width-' . str_replace( '.', '-', $field_width ) : '';

		$aria_require_attr    = $required ? 'true' : 'false';
		$placeholder_attr     = $placeholder ? 'placeholder="' . $placeholder . '" ' : '';
		$input_label_fallback = $label ? $label : __( 'Phone', 'sureforms' );
		$input_label          = '-lbl-' . SRFM_Helper::encrypt( $input_label_fallback );
		$conditional_class    = apply_filters( 'srfm_conditional_logic_classes', $form_id, $block_id );
		$unique_slug          = 'srfm-' . $slug . '-' . $block_id . $input_label;

		ob_start(); ?>
		<div data-block-id="<?php echo esc_attr( $block_id ); ?>" class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block srf-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-block<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $classname ); ?> <?php echo esc_attr( $conditional_class ); ?>">
				<?php echo wp_kses_post( SRFM_Helper::generate_common_form_markup( $form_id, 'label', $label, $slug, $block_id . $input_label, boolval( $required ) ) ); ?>
				<div class="srfm-block-wrap">
					<input type="tel" class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>" name="<?php echo esc_attr( $unique_slug ); ?>" id="<?php echo esc_attr( $unique_slug ); ?>" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>" auto-country="<?php echo esc_attr( $auto_country ? 'true' : 'false' ); ?>" value="" <?php echo wp_kses_post( $placeholder_attr ); ?>>
				</div>
				<?php echo wp_kses_post( SRFM_Helper::generate_common_form_markup( $form_id, 'help', '', '', '', false, $help ) ); ?>
				<?php echo wp_kses_post( SRFM_Helper::generate_common_form_markup( $form_id, 'error', '', '', '', boolval( $required ), '', $error_msg, false, '', true ) ); ?>
			</div>
		<?php
		return ob_get_clean();
	}
}
