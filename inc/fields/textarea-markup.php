<?php
/**
 * Sureforms Textarea Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc\Fields;

use SRFM\Inc\Traits\Get_Instance;
use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sureforms Textarea Markup Class.
 *
 * @since 0.0.1
 */
class Textarea_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms textarea classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function markup( $attributes ) {
		$block_id    = isset( $attributes['block_id'] ) ? Helper::get_string_value( $attributes['block_id'] ) : '';
		$form_id     = isset( $attributes['formId'] ) ? Helper::get_string_value( $attributes['formId'] ) : '';
		$default     = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
		$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$field_width = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help        = isset( $attributes['textAreaHelpText'] ) ? $attributes['textAreaHelpText'] : '';
		$max_length  = isset( $attributes['maxLength'] ) ? $attributes['maxLength'] : '';
		$rows        = isset( $attributes['rows'] ) ? $attributes['rows'] : '';
		$cols        = isset( $attributes['cols'] ) ? $attributes['cols'] : '';
		$error_msg   = isset( $attributes['errorMsg'] ) && $attributes['errorMsg'] ? $attributes['errorMsg'] : Helper::get_default_dynamic_block_option( 'srfm_textarea_block_required_text' );
		$classname   = isset( $attributes['className'] ) ? ' ' . $attributes['className'] : '';
		$block_slug  = isset( $attributes['slug'] ) ? $attributes['slug'] : '';
		$slug        = 'textarea';

		$block_width = $field_width ? ' srfm-block-width-' . str_replace( '.', '-', $field_width ) : '';

		// html attributes.
		$placeholder_attr = $placeholder ? ' placeholder="' . $placeholder . '" ' : '';
		$max_length_attr  = $max_length ? ' maxLength="' . $max_length . '" ' : '';
		$cols_attr        = $cols ? ' cols="' . $cols . '" ' : '';
		$rows_attr        = $rows ? ' rows="' . $rows . '" ' : '';

		$aria_require_attr = $required ? 'true' : 'false';

		$max_length_html = '' !== $max_length ? '0/' . $max_length : '';

		$input_label_fallback = $label ? $label : __( 'Textarea', 'sureforms' );
		$input_label          = '-lbl-' . Helper::encrypt( $input_label_fallback );

		$unique_slug       = 'srfm-' . $slug . '-' . $block_id . $input_label;
		$field_name        = $unique_slug . '-' . $block_slug;
		$conditional_class = apply_filters( 'srfm_conditional_logic_classes', $form_id, $block_id );

		ob_start(); ?>
		<div data-block-id="<?php echo esc_attr( $block_id ); ?>" class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block srf-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-block<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $classname ); ?> <?php echo esc_attr( $conditional_class ); ?>">
			<?php echo wp_kses_post( Helper::generate_common_form_markup( $form_id, 'label', $label, $slug, $block_id . $input_label, boolval( $required ) ) ); ?>
			<div class="srfm-block-wrap">
				<?php if ( $max_length_html ) { ?>
					<div class="srfm-text-counter"><?php echo esc_html( $max_length_html ); ?></div>
				<?php } ?>
				<textarea class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>" name="<?php echo esc_attr( $field_name ); ?>" id="<?php echo esc_attr( $unique_slug ); ?>" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>" <?php echo wp_kses_post( $placeholder_attr . '' . $max_length_attr . '' . $cols_attr . '' . $rows_attr ); ?> ><?php echo esc_html( $default ); ?></textarea>
			</div>
			<?php echo wp_kses_post( Helper::generate_common_form_markup( $form_id, 'help', '', '', '', false, $help ) ); ?>
			<?php echo wp_kses_post( Helper::generate_common_form_markup( $form_id, 'error', '', '', '', boolval( $required ), '', $error_msg ) ); ?>
		</div>

		<?php
		return ob_get_clean();

	}
}
