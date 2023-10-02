<?php
/**
 * PHP render form Address Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Address;

use SureForms\Inc\Blocks\Base;
use SureForms\Inc\Sureforms_Helper;

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
		$sureforms_helper_instance = new Sureforms_Helper();

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
			$id                   = isset( $attributes['id'] ) ? $sureforms_helper_instance->get_string_value( $attributes['id'] ) : '';
			$required             = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$options              = isset( $attributes['options'] ) ? $attributes['options'] : '';
			$label                = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help                 = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$error_msg            = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$line_one_placeholder = isset( $attributes['lineOnePlaceholder'] ) ? $attributes['lineOnePlaceholder'] : '';
			$line_two_placeholder = isset( $attributes['lineTwoPlaceholder'] ) ? $attributes['lineTwoPlaceholder'] : '';
			$city_placeholder     = isset( $attributes['cityPlaceholder'] ) ? $attributes['cityPlaceholder'] : '';
			$state_placeholder    = isset( $attributes['statePlaceholder'] ) ? $attributes['statePlaceholder'] : '';
			$postal_placeholder   = isset( $attributes['postalPlaceholder'] ) ? $attributes['postalPlaceholder'] : '';
			$line_one_label       = isset( $attributes['lineOneLabel'] ) ? $attributes['lineOneLabel'] : '';
			$line_two_label       = isset( $attributes['lineTwoLabel'] ) ? $attributes['lineTwoLabel'] : '';
			$city_label           = isset( $attributes['cityLabel'] ) ? $attributes['cityLabel'] : '';
			$state_label          = isset( $attributes['stateLabel'] ) ? $attributes['stateLabel'] : '';
			$postal_label         = isset( $attributes['postalLabel'] ) ? $attributes['postalLabel'] : '';
			$country_label        = isset( $attributes['countryLabel'] ) ? $attributes['countryLabel'] : '';
			$country_placeholder  = isset( $attributes['countryPlaceholder'] ) ? $attributes['countryPlaceholder'] : '';
			$classname            = isset( $attributes['className'] ) ? $attributes['className'] : '';
			ob_start(); ?>
			<!-- <div class="sureforms-address-container main-container frontend-inputs-holder <?php echo esc_attr( $classname ); ?>" id="sureforms-address-container-<?php echo esc_attr( $id ); ?>">
				<label class="text-primary"><?php echo esc_html( $label ); ?> 
					<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
				</label>
				<div
					class="sureforms-sureforms-address"
				>
				<input name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" type="hidden" id="fullAddress-<?php echo esc_attr( $id ); ?>" />
					<label
						class="text-primary"
						style="font-size: 14px;"
					>
					<?php echo esc_html( $line_one_label ); ?> 
					</label>
					<input
						class="sureforms-input-field"
						type="text"
						id="sureforms-address-line-1-<?php echo esc_attr( $id ); ?>"
						area-required=<?php echo esc_attr( $required ? 'true' : 'false' ); ?>
						placeholder="<?php echo esc_attr( $line_one_placeholder ); ?>"
					/>
				</div>
				<div
					class="sureforms-sureforms-address"
				>
					<label
						class="text-primary"
						style="font-size: 14px;"
					>
					<?php echo esc_html( $line_two_label ); ?> 
					</label>
					<input
						class="sureforms-input-field"
						type="text"
						id="sureforms-address-line-2-<?php echo esc_attr( $id ); ?>"
						placeholder="<?php echo esc_attr( $line_two_placeholder ); ?>"
					/>
				</div>
				<div class="sureforms-adrs-select-holder">
					<div
						class="sureforms-sureforms-address"
					>
						<label
							class="text-primary"
						>
						<?php echo esc_html( $city_label ); ?> 
						</label>
						<input
							class="sureforms-input-field"
							type="text"
							id="sureforms-address-city-<?php echo esc_attr( $id ); ?>"
							area-required=<?php echo esc_attr( $required ? 'true' : 'false' ); ?>
							placeholder="<?php echo esc_attr( $city_placeholder ); ?>"
						/>
					</div>
					<div class="sureforms-sureforms-address"
					>
						<label
							class="text-primary"
						>
						<?php echo esc_html( $state_label ); ?> 
						</label>
						<input
							type="text"
							class="sureforms-input-field"
							id="sureforms-address-state-<?php echo esc_attr( $id ); ?>"
							area-required=<?php echo esc_attr( $required ? 'true' : 'false' ); ?>
							placeholder="<?php echo esc_attr( $state_placeholder ); ?>"
						/>
					</div>
				</div>
				<div class="sureforms-adrs-select-holder">
					<div
						class="sureforms-sureforms-address"
					>
						<label
							class="text-primary"
							style="font-size: 14px;"
						>
						<?php echo esc_html( $postal_label ); ?> 
						</label>
						<input
							class="sureforms-input-field"
							type="text"
							id="sureforms-address-postal-<?php echo esc_attr( $id ); ?>"
							area-required=<?php echo esc_attr( $required ? 'true' : 'fasle' ); ?>
							placeholder="<?php echo esc_attr( $postal_placeholder ); ?>"
						/>
					</div>
					<div
						class="sureforms-sureforms-address"
					>
						<label
							class="text-primary"		
							style="font-size: 14px;"
						>
						<?php echo esc_html( $country_label ); ?>
						</label>
						<select 			
							class="sureforms-input-field"
							id="sureforms-address-country-<?php echo esc_attr( $id ); ?>">
							<?php if ( ! empty( $country_placeholder ) ) : ?>
								<option value="" selected disabled hidden><?php echo esc_html( $country_placeholder ); ?></option>
							<?php endif; ?>
							<?php foreach ( $data as $country ) { ?>
								<?php if ( is_array( $country ) && isset( $country['name'] ) ) { ?>
									<option value="<?php echo esc_attr( $sureforms_helper_instance->get_string_value( $country['name'] ) ); ?>">
										<?php echo esc_html( $sureforms_helper_instance->get_string_value( $country['name'] ) ); ?>
									</option>
								<?php } ?>
							<?php } ?>
						</select>
						</div>
					</div>
					<span style="display:none" class="error-message"><?php echo esc_html( $error_msg ); ?></span>
				</div> -->
				<div class="sureforms-address-container main-container frontend-inputs-holder  <?php echo esc_attr( $classname ); ?>" id="sfclassic-address-container-<?php echo esc_attr( $id ); ?>"> 
					<label for="text" class="sf-classic-label-text"><?php echo esc_html( $label ); ?> <?php echo $required && $label ? '<span class="text-red-500"> *</span>' : ''; ?></label>
					<input name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" type="hidden" id="fullAddress-<?php echo esc_attr( $id ); ?>" />
					<div class="mt-2">
						<input type="text" class=" sf-classic-adress-element !top-[3px] !rounded-t-md " id="sureforms-address-line-1-<?php echo esc_attr( $id ); ?>"
						area-required=<?php echo esc_attr( $required ? 'true' : 'false' ); ?>
						placeholder="<?php echo esc_attr( $line_one_placeholder ); ?>">
					</div>
					<div class="">
						<input type="text" class=" sf-classic-adress-element !top-[2px] " id="sureforms-address-line-2-<?php echo esc_attr( $id ); ?>"
						placeholder="<?php echo esc_attr( $line_two_placeholder ); ?>">
					</div>
					<div class="flex -space-x-px">
						<div class="w-1/2 min-w-0 flex-1">
						<input type="text" class=" sf-classic-adress-element !top-[1px] " id="sureforms-address-city-<?php echo esc_attr( $id ); ?>"
							area-required=<?php echo esc_attr( $required ? 'true' : 'false' ); ?>
							placeholder="<?php echo esc_attr( $city_placeholder ); ?>">
						</div>
						<div class="min-w-0 flex-1">
						<input type="text" class=" sf-classic-adress-element !top-[1px] " id="sureforms-address-state-<?php echo esc_attr( $id ); ?>"
							area-required=<?php echo esc_attr( $required ? 'true' : 'false' ); ?>
							placeholder="<?php echo esc_attr( $state_placeholder ); ?>">
						</div>
					</div>
					<div class="-space-y-px rounded-md shadow-sm">
						<div>
							<label for="country" class="sr-only">Country</label>
							<select id="sureforms-address-country-<?php echo esc_attr( $id ); ?>" autocomplete="country-name" class="sf-classic-adress-select">
							<?php if ( ! empty( $country_placeholder ) ) : ?>
								<option value="" selected disabled hidden><?php echo esc_html( $country_placeholder ); ?></option>
							<?php endif; ?>
							<?php foreach ( $data as $country ) { ?>
								<?php if ( is_array( $country ) && isset( $country['name'] ) ) { ?>
									<option value="<?php echo esc_attr( $sureforms_helper_instance->get_string_value( $country['name'] ) ); ?>">
										<?php echo esc_html( $sureforms_helper_instance->get_string_value( $country['name'] ) ); ?>
									</option>
								<?php } ?>
							<?php } ?>
							</select>
						</div>
						<div>
							<label for="postal-code" class="sr-only">ZIP / Postal code</label>
							<input type="text" autocomplete="postal-code" class=" sf-classic-adress-element !rounded-b-md  " id="sureforms-address-postal-<?php echo esc_attr( $id ); ?>"
							area-required=<?php echo esc_attr( $required ? 'true' : 'fasle' ); ?>
							placeholder="<?php echo esc_attr( $postal_placeholder ); ?>">
						</div>
					</div>
					<p style="display:none" class="error-message"><?php echo esc_html( $error_msg ); ?></p>
				</div>
			<?php
		}
			return ob_get_clean();
	}
}
