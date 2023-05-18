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
			$required = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$options  = isset( $attributes['options'] ) ? $attributes['options'] : '';
			$label    = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help     = isset( $attributes['help'] ) ? $attributes['help'] : '';
			ob_start(); ?>
			<div id="sureform-address-container">
				<label for="sureform-address"><?php echo esc_attr( $label ); ?> 
					<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
				</label>
				<div
					id="sureform-address"
					style="display: flex; flex-direction: column; gap: .5px;"
				>
					<input
						style="padding: 0 8px; 
						line-height: 2; 
						min-height: 30px;
						box-shadow: 0 0 0 transparent;
						border-radius: 4px;
						border: 1px solid #8c8f94;
						background-color: #fff;
						color: #2c3338;
						"
						type="text"
						id="address-line-1"
						required="<?php echo esc_attr( $required ? 'true' : 'false' ); ?>"
					/>
					<label
						style="color: #737373; font-size: 14px;"
						for="address-line-1"
					>
						Address Line 1
					</label>
				</div>
				<div
					style="display: flex; flex-direction: column; gap: .5px;"
				>
					<input
						style="padding: 0 8px; 
						line-height: 2; 
						min-height: 30px;
						box-shadow: 0 0 0 transparent;
						border-radius: 4px;
						border: 1px solid #8c8f94;
						background-color: #fff;
						color: #2c3338;
						"
						type="text"
						id="address-line-2" 
						required="<?php echo esc_attr( $required ? 'true' : 'false' ); ?>"
					/>
					<label
						style="color: #737373; font-size: 14px;"
						for="address-line-2"
					>
						Address Line 2
					</label>
				</div>
				<div style="display: flex; gap: 1rem;">
					<div
						style="display: flex; flex-direction: column; gap: .5px; width: 100%;"
					>
						<input
							style="padding: 0 8px; 
								line-height: 2; 
								min-height: 30px;
								box-shadow: 0 0 0 transparent;
								border-radius: 4px;
								border: 1px solid #8c8f94;
								background-color: #fff;
								color: #2c3338;
							"
							type="text"
							id="address-city"
							required="<?php echo esc_attr( $required ? 'true' : 'false' ); ?>"
						/>
						<label
							style="color: #737373; font-size: 14px;"
							for="address-city"
						>
							City
						</label>
					</div>
					<div style="display: flex; flex-direction: column; gap: .5px; width: 100%;"
					>
						<input
							type="text"
							style="padding: 0 8px; 
							line-height: 2; 
							min-height: 30px;
							box-shadow: 0 0 0 transparent;
							border-radius: 4px;
							border: 1px solid #8c8f94;
							background-color: #fff;
							color: #2c3338;
							"
							id="address-state"
							required="<?php echo esc_attr( $required ? 'true' : 'false' ); ?>"
						/>
						<label
							style="color: #737373; font-size: 14px;"
							for="address-state"
						>
							State / Province / Region
						</label>
					</div>
				</div>
				<div style="display: flex; gap: 1rem;">
					<div
						style="display: flex; flex-direction: column; gap: .5px; width: 100%;"
					>
						<input
							style="padding: 0 8px; 
							line-height: 2; 
							min-height: 30px;
							box-shadow: 0 0 0 transparent;
							border-radius: 4px;
							border: 1px solid #8c8f94;
							background-color: #fff;
							color: #2c3338;
							"
							type="text"
							id="address-city"
							required="<?php echo esc_attr( $required ? 'true' : 'false' ); ?>"
						/>
						<label
							style="color: #737373; font-size: 14px;"
							for="address-city"
						>
							Postal Code
						</label>
					</div>
					<div
						style="display: flex; flex-direction: column; gap: .5px; width: 100%;"
					>
						<select 			
							style="padding: 0 8px; 
								line-height: 2; 
								min-height: 30px;
								box-shadow: 0 0 0 transparent;
								border-radius: 4px;
								border: 1px solid #8c8f94;
								background-color: #fff;
								color: #2c3338;
							"
							id="sureform-phone-code">
							<?php foreach ( $data as $country ) { ?>
								<?php if ( is_array( $country ) && isset( $country['name'] ) ) { ?>
									<option value="<?php echo esc_attr( strval( $country['name'] ) ); ?>">
										<?php echo esc_html( strval( $country['name'] ) ); ?>
									</option>
								<?php } ?>
							<?php } ?>
						</select>
						<label
							style="color: #737373; font-size: 14px;"
							htmlFor="address-country"
						>
							Country
						</label>
							</div>
						</div>
				</div>
			<?php
		}
			return ob_get_clean();
	}
}
