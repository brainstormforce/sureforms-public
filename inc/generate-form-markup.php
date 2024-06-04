<?php
/**
 * Sureforms Generate Form Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc;

use WP_REST_Response;
use WP_Error;
use SRFM\Inc\Traits\Get_Instance;
use SRFM\Inc\Helper;
use SRFM\Inc\Smart_Tags;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Load Defaults Class.
 *
 * @since 0.0.1
 */
class Generate_Form_Markup {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since  0.0.1
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_custom_endpoint' ] );
	}

	/**
	 * Add custom API Route to generate form markup.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function register_custom_endpoint() {
		register_rest_route(
			'sureforms/v1',
			'/generate-form-markup',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_form_markup' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	/**
	 * Handle Form status
	 *
	 * @param int|string $id Contains form ID.
	 * @param boolean    $show_title_current_page Boolean to show/hide form title.
	 * @param string     $sf_classname additional class_name.
	 * @param string     $post_type Contains post type.
	 *
	 * @return string|false
	 * @since 0.0.1
	 */
	public static function get_form_markup( $id, $show_title_current_page = true, $sf_classname = '', $post_type = 'post' ) {
		if ( isset( $_GET['id'] ) && isset( $_GET['srfm_form_markup_nonce'] ) ) {
			$nonce = isset( $_GET['srfm_form_markup_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['srfm_form_markup_nonce'] ) ) : '';
			$id    = wp_verify_nonce( $nonce, 'srfm_form_markup' ) && ! empty( $_GET['srfm_form_markup_nonce'] ) ? Helper::get_integer_value( sanitize_text_field( wp_unslash( $_GET['id'] ) ) ) : '';
		} else {
			$id = Helper::get_integer_value( $id );
		}
		do_action( 'srfm_localize_conditional_logic_data', $id );
		$post = get_post( Helper::get_integer_value( $id ) );
		if ( $post && ! empty( $post->post_content ) ) {
			$content = apply_filters( 'the_content', $post->post_content ); //phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wordpress hook
		} else {
			$content = '';
		}

		$blocks            = parse_blocks( $content );
		$block_count       = count( $blocks );
		$color_secondary   = '';
		$current_post_type = get_post_type();

		ob_start();
		if ( '' !== $id && 0 !== $block_count ) {
			$color_primary            = Helper::get_meta_value( $id, '_srfm_color1' );
			$form_font_size           = Helper::get_meta_value( $id, '_srfm_fontsize' );
			$classname                = Helper::get_meta_value( $id, '_srfm_additional_classes' );
			$is_page_break            = Helper::get_meta_value( $id, '_srfm_is_page_break' );
			$page_break_progress_type = Helper::get_meta_value( $id, '_srfm_page_break_progress_indicator' );
			$form_confirmation        = get_post_meta( $id, '_srfm_form_confirmation' );
			$confirmation_type        = '';
			$submission_action        = '';
			$success_url              = '';
			if ( is_array( $form_confirmation ) && isset( $form_confirmation[0][0] ) ) {
				$confirmation_data = $form_confirmation[0][0];
				$page_url          = isset( $confirmation_data['page_url'] ) ? $confirmation_data['page_url'] : '';
				$custom_url        = isset( $confirmation_data['custom_url'] ) ? $confirmation_data['custom_url'] : '';
				$confirmation_type = isset( $confirmation_data['confirmation_type'] ) ? $confirmation_data['confirmation_type'] : '';
				$submission_action = isset( $confirmation_data['submission_action'] ) ? $confirmation_data['submission_action'] : '';
				$success_url       = '';
				if ( 'different page' === $confirmation_type ) {
					$success_url = $page_url;
				} elseif ( 'custom url' === $confirmation_type ) {
					$success_url = $custom_url;
				}
			}

			// Submit button.
			$button_text          = Helper::get_meta_value( $id, '_srfm_submit_button_text' );
			$button_alignment     = Helper::get_meta_value( $id, '_srfm_submit_alignment' );
			$btn_from_theme       = Helper::get_meta_value( $id, '_srfm_inherit_theme_button' );
			$btn_text_color       = Helper::get_meta_value( $id, '_srfm_button_text_color', true, '#000000' );
			$btn_bg_type          = Helper::get_meta_value( $id, '_srfm_btn_bg_type' );
			$instant_form         = Helper::get_meta_value( $id, '_srfm_instant_form' );
			$is_inline_button     = Helper::get_meta_value( $id, '_srfm_is_inline_button' );
			$security_type        = Helper::get_meta_value( $id, '_srfm_captcha_security_type' );
			$form_custom_css_meta = Helper::get_meta_value( $id, '_srfm_form_custom_css' );
			$custom_css           = ! empty( $form_custom_css_meta ) && is_string( $form_custom_css_meta ) ? $form_custom_css_meta : '';

			$btn_border_radius = '6px';
			if ( 'filled' === $btn_bg_type ) {
				$btn_bg_color      = Helper::get_meta_value( $id, '_srfm_button_bg_color', true, '#D54407' );
				$btn_border_color  = Helper::get_meta_value( $id, '_srfm_button_border_color', true, '#000000' );
				$btn_border_width  = Helper::get_meta_value( $id, '_srfm_button_border_width', true, '0px' );
				$btn_border_radius = Helper::get_meta_value( $id, '_srfm_button_border_radius', true, '4' ) . 'px';
				$btn_border        = $btn_border_width . 'px solid ' . $btn_border_color;
			} else {
				$btn_bg_color = '';
				$btn_border   = 'none';
			}
			$bg_type = Helper::get_meta_value( $id, '_srfm_bg_type', true, 'image' );

			if ( 'image' === $bg_type ) {
				$background_image_url = Helper::get_meta_value( $id, '_srfm_bg_image' );
				$bg_image             = $background_image_url ? 'url(' . $background_image_url . ')' : '';
				$bg_color             = '#ffffff';
			} else {
				$background_color = Helper::get_meta_value( $id, '_srfm_bg_color' );
				$bg_image         = 'none';
				$bg_color         = $background_color ? $background_color : '';
			}

			$full                       = 'justify' === $button_alignment ? true : false;
			$recaptcha_version          = 'g-recaptcha' === $security_type ? Helper::get_meta_value( $id, '_srfm_form_recaptcha' ) : '';
			$srfm_cf_appearance_mode    = '';
			$srfm_cf_turnstile_site_key = '';

			$google_captcha_site_key = '';

			if ( 'none' !== $security_type ) {
				$global_setting_options = get_option( 'srfm_security_settings_options' );
			} else {
				$global_setting_options = [];
			}

			if ( is_array( $global_setting_options ) && 'cf-turnstile' === $security_type ) {
				$srfm_cf_turnstile_site_key = isset( $global_setting_options['srfm_cf_turnstile_site_key'] ) ? $global_setting_options['srfm_cf_turnstile_site_key'] : '';
				$srfm_cf_appearance_mode    = isset( $global_setting_options['srfm_cf_appearance_mode'] ) ? $global_setting_options['srfm_cf_appearance_mode'] : 'auto';
			}

			if ( is_array( $global_setting_options ) && 'g-recaptcha' === $security_type ) {
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

			$primary_color = $color_primary;

			$label_text_color = Helper::get_meta_value( $id, '_srfm_label_color', true, '#111827' );
			$help_color_var   = Helper::get_meta_value( $id, '_srfm_help_color', true, '#4B5563' );

			// New colors.

			$primary_color_var    = $primary_color ? $primary_color : '#046bd2';
			$label_text_color_var = $label_text_color ? $label_text_color : '#111827';

			$body_input_color_var  = Helper::get_meta_value( $id, '_srfm_input_text_color', true, '#4B5563' );
			$placeholder_color_var = Helper::get_meta_value( $id, '_srfm_input_placeholder_color', true, '#94A3B8' );
			$border_color_var      = Helper::get_meta_value( $id, '_srfm_input_border_color', true, '#D0D5DD' );
			$shadow_color_var      = Helper::get_meta_value( $id, '_srfm_input_shadow_color', true, '#D0D5DD' );
			$base_background_var   = Helper::get_meta_value( $id, '_srfm_input_bg_color', true, '#FFFFFF' );
			$light_background_var  = '#F9FAFB';

			// Info colors.
			$info_surface_var          = '#3B82F6';
			$info_text_var             = '#2563EB';
			$info_background_color_var = '#EFF6FF';

			// Success colors.
			$success_surface_var          = '#10B981';
			$success_text_var             = '#16A34A';
			$success_background_color_var = '#F0FDF4';

			// Warning colors.
			$warning_surface_var          = '#FACC15';
			$warning_text_var             = '#CA8A04';
			$warning_background_color_var = '#FEFCE8';

			// Error colors.
			$error_surface_var          = Helper::get_meta_value( $id, '_srfm_field_error_surface_color', true, '#EF4444' );
			$error_shadow_var           = Helper::get_meta_value( $id, '_srfm_field_error_shadow_color', true, '#FEE4E2' );
			$error_text_var             = Helper::get_meta_value( $id, '_srfm_field_error_color', true, '#DC2626' );
			$error_background_color_var = Helper::get_meta_value( $id, '_srfm_field_error_bg_color', true, '#FEF2F2' );

			$font_size_var          = $form_font_size ? $form_font_size . 'px' : '20px';
			$media_query_mobile_var = '576px';
			$border_var             = get_post_meta( Helper::get_integer_value( $id ), '_srfm_input_border_width', true ) ? Helper::get_string_value( get_post_meta( Helper::get_integer_value( $id ), '_srfm_input_border_width', true ) ) . 'px' : '1px';
			$border_radius_var      = get_post_meta( Helper::get_integer_value( $id ), '_srfm_input_border_radius', true ) ? Helper::get_string_value( get_post_meta( Helper::get_integer_value( $id ), '_srfm_input_border_radius', true ) ) . 'px' : '4px';
			$container_id           = '.srfm-form-container-' . Helper::get_string_value( $id );
			?>

			<div class="srfm-form-container srfm-form-container-<?php echo esc_attr( Helper::get_string_value( $id ) ); ?> <?php echo esc_attr( $sf_classname ); ?> <?php echo esc_attr( $classname ); ?>">
			<style>
				<?php echo esc_html( $container_id ); ?> {
					--srfm-primary-color : <?php echo esc_html( $primary_color_var ); ?>;
					--srfm-label-text-color : <?php echo esc_html( $label_text_color_var ); ?>;
					--srfm-body-input-color : <?php echo esc_html( $body_input_color_var ); ?>;
					--srfm-placeholder-color : <?php echo esc_html( $placeholder_color_var ); ?>;
					--srfm-border-color : <?php echo esc_html( $border_color_var ); ?>;
					--srfm-shadow-color : <?php echo esc_html( $primary_color_var . '30' ); ?>;
					--srfm-help-color : <?php echo esc_html( $help_color_var ); ?>;
					--srfm-base-background-color : <?php echo esc_html( $base_background_var ); ?>;
					--srfm-light-background-color : <?php echo esc_html( $light_background_var ); ?>;
					--srfm-info-surface-color : <?php echo esc_html( $info_surface_var ); ?>;
					--srfm-info-text-color : <?php echo esc_html( $info_text_var ); ?>;
					--srfm-info-background-color : <?php echo esc_html( $info_background_color_var ); ?>;
					--srfm-success-surface-color : <?php echo esc_html( $success_surface_var ); ?>;
					--srfm-success-text-color : <?php echo esc_html( $success_text_var ); ?>;
					--srfm-success-background-color : <?php echo esc_html( $success_background_color_var ); ?>;
					--srfm-warning-surface-color : <?php echo esc_html( $warning_surface_var ); ?>;
					--srfm-warning-text-color : <?php echo esc_html( $warning_text_var ); ?>;
					--srfm-warning-background-color : <?php echo esc_html( $warning_background_color_var ); ?>;
					--srfm-error-surface-color : <?php echo esc_html( $error_surface_var ); ?>;
					--srfm-error-shadow-color : <?php echo esc_html( $error_surface_var . '30' ); ?>;
					--srfm-error-text-color : <?php echo esc_html( $error_text_var ); ?>;
					--srfm-error-background-color : <?php echo esc_html( $error_background_color_var ); ?>;
					--srfm-font-size: <?php echo esc_html( $font_size_var ); ?>;
					--srfm-mobile-media-query: <?php echo esc_html( $media_query_mobile_var ); ?>;
					--srfm-border-radius: <?php echo esc_html( $border_radius_var ); ?>;
					--srfm-border: <?php echo esc_html( $border_var ); ?>;
					--srfm-bg-image: <?php echo $bg_image ? esc_html( $bg_image ) : ''; ?>;
					--srfm-bg-color: <?php echo $bg_color ? esc_html( $bg_color ) : ''; ?>;
					font-size: var(--srfm-font-size );
					--srfm-btn-text-color: <?php echo esc_html( $btn_text_color ); ?>;
					--srfm-btn-bg-color: <?php echo esc_html( $btn_bg_color ); ?>;
					--srfm-btn-border: <?php echo esc_html( $btn_border ); ?>;
					--srfm-btn-border-radius: <?php echo esc_html( $btn_border_radius ); ?>;
				}
					<?php
					do_action( 'srfm_form_css_variables', $id );
						// echo custom css on page/post.
					if ( 'sureforms_form' !== $current_post_type ) :
						echo wp_kses_post( $custom_css );
						endif;
					?>
			</style>
			<?php
			if ( 'sureforms_form' !== $current_post_type && true === $show_title_current_page ) {
				$title = ! empty( get_the_title( (int) $id ) ) ? get_the_title( (int) $id ) : '';
				?>
				<h2 class="srfm-form-title"><?php echo esc_html( $title ); ?></h2> 
				<?php
			}
			?>
			<?php
			if ( ! $instant_form && current_user_can( 'manage_options' ) && is_singular( 'sureforms_form' ) ) {
				?>
				<div class="srfm-instant-form-wrn-ctn">
					<div class="srfm-svg-container">
					<?php echo Helper::fetch_svg( 'instant-form-warning', '' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ignored to render svg. ?>
					</div>
					<div class="srfm-wrn-text-ctn">
						<span class="srfm-wrn-description">
						<?php echo esc_html__( 'Enable the Instant Form in the editor from ', 'sureforms' ); ?>
						<a class="srfm-wrn-link" href="<?php echo esc_url( admin_url( 'post.php?post=' . $id . '&action=edit' ) ); ?>">
							<?php echo esc_html__( 'here.', 'sureforms' ); ?>
						</a>
						</span>
					</div>
				</div> 
				<?php
			}
			?>
				<form method="post" id="srfm-form-<?php echo esc_attr( Helper::get_string_value( $id ) ); ?>" class="srfm-form <?php echo esc_attr( 'sureforms_form' === $post_type ? 'srfm-single-form ' : '' ); ?>"
				form-id="<?php echo esc_attr( Helper::get_string_value( $id ) ); ?>" after-submission="<?php echo esc_attr( $submission_action ); ?>" message-type="<?php echo esc_attr( $confirmation_type ? $confirmation_type : 'same page' ); ?>" success-url="<?php echo esc_attr( $success_url ? $success_url : '' ); ?>" ajaxurl="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" nonce="<?php echo esc_attr( wp_create_nonce( 'unique_validation_nonce' ) ); ?>"
				>
				<?php
					wp_nonce_field( 'srfm-form-submit', 'sureforms_form_submit' );
					$global_setting_options = get_option( 'srfm_general_settings_options' );
					$honeypot_spam          = is_array( $global_setting_options ) && isset( $global_setting_options['srfm_honeypot'] ) ? $global_setting_options['srfm_honeypot'] : '';

				if ( defined( 'SRFM_PRO_VER' ) ) {
					if ( $is_page_break && 'none' !== $page_break_progress_type ) {
						do_action( 'srfm_page_break_header', $id );
					}
				}
				?>

				<input type="hidden" value="<?php echo esc_attr( Helper::get_string_value( $id ) ); ?>" name="form-id">
				<input type="hidden" value="" name="srfm-sender-email-field" id="srfm-sender-email">
				<?php if ( $honeypot_spam ) : ?>
					<input type="hidden" value="" name="srfm-honeypot-field">
				<?php endif; ?>
				<?php

				if ( defined( 'SRFM_PRO_VER' ) && $is_page_break ) {
					do_action( 'srfm_page_break_pagination', $post, $id );
				} else {
					// phpcs:ignore
					echo $content;
					// phpcs:ignoreEnd
				}
				?>
				<?php if ( 0 !== $block_count && ! $is_inline_button || $is_page_break ) : ?>

					<?php if ( is_string( $google_captcha_site_key ) && ! empty( $google_captcha_site_key ) && 'g-recaptcha' === $security_type ) : ?>

						<?php if ( 'v2-checkbox' === $recaptcha_version ) : ?>
							<?php
							wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js', [], SRFM_VER, true );
							?>
							<div class='g-recaptcha'  recaptcha-type="<?php echo esc_attr( $recaptcha_version ); ?>" data-sitekey="<?php echo esc_attr( strval( $google_captcha_site_key ) ); ?>" ></div>
						<?php endif; ?>

						<?php if ( 'v2-invisible' === $recaptcha_version ) : ?>
							<?php
							wp_enqueue_script( 'google-recaptcha-invisible', 'https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit', [ SRFM_SLUG . '-form-submit' ], SRFM_VER, true );
							?>
							<div class='g-recaptcha' recaptcha-type="<?php echo esc_attr( $recaptcha_version ); ?>" data-sitekey="<?php echo esc_attr( $google_captcha_site_key ); ?>" data-size="invisible"></div>
						<?php endif; ?>

						<?php if ( 'v3-reCAPTCHA' === $recaptcha_version ) : ?>
							<?php wp_enqueue_script( 'srfm-google-recaptchaV3', 'https://www.google.com/recaptcha/api.js?render=' . esc_js( $google_captcha_site_key ), [], SRFM_VER, true ); ?>
						<?php endif; ?>

					<?php endif; ?>

					<?php
					if ( defined( 'SRFM_PRO_VER' ) && $is_page_break ) {
						do_action( 'srfm_page_break_btn', $id );
					}
					?>

					<div class="srfm-submit-container <?php echo '#0284c7' !== $color_primary ? 'srfm-frontend-inputs-holder' : ''; ?> <?php echo esc_attr( $is_page_break ? 'hide' : '' ); ?>">
						<div style="width: <?php echo esc_attr( $full ? '100%;' : ';' ); ?> text-align: <?php echo esc_attr( $button_alignment ? $button_alignment : 'left' ); ?>" class="wp-block-button">
						<?php

						if ( 'cf-turnstile' === $security_type ) :
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
							?>
							<div id="srfm-cf-sitekey" class="cf-turnstile" data-theme="<?php echo esc_attr( $srfm_cf_appearance_mode ); ?>" data-sitekey="<?php echo esc_attr( $srfm_cf_turnstile_site_key ); ?>"></div>
							<?php
						endif;
						?>
						<button style="width:<?php echo esc_attr( $full ? '100%;' : '' ); ?>" id="srfm-submit-btn"class="<?php echo esc_attr( '1' === $btn_from_theme ? 'wp-block-button__link' : 'srfm-btn-bg-color srfm-button srfm-submit-button ' ); ?><?php echo 'v3-reCAPTCHA' === $recaptcha_version ? ' g-recaptcha' : ''; ?>"
						<?php if ( 'v3-reCAPTCHA' === $recaptcha_version ) : ?>
							recaptcha-type="<?php echo esc_attr( $recaptcha_version ); ?>" 
							data-sitekey="<?php echo esc_attr( $google_captcha_site_key ); ?>"
						<?php endif; ?>
						>
							<div class="srfm-submit-wrap">
								<?php echo esc_html( $button_text ); ?>
							<div class="srfm-loader"></div>
							</div>
						</button>
						</div>
					</div>
				<?php endif; ?>
				<p id="srfm-error-message" class="srfm-error-message" hidden="true"><?php echo esc_html__( 'There was an error trying to submit your form. Please try again.', 'sureforms' ); ?></p>
			</form>
			<div id="srfm-success-message-page-<?php echo esc_attr( Helper::get_string_value( $id ) ); ?>"  class="srfm-single-form srfm-success-box in-page"></div>
			<?php
			$page_url  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$path      = Helper::get_string_value( wp_parse_url( $page_url, PHP_URL_PATH ) );
			$segments  = explode( '/', $path );
			$form_path = isset( $segments[1] ) ? $segments[1] : '';
		}
		?>
			</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Generate form confirmation markup
	 *
	 * @param array<mixed> $form_data contains form data.
	 * @param array<mixed> $submission_data contains submission data.
	 * @since 0.0.3
	 * @return string|false
	 */
	public static function get_confirmation_markup( $form_data = [], $submission_data = [] ) {

		$confirmation_message = '';

		if ( empty( $form_data ) ) {
			return $confirmation_message;
		}

		$form_confirmation = isset( $form_data['form-id'] ) ?
			get_post_meta( Helper::get_integer_value( $form_data['form-id'] ), '_srfm_form_confirmation' ) : null;

		if ( ! is_array( $form_confirmation ) ) {
			return $confirmation_message;
		}

		$confirmation_data = is_array( $form_confirmation[0] ) && isset( $form_confirmation[0][0] ) ? $form_confirmation[0][0] : null;

		if ( is_array( $form_confirmation ) && isset( $confirmation_data['message'] ) && is_string( $confirmation_data['message'] ) ) {
			$confirmation_message = $confirmation_data['message'];
		}
		if ( empty( $submission_data ) ) {
			return $confirmation_message;
		}
		$smart_tags           = new Smart_Tags();
		$confirmation_message = $smart_tags->process_smart_tags( $confirmation_data['message'], $submission_data, $form_data );

		return $confirmation_message;

	}
}
