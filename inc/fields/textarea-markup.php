<?php
/**
 * Sureforms Textarea Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;

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
	 * @param int|string   $form_id form id.
	 *
	 * @return string|boolean
	 */
	public function default( $attributes, $form_id ) {
		$block_id    = isset( $attributes['block_id'] ) ? Sureforms_Helper::get_string_value( $attributes['block_id'] ) : '';
		$default     = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
		$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$field_width = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help        = isset( $attributes['textAreaHelpText'] ) ? $attributes['textAreaHelpText'] : '';
		$max_length  = isset( $attributes['maxLength'] ) ? $attributes['maxLength'] : '';
		$rows        = isset( $attributes['rows'] ) ? $attributes['rows'] : '';
		$cols        = isset( $attributes['cols'] ) ? $attributes['cols'] : '';
		$error_msg   = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$classname   = isset( $attributes['className'] ) ? ' ' . $attributes['className'] : '';
		$slug        = 'textarea';

		$block_width = $field_width ? ' srfm-block-width-' . str_replace( '.', '-', $field_width ) : '';

		// html attributes.
		$placeholder_attr   = $placeholder ? ' placeholder="' . $placeholder . '" ' : '';
		$max_length_attr    = $max_length ? ' maxLength="' . $max_length . '" ' : '';
		$default_value_attr = $default ? ' value="' . $default . '" ' : '';
		$cols_attr          = $cols ? ' cols="' . $cols . '" ' : '';
		$rows_attr          = $rows ? ' rows="' . $rows . '" ' : '';

		$aria_require_attr = $required ? 'true' : 'false';

		$max_length_html = '' !== $max_length ? '0/' . $max_length : '';

		$input_label_fallback = $label ? $label : __( 'Textarea', 'sureforms' );
		$input_label          = '-lbl-' . Sureforms_Helper::encrypt( $input_label_fallback );

		ob_start(); ?>
		<div class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block srf-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-block<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $classname ); ?>">
			<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( $form_id, 'label', $label, $slug, $block_id, boolval( $required ) ) ); ?>
			<div class="srfm-block-wrap">
				<?php if ( $max_length_html ) { ?>
					<div class="srfm-text-counter"><?php echo esc_html( $max_length_html ); ?></div>
				<?php } ?>
				<textarea class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>" name="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?><?php echo esc_attr( $input_label ); ?>" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>" <?php echo wp_kses_post( $placeholder_attr . '' . $default_value_attr . '' . $max_length_attr . '' . $cols_attr . '' . $rows_attr ); ?> ></textarea>
			</div>
			<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( $form_id, 'help', '', '', '', false, $help ) ); ?>
			<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( $form_id, 'error', '', '', '', boolval( $required ), '', $error_msg ) ); ?>
		</div>

		<?php
		return ob_get_clean();

	}
}
