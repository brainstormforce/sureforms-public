<?php
/**
 * PHP render form Phone Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Phone;

use SureForms\Inc\Blocks\Base;

/**
 * Address Block.
 */
class Block extends Base {
	/**
	 * Render the block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param string       $content Post content.
	 *
	 * @return string|boolean
	 */
	public function render( $attributes, $content = '' ) {
		$upload_dir = wp_upload_dir();
		$file_path  = plugin_dir_url( __FILE__ ) . '/phone_codes.json';
		$response   = wp_remote_get( $file_path );

		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
			$json_string = wp_remote_retrieve_body( $response );
			$data        = json_decode( $json_string, true );
		} else {
			$data = array();
		}

		if ( ! empty( $attributes ) ) {
			$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
			$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help        = isset( $attributes['help'] ) ? $attributes['help'] : '';
			ob_start();
			?>
			<div class="sureform-input-phone-container" style="display:flex; flex-direction:column; gap:0.5rem;">
				<label for="sureform-input-phone"><?php echo esc_attr( $label ); ?>
					<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
				</label>
				<div style="display:flex; gap:.5rem">
					<select id="sureform-phone-code" style="width:fit-content;">
						<?php
						if ( is_array( $data ) ) {
							foreach ( $data as $country ) {
								if ( isset( $country['code'] ) && isset( $country['dial_code'] ) ) {
									?>
						<option value="<?php echo esc_attr( $country['dial_code'] ); ?>"><?php echo esc_html( $country['code'] . ' ' . $country['dial_code'] ); ?></option>
									<?php
								}
							}
						}
						?>
					</select>
					<input id="sureform-input-phone" type="tel" required="<?php echo esc_attr( $required ? 'true' : 'false' ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>"
						style="padding: 0 8px;
							line-height: 2;
							min-height: 30px;
							box-shadow: 0 0 0 transparent;
							border-radius: 4px;
							border: 1px solid #8c8f94;
							background-color: #fff;
							color: #2c3338;
						">
				</div>
			<?php echo '' !== $help ? '<label for="sureform-input-phone" style="color:#ddd;">' . esc_attr( $help ) . '</label>' : ''; ?>
		</div>
			<?php
		}
			return ob_get_clean();
	}
}
