<?php
/**
 * Sureforms Url Markup Class file.
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
 * Sureforms Url Field Markup Class.
 *
 * @since 0.0.1
 */
class SRFM_Url_Markup extends SRFM_Base {
	use SRFM_Get_Instance;

	/**
	 * Render the sureforms url classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param int|string   $form_id form id.
	 *
	 * @return string|boolean
	 */
	public function markup( $attributes, $form_id ) {
			$block_id    = isset( $attributes['block_id'] ) ? SRFM_Helper::get_string_value( $attributes['block_id'] ) : '';
			$default     = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
			$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
			$field_width = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
			$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help        = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$error_msg   = isset( $attributes['errorMsg'] ) && $attributes['errorMsg'] ? $attributes['errorMsg'] : SRFM_Helper::get_default_dynamic_block_option( 'srfm_url_block_required_text' );
			$classname   = isset( $attributes['className'] ) ? ' ' . $attributes['className'] : '';
			$slug        = 'url';

			$block_width = $field_width ? ' srfm-block-width-' . str_replace( '.', '-', $field_width ) : '';

			// html attributes.
			$placeholder_attr     = $placeholder ? ' placeholder="' . $placeholder . '" ' : '';
			$default_value_attr   = $default ? ' value="' . $default . '" ' : '';
			$aria_require_attr    = $required ? 'true' : 'false';
			$input_label_fallback = $label ? $label : __( 'Url', 'sureforms' );
			$input_label          = '-lbl-' . SRFM_Helper::encrypt( $input_label_fallback );

			$unique_slug = 'srfm-' . $slug . '-' . $block_id . $input_label;

			ob_start(); ?>
			<div class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $classname ); ?>">
				<?php echo wp_kses_post( SRFM_Helper::generate_common_form_markup( $form_id, 'label', $label, $slug, $block_id . $input_label, boolval( $required ) ) ); ?>
					<div class="srfm-block-wrap">
						<span class="srfm-protocol"><?php esc_html_e( 'https://', 'sureforms' ); ?></span>
						<input class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>" type="text" name="<?php echo esc_attr( $unique_slug ); ?>" id="<?php echo esc_attr( $unique_slug ); ?>" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>" <?php echo wp_kses_post( $default_value_attr . ' ' . $placeholder_attr ); ?> />
						<?php echo SRFM_Helper::fetch_svg( 'error', 'srfm-error-icon' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ignored to render svg ?>
					</div>
				<?php echo wp_kses_post( SRFM_Helper::generate_common_form_markup( $form_id, 'help', '', '', '', false, $help ) ); ?>
				<?php echo wp_kses_post( SRFM_Helper::generate_common_form_markup( $form_id, 'error', '', '', '', boolval( $required ), '', $error_msg, false, '', true ) ); ?>
			</div>
		<?php
		return ob_get_clean();

	}

}

