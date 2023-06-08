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
			$success_message      = get_post_meta( intval( $id ), '_sureforms_thankyou_message', true ) ? strval( get_post_meta( intval( $id ), '_sureforms_thankyou_message', true ) ) : '';
			ob_start();
			?>
				<form method="post" action="../../sureforms-submit.php" id="sureforms-form-<?php echo esc_attr( $id ); ?>" class="sureforms-form <?php echo esc_attr( '' !== $background_image_url ? 'sureforms-form-background' : '' ); ?>"
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
			<script type="text/javascript">
				<?php if ( isset( $form_path ) && 'form' !== $form_path ) { ?>
					document.addEventListener('DOMContentLoaded', function() {
					// Capture the form submission event
					var form = document.querySelector('#sureforms-form-<?php echo esc_attr( $id ); ?>');
					form.addEventListener('submit', function(e) {
						e.preventDefault(); // Prevent the default form submission

						// Get the form data
						var formData = new FormData(form);
						var serializedData = new URLSearchParams(formData).toString();

						// Make an AJAX request to the API endpoint
						var xhr = new XMLHttpRequest();
						xhr.open('POST', '/wp-json/sureforms/v1/submit-form', true);
						xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
						document.querySelector(".sureforms-loader").removeAttribute("style");
						xhr.onload = function() {
						if (xhr.status >= 200 && xhr.status < 400) {
							// Handle the successful response
						document.querySelector(".sureforms-loader").setAttribute("style","display: none");
							var response = JSON.parse(xhr.responseText);
							document.querySelector("#sureforms-success-message").removeAttribute("hidden");
							setTimeout(() => {
								document.querySelector("#sureforms-success-message").setAttribute("hidden","true");
							}, 2000);
						} else {
							// Handle the error response
							document.querySelector("#sureforms-error-message").removeAttribute("hidden");
							console.error('Error:', xhr.statusText);
						}
						};
						xhr.onerror = function() {
						// Handle the network error
						document.querySelector("#sureforms-error-message").removeAttribute("hidden");
						console.error('Network Error');
						};
						xhr.send(serializedData);
					});
					});
				<?php } ?>
			</script>
		<?php
		return ob_get_clean();
	}
}
