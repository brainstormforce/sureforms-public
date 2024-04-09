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
	 * @return string|boolean|void
	 */
	public function markup( $attributes ) {

		$block_id          = isset( $attributes['block_id'] ) ? Helper::get_string_value( $attributes['block_id'] ) : '';
		$form_id           = isset( $attributes['formId'] ) ? Helper::get_string_value( $attributes['formId'] ) : '';
		$button_text       = isset( $attributes['buttonText'] ) ? $attributes['buttonText'] : '';
		$btn_from_theme    = Helper::get_meta_value( $form_id, '_srfm_inherit_theme_button' );
		$is_page_break     = Helper::get_meta_value( $form_id, '_srfm_is_page_break' );
		$conditional_class = apply_filters( 'srfm_conditional_logic_classes', $form_id, $block_id );
		$field_width       = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$block_width       = $field_width ? ' srfm-block-width-' . str_replace( '.', '-', $field_width ) : '';
		$slug              = 'inline-button';
		$class_name        = isset( $attributes['className'] ) ? ' ' . $attributes['className'] : '';

		$recaptcha_version = Helper::get_meta_value( $form_id, '_srfm_form_recaptcha' );

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

		$theme_name = wp_get_theme()->get( 'Name' );

		$add_button_padding = true;

		if ( 'Astra' === $theme_name || 'Blocksy' === $theme_name ) {
			$add_button_padding = false;
		}

			ob_start(); ?>
			<?php if ( '1' !== $is_page_break ) : ?>
				<div data-block-id="<?php echo esc_attr( $block_id ); ?>" style="padding: 0 .3em; " class="<?php echo esc_attr( $class_name ); ?> <?php echo esc_attr( $conditional_class ); ?> srf-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-block<?php echo esc_attr( $block_width ); ?> srfm-block srfm-inline-button">
				<?php if ( 'v2-checkbox' === $recaptcha_version ) : ?>
					<?php echo "<div class='g-recaptcha' data-sitekey='" . esc_attr( strval( $google_captcha_site_key ) ) . "'></div>"; ?>
				<?php endif; ?>
				<?php echo wp_kses_post( Helper::generate_common_form_markup( $form_id, 'label', '‎', '', '', false ) ); ?>
				<?php
				if ( '' !== $google_captcha_site_key ) :
					if ( 'v3-reCAPTCHA' === $recaptcha_version ) :
						wp_enqueue_script( 'srfm-google-recaptchaV3', 'https://www.google.com/recaptcha/api.js?render=' . esc_js( $google_captcha_site_key ), [], SRFM_VER, true );
						endif;
					?>
						<button style="<?php echo $add_button_padding ? esc_attr( 'padding: 1em; ' ) : ''; ?>width:100%; font-family: inherit; font-weight: var(--wp--custom--font-weight--medium); line-height: normal; padding: 1em;" id="srfm-submit-btn" class="<?php echo esc_attr( 'v3-reCAPTCHA' === $recaptcha_version ? 'g-recaptcha ' : '' ); ?>srfm-block-width-25 srfm-button srfm-submit-button <?php echo esc_attr( '1' === $btn_from_theme ? 'wp-block-button__link' : 'srfm-btn-bg-color' ); ?>" <?php echo 'v2-invisible' === $recaptcha_version || 'v3-reCAPTCHA' === $recaptcha_version ? esc_attr( 'recaptcha-type=' . $recaptcha_version . ' data-sitekey=' . $google_captcha_site_key ) : ''; ?>>
							<div class="srfm-submit-wrap">
							<?php echo esc_html( $button_text ); ?>
								<div class="srfm-loader"></div>
							</div>
						</button>
					<?php endif; ?>
					<?php if ( 'none' === $recaptcha_version || '' === $recaptcha_version ) : ?>
						<button style="<?php echo $add_button_padding ? esc_attr( 'padding: 1em; ' ) : ''; ?>width:100%; font-family: inherit; font-weight: var(--wp--custom--font-weight--medium); line-height: normal; " id="srfm-submit-btn" class="srfm-button srfm-submit-button <?php echo esc_attr( '1' === $btn_from_theme ? 'wp-block-button__link' : 'srfm-btn-bg-color' ); ?>">
							<div class="srfm-submit-wrap">
								<?php echo esc_html( $button_text ); ?>
								<div class="srfm-loader"></div>
							</div>
						</button>
					<?php endif; ?>
				</div>
				<?php
		endif;

			return ob_get_clean();
	}

}
