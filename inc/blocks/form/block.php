<?php
/**
 * PHP render Form Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Form;

use SureForms\Inc\Blocks\Base;
use SureForms\Inc\Sureforms_Helper;

/**
 * Submit Block.
 */
class Block extends Base {
	/**
	 * Render the block.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param string       $content Post content.
	 *
	 * @since 0.0.1
	 * @return string|boolean
	 */
	public function render( $attributes, $content = '' ) {
		$id                 = isset( $attributes['id'] ) ? Sureforms_Helper::get_string_value( $attributes['id'] ) : '';
		$block_count        = isset( $attributes['block_count'] ) ? Sureforms_Helper::get_integer_value( $attributes['block_count'] ) : '';
		$color_primary      = '#0284c7';
		$color_secondary    = '';
		$color_text_primary = '#fff';

		ob_start();
		if ( ! empty( $attributes ) && ! empty( $content ) ) {
			$color_primary         = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_color1', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_color1', true ) ) : '#0284c7';
			$color_secondary       = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_color2', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_color2', true ) ) : '';
			$color_text_primary    = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_textcolor1', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_textcolor1', true ) ) : '#fff';
			$background_image_url  = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_bg', true ) ? rawurldecode( Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_bg', true ) ) ) : '';
			$form_font_size        = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_fontsize', true ) ? get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_fontsize', true ) : '';
			$success_submit_type   = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_submit_type', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_submit_type', true ) ) : '';
			$success_message_title = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_thankyou_message_title', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_thankyou_message_title', true ) ) : '';
			$success_message       = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_thankyou_message', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_thankyou_message', true ) ) : '';
			$success_url           = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_submit_url', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_srfm_submit_url', true ) ) : '';
			$classname             = isset( $attributes['className'] ) ? $attributes['className'] : '';

			// Submit button.
			$button_text      = isset( $attributes['submitButtonText'] ) ? Sureforms_Helper::get_string_value( $attributes['submitButtonText'] ) : '';
			$button_alignment = get_post_meta( intval( $id ), '_srfm_submit_alignment', true ) ? strval( get_post_meta( intval( $id ), '_srfm_submit_alignment', true ) ) : '';

			if ( 'justify' === $button_alignment ) {
				$full = true;
			} else {
				$full = false;
			}
			$recaptcha_version = get_post_meta( intval( $id ), '_srfm_form_recaptcha', true ) ? strval( get_post_meta( intval( $id ), '_srfm_form_recaptcha', true ) ) : '';

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
			?>
				<form method="post" id="srfm-form-<?php echo esc_attr( $id ); ?>" class="srfm-form srfm-form-style-classic <?php echo esc_attr( $classname ); ?> <?php echo esc_attr( '' !== $background_image_url ? 'srfm-form-background' : '' ); ?>"
				form-id="<?php echo esc_attr( $id ); ?>" message-type="<?php echo esc_attr( $success_submit_type ? $success_submit_type : 'message' ); ?>" success-url="<?php echo esc_attr( $success_url ? $success_url : '' ); ?>" ajaxurl="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" nonce="<?php echo esc_attr( wp_create_nonce( 'unique_validation_nonce' ) ); ?>"
				>
				<?php
					wp_nonce_field( 'srfm-form-submit', 'sureforms_form_submit' );
					$honeypot_spam = get_option( 'honeypot' );
				?>
				<input type="hidden" value="<?php echo esc_attr( $id ); ?>" name="form-id">
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
								<div class="!srfm-flex !srfm-gap-[6px] !srfm-justify-center !srfm-items-center !srfm-min-h-[24px]">
									<?php echo esc_html( $button_text ); ?>
									<div style="display: none;" class="srfm-loader"></div>
								</div>
							</button>
						<?php endif; ?>
							<?php if ( 'v2-invisible' === $recaptcha_version ) : ?>
								<button style="color: #ffffff; width:<?php echo esc_attr( $full ? '100%;' : '' ); ?>" class="srfm-button !srfm-rounded-md !srfm-px-3.5 !srfm-py-2 !srfm-text-sm !font-semibold text-srfm_primary_text_color !srfm-shadow-sm hover:!opacity-80 !srfm-bg-srfm_primary_color" recaptcha-type="<?php echo esc_attr( $recaptcha_version ); ?> . '" data-sitekey="<?php echo esc_attr( $google_captcha_site_key ); ?>" id="srfm-submit-btn">
									<div class="!srfm-flex !srfm-gap-[6px] !srfm-justify-center !srfm-items-center !srfm-min-h-[24px]">
									<?php echo esc_html( $button_text ); ?>
										<div style="display: none;" class="srfm-loader"></div>
									</div>
								</button>
							<?php endif; ?>
							<?php if ( 'v3-reCAPTCHA' === $recaptcha_version ) : ?>
								<?php wp_enqueue_script( 'srfm-google-recaptchaV3', 'https://www.google.com/recaptcha/api.js?render=' . esc_js( $google_captcha_site_key ), array(), SUREFORMS_VER, true ); ?>
								<button style="color: #ffffff; width:<?php echo esc_attr( $full ? '100%;' : '' ); ?>" class="g-recaptcha srfm-button !srfm-rounded-md !srfm-px-3.5 !srfm-py-2 !srfm-text-sm !font-semibold text-srfm_primary_text_color !srfm-shadow-sm hover:!opacity-80 !srfm-bg-srfm_primary_color" recaptcha-type="<?php echo esc_attr( $recaptcha_version ); ?>" data-sitekey="<?php echo esc_attr( $google_captcha_site_key ); ?>" id="srfm-submit-btn">
									<div class="!srfm-flex !srfm-gap-[6px] !srfm-justify-center !srfm-items-center !srfm-min-h-[24px]">
										<?php echo esc_html( $button_text ); ?>
										<div style="display: none;" class="srfm-loader"></div>
									</div>
								</button>
						<?php endif; ?>
						<?php endif; ?>
						<?php if ( 'none' === $recaptcha_version || '' === $recaptcha_version ) : ?>
							<button style="color: #ffffff; width:<?php echo esc_attr( $full ? '100%;' : '' ); ?>" id="srfm-submit-btn" class="srfm-button !srfm-rounded-md !srfm-px-3.5 !srfm-py-2 !srfm-text-sm !font-semibold text-srfm_primary_text_color !srfm-shadow-sm hover:!opacity-80 !srfm-bg-srfm_primary_color">
									<div class="!srfm-flex !srfm-gap-[6px] !srfm-justify-center !srfm-items-center !srfm-min-h-[24px]">
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
			<div id="srfm-success-message-page-<?php echo esc_attr( $id ); ?>" style="height:0; opacity:0; min-height:0;" class="srfm-single-form srfm-success-box in-page"> 
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
			<style>
				<?php echo esc_attr( '#srfm-form-' . $id . ', #srfm-success-message-page-' . $id ); ?> {
					--srfm-secondary-color: 
					<?php
						echo esc_attr( $color_secondary );
					?>
						;
					--srfm-primary-color: 
					<?php
						echo esc_attr( $color_primary );
					?>
						;
					--srfm-primary-text-color:
					<?php
						echo esc_attr( $color_text_primary );
					?>
						;
					font-size: <?php echo esc_attr( isset( $form_font_size ) ? $form_font_size . 'px;' : '' ); ?>

				}
				<?php echo esc_attr( '#srfm-form-' . $id ); ?> {
					background-image: url('<?php echo esc_url( isset( $background_image_url ) ? $background_image_url : '' ); ?>'); 

				}
			</style>
		<?php
		return ob_get_clean();
	}
}
