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
	 * @var bool
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
	 * @since 0.0.5
	 */
	protected $captcha_security_type;

	/**
	 * Cloudflare Turnstile site key.
	 *
	 * @var string
	 * @since 0.0.5
	 */
	protected $cf_turnstile_site_key;

	/**
	 * Cloudflare Turnstile appearance mode
	 *
	 * @var string
	 * @since 0.0.5
	 */
	protected $cf_appearance_mode;

	/**
	 * Security - hCaptcha site key.
	 *
	 * @var string
	 * @since 0.0.5
	 */
	protected $hcaptcha_site_key;

	/**
	 * Initialize the properties based on block attributes.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @since 0.0.2
	 */
	public function __construct( $attributes ) {
		$this->set_properties( $attributes );
		$this->slug                    = 'inline-button';
		$this->button_text             = $attributes['buttonText'] ?? '';
		$this->btn_from_theme          = Helper::get_meta_value( $this->form_id, '_srfm_inherit_theme_button' );
		$page_break_settings           = defined( 'SRFM_PRO_VER' ) ? get_post_meta( (int) $this->form_id, '_srfm_page_break_settings', true ) : [];
		$page_break_settings           = is_array( $page_break_settings ) ? $page_break_settings : [];
		$this->is_page_break           = $page_break_settings['is_page_break'] ?? false;
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
					$this->google_captcha_site_key = $this->global_setting_options['srfm_v2_checkbox_site_key'] ?? '';
					break;
				case 'v2-invisible':
					$this->google_captcha_site_key = $this->global_setting_options['srfm_v2_invisible_site_key'] ?? '';
					break;
				case 'v3-reCAPTCHA':
					$this->google_captcha_site_key = $this->global_setting_options['srfm_v3_site_key'] ?? '';
					break;
				default:
					break;
			}
			if ( 'cf-turnstile' === $this->captcha_security_type ) {
				$this->cf_turnstile_site_key = $this->global_setting_options['srfm_cf_turnstile_site_key'] ?? '';
				$this->cf_appearance_mode    = $this->global_setting_options['srfm_cf_appearance_mode'] ?? 'auto';
			}
			if ( 'hcaptcha' === $this->captcha_security_type ) {
				$this->hcaptcha_site_key = $this->global_setting_options['srfm_hcaptcha_site_key'] ?? '';
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
	 * @return string|bool|void
	 */
	public function markup() {
		$container_hidden_class = 'g-recaptcha' === $this->captcha_security_type && ( 'v3-reCAPTCHA' === $this->recaptcha_version || 'v2-invisible' === $this->recaptcha_version ) ? 'srfm-display-none' : '';
		ob_start(); ?>
			<?php if ( ! $this->is_page_break ) { ?>
				<?php if ( $this->captcha_security_type && 'none' !== $this->captcha_security_type ) { ?>
			<div class="srfm-captcha-container <?php echo esc_attr( $container_hidden_class ); ?>">
					<?php if ( 'g-recaptcha' === $this->captcha_security_type && 'v2-checkbox' === $this->recaptcha_version ) { ?>
						<?php echo "<div class='g-recaptcha' data-callback='onSuccess' data-error-callback='onGCaptchaV2CheckBoxError' recaptcha-type='" . esc_attr( $this->recaptcha_version ) . "' data-sitekey='" . esc_attr( strval( $this->google_captcha_site_key ) ) . "'></div>"; ?>
					<?php } ?>
					<?php if ( 'cf-turnstile' === $this->captcha_security_type && $this->cf_turnstile_site_key ) { ?>
						<?php echo "<div id='srfm-cf-sitekey' class='cf-turnstile' data-callback='onSuccess' data-error-callback='onTurnstileError' data-theme='" . esc_attr( strval( $this->cf_appearance_mode ) ) . "' data-sitekey='" . esc_attr( strval( $this->cf_turnstile_site_key ) ) . "'></div>"; ?>
					<?php } ?>
					<?php if ( 'hcaptcha' === $this->captcha_security_type && $this->hcaptcha_site_key ) { ?>
						<?php echo "<div id='srfm-hcaptcha-sitekey' data-callback='onSuccess' data-error-callback='onHCaptchaError' class='h-captcha' data-sitekey='" . esc_attr( strval( $this->hcaptcha_site_key ) ) . "'></div>"; ?>
					<?php } ?>
			<div class="srfm-validation-error" id="captcha-error" style="display: none;"><?php echo esc_attr__( 'Please verify that you are not a robot.', 'sureforms' ); ?></div>
			</div>
				<?php } ?>
			<div data-block-id="<?php echo esc_attr( $this->block_id ); ?>" class="<?php echo esc_attr( $this->class_name ); ?> <?php echo esc_attr( $this->conditional_class ); ?> srf-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?>-block<?php echo esc_attr( $this->block_width ); ?> srfm-block srfm-custom-button-ctn <?php echo esc_attr( '1' === $this->btn_from_theme ? 'wp-block-button' : '' ); ?>">
				<?php
				if ( 'g-recaptcha' === $this->captcha_security_type ) {

					if ( empty( $this->google_captcha_site_key ) ) {
						Helper::render_missing_sitekey_error( 'Google reCAPTCHA' );
					} else {
						if ( 'v3-reCAPTCHA' === $this->recaptcha_version ) {
							wp_enqueue_script( 'srfm-google-recaptchaV3', 'https://www.google.com/recaptcha/api.js?render=' . $this->google_captcha_site_key, [], null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- This is a third-party script, and specifying a version may lead to caching issues. Using null ensures the latest version is always loaded.
						}

						if ( 'v2-checkbox' === $this->recaptcha_version ) {
							wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js', [], SRFM_VER, true );
						}

						if ( 'v2-invisible' === $this->recaptcha_version ) {
							wp_enqueue_script( 'google-recaptcha-invisible', 'https://www.google.com/recaptcha/api.js?onload=recaptchaCallback&render=explicit', [ SRFM_SLUG . '-form-submit' ], SRFM_VER, true );
						}
					}
				}

				if ( 'cf-turnstile' === $this->captcha_security_type ) {
					if ( ! empty( $this->cf_turnstile_site_key ) ) {
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
					} else {
						Helper::render_missing_sitekey_error( 'Cloudflare Turnstile' );
					}
				}
				if ( 'hcaptcha' === $this->captcha_security_type ) {
					if ( ! empty( $this->hcaptcha_site_key ) ) {
						wp_enqueue_script( 'hcaptcha', 'https://js.hcaptcha.com/1/api.js', [], null, [ 'strategy' => 'defer' ] ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
					} else {
						Helper::render_missing_sitekey_error( 'HCaptcha' );
					}
				}
				$srfm_custom_button_classes = apply_filters( 'srfm_add_button_classes', [ 'v2-invisible' === $this->recaptcha_version || 'v3-reCAPTCHA' === $this->recaptcha_version ? 'g-recaptcha ' : '', '1' === $this->btn_from_theme ? 'wp-block-button__link' : 'srfm-button srfm-submit-button srfm-btn-frontend srfm-custom-button' ], $this->form_id );

				$button_style  = $this->btn_from_theme ? '' : ' font-family: inherit; font-weight: var(--wp--custom--font-weight--medium); line-height: normal;';
				$button_style .= 'width:100%;';

				$button_attributes = '';
				if ( 'g-recaptcha' === $this->captcha_security_type && ( 'v2-invisible' === $this->recaptcha_version || 'v3-reCAPTCHA' === $this->recaptcha_version ) ) {
					$button_attributes = 'recaptcha-type=' . $this->recaptcha_version . ' data-sitekey=' . $this->google_captcha_site_key . ' data-callback="recaptchaCallback"';
				}
				?>
				<button style="<?php echo esc_attr( $button_style ); ?>" id="srfm-submit-btn" class="<?php echo esc_attr( Helper::join_strings( $srfm_custom_button_classes ) ); ?>" <?php echo esc_attr( $button_attributes ); ?>>
					<div class="srfm-submit-wrap">
						<?php echo esc_html( $this->button_text ); ?>
						<div class="srfm-loader"></div>
					</div>
				</button>
				<div class="srfm-error-wrap"></div>
			</div>
				<?php
			}
			return ob_get_clean();
	}

}
