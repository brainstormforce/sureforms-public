<?php
/**
 * PHP render form Submit Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Submit;

use SureForms\Inc\Blocks\Base;
use SureForms\Inc\Sureforms_Helper;
use WP_Query;

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
	 * @return string|boolean
	 */
	public function render( $attributes, $content = '' ) {
		$sureforms_helper_instance = new Sureforms_Helper();

		if ( ! empty( $attributes ) ) {
			$id                = isset( $attributes['id'] ) ? $sureforms_helper_instance->get_string_value( $attributes['id'] ) : '';
			$text              = isset( $attributes['text'] ) ? $sureforms_helper_instance->get_string_value( $attributes['text'] ) : 'Submit';
			$full              = isset( $attributes['full'] ) ? $attributes['full'] : false;
			$button_alignment  = isset( $attributes['buttonAlignment'] ) ? $attributes['buttonAlignment'] : '';
			$classname         = isset( $attributes['className'] ) ? $attributes['className'] : '';
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
			ob_start();
			?>
			<div class="srfm-submit-container srfm-frontend-inputs-holder <?php echo esc_attr( $classname ); ?>">
				<div style="width: <?php echo esc_attr( $full ? '100%;' : ';' ); ?> text-align: <?php echo esc_attr( $button_alignment ? $button_alignment : 'left' ); ?>">
				<?php if ( '' !== $google_captcha_site_key ) : ?>
					<?php if ( 'v2-checkbox' === $recaptcha_version ) : ?>
						<div class='g-recaptcha' data-sitekey="<?php echo esc_attr( strval( $google_captcha_site_key ) ); ?>" style="margin-bottom: 2rem;"></div>
						<button style="width: <?php echo esc_attr( $full ? '100%;' : ';' ); ?>" type="submit" id="srfm-submit-btn" class="srfm-button">
						<div style="display: flex; gap: 6px; align-items: center;min-height:24px">
							<?php echo esc_html( $text ); ?>
							<div style="display: none;" class="srfm-loader"></div>
						</div>
					</button>
					<?php endif; ?>
					<?php if ( 'v3-reCAPTCHA' === $recaptcha_version ) : ?>
						<button style="width: <?php echo esc_attr( $full ? '100%;' : ';' ); ?>" type="submit" class="g-recaptcha srfm-button" recaptcha-type="<?php echo esc_attr( $recaptcha_version ); ?>" data-sitekey="<?php echo esc_attr( $google_captcha_site_key ); ?>" id="srfm-submit-btn">
							<div style="display: flex; gap: 6px; align-items: center;min-height:24px">
								<?php echo esc_html( $text ); ?>
								<div style="display: none;" class="srfm-loader"></div>
							</div>
						</button>
					<?php endif; ?>
					<?php if ( 'v2-invisible' === $recaptcha_version ) : ?>
						<button style="width: <?php echo esc_attr( $full ? '100%;' : ';' ); ?>" type="submit" class="srfm-button" recaptcha-type="<?php echo esc_attr( $recaptcha_version ); ?>" data-sitekey="<?php echo esc_attr( $google_captcha_site_key ); ?>" id="srfm-submit-btn">
							<div style="display: flex; gap: 6px; align-items: center;min-height:24px">
								<?php echo esc_html( $text ); ?>
								<div style="display: none;" class="srfm-loader"></div>
							</div>
						</button>
					<?php endif; ?>
					<?php endif; ?>
					<?php if ( 'none' === $recaptcha_version || '' === $recaptcha_version ) : ?>
							<button style="width: <?php echo esc_attr( $full ? '100%;' : ';' ); ?>;" type="submit" id="srfm-submit-btn" class="srfm-button">
							<div style="display: flex; gap: 6px; align-items: center;min-height:24px; justify-content: center">
								<?php echo esc_html( $text ); ?>
								<div style="display: none;" class="srfm-loader"></div>
							</div>
						</button>
					<?php endif; ?>
				</div>
			</div>
			<?php
		}
		return ob_get_clean();
	}

}
