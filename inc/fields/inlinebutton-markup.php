<?php
/**
 * Sureforms Input Markup Class file.
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
 * Sureforms Input Markup Class.
 *
 * @since 0.0.1
 */
class Inlinebutton_Markup extends Base {
	use Get_Instance;

	/**
	 * Render input markup
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function markup( $attributes, $content = '' ) {

		$id                = isset( $attributes['formId'] ) ? $attributes['formId'] : '';
		$button_text       = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$color_primary     = isset( $attributes['colorPrimary'] ) ? $attributes['colorPrimary'] : '';
		$btn_from_theme    = Helper::get_meta_value( $id, '_srfm_inherit_theme_button' );
		$btn_text_color    = Helper::get_meta_value( $id, '_srfm_button_text_color', true, '#000000' );
		$btn_bg_type       = Helper::get_meta_value( $id, '_srfm_btn_bg_type' );
		$is_page_break     = Helper::get_meta_value( $id, '_srfm_is_page_break' );
		$full              = Helper::get_meta_value( $id, '_srfm_full_width' );
		$button_alignment  = Helper::get_meta_value( $id, '_srfm_button_alignment' );

		$width  = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';

		ob_start(); ?>
			<div class="srfm-block-single srfm-block srfm-block-width-<?php echo esc_attr( $width ); ?>">
				<?php echo wp_kses_post( Helper::generate_common_form_markup( $id, 'label', '‎', '', '', false ) ); ?>
				<div class="srfm-block-wrap">
					<button style="width:100%; font-family: inherit; font-weight: var(--wp--custom--font-weight--medium); line-height: normal; padding: calc(.667em + 2px) calc(1.333em + 2px);" id="srfm-submit-btn" class="srfm-block-width-25 srfm-button srfm-submit-button <?php echo esc_attr( '1' === $btn_from_theme ? 'wp-block-button__link' : 'srfm-btn-bg-color' ); ?>">
						<div class="srfm-submit-wrap">
							<?php echo esc_html( $button_text ); ?>
							<div class="srfm-loader"></div>
						</div>
					</button>
				</div>
			</div>
		<?php
		return ob_get_clean();
	}

}
