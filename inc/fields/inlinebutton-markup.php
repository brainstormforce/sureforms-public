<?php
/**
 * Sureforms Inline Button Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc\Fields;

use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sureforms Inline Button Markup Class.
 *
 * @since 0.0.2
 */
class Inlinebutton_Markup extends Base {

	/**
	 * Text displayed on the button.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $button_text;

	/**
	 * Button style inherited from the theme.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $btn_from_theme;

	/**
	 * Used as a flag which decides whether page break is added.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $is_page_break;

	/**
	 * Version of reCAPTCHA to use.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $recaptcha_version;

	/**
	 * Site key for Google reCAPTCHA.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $google_captcha_site_key;

	/**
	 * Global setting options for security settings.
	 *
	 * @var array<mixed>|mixed
	 * @since 0.0.2
	 */
	protected $global_setting_options;

	/**
	 * Flag indicating if padding should be added to the button.
	 *
	 * @var bool
	 * @since 0.0.2
	 */
	protected $add_button_padding;

	/**
	 * Security type.
	 *
	 * @var string
	 * @since x.x.x
	 */
	protected $captcha_security_type;

	/**
	 * Cloudflare Turnstile site key.
	 *
	 * @var string
	 * @since x.x.x
	 */
	protected $srfm_cf_turnstile_site_key;

	/**
	 * Cloudflare Turnstile appearance mode
	 *
	 * @var string
	 * @since x.x.x
	 */
	protected $srfm_cf_appearance_mode;

	/**
	 * Security - hCaptcha site key.
	 *
	 * @var string
	 * @since x.x.x
	 */
	protected $srfm_hcaptcha_site_key;

	/**
	 * Initialize the properties based on block attributes.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @since 0.0.2
	 */
	public function __construct( $attributes ) {
		$this->set_properties( $attributes );
		$this->slug                    = 'inline-button';
		$this->button_text             = isset( $attributes['buttonText'] ) ? $attributes['buttonText'] : '';
		$this->btn_from_theme          = Helper::get_meta_value( $this->form_id, '_srfm_inherit_theme_button' );
		$this->is_page_break           = Helper::get_meta_value( $this->form_id, '_srfm_is_page_break' );
		$this->captcha_security_type   = Helper::get_meta_value( $this->form_id, '_srfm_captcha_security_type' );
		$this->recaptcha_version       = Helper::get_meta_value( $this->form_id, '_srfm_form_recaptcha' );
		$this->google_captcha_site_key = '';
		$this->global_setting_options  = [];
		if ( 'none' !== $this->captcha_security_type ) {
			$this->global_setting_options = get_option( 'srfm_security_settings_options' );
		}

		if ( is_array( $this->global_setting_options ) ) {
			switch ( $this->recaptcha_version ) {
				case 'v2-checkbox':
					$this->google_captcha_site_key = isset( $this->global_setting_options['srfm_v2_checkbox_site_key'] ) ? $this->global_setting_options['srfm_v2_checkbox_site_key'] : '';
					break;
				case 'v2-invisible':
					$this->google_captcha_site_key = isset( $this->global_setting_options['srfm_v2_invisible_site_key'] ) ? $this->global_setting_options['srfm_v2_invisible_site_key'] : '';
					break;
				case 'v3-reCAPTCHA':
					$this->google_captcha_site_key = isset( $this->global_setting_options['srfm_v3_site_key'] ) ? $this->global_setting_options['srfm_v3_site_key'] : '';
					break;
				default:
					break;
			}
			if ( 'cf-turnstile' === $this->captcha_security_type ) {
				$this->srfm_cf_turnstile_site_key = isset( $this->global_setting_options['srfm_cf_turnstile_site_key'] ) ? $this->global_setting_options['srfm_cf_turnstile_site_key'] : '';
				$this->srfm_cf_appearance_mode    = isset( $this->global_setting_options['srfm_cf_appearance_mode'] ) ? $this->global_setting_options['srfm_cf_appearance_mode'] : 'auto';
			}
			if ( 'hcaptcha' === $this->captcha_security_type ) {
				$this->srfm_hcaptcha_site_key = isset( $this->global_setting_options['srfm_hcaptcha_site_key'] ) ? $this->global_setting_options['srfm_hcaptcha_site_key'] : '';
			}
		}
		$theme_name               = wp_get_theme()->get( 'Name' );
		$this->add_button_padding = true;
		if ( 'Astra' === $theme_name || 'Blocksy' === $theme_name ) {
			$this->add_button_padding = false;
		}
	}

	/**
	 * Render inline button markup
	 *
	 * @since 0.0.2
	 * @return string|boolean|void
	 */
	public function markup() {
		ob_start(); ?>
		<?php if ( '1' !== $this->is_page_break ) : ?>
			<?php if ( 'v2-checkbox' === $this->recaptcha_version ) : ?>
				<?php echo "<div class='g-recaptcha' data-sitekey='" . esc_attr( strval( $this->google_captcha_site_key ) ) . "'></div>"; ?>
			<?php endif; ?>
			<?php if ( 'cf-turnstile' === $this->captcha_security_type && $this->srfm_cf_turnstile_site_key ) : ?>
				<?php echo "<div id='srfm-cf-sitekey' class='cf-turnstile' data-theme='" . esc_attr( strval( $this->srfm_cf_appearance_mode ) ) . "' data-sitekey='" . esc_attr( strval( $this->srfm_cf_turnstile_site_key ) ) . "'></div>"; ?>
			<?php endif; ?>
			<?php if ( 'hcaptcha' === $this->captcha_security_type && $this->srfm_hcaptcha_site_key ) : ?>
				<?php echo "<div id='srfm-hcaptcha-sitekey' class='h-captcha' data-sitekey='" . esc_attr( strval( $this->srfm_hcaptcha_site_key ) ) . "'></div>"; ?>
			<?php endif; ?>
			<div data-block-id="<?php echo esc_attr( $this->block_id ); ?>" style="padding: 0 .3em; " class="<?php echo esc_attr( $this->class_name ); ?> <?php echo esc_attr( $this->conditional_class ); ?> srf-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?>-block<?php echo esc_attr( $this->block_width ); ?> srfm-block srfm-custom-button-ctn">
			<?php echo wp_kses_post( Helper::generate_common_form_markup( $this->form_id, 'label', '‎', '', '', false ) ); ?>
			<?php
			if ( 'v3-reCAPTCHA' === $this->recaptcha_version ) :
				wp_enqueue_script( 'srfm-google-recaptchaV3', 'https://www.google.com/recaptcha/api.js?render=' . esc_js( $this->google_captcha_site_key ), [], SRFM_VER, true );
			endif;

			if ( 'v2-checkbox' === $this->recaptcha_version ) :
				wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js', [], SRFM_VER, true );
			endif;

			if ( 'v2-invisible' === $this->recaptcha_version ) :
				wp_enqueue_script( 'google-recaptcha-invisible', 'https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit', [ SRFM_SLUG . '-form-submit' ], SRFM_VER, true );
			endif;

			if ( 'cf-turnstile' === $this->captcha_security_type ) :
				// Cloudflare Turnstile script.
				wp_enqueue_script( // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
					SRFM_SLUG . '-cf-turnstile',
					'https://challenges.cloudflare.com/turnstile/v0/api.js',
					[],
					null,
					[
						false,
						'defer' => true,
					]
				);
			endif;
			if ( 'hcaptcha' === $this->captcha_security_type ) :
				wp_enqueue_script( 'hcaptcha', 'https://js.hcaptcha.com/1/api.js', [], null, [ 'strategy' => 'defer' ] ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion 
			endif;
			?>
				<button style="<?php echo $this->add_button_padding ? esc_attr( 'padding: 1em; ' ) : ''; ?>width:100%; font-family: inherit; font-weight: var(--wp--custom--font-weight--medium); line-height: normal;" id="srfm-submit-btn" class="<?php echo esc_attr( 'v2-invisible' === $this->recaptcha_version || 'v3-reCAPTCHA' === $this->recaptcha_version ? 'g-recaptcha ' : '' ); ?>srfm-button srfm-submit-button <?php echo esc_attr( '1' === $this->btn_from_theme ? 'wp-block-button__link' : 'srfm-btn-bg-color' ); ?> srfm-custom-button" <?php echo 'v2-invisible' === $this->recaptcha_version || 'v3-reCAPTCHA' === $this->recaptcha_version ? esc_attr( 'recaptcha-type=' . $this->recaptcha_version . ' data-sitekey=' . $this->google_captcha_site_key ) : ''; ?>>
					<div class="srfm-submit-wrap">
						<?php echo esc_html( $this->button_text ); ?>
						<div class="srfm-loader"></div>
					</div>
				</button>
			</div>
			<?php
		endif;
		return ob_get_clean();
	}

}
