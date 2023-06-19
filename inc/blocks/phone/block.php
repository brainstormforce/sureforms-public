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
			$id          = isset( $attributes['id'] ) ? strval( $attributes['id'] ) : '';
			$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
			$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help        = isset( $attributes['help'] ) ? $attributes['help'] : '';
			ob_start();
			?>
			<div class="sureforms-input-phone-container main-container" id="sureforms-input-phone-<?php echo esc_attr( $id ); ?>" style="display:flex; flex-direction:column; gap:0.5rem;">
				<label for="sureforms-input-phone"><?php echo esc_attr( $label ); ?>
					<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
				</label>
				<div style="display:flex; gap:.5rem">
				<input name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" type="hidden" id="fullPhoneNumber-<?php echo esc_attr( $id ); ?>" />
					<select id="sureforms-country-code-<?php echo esc_attr( $id ); ?>" style="width:fit-content;border: 2px solid #8c8f94" <?php echo esc_attr( $required ? 'required' : '' ); ?>>
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
					<input type="tel" <?php echo esc_attr( $required ? 'required' : '' ); ?> placeholder="<?php echo esc_attr( $placeholder ); ?>"
						id="sureforms-phone-number-<?php echo esc_attr( $id ); ?>"
						style="padding: 5px;
							line-height: 2;
							min-height: 30px;
							box-shadow: 0 0 0 transparent;
							border-radius: 4px;
							border: 2px solid #8c8f94;
							background-color: #fff;
							color: #2c3338;
						">
				</div>
			<?php echo '' !== $help ? '<label for="sureforms-input-phone" style="color:#ddd;">' . esc_attr( $help ) . '</label>' : ''; ?>
		</div>
			<?php
		}
			return ob_get_clean();
	}
}
