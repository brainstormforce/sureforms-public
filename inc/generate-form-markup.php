<?php
/**
 * Sureforms Generate Form Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc;

use WP_REST_Response;
use WP_Error;
use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;

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
	 * Add custom API Route load-form-defaults
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function register_custom_endpoint() {
		register_rest_route(
			'sureforms/v1',
			'/generate-form-markup',
			array(
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_form_markup' ],
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handle Form status
	 *
	 * @param int|string $id Contains form ID.
	 * @param boolean    $hide_title_current_page Boolean to show/hide form title.
	 * @param string     $post_type Contains post type.
	 *
	 * @return string|false
	 * @since 0.0.1
	 */
	public static function get_form_markup( $id, $hide_title_current_page = false, $post_type = 'post' ) {
		$id = isset( $_GET['id'] ) && wp_verify_nonce( $_GET['srfm_form_markup_nonce'], 'srfm_form_markup' ) ? Sureforms_Helper::get_string_value( $_GET['id'] ) : Sureforms_Helper::get_integer_value( $id );

		$post = get_post( Sureforms_Helper::get_integer_value( $id ) );
		if ( $post && ! empty( $post->post_content ) ) {
			$content = apply_filters( 'the_content', $post->post_content );
		} else {
			$content = '';
		}

		$blocks            = parse_blocks( $content );
		$block_count       = count( $blocks );
		$color_secondary   = '';
		$current_post_type = get_post_type();

		ob_start();
		if ( '' !== $id && 0 !== $block_count ) {
			$color_primary            = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_color1', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_color1', true ) ) : '';
			$color_secondary          = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_color2', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_color2', true ) ) : '';
			$color_text_primary       = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_textcolor1', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_textcolor1', true ) ) : '';
			$background_image_url     = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_bg', true ) ? rawurldecode( Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_bg', true ) ) ) : '';
			$form_font_size           = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_fontsize', true ) ? get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_fontsize', true ) : '';
			$success_submit_type      = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_submit_type', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_submit_type', true ) ) : '';
			$success_message_title    = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_thankyou_message_title', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_thankyou_message_title', true ) ) : '';
			$success_message          = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_thankyou_message', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_thankyou_message', true ) ) : '';
			$success_url              = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_submit_url', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_submit_url', true ) ) : '';
			$classname                = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_additional_classes', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_additional_classes', true ) ) : '';
			$styling                  = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_form_styling', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_form_styling', true ) ) : '';
			$is_page_break            = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_is_page_break', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_is_page_break', true ) ) : '';
			$page_break_progress_type = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_page_break_progress_indicator', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_page_break_progress_indicator', true ) ) : '';
			// Submit button.
			$button_text      = get_post_meta( intval( $id ), '_srfm_submit_button_text', true ) ? strval( get_post_meta( intval( $id ), '_srfm_submit_button_text', true ) ) : '';
			$button_alignment = get_post_meta( intval( $id ), '_srfm_submit_alignment', true ) ? strval( get_post_meta( intval( $id ), '_srfm_submit_alignment', true ) ) : '';

			$full               = 'justify' === $button_alignment ? true : false;
			$recaptcha_version  = get_post_meta( intval( $id ), '_srfm_form_recaptcha', true ) ? strval( get_post_meta( intval( $id ), '_srfm_form_recaptcha', true ) ) : '';
			$show_title_on_page = get_post_meta( intval( $id ), '_srfm_page_form_title', true ) ? strval( get_post_meta( intval( $id ), '_srfm_page_form_title', true ) ) : '';

			$google_captcha_site_key = '';
			switch ( $recaptcha_version ) {
				case 'v2-checkbox':
					$google_captcha_site_key = ! empty( get_option( 'sureforms_v2_checkbox_site' ) ) ? strval( get_option( 'sureforms_v2_checkbox_site' ) ) : '';
					break;
				case 'v2-invisible':
					$google_captcha_site_key = ! empty( get_option( 'sureforms_v2_invisible_site' ) ) ? strval( get_option( 'sureforms_v2_invisible_site' ) ) : '';
					break;
				case 'v3-reCAPTCHA':
					$google_captcha_site_key = ! empty( get_option( 'sureforms_v3_site' ) ) ? strval( get_option( 'sureforms_v3_site' ) ) : '';
					break;
				default:
					break;
			}

			$primary_color = $color_primary;

			$label_text_color = $color_text_primary;

			// New colors.

			$primary_color_var    = $primary_color ? $primary_color : '#046bd2';
			$label_text_color_var = $label_text_color ? $label_text_color : '#1F2937';

			$body_input_color_var  = '#4B5563';
			$placeholder_color_var = '#9CA3AF';
			$border_color_var      = '#D0D5DD';
			$help_color_var        = '#6B7280';
			$base_background_var   = '#FFFFFF';
			$light_background_var  = '#F9FAFB';

			// Info colors.
			$info_surface_var          = '#3B82F6';
			$info_text_var             = '#2563EB';
			$info_background_color_var = '#EFF6FF';

			// Success colors.
			$success_surface_var          = '#22C55E';
			$success_text_var             = '#16A34A';
			$success_background_color_var = '#F0FDF4';

			// Warning colors.
			$warning_surface_var          = '#FACC15';
			$warning_text_var             = '#CA8A04';
			$warning_background_color_var = '#FEFCE8';

			// Error colors.
			$error_surface_var          = '#EF4444';
			$error_text_var             = '#DC2626';
			$error_background_color_var = '#FEF2F2';

			$font_size_var          = $form_font_size ? $form_font_size . 'px' : '20px';
			$media_query_mobile_var = '576px';
			$border_var             = '1px';
			$border_radius_var      = '4px';
			$container_id           = '.srfm-form-container-' . Sureforms_Helper::get_string_value( $id );
			$bg_image               = $background_image_url ? 'url(' . $background_image_url . ')' : '';
			?>

			<div class="srfm-form-container srfm-form-container-<?php echo esc_attr( Sureforms_Helper::get_string_value( $id ) ); ?>">
			<style>
				<?php echo esc_html( $container_id ); ?> {
					--srfm-primary-color : <?php echo esc_html( $primary_color_var ); ?>;
					--srfm-label-text-color : <?php echo esc_html( $label_text_color_var ); ?>;
					--srfm-body-input-color : <?php echo esc_html( $body_input_color_var ); ?>;
					--srfm-placeholder-color : <?php echo esc_html( $placeholder_color_var ); ?>;
					--srfm-border-color : <?php echo esc_html( $border_color_var ); ?>;
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
					--srfm-error-text-color : <?php echo esc_html( $error_text_var ); ?>;
					--srfm-error-background-color : <?php echo esc_html( $error_background_color_var ); ?>;
					--srfm-font-size: <?php echo esc_html( $font_size_var ); ?>;
					--srfm-mobile-media-query: <?php echo esc_html( $media_query_mobile_var ); ?>;
					--srfm-border-radius: <?php echo esc_html( $border_radius_var ); ?>;
					--srfm-border: <?php echo esc_html( $border_var ); ?>;
					--srfm-bg-image: <?php echo esc_html( $bg_image ); ?>;
					font-size: var(--srfm-font-size );
				}
			</style>
			<?php
			if ( 'sureforms_form' !== $current_post_type && '1' !== $show_title_on_page && true !== $hide_title_current_page ) {
				$title = ! empty( get_the_title( (int) $id ) ) ? get_the_title( (int) $id ) : '';
				?>
				<h1 class="srfm-form-title"><?php echo esc_html( $title ); ?></h1> 
				<?php
			}
			?>
				<form method="post" id="srfm-form-<?php echo esc_attr( Sureforms_Helper::get_string_value( $id ) ); ?>" class="srfm-form <?php echo esc_attr( 'classic' === $styling ? 'srfm-form-style-classic ' : '' ); ?><?php echo esc_attr( 'sureforms_form' === $post_type ? 'srfm-single-form ' : '' ); ?><?php echo esc_attr( $classname ); ?><?php echo esc_attr( '' !== $background_image_url ? 'srfm-form-background' : '' ); ?>"
				form-id="<?php echo esc_attr( Sureforms_Helper::get_string_value( $id ) ); ?>" message-type="<?php echo esc_attr( $success_submit_type ? $success_submit_type : 'message' ); ?>" success-url="<?php echo esc_attr( $success_url ? $success_url : '' ); ?>" ajaxurl="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" nonce="<?php echo esc_attr( wp_create_nonce( 'unique_validation_nonce' ) ); ?>"
				>
				<?php
					wp_nonce_field( 'srfm-form-submit', 'sureforms_form_submit' );
					$honeypot_spam = get_option( 'honeypot' );

				if ( defined( 'SUREFORMS_PRO_VER' ) ) {
					if ( $is_page_break && 'none' !== $page_break_progress_type ) {
						do_action( 'sureforms_page_break_header', $id );
					}
				}
				?>

				<input type="hidden" value="<?php echo esc_attr( Sureforms_Helper::get_string_value( $id ) ); ?>" name="form-id">
				<input type="hidden" value="" name="srfm-sender-email-field" id="srfm-sender-email">
				<?php if ( '1' === $honeypot_spam ) : ?>
					<input type="hidden" value="" name="srfm-honeypot-field">
				<?php endif; ?>
				<?php

				if ( defined( 'SUREFORMS_PRO_VER' ) && $is_page_break ) {
					do_action( 'sureforms_page_break_pagination', $post, $id );
				} else {
					// phpcs:ignore
					echo $content;
					// phpcs:ignoreEnd
				}
				?>

				<?php
				if ( defined( 'SUREFORMS_PRO_VER' ) && $is_page_break ) {
					do_action( 'sureforms_page_break_btn', $id );
				}
				?>
				<?php if ( 0 !== $block_count ) : ?>
					<div class="srfm-submit-container <?php echo '#0284c7' !== $color_primary ? 'srfm-frontend-inputs-holder' : ''; ?>">
					<div style="width: <?php echo esc_attr( $full ? '100%;' : ';' ); ?> text-align: <?php echo esc_attr( $button_alignment ? $button_alignment : 'left' ); ?>" class="wp-block-button">
						<?php if ( '' !== $google_captcha_site_key ) : ?>
							<?php if ( 'v2-checkbox' === $recaptcha_version ) : ?>
							<div class='g-recaptcha' data-sitekey="<?php echo esc_attr( strval( $google_captcha_site_key ) ); ?>" ></div>
							<button style="width:<?php echo esc_attr( $full ? '100%;' : '' ); ?>" id="srfm-submit-btn" class="srfm-button srfm-submit-button wp-block-button__link">
								<div class="srfm-submit-wrap">
									<?php echo esc_html( $button_text ); ?>
									<div class="srfm-loader"></div>
								</div>
							</button>
						<?php endif; ?>
							<?php if ( 'v2-invisible' === $recaptcha_version ) : ?>
								<button style="width:<?php echo esc_attr( $full ? '100%;' : '' ); ?>" class="srfm-button srfm-submit-button wp-block-button__link" recaptcha-type="<?php echo esc_attr( $recaptcha_version ); ?> . '" data-sitekey="<?php echo esc_attr( $google_captcha_site_key ); ?>" id="srfm-submit-btn">
									<div class="srfm-submit-wrap">
									<?php echo esc_html( $button_text ); ?>
										<div class="srfm-loader"></div>
									</div>
								</button>
							<?php endif; ?>
							<?php if ( 'v3-reCAPTCHA' === $recaptcha_version ) : ?>
								<?php wp_enqueue_script( 'srfm-google-recaptchaV3', 'https://www.google.com/recaptcha/api.js?render=' . esc_js( $google_captcha_site_key ), array(), SUREFORMS_VER, true ); ?>
								<button style=" width:<?php echo esc_attr( $full ? '100%;' : '' ); ?>" class="g-recaptcha srfm-button srfm-submit-button wp-block-button__link" recaptcha-type="<?php echo esc_attr( $recaptcha_version ); ?>" data-sitekey="<?php echo esc_attr( $google_captcha_site_key ); ?>" id="srfm-submit-btn">
									<div class="srfm-submit-wrap">
										<?php echo esc_html( $button_text ); ?>
										<div class="srfm-loader"></div>
									</div>
								</button>
						<?php endif; ?>
						<?php endif; ?>
						<?php if ( 'none' === $recaptcha_version || '' === $recaptcha_version ) : ?>
							<button style="width:<?php echo esc_attr( $full ? '100%;' : '' ); ?>" id="srfm-submit-btn" class="srfm-button srfm-submit-button wp-block-button__link">
									<div class="srfm-submit-wrap">
										<?php echo esc_html( $button_text ); ?>
										<div class="srfm-loader"></div>
									</div>
								</button>
						<?php endif; ?>
					</div>
					</div>
				<?php endif; ?>
				<p id="srfm-error-message" class="srfm-error-message" hidden="true"><?php echo esc_html__( 'There was an error trying to submit your form. Please try again.', 'sureforms' ); ?></p>
			</form>
			<div id="srfm-success-message-page-<?php echo esc_attr( Sureforms_Helper::get_string_value( $id ) ); ?>"  class="srfm-single-form srfm-success-box in-page">
			<article class="srfm-success-box-header">
					<?php echo Sureforms_Helper::fetch_svg( 'check-circle', 'srfm-check-circle-icon' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ignored to render svg ?>
					<h2><?php echo esc_html( $success_message_title ); ?></h2>
				</article>
				<article class="srfm-success-box-description">
					<p><?php echo esc_html( $success_message ); ?></p>
				</article>
			</div>
			<?php
			$page_url  = $_SERVER['REQUEST_URI'];
			$path      = Sureforms_Helper::get_string_value( wp_parse_url( $page_url, PHP_URL_PATH ) );
			$segments  = explode( '/', $path );
			$form_path = isset( $segments[1] ) ? $segments[1] : '';
		}
		?>
			</div>
		<?php
		return ob_get_clean();
	}


}
