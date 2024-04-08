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
		$btn_from_theme    = Helper::get_meta_value( $id, '_srfm_inherit_theme_button' );
		$is_page_break     = Helper::get_meta_value( $id, '_srfm_is_page_break' );

		$width  = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';

		$recaptcha_version  = Helper::get_meta_value( $id, '_srfm_form_recaptcha' );

		$google_captcha_site_key = '';

		if ( 'none' !== $recaptcha_version ) {
			$global_setting_options = get_option( 'srfm_security_settings_options' );
		} else {
			$global_setting_options = [];
		}

		if ( is_array( $global_setting_options ) ) {
			switch ( $recaptcha_version ) {
				case 'v2-checkbox':
					$google_captcha_site_key = isset( $global_setting_options['srfm_v2_checkbox_site_key'] ) ? $global_setting_options['srfm_v2_checkbox_site_key'] : '';
					break;
				case 'v2-invisible':
					$google_captcha_site_key = isset( $global_setting_options['srfm_v2_invisible_site_key'] ) ? $global_setting_options['srfm_v2_invisible_site_key'] : '';
					break;
				case 'v3-reCAPTCHA':
					$google_captcha_site_key = isset( $global_setting_options['srfm_v3_site_key'] ) ? $global_setting_options['srfm_v3_site_key'] : '';
					break;
				default:
					break;
			}
		}

		$btn_height = "40px";

		if ( wp_is_block_theme() ){
			$btn_height = "45px";
		}

		if ( ! $is_page_break ):
			ob_start(); ?>
			<div style="width: <?php echo esc_attr( $width ); ?>%; margin-top: 2px;">
			<?php echo wp_kses_post( Helper::generate_common_form_markup( $id, 'label', '‎', '', '', false ) ); ?>
			<?php
				if ( '' !== $google_captcha_site_key ):
					if( 'v3-reCAPTCHA' === $recaptcha_version ):
						wp_enqueue_script( 'srfm-google-recaptchaV3', 'https://www.google.com/recaptcha/api.js?render=' . esc_js( $google_captcha_site_key ), [], SRFM_VER, true );
					endif;
				?>
					<button style="width:100%; height: <?php echo esc_attr( $btn_height ); ?>; font-family: inherit; font-weight: var(--wp--custom--font-weight--medium); line-height: normal; padding: calc(.667em + 2px) calc(1.333em + 2px);" id="srfm-submit-btn" class="<?php echo esc_attr( 'v3-reCAPTCHA' === $recaptcha_version ? 'g-recaptcha ' : '' ); ?>srfm-block-width-25 srfm-button srfm-submit-button <?php echo esc_attr( '1' === $btn_from_theme ? 'wp-block-button__link' : 'srfm-btn-bg-color' ); ?>" <?php echo 'v2-invisible' === $recaptcha_version || 'v3-reCAPTCHA' === $recaptcha_version ? esc_attr( 'recaptcha-type=' . $recaptcha_version . ' data-sitekey=' . $google_captcha_site_key ) : ''; ?>>
						<div class="srfm-submit-wrap">
							<?php echo esc_html( $button_text ); ?>
							<div class="srfm-loader"></div>
						</div>
					</button>
				<?php endif; ?>
				<?php if ( 'none' === $recaptcha_version || '' === $recaptcha_version ) : ?>
					<button style="width:100%; height: <?php echo esc_attr( $btn_height ); ?>; font-family: inherit; font-weight: var(--wp--custom--font-weight--medium); line-height: normal; padding: calc(.667em + 2px) calc(1.333em + 2px);" id="srfm-submit-btn" class="srfm-button srfm-submit-button <?php echo esc_attr( '1' === $btn_from_theme ? 'wp-block-button__link' : 'srfm-btn-bg-color' ); ?>">
						<div class="srfm-submit-wrap">
							<?php echo esc_html( $button_text ); ?>
							<div class="srfm-loader"></div>
						</div>
					</button>
				<?php endif; ?>
				</div>
			<?php
			return ob_get_clean();
		endif;
	}

}
