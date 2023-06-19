<?php
/**
 * PHP render Form Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Form;

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
	 * @since X.X.X
	 * @return string|boolean
	 */
	public function render( $attributes, $content = '' ) {
		$id = '';
		if ( ! empty( $attributes ) && ! empty( $content ) ) {
			$id                   = isset( $attributes['id'] ) ? strval( $attributes['id'] ) : '';
			$color_primary        = get_post_meta( intval( $id ), '_sureforms_color1', true ) ? strval( get_post_meta( intval( $id ), '_sureforms_color1', true ) ) : '';
			$color_secondary      = get_post_meta( intval( $id ), '_sureforms_color2', true ) ? strval( get_post_meta( intval( $id ), '_sureforms_color2', true ) ) : '';
			$background_image_url = get_post_meta( intval( $id ), '_sureforms_bg', true ) ? rawurldecode( strval( get_post_meta( intval( $id ), '_sureforms_bg', true ) ) ) : '';
			$form_font_size       = get_post_meta( intval( $id ), '_sureforms_fontsize', true ) ? get_post_meta( intval( $id ), '_sureforms_fontsize', true ) : '';
			$success_submit_type  = get_post_meta( intval( $id ), '_sureforms_submit_type', true ) ? strval( get_post_meta( intval( $id ), '_sureforms_submit_type', true ) ) : '';
			$success_message      = get_post_meta( intval( $id ), '_sureforms_thankyou_message', true ) ? strval( get_post_meta( intval( $id ), '_sureforms_thankyou_message', true ) ) : '';
			$success_url          = get_post_meta( intval( $id ), '_sureforms_submit_url', true ) ? strval( get_post_meta( intval( $id ), '_sureforms_submit_url', true ) ) : '';
			ob_start();
			?>
				<form method="post" id="sureforms-form-<?php echo esc_attr( $id ); ?>" class="sureforms-form <?php echo esc_attr( '' !== $background_image_url ? 'sureforms-form-background' : '' ); ?>"
				style="background-image: url('<?php echo esc_url( $background_image_url ); ?>'); padding: 2rem; font-size:<?php echo esc_attr( $form_font_size . 'px;' ); ?> ">
				<?php
					wp_nonce_field( 'sureforms-form-submit', 'sureforms_form_submit' );
					$honeypot_spam = get_option( 'honeypot' );
				?>
				<input type="hidden" value="<?php echo esc_attr( $id ); ?>" name="form-id">
				<?php if ( '1' === $honeypot_spam ) : ?>
					<input type="hidden" value="" name="sureforms-honeypot-field">
				<?php endif; ?>
				<?php
					// phpcs:ignore
					echo $content;
					// phpcs:ignoreEnd
				?>
			</form>
			<h2 id="sureforms-success-message" hidden="true" style="text-align:center"><?php echo esc_html( $success_message ); ?></h2>
			<h2 id="sureforms-error-message" hidden="true" style="text-align:center"><?php echo esc_attr__( 'Error Submiting Form', 'sureforms' ); ?></h2>
			<?php
			$page_url  = $_SERVER['REQUEST_URI'];
			$path      = strval( wp_parse_url( $page_url, PHP_URL_PATH ) );
			$segments  = explode( '/', $path );
			$form_path = isset( $segments[1] ) ? $segments[1] : '';
		}

		?>
			<style>
				:root {
					--secondary-color: 
					<?php
						// @phpstan-ignore-next-line
						echo esc_attr( $color_secondary );
					?>
						;
					--primary-color: 
					<?php
						// @phpstan-ignore-next-line
						echo esc_attr( $color_primary );
					?>
					;
				}
			</style>
			<?php
			if ( isset( $form_path ) && 'form' !== $form_path ) {
					wp_enqueue_script( 'form-submit', SUREFORMS_URL . 'inc/form-submit.js', [], SUREFORMS_VER, true );
					$success_submit_type = isset( $success_submit_type ) ? $success_submit_type : 'message';
					$success_url         = isset( $success_url ) ? $success_url : '/';
					wp_localize_script(
						'form-submit',
						'formSubmitData',
						array(
							'successSubmitType' => $success_submit_type,
							'successUrl'        => $success_url,
							'formID'            => $id,
						)
					);
			}
			?>
		<?php
		return ob_get_clean();
	}
}
