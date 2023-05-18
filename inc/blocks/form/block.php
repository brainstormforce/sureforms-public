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
	 * @return string|boolean
	 */
	public function render( $attributes, $content = '' ) {
		$id = '';

		if ( ! empty( $attributes ) && ! empty( $content ) ) {
			$id = isset( $attributes['id'] ) ? strval( $attributes['id'] ) : '';

			ob_start();
			?>
			<form method="post" id="sureforms-form-<?php echo esc_attr( $id ); ?>" class="sureforms-form" style="display:flex; flex-direction:column; gap:2rem">
			<input type="hidden" value="<?php echo esc_attr( $id ); ?>" name="form-id">
				<?php
					// phpcs:ignore
					echo $content;
					// phpcs:ignoreEnd
				?>
			</form>
			<h2 id="sureforms-success-message" hidden="true" style="text-align:center">Form Submitted Successfully</h2>
			<h2 id="sureforms-error-message" hidden="true" style="text-align:center">Error Submiting Successfully</h2>
			<?php
		}

		?>
			<script type="text/javascript">
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
						// console.log('API response:', response);
						// You can perform additional actions here, such as showing a success message or redirecting the user
					} else {
						// Handle the error response
						document.querySelector("#sureforms-error-message").removeAttribute("hidden");
						console.error('Error:', xhr.statusText);
						// You can display an error message or take appropriate action
					}
					};
					xhr.onerror = function() {
					// Handle the network error
					document.querySelector("#sureforms-error-message").removeAttribute("hidden");
					console.error('Network Error');
					// You can display an error message or take appropriate action
					};
					xhr.send(serializedData);
				});
				});
			</script>
		<?php
		return ob_get_clean();
	}
}
