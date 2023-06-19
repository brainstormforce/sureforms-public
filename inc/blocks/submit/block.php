<?php
/**
 * PHP render form Submit Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Submit;

use SureForms\Inc\Blocks\Base;

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

		if ( ! empty( $attributes ) ) {
			$text             = isset( $attributes['text'] ) ? strval( $attributes['text'] ) : 'Submit';
			$full             = isset( $attributes['full'] ) ? $attributes['full'] : false;
			$button_alignment = isset( $attributes['buttonAlignment'] ) ? $attributes['buttonAlignment'] : '';

			$google_captcha_site_key = ! empty( get_option( 'recaptcha_site_key' ) ) ? get_option( 'recaptcha_site_key' ) : '';
			ob_start();
			?>
			<div id="sureforms-submit-container" style="display: flex; flex-direction: column; gap: 0.5rem;">
				<?php if ( '' !== $google_captcha_site_key && ! empty( get_option( 'recaptcha_secret_key' ) ) ) : ?>
					<div class='g-recaptcha' data-sitekey="<?php echo esc_attr( strval( $google_captcha_site_key ) ); ?>"></div>
				<?php endif; ?>
				<div style="width: <?php echo esc_attr( $full ? '100%;' : ';' ); ?> align-self: <?php echo esc_attr( 'right' === $button_alignment ? 'end' : $button_alignment ); ?>">
					<button style="width: <?php echo esc_attr( $full ? '100%;' : ';' ); ?>" type="submit" id="sureforms-submit-btn" class="sureforms-button">
						<div style="display: flex; gap: 6px; align-items: center;">
							<?php echo esc_html( $text ); ?>
							<div style="" class="sureforms-loader"></div>
						</div>
					</button>
				</div>
			</div>
			<?php
		}
		return ob_get_clean();
	}

}
