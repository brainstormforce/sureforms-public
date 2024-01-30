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
	 * @since  X.X.X
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
			$form_font_size           = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_fontsize', true ) ? get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_fontsize', true ) : '';
			$success_submit_type      = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_submit_type', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_submit_type', true ) ) : '';
			$success_message_title    = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_thankyou_message_title', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_thankyou_message_title', true ) ) : '';
			$success_message          = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_thankyou_message', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_thankyou_message', true ) ) : '';
			$success_url              = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_submit_url', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_submit_url', true ) ) : '';
			$classname                = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_additional_classes', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_additional_classes', true ) ) : '';
			$styling                  = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_form_styling', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_form_styling', true ) ) : '';
			$is_page_break            = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_is_page_break', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_is_page_break', true ) ) : '';
			$page_break_progress_type = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_page_break_progress_indicator', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_page_break_progress_indicator', true ) ) : '';
			$page_break_first_label   = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_first_page_label', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_first_page_label', true ) ) : '';
			$page_break_toggle_label  = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_page_break_toggle_label', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_page_break_toggle_label', true ) ) : '';
			$previous_btn_text        = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_previous_button_text', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_previous_button_text', true ) ) : 'Previous';
			$next_btn_text            = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_next_button_text', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_next_button_text', true ) ) : 'Next';
			// Submit button.
			$button_text      = get_post_meta( intval( $id ), '_srfm_submit_button_text', true ) ? strval( get_post_meta( intval( $id ), '_srfm_submit_button_text', true ) ) : '';
			$button_alignment = get_post_meta( intval( $id ), '_srfm_submit_alignment', true ) ? strval( get_post_meta( intval( $id ), '_srfm_submit_alignment', true ) ) : '';
			$btn_from_theme   = get_post_meta( intval( $id ), '_srfm_inherit_theme_buttom', true ) ? strval( get_post_meta( intval( $id ), '_srfm_inherit_theme_buttom', true ) ) : '';
			$btn_text_color   = get_post_meta( intval( $id ), '_srfm_button_text_color', true ) ? strval( get_post_meta( intval( $id ), '_srfm_button_text_color', true ) ) : '#000000';
			$btn_bg_type      = get_post_meta( intval( $id ), '_srfm_btn_bg_type', true ) ? strval( get_post_meta( intval( $id ), '_srfm_btn_bg_type', true ) ) : '';
			if ( 'filled' === $btn_bg_type ) {
				$btn_bg_color     = get_post_meta( intval( $id ), '_srfm_button_bg_color', true ) ? strval( get_post_meta( intval( $id ), '_srfm_button_bg_color', true ) ) : '#0184C7';
				$btn_border_color = get_post_meta( intval( $id ), '_srfm_button_border_color', true ) ? strval( get_post_meta( intval( $id ), '_srfm_button_border_color', true ) ) : '#00000';
				$btn_border_width = get_post_meta( intval( $id ), '_srfm_button_border_width', true ) ? strval( get_post_meta( intval( $id ), '_srfm_button_border_width', true ) ) : '1px';
				$btn_border       = $btn_border_width . 'px solid ' . $btn_border_color;
			} else {
				$btn_bg_color = '';
				$btn_border   = 'none';
			}
			$bg_type = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_bg_type', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_bg_type', true ) ) : 'image';

			if ( 'image' === $bg_type ) {
				$background_image_url = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_bg', true ) ? rawurldecode( Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_bg', true ) ) ) : '';
				$bg_image             = $background_image_url ? 'url(' . $background_image_url . ')' : '';
				$bg_color             = '#ffffff';
			} else {
				$background_color = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_bg_color', true ) ? rawurldecode( Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_bg_color', true ) ) ) : '';
				$bg_image         = 'none';
				$bg_color         = $background_color ? $background_color : '';
			}

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

			$label_text_color = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_label_color', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_label_color', true ) ) : '#1f2937';

			// New colors.

			$primary_color_var    = $primary_color ? $primary_color : '#046bd2';
			$label_text_color_var = $label_text_color ? $label_text_color : '#1F2937';

			$body_input_color_var  = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_input_text_color', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_input_text_color', true ) ) : '#4B5563';
			$placeholder_color_var = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_input_placeholder_color', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_input_placeholder_color', true ) ) : '#9CA3AF';
			$border_color_var      = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_input_border_color', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_input_border_color', true ) ) : '#D0D5DD';
			$help_color_var        = '#6B7280';
			$base_background_var   = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_input_bg_color', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_input_bg_color', true ) ) : '#FFFFFF';
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
			$error_surface_var          = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_field_error_surface_color', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_field_error_surface_color', true ) ) : '#EF4444';
			$error_text_var             = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_field_error_color', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_field_error_color', true ) ) : '#DC2626';
			$error_background_color_var = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_field_error_bg_color', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_field_error_bg_color', true ) ) : '#FEF2F2';

			$font_size_var          = $form_font_size ? $form_font_size . 'px' : '20px';
			$media_query_mobile_var = '576px';
			$border_var             = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_input_border_width', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_input_border_width', true ) ) . 'px' : '1px';
			$border_radius_var      = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_input_border_radius', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_input_border_radius', true ) ) . 'px' : '4px';
			$container_id           = '.srfm-form-container-' . Sureforms_Helper::get_string_value( $id );
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
					--srfm-bg-image: <?php echo $bg_image ? esc_html( $bg_image ) : ''; ?>;
					--srfm-bg-color: <?php echo $bg_color ? esc_html( $bg_color ) : ''; ?>;
					font-size: var(--srfm-font-size );
					--srfm-btn-text-color: <?php echo esc_html( $btn_text_color ); ?>;
					--srfm-btn-bg-color: <?php echo esc_html( $btn_bg_color ); ?>;
					--srfm-btn-border: <?php echo esc_html( $btn_border ); ?>;
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
				<form method="post" id="srfm-form-<?php echo esc_attr( Sureforms_Helper::get_string_value( $id ) ); ?>" class="srfm-form <?php echo esc_attr( 'classic' === $styling ? 'srfm-form-style-classic ' : '' ); ?><?php echo esc_attr( 'sureforms_form' === $post_type ? 'srfm-single-form ' : '' ); ?><?php echo esc_attr( $classname ); ?><?php // echo esc_attr( '' !== $background_image_url ? 'srfm-form-background' : '' ); ?>"
				form-id="<?php echo esc_attr( Sureforms_Helper::get_string_value( $id ) ); ?>" message-type="<?php echo esc_attr( $success_submit_type ? $success_submit_type : 'message' ); ?>" success-url="<?php echo esc_attr( $success_url ? $success_url : '' ); ?>" ajaxurl="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" nonce="<?php echo esc_attr( wp_create_nonce( 'unique_validation_nonce' ) ); ?>"
				>
				<?php
					wp_nonce_field( 'srfm-form-submit', 'sureforms_form_submit' );
					$honeypot_spam = get_option( 'honeypot' );
				?>
				<!-- page-break progress header start -->
				<?php
				if ( $is_page_break && 'none' !== $page_break_progress_type ) {
					self::render_break_header_container( $is_page_break, $page_break_progress_type, $page_break_toggle_label );
				}
				?>
				<!-- page-break progress header end -->
				<input type="hidden" value="<?php echo esc_attr( Sureforms_Helper::get_string_value( $id ) ); ?>" name="form-id">
				<input type="hidden" value="" name="srfm-sender-email-field" id="srfm-sender-email">
				<?php if ( '1' === $honeypot_spam ) : ?>
					<input type="hidden" value="" name="srfm-honeypot-field">
				<?php endif; ?>
				<?php
				if ( $is_page_break ) {
					if ( $post ) {
						self::form_pagination( $post, $page_break_first_label );
					}
				} else {
					// phpcs:ignore
					echo $content;
					// phpcs:ignoreEnd
				}
				?>
				<?php if ( $is_page_break ) : ?>
					<div class="srfm-page-break-buttons wp-block-button">
						<button class="srfm-pre-btn <?php echo esc_attr( '1' === $btn_from_theme ? 'wp-block-button__link' : 'srfm-btn-bg-color' ); ?>">
							<?php echo esc_attr( $previous_btn_text ); ?>
						</button>
						<button class="srfm-nxt-btn <?php echo esc_attr( '1' === $btn_from_theme ? 'wp-block-button__link' : 'srfm-btn-bg-color' ); ?>">
							<?php echo esc_attr( $next_btn_text ); ?>
						</button>
					</div>
				<?php endif; ?>
				<?php if ( 0 !== $block_count ) : ?>
					<div class="srfm-submit-container <?php echo '#0284c7' !== $color_primary ? 'srfm-frontend-inputs-holder' : ''; ?>">
					<div style="width: <?php echo esc_attr( $full ? '100%;' : ';' ); ?> text-align: <?php echo esc_attr( $button_alignment ? $button_alignment : 'left' ); ?>" class="wp-block-button">
						<?php if ( '' !== $google_captcha_site_key ) : ?>
							<?php if ( 'v2-checkbox' === $recaptcha_version ) : ?>
							<div class='g-recaptcha' data-sitekey="<?php echo esc_attr( strval( $google_captcha_site_key ) ); ?>" ></div>
							<button style="width:<?php echo esc_attr( $full ? '100%;' : '' ); ?>" id="srfm-submit-btn" class="srfm-button srfm-submit-button <?php echo esc_attr( '1' === $btn_from_theme ? 'wp-block-button__link' : 'srfm-btn-bg-color' ); ?>">
								<div class="srfm-submit-wrap">
									<?php echo esc_html( $button_text ); ?>
									<div class="srfm-loader"></div>
								</div>
							</button>
						<?php endif; ?>
							<?php if ( 'v2-invisible' === $recaptcha_version ) : ?>
								<button style="width:<?php echo esc_attr( $full ? '100%;' : '' ); ?>" class="srfm-button srfm-submit-button <?php echo esc_attr( '1' === $btn_from_theme ? 'wp-block-button__link' : 'srfm-btn-bg-color' ); ?>" recaptcha-type="<?php echo esc_attr( $recaptcha_version ); ?> . '" data-sitekey="<?php echo esc_attr( $google_captcha_site_key ); ?>" id="srfm-submit-btn">
									<div class="srfm-submit-wrap">
									<?php echo esc_html( $button_text ); ?>
										<div class="srfm-loader"></div>
									</div>
								</button>
							<?php endif; ?>
							<?php if ( 'v3-reCAPTCHA' === $recaptcha_version ) : ?>
								<?php wp_enqueue_script( 'srfm-google-recaptchaV3', 'https://www.google.com/recaptcha/api.js?render=' . esc_js( $google_captcha_site_key ), array(), SUREFORMS_VER, true ); ?>
								<button style=" width:<?php echo esc_attr( $full ? '100%;' : '' ); ?>" class="g-recaptcha srfm-button srfm-submit-button <?php echo esc_attr( '1' === $btn_from_theme ? 'wp-block-button__link' : 'srfm-btn-bg-color' ); ?>" recaptcha-type="<?php echo esc_attr( $recaptcha_version ); ?>" data-sitekey="<?php echo esc_attr( $google_captcha_site_key ); ?>" id="srfm-submit-btn">
									<div class="srfm-submit-wrap">
										<?php echo esc_html( $button_text ); ?>
										<div class="srfm-loader"></div>
									</div>
								</button>
						<?php endif; ?>
						<?php endif; ?>
						<?php if ( 'none' === $recaptcha_version || '' === $recaptcha_version ) : ?>
							<button style="width:<?php echo esc_attr( $full ? '100%;' : '' ); ?>" id="srfm-submit-btn" class="srfm-button srfm-submit-button <?php echo esc_attr( '1' === $btn_from_theme ? 'wp-block-button__link' : 'srfm-btn-bg-color' ); ?>">
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

	/**
	 * Form pagination
	 *
	 * @param \WP_Post $post The current WP_Post object.
	 * @param string   $page_break_first_label label of first page break.
	 * @since 0.0.1
	 * @return void
	 */
	public static function form_pagination( $post, $page_break_first_label ) {
		$content = $post->post_content;
		preg_match_all( '/wp:sureforms\/page-break {"block_id":"[^"]*","label":"([^"]*)"} \/-->/', $content, $matches );
		$labels  = $matches[1];
		$content = preg_replace( '/<!--\s*wp:sureforms\/page-break\s*{[^}]+?}\s*\/-->/i', '<!-- wp:sureforms/page-break /-->', $content );
		if ( ! $content ) {
			return;
		}
		$pages       = explode( '<!-- wp:sureforms/page-break /-->', $content );
		$new_content = '';
		$i           = 0;
		foreach ( $pages as $page ) {
			if ( 0 === $i ) {
				$label = $page_break_first_label;
			} else {
				$label = isset( $labels[ $i - 1 ] ) ? $labels[ $i - 1 ] : 'page-break';
			}
			$new_content .= '<div class="srfm-page-break" data="' . $label . '">';
			$new_content .= apply_filters( 'the_content', $page );
			$new_content .= '</div>';
			$i++;
		}
		// phpcs:ignore
		echo $new_content;
	}

	/**
	 * Render page break header
	 *
	 * @param boolean|string $is_page_break page break enable.
	 * @param string         $page_break_progress_type type of progress type.
	 * @param string         $page_break_toggle_label is label enable.
	 * @since 0.0.1
	 * @return void
	 */
	public static function render_break_header_container( $is_page_break, $page_break_progress_type, $page_break_toggle_label ) {
		if ( ! $is_page_break ) {
			return;
		}

		echo '<div class="srfm-page-break-header-container" type="' . esc_attr( $page_break_progress_type ) . '" toggle-label="' . esc_attr( $page_break_toggle_label ) . '">';
		self::render_page_break_progress_container( $page_break_progress_type );
		echo '</div>';
	}

	/**
	 * Render page break progress section
	 *
	 * @param string $page_break_progress_type type of progress type.
	 * @since 0.0.1
	 * @return void
	 */
	public static function render_page_break_progress_container( $page_break_progress_type ) {
		if ( 'steps' === $page_break_progress_type ) {
			echo '<div class="srfm-page-break-progress-container">
						<ul class="srfm-progress-connector"></ul>
				  </div>';
		} elseif ( 'connector' === $page_break_progress_type ) {
			echo '<div class="srfm-page-break-steps">
				<div class="srfm-steps-content">
					<div class="srfm-steps-label"><div><span class="srfm-step-count"></span> / <span class="srfm-step-total"></span></div><span class="srfm-steps-page-title"></span></div>
				</div>
				<div class="srfm-steps-container">
					<div class="srfm-progress"></div>
				</div>
			</div>';
		} else {
			echo '<div class="srfm-page-break-progress">
			<progress class="srfm-page-break-indicator" max="100" value="0"></progress>
			<span class="srfm-progress-bar-text">0%</span>
			</div>';
		}
	}
}
