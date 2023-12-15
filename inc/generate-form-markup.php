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

		$blocks             = parse_blocks( $content );
		$block_count        = count( $blocks );
		$color_primary      = '#0284c7';
		$color_secondary    = '';
		$color_text_primary = '#fff';
		$current_post_type  = get_post_type();

		ob_start();
		if ( '' !== $id && 0 !== $block_count ) {
			$color_primary         = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_color1', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_color1', true ) ) : '#0284c7';
			$color_secondary       = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_color2', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_color2', true ) ) : '';
			$color_text_primary    = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_textcolor1', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_textcolor1', true ) ) : '#fff';
			$background_image_url  = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_bg', true ) ? rawurldecode( Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_bg', true ) ) ) : '';
			$form_font_size        = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_fontsize', true ) ? get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_fontsize', true ) : '';
			$success_submit_type   = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_submit_type', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_submit_type', true ) ) : '';
			$success_message_title = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_thankyou_message_title', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_thankyou_message_title', true ) ) : '';
			$success_message       = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_thankyou_message', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_thankyou_message', true ) ) : '';
			$success_url           = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_submit_url', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_submit_url', true ) ) : '';
			$classname             = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_additional_classes', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_additional_classes', true ) ) : '';
			$styling               = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_form_styling', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_form_styling', true ) ) : '';
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

			$primary_color = '';

			$label_text_color = '';
			$body_input_color = '';
			$placeholder_color = '';
			$border_color = '';
			$help_text_color = '';
			$base_background_color = '';
			$light_background_color = '';

			//Info colors.
			$info_surface_color = '';
			$info_text_color = '';
			$info_background_color = '';
	
			//Success colors
			$success_surface_color = '';
			$success_text_color = '';
			$success_background_color = '';
	
			//Warning colors
			$success_surface_color = '';
			$success_text_color = '';
			$success_background_color = '';
	
			//Warning colors
			$warning_surface_color = '';
			$warning_text_color = '';
			$warning_background_color = '';
	
			//Warning colors
			$error_surface_color = '';
			$error_text_color = '';
			$error_background_color = '';
	
			$font_size = '';
			$media_query_mobile = '';
			$border = '';
			$border_radius = '';

			// New colors.

			$primary_color_var = $primary_color ? $primary_color : '#046bd2';

			$label_text_color_var = $label_text_color ? $label_text_color : '#1F2937';
			$body_input_color_var = $body_input_color ? $body_input_color : '#4B5563';
			$placeholder_color_var = $placeholder_color ? $placeholder_color : '#9CA3AF';
			$border_color_var = $border_color ? $border_color : '#D1D5DB';
			$help_color_var = $help_text_color ? $help_text_color : '#6B7280';
			$base_background_var = $base_background_color ? $base_background_color : '#FFFFFF';
			$light_background_var = $light_background_color ? $light_background_color : '#F9FAFB';
	
			//Info colors.
			$info_surface_var = $info_surface_color ? $info_surface_color : '#3B82F6';
			$info_text_var = $info_text_color ? $info_text_color : '#2563EB';
			$info_background_color_var = $info_background_color ? $info_background_color : '#EFF6FF';
	
			//Success colors.
			$success_surface_var = $success_surface_color ? $success_surface_color : '#22C55E';
			$success_text_var = $success_text_color ? $success_text_color : '#16A34A';
			$success_background_color_var = $success_background_color ? $success_background_color : '#F0FDF4';
	
			//Warning colors.
			$warning_surface_var = $warning_surface_color ? $warning_surface_color : '#FACC15';
			$warning_text_var = $warning_text_color ? $warning_text_color : '#CA8A04';
			$warning_background_color_var = $warning_background_color ? $warning_background_color : '#FEFCE8';
	
			//Error colors.
			$error_surface_var = $warning_surface_color ? $warning_surface_color : '#EF4444';
			$error_text_var = $warning_text_color ? $warning_text_color : '#DC2626';
			$error_background_color_var = $warning_background_color ? $warning_background_color : '#FEF2F2';
	
			$font_size_var = $font_size ? $font_size : '20px';
			$media_query_mobile_var =  $media_query_mobile ? $media_query_mobile : '576px';
			$border_var = $border ? $border : '1px';
			$border_radius_var = $border_radius ? $border_radius : '8px';
			$container_id = '.srfm-form-container-' . Sureforms_Helper::get_string_value( $id );
			?>

			<div class="srfm-form-container srfm-form-container-<?php echo esc_attr( Sureforms_Helper::get_string_value( $id ) ); ?>">
				<div class="srfm-form-loader">
					<div class="loader"></div>
				</div>
			<style>
				<?php echo $container_id ?> {
					--srfm-primary-color : <?php echo esc_html($primary_color_var); ?>;
					--srfm-label-text-color : <?php echo esc_html($label_text_color_var); ?>;
					--srfm-body-input-color : <?php echo esc_html($body_input_color_var); ?>;
					--srfm-placeholder-color : <?php echo esc_html($placeholder_color_var); ?>;
					--srfm-border-color : <?php echo esc_html($border_color_var); ?>;
					--srfm-help-color : <?php echo esc_html($help_color_var); ?>;
					--srfm-base-background-color : <?php echo esc_html($base_background_var); ?>;
					--srfm-light-background-color : <?php echo esc_html($light_background_var); ?>;
					--srfm-info-surface-color : <?php echo esc_html($info_surface_var); ?>;
					--srfm-info-text-color : <?php echo esc_html($info_text_var); ?>;
					--srfm-info-background-color : <?php echo esc_html($info_background_color_var); ?>;
					--srfm-success-surface-color : <?php echo esc_html($success_surface_var); ?>;
					--srfm-success-text-color : <?php echo esc_html($success_text_var); ?>;
					--srfm-success-background-color : <?php echo esc_html($success_background_color_var); ?>;
					--srfm-warning-surface-color : <?php echo esc_html($warning_surface_var); ?>;
					--srfm-warning-text-color : <?php echo esc_html($warning_text_var); ?>;
					--srfm-warning-background-color : <?php echo esc_html($warning_background_color_var); ?>;
					--srfm-error-surface-color : <?php echo esc_html($error_surface_var); ?>;
					--srfm-error-text-color : <?php echo esc_html($error_text_var); ?>;
					--srfm-error-background-color : <?php echo esc_html($error_background_color_var); ?>;
					--srfm-font-size: <?php echo esc_html( $font_size_var ); ?>;
					--srfm-mobile-media-query: <?php echo esc_html( $media_query_mobile_var ); ?>;
					--srfm-border-radius: <?php echo esc_html( $border_radius_var ); ?>;
					--srfm-border: <?php echo esc_html( $border_var ); ?>;
					font-size: var(--srfm-font-size );
				}
			</style>
			<?php
			if ( 'sureforms_form' !== $current_post_type && '1' !== $show_title_on_page && true !== $hide_title_current_page ) {
				$title = ! empty( get_the_title( (int) $id ) ) ? get_the_title( (int) $id ) : '';
				?> <h1 class="srfm-form-title"><?php echo esc_html( $title ); ?></h1> 
				<?php
			}
			?>
				<form method="post" id="srfm-form-<?php echo esc_attr( Sureforms_Helper::get_string_value( $id ) ); ?>" class="srfm-form <?php echo esc_attr( 'classic' === $styling ? 'srfm-form-style-classic ' : '' ); ?><?php echo esc_attr( 'sureforms_form' === $post_type ? 'srfm-single-form ' : '' ); ?><?php echo esc_attr( $classname ); ?><?php echo esc_attr( '' !== $background_image_url ? 'srfm-form-background' : '' ); ?>"
				form-id="<?php echo esc_attr( Sureforms_Helper::get_string_value( $id ) ); ?>" message-type="<?php echo esc_attr( $success_submit_type ? $success_submit_type : 'message' ); ?>" success-url="<?php echo esc_attr( $success_url ? $success_url : '' ); ?>" ajaxurl="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" nonce="<?php echo esc_attr( wp_create_nonce( 'unique_validation_nonce' ) ); ?>"
				>
				<?php
					wp_nonce_field( 'srfm-form-submit', 'sureforms_form_submit' );
					$honeypot_spam = get_option( 'honeypot' );
				?>
				<input type="hidden" value="<?php echo esc_attr( Sureforms_Helper::get_string_value( $id ) ); ?>" name="form-id">
				<input type="hidden" value="" name="srfm-sender-email-field" id="srfm-sender-email">
				<?php if ( '1' === $honeypot_spam ) : ?>
					<input type="hidden" value="" name="srfm-honeypot-field">
				<?php endif; ?>
				<?php
					// phpcs:ignore
					echo $content;
					// phpcs:ignoreEnd
				?>
				<?php if ( 0 !== $block_count ) : ?>
					<div class="srfm-submit-container <?php echo '#0284c7' !== $color_primary ? 'srfm-frontend-inputs-holder' : ''; ?>">
					<div style="width: <?php echo esc_attr( $full ? '100%;' : ';' ); ?> text-align: <?php echo esc_attr( $button_alignment ? $button_alignment : 'left' ); ?>">
						<?php if ( '' !== $google_captcha_site_key ) : ?>
							<?php if ( 'v2-checkbox' === $recaptcha_version ) : ?>
							<div class='g-recaptcha' data-sitekey="<?php echo esc_attr( strval( $google_captcha_site_key ) ); ?>" ></div>
							<button style="color: #ffffff; width:<?php echo esc_attr( $full ? '100%;' : '' ); ?>" id="srfm-submit-btn" class="srfm-button !srfm-mt-[24px] !srfm-rounded-md !srfm-px-3.5 !srfm-py-2 !srfm-text-sm !font-semibold text-srfm_primary_text_color !srfm-shadow-sm hover:!opacity-80 !srfm-bg-srfm_primary_color">
								<div class="srfm-submit-text">
									<?php echo esc_html( $button_text ); ?>
									<div style="display: none;" class="srfm-loader"></div>
								</div>
							</button>
						<?php endif; ?>
							<?php if ( 'v2-invisible' === $recaptcha_version ) : ?>
								<button style="color: #ffffff; width:<?php echo esc_attr( $full ? '100%;' : '' ); ?>" class="srfm-button !srfm-rounded-md !srfm-px-3.5 !srfm-py-2 !srfm-text-sm !font-semibold text-srfm_primary_text_color !srfm-shadow-sm hover:!opacity-80 !srfm-bg-srfm_primary_color" recaptcha-type="<?php echo esc_attr( $recaptcha_version ); ?> . '" data-sitekey="<?php echo esc_attr( $google_captcha_site_key ); ?>" id="srfm-submit-btn">
									<div class="srfm-submit-text">
									<?php echo esc_html( $button_text ); ?>
										<div style="display: none;" class="srfm-loader"></div>
									</div>
								</button>
							<?php endif; ?>
							<?php if ( 'v3-reCAPTCHA' === $recaptcha_version ) : ?>
								<?php wp_enqueue_script( 'srfm-google-recaptchaV3', 'https://www.google.com/recaptcha/api.js?render=' . esc_js( $google_captcha_site_key ), array(), SUREFORMS_VER, true ); ?>
								<button style="color: #ffffff; width:<?php echo esc_attr( $full ? '100%;' : '' ); ?>" class="g-recaptcha srfm-button !srfm-rounded-md !srfm-px-3.5 !srfm-py-2 !srfm-text-sm !font-semibold text-srfm_primary_text_color !srfm-shadow-sm hover:!opacity-80 !srfm-bg-srfm_primary_color" recaptcha-type="<?php echo esc_attr( $recaptcha_version ); ?>" data-sitekey="<?php echo esc_attr( $google_captcha_site_key ); ?>" id="srfm-submit-btn">
									<div class="srfm-submit-text">
										<?php echo esc_html( $button_text ); ?>
										<div style="display: none;" class="srfm-loader"></div>
									</div>
								</button>
						<?php endif; ?>
						<?php endif; ?>
						<?php if ( 'none' === $recaptcha_version || '' === $recaptcha_version ) : ?>
							<button style="color: #ffffff; width:<?php echo esc_attr( $full ? '100%;' : '' ); ?>" id="srfm-submit-btn" class="srfm-button !srfm-rounded-md !srfm-px-3.5 !srfm-py-2 !srfm-text-sm !font-semibold text-srfm_primary_text_color !srfm-shadow-sm hover:!opacity-80 !srfm-bg-srfm_primary_color">
									<div class="srfm-submit-text">
										<?php echo esc_html( $button_text ); ?>
										<div style="display: none;" class="srfm-loader"></div>
									</div>
								</button>
						<?php endif; ?>
					</div>
					</div>
				<?php endif; ?>
				<p id="srfm-error-message" class="srfm-error-message" hidden="true"><?php echo esc_html__( 'There was an error trying to submit your form. Please try again.', 'sureforms' ); ?></p>
			</form>
			<div id="srfm-success-message-page-<?php echo esc_attr( Sureforms_Helper::get_string_value( $id ) ); ?>"  class="srfm-single-form srfm-success-box in-page"> 
				<i class="fa-regular fa-circle-check"></i>
				<article class="srfm-success-box-header">
					<?php echo esc_html( $success_message_title ); ?>
				</article>
				<article class="srfm-success-box-subtxt srfm-text-gray-900">
					<?php echo esc_html( $success_message ); ?>
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
