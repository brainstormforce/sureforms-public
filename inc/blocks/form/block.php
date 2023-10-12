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
		$color_text_primary = '';

		ob_start();
		if ( ! empty( $attributes ) && ! empty( $content ) ) {
			$color_primary        = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_sureforms_color1', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_sureforms_color1', true ) ) : '#0284c7';
			$color_secondary      = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_sureforms_color2', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_sureforms_color2', true ) ) : '';
			$color_text_primary   = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_sureforms_textcolor1', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_sureforms_textcolor1', true ) ) : '';
			$background_image_url = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_sureforms_bg', true ) ? rawurldecode( Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_sureforms_bg', true ) ) ) : '';
			$form_font_size       = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_sureforms_fontsize', true ) ? get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_sureforms_fontsize', true ) : '';
			$success_submit_type  = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_sureforms_submit_type', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_sureforms_submit_type', true ) ) : '';
			$success_message      = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_sureforms_thankyou_message', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_sureforms_thankyou_message', true ) ) : '';
			$success_url          = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_sureforms_submit_url', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_sureforms_submit_url', true ) ) : '';
			$classname            = isset( $attributes['className'] ) ? $attributes['className'] : '';
			$styling              = get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_sureforms_form_styling', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $id ), '_sureforms_form_styling', true ) ) : '';

			// Submit button.
			$button_text      = isset( $attributes['submitButtonText'] ) ? Sureforms_Helper::get_string_value( $attributes['submitButtonText'] ) : '';
			$button_alignment = get_post_meta( intval( $id ), '_sureforms_submit_alignment', true ) ? strval( get_post_meta( intval( $id ), '_sureforms_submit_alignment', true ) ) : '';
			$styling          = get_post_meta( intval( $id ), '_sureforms_form_styling', true ) ? strval( get_post_meta( intval( $id ), '_sureforms_form_styling', true ) ) : '';

			if ( 'justify' === $button_alignment ) {
				$full = true;
			} else {
				$full = false;
			}
			$recaptcha_version = get_post_meta( intval( $id ), '_sureforms_form_recaptcha', true ) ? strval( get_post_meta( intval( $id ), '_sureforms_form_recaptcha', true ) ) : '';

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
				<form method="post" id="sureforms-form-<?php echo esc_attr( $id ); ?>" class="sureforms-form <?php echo esc_attr( 'classic' === $styling ? 'sf-form-style-classic ' : '' ); ?> <?php echo esc_attr( $classname ); ?> <?php echo esc_attr( '' !== $background_image_url ? 'sureforms-form-background' : '' ); ?>"
				form-id="<?php echo esc_attr( $id ); ?>" message-type="<?php echo esc_attr( $success_submit_type ? $success_submit_type : 'message' ); ?>" success-url="<?php echo esc_attr( $success_url ? $success_url : '' ); ?>" ajaxurl="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" nonce="<?php echo esc_attr( wp_create_nonce( 'unique_validation_nonce' ) ); ?>"
				>
				<?php
					wp_nonce_field( 'sureforms-form-submit', 'sureforms_form_submit' );
					$honeypot_spam = get_option( 'honeypot' );
				?>
				<input type="hidden" value="<?php echo esc_attr( $id ); ?>" name="form-id">
				<input type="hidden" value="" name="sureforms-sender-email-field" id="sureforms-sender-email">
				<?php if ( '1' === $honeypot_spam ) : ?>
					<input type="hidden" value="" name="sureforms-honeypot-field">
				<?php endif; ?>
				<?php
					// phpcs:ignore
					echo $content;
					// phpcs:ignoreEnd
				?>
				<?php if ( 0 !== $block_count ) : ?>
					<div class="sureforms-submit-container <?php echo '#0284c7' !== $color_primary ? 'frontend-inputs-holder' : ''; ?>">
					<div style="width: <?php echo esc_attr( $full ? '100%;' : ';' ); ?> text-align: <?php echo esc_attr( $button_alignment ? $button_alignment : 'left' ); ?>" class="<?php echo 'inherit' === $styling && '#0284c7' === $color_primary ? 'wp-block-button' : ''; ?>">
						<?php if ( '' !== $google_captcha_site_key ) : ?>
							<?php if ( 'v2-checkbox' === $recaptcha_version ) : ?>
							<div class='g-recaptcha' data-sitekey="<?php echo esc_attr( strval( $google_captcha_site_key ) ); ?>" ></div>
								<?php
								switch ( $styling ) {
									case 'inherit':
										echo '<button style="margin-top: 24px; width: ' . esc_attr( $full ? '100%' : '' ) . ';" type="submit" id="sureforms-submit-btn" class="sureforms-button ' . ( 'inherit' === $styling && '#0284c7' === $color_primary ? 'wp-block-button__link' : '' ) . '">
										<div style="display: flex; gap: 6px; align-items: center; min-height: 24px; justify-content: center;">
											' . esc_html( $button_text ) . '
											<div style="display: none;" class="sureforms-loader"></div>
										</div>
									</button>';
										break;
									case 'classic':
										// @phpstan-ignore-next-line
										echo '<button style="color: #ffffff; width: ' . esc_attr( $full ? '100%' : '' ) . ';' . ( '#0284c7' === $color_primary && 'inherit' === $styling ? 'background-color: #0284C7; border: none; padding: 13px 25px; border-radius: 4px;' : '' ) . '" id="sureforms-submit-btn" class="sureforms-button !mt-[24px] !rounded-md !px-3.5 !py-2 !text-sm !font-semibold text-sf_primary_text_color !shadow-sm hover:!opacity-80 ' . ( 'inherit' === $styling && '#0284c7' === $color_primary ? 'wp-block-button__link' : 'bg-sf_primary_color' ) . '">
										<div class="!flex !gap-[6px] !justify-center !items-center !min-h-[24px]">
											' . esc_html( $button_text ) . '
											<div style="display: none;" class="sureforms-loader"></div>
										</div>
										</button>';
										break;
									default:
										echo '<button style="margin-top: 24px; width: ' . esc_attr( $full ? '100%' : '' ) . ';' . ( empty( $color_primary ) && 'inherit' === $styling ? 'background-color: #0284C7; color: #ffffff; border: none; padding: 13px 25px; border-radius: 4px;' : '' ) . '" type="submit" id="sureforms-submit-btn" class="sureforms-button ' . ( 'inherit' === $styling && '#0284c7' === $color_primary ? 'wp-block-button__link' : '' ) . '">
									<div style="display: flex; gap: 6px; align-items: center; min-height: 24px; justify-content: center;">
										' . esc_html( $button_text ) . '
										<div style="display: none;" class="sureforms-loader"></div>
									</div>
								</button>';
										break;
								};
								?>
						<?php endif; ?>
							<?php if ( 'v2-invisible' === $recaptcha_version ) : ?>
								<?php
								switch ( $styling ) {
									case 'inherit':
										echo '<button style="width: ' . esc_attr( $full ? '100%;' : '' ) . ';' . ( empty( $color_primary ) && 'inherit' === $styling ? 'background-color: #0284C7; color: #ffffff; border: none; padding: 13px 25px; border-radius: 4px;' : '' ) . '" type="submit" class="sureforms-button ' . ( 'inherit' === $styling && '#0284c7' === $color_primary ? 'wp-block-button__link' : '' ) . '" recaptcha-type="' . esc_attr( $recaptcha_version ) . '" data-sitekey="' . esc_attr( $google_captcha_site_key ) . '" id="sureforms-submit-btn">
											<div style="display: flex; gap: 6px; align-items: center; min-height: 24px; justify-content: center;">
												' . esc_html( $button_text ) . '
												<div style="display: none;" class="sureforms-loader"></div>
											</div>
										</button>';
										break;
									case 'classic':
										// @phpstan-ignore-next-line
										echo '<button style="color: #ffffff; width: ' . esc_attr( $full ? '100%;' : '' ) . ';' . ( empty( $color_primary ) && 'inherit' === $styling ? 'background-color: #0284C7; border: none; padding: 13px 25px; border-radius: 4px;' : '' ) . '" class="sureforms-button !rounded-md !px-3.5 !py-2 !text-sm !font-semibold text-sf_primary_text_color !shadow-sm hover:!opacity-80 ' . ( 'inherit' === $styling && '#0284c7' === $color_primary ? 'wp-block-button__link' : 'bg-sf_primary_color' ) . '" recaptcha-type="' . esc_attr( $recaptcha_version ) . '" data-sitekey="' . esc_attr( $google_captcha_site_key ) . '" id="sureforms-submit-btn">
											<div class="!flex !gap-[6px] !justify-center !items-center !min-h-[24px]">
												' . esc_html( $button_text ) . '
												<div style="display: none;" class="sureforms-loader"></div>
											</div>
										</button>';
										break;
									default:
										echo '<button style="width: ' . esc_attr( $full ? '100%;' : '' ) . ';' . ( empty( $color_primary ) && 'inherit' === $styling ? 'background-color: #0284C7; color: #ffffff; border: none; padding: 13px 25px; border-radius: 4px;' : '' ) . '" type="submit" class="sureforms-button ' . ( 'inherit' === $styling && '#0284c7' === $color_primary ? 'wp-block-button__link' : '' ) . '" recaptcha-type="' . esc_attr( $recaptcha_version ) . '" data-sitekey="' . esc_attr( $google_captcha_site_key ) . '" id="sureforms-submit-btn">
											<div style="display: flex; gap: 6px; align-items: center; min-height: 24px; justify-content: center;">
												' . esc_html( $button_text ) . '
												<div style="display: none;" class="sureforms-loader"></div>
											</div>
										</button>';
										break;
								};
								?>
							<?php endif; ?>
							<?php if ( 'v3-reCAPTCHA' === $recaptcha_version ) : ?>
								<?php wp_enqueue_script( 'sureforms-google-recaptchaV3', 'https://www.google.com/recaptcha/api.js?render=' . esc_js( $google_captcha_site_key ), array(), SUREFORMS_VER, true ); ?>
									<?php
									switch ( $styling ) {
										case 'inherit':
											// @phpstan-ignore-next-line
											echo '<button style="width: ' . ( esc_attr( $full ? '100%;' : ';' ) ) . ( empty( $color_primary ) && 'inherit' === $styling ? 'background-color: #0284C7; color: #ffffff; border: none; padding: 13px 25px; border-radius: 4px;' : '' ) . '" type="submit" class="g-recaptcha sureforms-button ' . ( 'inherit' === $styling ? 'wp-block-button__link' : '' ) . '" recaptcha-type="' . esc_attr( $recaptcha_version ) . '" data-sitekey="' . esc_attr( $google_captcha_site_key ) . '" id="sureforms-submit-btn">
											<div style="display: flex; gap: 6px; align-items: center; min-height: 24px; justify-content: center;">
												' . esc_html( $button_text ) . '
												<div style="display: none;" class="sureforms-loader"></div>
											</div>
										</button>';
											break;
										case 'classic':
											// @phpstan-ignore-next-line
											echo '<button style="color: #ffffff; width: ' . ( esc_attr( $full ? '100%;' : ';' ) ) . ( empty( $color_primary ) && 'inherit' === $styling ? 'background-color: #0284C7; border: none; padding: 13px 25px; border-radius: 4px;' : '' ) . '" class="g-recaptcha sureforms-button !rounded-md !px-3.5 !py-2 !text-sm !font-semibold text-sf_primary_text_color !shadow-sm hover:!opacity-80 ' . ( 'inherit' === $styling && '#0284c7' === $color_primary ? 'wp-block-button__link' : 'bg-sf_primary_color' ) . '" recaptcha-type="' . esc_attr( $recaptcha_version ) . '" data-sitekey="' . esc_attr( $google_captcha_site_key ) . '" id="sureforms-submit-btn">
											<div class="!flex !gap-[6px] !justify-center !items-center !min-h-[24px]">
												' . esc_html( $button_text ) . '
												<div style="display: none;" class="sureforms-loader"></div>
											</div>
										</button>';
											break;
										default:
											echo '<button style="width: ' . ( esc_attr( $full ? '100%;' : ';' ) ) . ( empty( $color_primary ) && 'inherit' === $styling ? 'background-color: #0284C7; color: #ffffff; border: none; padding: 13px 25px; border-radius: 4px;' : '' ) . '" type="submit" class="g-recaptcha sureforms-button ' . ( 'inherit' === $styling ? 'wp-block-button__link' : '' ) . '" recaptcha-type="' . esc_attr( $recaptcha_version ) . '" data-sitekey="' . esc_attr( $google_captcha_site_key ) . '" id="sureforms-submit-btn">
											<div style="display: flex; gap: 6px; align-items: center; min-height: 24px; justify-content: center;">
												' . esc_html( $button_text ) . '
												<div style="display: none;" class="sureforms-loader"></div>
											</div>
										</button>';
											break;
									};
									?>
						<?php endif; ?>
						<?php endif; ?>
						<?php if ( 'none' === $recaptcha_version || '' === $recaptcha_version ) : ?>
							<?php
							switch ( $styling ) {
								case 'inherit':
									echo '<button style="width: ' . ( esc_attr( $full ? '100%;' : ';' ) ) . ( empty( $color_primary ) && 'inherit' === $styling ? 'background-color: #0284C7; color: #ffffff; border: none; padding: 13px 25px; border-radius: 4px;' : '' ) . '" type="submit" id="sureforms-submit-btn" class="sureforms-button ' . ( 'inherit' === $styling && '#0284c7' === $color_primary ? 'wp-block-button__link' : '' ) . '">
									<div style="display: flex; gap: 6px; align-items: center; min-height: 24px; justify-content: center;">
										' . esc_html( $button_text ) . '
										<div style="display: none;" class="sureforms-loader"></div>
									</div>
								</button>';
									break;
								case 'classic':
									// @phpstan-ignore-next-line
									echo '<button style="color: #ffffff; width: ' . ( esc_attr( $full ? '100%;' : ';' ) ) . ( empty( $color_primary ) && 'inherit' === $styling ? 'background-color: #0284C7; border: none; padding: 13px 25px; border-radius: 4px;' : '' ) . '" id="sureforms-submit-btn" class="sureforms-button !rounded-md !px-3.5 !py-2 !text-sm !font-semibold text-sf_primary_text_color !shadow-sm hover:!opacity-80 ' . ( 'inherit' === $styling && '#0284c7' === $color_primary ? 'wp-block-button__link' : 'bg-sf_primary_color' ) . '">
									<div class="!flex !gap-[6px] !justify-center !items-center !min-h-[24px]">
										' . esc_html( $button_text ) . '
										<div style="display: none;" class="sureforms-loader"></div>
									</div>
								</button>';
									break;
								default:
									echo '<button style="width: ' . ( esc_attr( $full ? '100%;' : ';' ) ) . ( empty( $color_primary ) && 'inherit' === $styling ? 'background-color: #0284C7; color: #ffffff; border: none; padding: 13px 25px; border-radius: 4px;' : '' ) . '" type="submit" id="sureforms-submit-btn" class="sureforms-button ' . ( 'inherit' === $styling && '#0284c7' === $color_primary ? 'wp-block-button__link' : '' ) . '">
									<div style="display: flex; gap: 6px; align-items: center; min-height: 24px; justify-content: center;">
										' . esc_html( $button_text ) . '
										<div style="display: none;" class="sureforms-loader"></div>
									</div>
								</button>';
									break;
							};
							?>
						<?php endif; ?>
					</div>
					</div>
				<?php endif; ?>
				<p id="sureforms-error-message" class="sureforms-error-message" hidden="true"><?php echo esc_html__( 'There was an error trying to submit your form. Please try again.', 'sureforms' ); ?></p>
			</form>
			<div id="sureforms-success-message-page-<?php echo esc_attr( $id ); ?>" style="height:0;" class="sureforms-single-form sureforms-success-box in-page"> 
				<i class="fa-regular fa-circle-check"></i>
				<article class="sureforms-success-box-header">
					<?php echo esc_html__( 'Thank you', 'sureforms' ); ?>
				</article>
				<article class="sureforms-success-box-subtxt text-gray-900">
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
				<?php echo esc_attr( '#sureforms-form-' . $id . ', #sureforms-success-message-page-' . $id ); ?> {
					--sf-secondary-color: 
					<?php
						echo esc_attr( $color_secondary );
					?>
						;
					--sf-primary-color: 
					<?php
						echo esc_attr( $color_primary );
					?>
						;
					--sf-primary-text-color:
					<?php
						echo esc_attr( $color_text_primary );
					?>
						;
					font-size: <?php echo esc_attr( isset( $form_font_size ) ? $form_font_size . 'px;' : '' ); ?>

				}
				<?php echo esc_attr( '#sureforms-form-' . $id ); ?> {
					background-image: url('<?php echo esc_url( isset( $background_image_url ) ? $background_image_url : '' ); ?>'); 

				}
			</style>
		<?php
		return ob_get_clean();
	}
}
