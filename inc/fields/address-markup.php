<?php
/**
 * Sureforms Address Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;

/**
 * Sureforms Address Markup Class.
 *
 * @since 0.0.1
 */
class Address_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms Address default styling block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function default_styling( $attributes ) {
			$id                   = isset( $attributes['id'] ) ? strval( $attributes['id'] ) : '';
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

			$file_path = plugin_dir_url( __FILE__ ) . '/countries.json';
			$response  = wp_remote_get( $file_path );
		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
			$json_string = wp_remote_retrieve_body( $response );
			$data        = json_decode( $json_string, true );
		} else {
			$data = array();
		}

		$output  = '';
		$output .= '
		<div class="sureforms-address-container main-container frontend-inputs-holder ' . esc_attr( $classname ) . '" id="sureforms-address-container-' . esc_attr( $id ) . '">
		<label class="sf-text-primary">' . esc_html( $label ) . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '</label>
				<div					class="sureforms-sureforms-address"				>
				<input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ) . '" type="hidden" id="fullAddress-' . esc_attr( $id ) . '" />
					<label
						class="sf-text-primary"
						style="font-size: 14px;"
					>
					' . esc_html( $line_one_label ) . ' 
					</label>
					<input
						class="sureforms-input-field"
						type="text"
						id="sureforms-address-line-1-' . esc_attr( $id ) . '"
						aria-required=' . esc_attr( $required ? 'true' : 'false' ) . '
						placeholder="' . esc_attr( $line_one_placeholder ) . '"
					/>
				</div>
				<div
					class="sureforms-sureforms-address"
				>
					<label
						class="sf-text-primary"
						style="font-size: 14px;"
					>
					' . esc_html( $line_two_label ) . '
					</label>
					<input
						class="sureforms-input-field"
						type="text"
						id="sureforms-address-line-2-' . esc_attr( $id ) . '"
						placeholder="' . esc_attr( $line_two_placeholder ) . '"
					/>
				</div>
				<div class="sureforms-adrs-select-holder">
					<div
						class="sureforms-sureforms-address"
					>
						<label
							class="sf-text-primary"
						>
						' . esc_html( $city_label ) . ' 
						</label>
						<input
							class="sureforms-input-field"
							type="text"
							id="sureforms-address-city-' . esc_attr( $id ) . '"
							aria-required=' . esc_attr( $required ? 'true' : 'false' ) . '
							placeholder="' . esc_attr( $city_placeholder ) . '"
						/>
					</div>
					<div class="sureforms-sureforms-address"
					>
						<label
							class="sf-text-primary"
						>
						' . esc_html( $state_label ) . ' 
						</label>
						<input
							type="text"
							class="sureforms-input-field"
							id="sureforms-address-state-' . esc_attr( $id ) . '"
							aria-required=' . esc_attr( $required ? 'true' : 'false' ) . '
							placeholder="' . esc_attr( $state_placeholder ) . '"
						/>
					</div>
				</div>
				<div class="sureforms-adrs-select-holder">
					<div
						class="sureforms-sureforms-address"
					>
						<label
							class="sf-text-primary"
							style="font-size: 14px;"
						>
						' . esc_html( $postal_label ) . ' 
						</label>
						<input
							class="sureforms-input-field"
							type="text"
							id="sureforms-address-postal-' . esc_attr( $id ) . '"
							aria-required=' . esc_attr( $required ? 'true' : 'fasle' ) . '
							placeholder="' . esc_attr( $postal_placeholder ) . '"
						/>
					</div>
					<div
						class="sureforms-sureforms-address"
					>
						<label
							class="sf-text-primary"		
							style="font-size: 14px;"
						>
						' . esc_html( $country_label ) . '
						</label>
						<select 			
							class="sureforms-input-field"
							id="sureforms-address-country-' . esc_attr( $id ) . '">';

		if ( ! empty( $country_placeholder ) ) :
			$output .= '<option value="" selected disabled hidden>' . esc_html( $country_placeholder ) . '</option>';
							endif;
		if ( is_array( $data ) ) {
			foreach ( $data as $country ) {
				if ( is_array( $country ) && isset( $country['name'] ) ) {
					$output .= '<option value="' . esc_attr( strval( $country['name'] ) ) . '">
										' . esc_html( strval( $country['name'] ) ) . '
									</option>';
				}
			}
		}
						$output .= '</select>
						</div>
					</div>
					<span style="display:none" class="error-message">' . esc_html( $error_msg ) . '</span>
				</div>
		';
		return $output;
	}

	/**
	 * Render the sureforms address classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function classic_styling( $attributes ) {
			$id                   = isset( $attributes['id'] ) ? strval( $attributes['id'] ) : '';
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

			$data      = [];
			$file_path = plugin_dir_url( __FILE__ ) . '/countries.json';
			$response  = wp_remote_get( $file_path );
		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
			$json_string = wp_remote_retrieve_body( $response );
			$data        = json_decode( $json_string, true );
		} else {
			$data = array();
		}
		$output  = '';
		$output .= '
		<div class="sureforms-address-container main-container frontend-inputs-holder ' . esc_attr( $classname ) . '" id="sfclassic-address-container-' . esc_attr( $id ) . '"> 
					<label for="text" class="sf-classic-label-text">' . esc_html( $label ) . ( $required && $label ? '<span class="text-red-500"> *</span>' : '' ) . '</label>
					<input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ) . '" type="hidden" id="fullAddress-' . esc_attr( $id ) . '" />
					<div class="mt-2">
						<input type="text" class=" sf-classic-adress-element !top-[3px] !rounded-t-md " id="sureforms-address-line-1-' . esc_attr( $id ) . '"
						aria-required=' . esc_attr( $required ? 'true' : 'false' ) . '
						placeholder="' . esc_attr( $line_one_placeholder ) . '">
					</div>
					<div class="">
						<input type="text" class=" sf-classic-adress-element !top-[2px] " id="sureforms-address-line-2-' . esc_attr( $id ) . '"
						placeholder="' . esc_attr( $line_two_placeholder ) . '">
					</div>
					<div class="flex -space-x-px">
						<div class="w-1/2 min-w-0 flex-1">
						<input type="text" class=" sf-classic-adress-element !top-[1px] " id="sureforms-address-city-' . esc_attr( $id ) . '"
							aria-required=' . esc_attr( $required ? 'true' : 'false' ) . '
							placeholder="' . esc_attr( $city_placeholder ) . '">
						</div>
						<div class="min-w-0 flex-1">
						<input type="text" class=" sf-classic-adress-element !top-[1px] " id="sureforms-address-state-' . esc_attr( $id ) . '"
							aria-required=' . esc_attr( $required ? 'true' : 'false' ) . '
							placeholder="' . esc_attr( $state_placeholder ) . '">
						</div>
					</div>
					<div class="-space-y-px rounded-md shadow-sm">
						<div>
							<label for="country" class="sr-only">Country</label>
							<select id="sureforms-address-country-' . esc_attr( $id ) . '" autocomplete="country-name" class="sf-classic-adress-select">';

		if ( ! empty( $country_placeholder ) ) :
			$output .= '<option value="" selected disabled hidden>' . esc_html( $country_placeholder ) . '</option>';
							endif;
		if ( is_array( $data ) ) {
			foreach ( $data as $country ) {
				if ( is_array( $country ) && isset( $country['name'] ) ) {
					$output .= '<option value="' . esc_attr( strval( $country['name'] ) ) . '">
										' . esc_html( strval( $country['name'] ) ) . '
									</option>';
				}
			}
		}

							$output .= '
							</select>
						</div>
						<div>
							<label for="postal-code" class="sr-only">ZIP / Postal code</label>
							<input type="text" autocomplete="postal-code" class=" sf-classic-adress-element !rounded-b-md  " id="sureforms-address-postal-' . esc_attr( $id ) . '"
							aria-required=' . esc_attr( $required ? 'true' : 'fasle' ) . '
							placeholder="' . esc_attr( $postal_placeholder ) . '">
						</div>
					</div>
					<p style="display:none" class="error-message">' . esc_html( $error_msg ) . '</p>
				</div>
		';
		return $output;
	}

}
