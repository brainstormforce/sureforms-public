<?php
/**
 * PHP render form Address Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Address;

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
		$file_path  = plugin_dir_url( __FILE__ ) . '/country.json';
		$response   = wp_remote_get( $file_path );

		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
			$json_string = wp_remote_retrieve_body( $response );
			$data        = (array) json_decode( $json_string, true );
		} else {
			$data = array();
		}

		if ( ! empty( $attributes ) ) {
			$id       = isset( $attributes['id'] ) ? strval( $attributes['id'] ) : '';
			$required = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$options  = isset( $attributes['options'] ) ? $attributes['options'] : '';
			$label    = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help     = isset( $attributes['help'] ) ? $attributes['help'] : '';
			ob_start(); ?>
			<div class="sureforms-address-container" id="sureforms-address-container-<?php echo esc_attr( $id ); ?>" style="display: flex; flex-direction: column; gap: .5rem; ">
				<label for="sureforms-address-<?php echo esc_attr( $id ); ?>"><?php echo esc_attr( $label ); ?> 
					<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
				</label>
				<div style="display: flex; flex-direction: column; gap: .5px;">
					<input name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" type="hidden" id="fullAddress-<?php echo esc_attr( $id ); ?>" />
					<input style="padding: 0 8px; line-height: 2; min-height: 30px; box-shadow: 0 0 0 transparent;border-radius: 4px; border: 1px solid #8c8f94; background-color: #fff; color: #2c3338;" type="text"
					id="sureforms-address-line-1-<?php echo esc_attr( $id ); ?>" <?php echo esc_attr( $required ? 'required' : '' ); ?> />
					<label style="color: #737373;"> Address Line 1</label>
				</div>
				<div style="display: flex; flex-direction: column; gap: .5px;">
					<input name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" type="hidden" id="fullAddress-<?php echo esc_attr( $id ); ?>" />
						<input style="padding: 0 8px; line-height: 2; min-height: 30px; box-shadow: 0 0 0 transparent;border-radius: 4px; border: 1px solid #8c8f94; background-color: #fff; color: #2c3338;" type="text"
						id="sureforms-address-line-2-<?php echo esc_attr( $id ); ?>" <?php echo esc_attr( $required ? 'required' : '' ); ?> />
					<label style="color: #737373;"> Address Line 2</label>
				</div>
				<div style="display: flex; gap: 1rem;  width: 100%;">
					<div style="display: flex; flex-direction: column; gap: .5px; width: 50%;">
						<input style="padding: 0 8px; line-height: 2; min-height: 30px; box-shadow: 0 0 0 transparent; border-radius: 4px; border: 1px solid #8c8f94; background-color: #fff; color: #2c3338;" type="text" id="sureforms-address-city-<?php echo esc_attr( $id ); ?>"
						<?php echo esc_attr( $required ? 'required' : '' ); ?> />
						<label style="color: #737373;">City</label>
					</div>
					<div style="display: flex; flex-direction: column; gap: .5px; width: 50%;">
						<input style="padding: 0 8px; line-height: 2; min-height: 30px; box-shadow: 0 0 0 transparent; border-radius: 4px; border: 1px solid #8c8f94; background-color: #fff; color: #2c3338;" type="text" id="sureforms-address-city-<?php echo esc_attr( $id ); ?>" <?php echo esc_attr( $required ? 'required' : '' ); ?> />
						<label class="text-size" style="color: #737373;">State / Province / Region</label>
					</div>
					</div>
				</div>
				<div style="display: flex; gap: 1rem;">
					<div style="display: flex; flex-direction: column; gap: .5px; width: 100%;">
						<input style="padding: 0 8px; line-height: 2; min-height: 30px; box-shadow: 0 0 0 transparent;border-radius: 4px; border: 1px solid #8c8f94; background-color: #fff; color: #2c3338;"
						type="text" class="sureforms-address-postal" <?php echo esc_attr( $required ? 'required' : '' ); ?> />
					<label class="text-size" style="color: #737373;">Postal Code</label>
				</div>
				<div style="display: flex; flex-direction: column; gap: .5px; width: 100%;">
					<select style="padding: 0 8px; line-height: 2; min-height: 30px; box-shadow: 0 0 0 transparent; border-radius: 4px; border: 1px solid #8c8f94; background-color: #fff; color: #2c3338;" id="sureforms-address-country-<?php echo esc_attr( $id ); ?>">
						<?php foreach ( $data as $country ) { ?>
							<?php if ( is_array( $country ) && isset( $country['name'] ) ) { ?>
								<option value="<?php echo esc_attr( strval( $country['name'] ) ); ?>">
									<?php echo esc_html( strval( $country['name'] ) ); ?>
								</option>
							<?php } ?>
						<?php } ?>
					</select>
					<label class="text-size" style="color: #737373;">Country</label>
				</div>
			</div>
			<?php
		}
			return ob_get_clean();
	}
}
