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
	 * Return Phone codes
	 *
	 * @return mixed|array<mixed|string> $data with phone codes
	 */
	public function get_countries() {
		$file_path = plugin_dir_url( __FILE__ ) . 'countries.json';
		$response  = wp_remote_get( $file_path );
		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
			$json_string = wp_remote_retrieve_body( $response );
			$data        = json_decode( $json_string, true );
		} else {
			$data = array();
		}

		return $data;
	}
	/**
	 * Render the sureforms Address default styling block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function default_styling( $attributes ) {
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
			$block_id             = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';
			$help                 = isset( $attributes['help'] ) ? $attributes['help'] : '';

			$data = $this->get_countries();

		$output  = '';
		$output .= '
		<div class="srfm-address-container srfm-main-container srfm-frontend-inputs-holder ' . esc_attr( $classname ) . '" id="srfm-address-container-' . esc_attr( $block_id ) . '">
		<label class="srfm-text-primary">' . esc_html( $label ) . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '</label>
				<div					class="srfm-forms-address "				>
				<input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" type="hidden" id="srfm-fullAddress-' . esc_attr( $block_id ) . '" />
					<label
						class="srfm-text-primary"
						style="font-size: 14px;"
					>
					' . esc_html( $line_one_label ) . ' 
					</label>
					<input
						class="srfm-input-field"
						type="text"
						id="srfm-address-line-1-' . esc_attr( $block_id ) . '"
						aria-required=' . esc_attr( $required ? 'true' : 'false' ) . '
						placeholder="' . esc_attr( $line_one_placeholder ) . '"
					/>
				</div>
				<div
					class="srfm-forms-address "
				>
					<label
						class="srfm-text-primary"
						style="font-size: 14px;"
					>
					' . esc_html( $line_two_label ) . '
					</label>
					<input
						class="srfm-input-field"
						type="text"
						id="srfm-address-line-2-' . esc_attr( $block_id ) . '"
						placeholder="' . esc_attr( $line_two_placeholder ) . '"
					/>
				</div>
				<div class="srfm-adrs-select-holder">
					<div
						class="srfm-forms-address "
					>
						<label
							class="srfm-text-primary"
						>
						' . esc_html( $city_label ) . ' 
						</label>
						<input
							class="srfm-input-field"
							type="text"
							id="srfm-address-city-' . esc_attr( $block_id ) . '"
							aria-required=' . esc_attr( $required ? 'true' : 'false' ) . '
							placeholder="' . esc_attr( $city_placeholder ) . '"
						/>
					</div>
					<div class="srfm-forms-address "
					>
						<label
							class="srfm-text-primary"
						>
						' . esc_html( $state_label ) . ' 
						</label>
						<input
							type="text"
							class="srfm-input-field"
							id="srfm-address-state-' . esc_attr( $block_id ) . '"
							aria-required=' . esc_attr( $required ? 'true' : 'false' ) . '
							placeholder="' . esc_attr( $state_placeholder ) . '"
						/>
					</div>
				</div>
				<div class="srfm-adrs-select-holder">
					<div
						class="srfm-forms-address "
					>
						<label
							class="srfm-text-primary"
							style="font-size: 14px;"
						>
						' . esc_html( $postal_label ) . ' 
						</label>
						<input
							class="srfm-input-field"
							type="text"
							id="srfm-address-postal-' . esc_attr( $block_id ) . '"
							aria-required=' . esc_attr( $required ? 'true' : 'fasle' ) . '
							placeholder="' . esc_attr( $postal_placeholder ) . '"
						/>
					</div>
					<div
						class="srfm-forms-address "
					>
						<label
							class="srfm-text-primary"		
							style="font-size: 14px;"
						>
						' . esc_html( $country_label ) . '
						</label>
						<select 			
							class="srfm-input-field"
							id="srfm-address-country-' . esc_attr( $block_id ) . '">';

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
					</div>' .
					( '' !== $help ? '<label for="srfm-input-email" class="srfm-text-secondary srfm-helper-txt">' . esc_html( $help ) . '</label>' : '' ) .
					'<span style="display:none" class="srfm-error-message">' . esc_html( $error_msg ) . '</span>
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
			$required             = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$options              = isset( $attributes['options'] ) ? $attributes['options'] : '';
			$field_width          = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
			$label                = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help                 = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$error_msg            = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$line_one_placeholder = isset( $attributes['lineOnePlaceholder'] ) ? $attributes['lineOnePlaceholder'] : '';
			$line_two_placeholder = isset( $attributes['lineTwoPlaceholder'] ) ? $attributes['lineTwoPlaceholder'] : '';
			$city_placeholder     = isset( $attributes['cityPlaceholder'] ) ? $attributes['cityPlaceholder'] : '';
			$state_placeholder    = isset( $attributes['statePlaceholder'] ) ? $attributes['statePlaceholder'] : '';
			$postal_placeholder   = isset( $attributes['postalPlaceholder'] ) ? $attributes['postalPlaceholder'] : '';
			$country_placeholder  = isset( $attributes['countryPlaceholder'] ) ? $attributes['countryPlaceholder'] : '';
			$classname            = isset( $attributes['className'] ) ? $attributes['className'] : '';
			$block_id             = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';
			$help                 = isset( $attributes['help'] ) ? $attributes['help'] : '';

			$data = $this->get_countries();
		$output   = '';
		$output  .= '
		<div class="srfm-address-container srfm-main-container srfm-frontend-inputs-holder ' . esc_attr( $classname ) . '" id="srfm-address-container-' . esc_attr( $block_id ) . '"  style="width:calc(' . esc_attr( $field_width ) . '% - 20px);" > 
					<label for="srfm-address-line-1-' . esc_attr( $block_id ) . '" class="srfm-classic-label-text">' . esc_html( $label ) . ( $required && $label ? '<span class="srfm-text-red-500"> *</span>' : '' ) . '</label>
					<input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" type="hidden" id="srfm-fullAddress-' . esc_attr( $block_id ) . '" />
					<div class="srfm-mt-2">
						<input type="text" class=" srfm-classic-address-element !srfm-top-[3px] !srfm-rounded-t-md " id="srfm-address-line-1-' . esc_attr( $block_id ) . '"
						aria-required=' . esc_attr( $required ? 'true' : 'false' ) . '
						placeholder="' . esc_attr( $line_one_placeholder ) . '">
					</div>
					<div class="">
						<input type="text" class=" srfm-classic-address-element !srfm-top-[2px] " id="srfm-address-line-2-' . esc_attr( $block_id ) . '"
						placeholder="' . esc_attr( $line_two_placeholder ) . '">
					</div>
					<div class="srfm-flex -space-x-px">
						<div class="srfm-w-1/2 srfm-min-w-0 srfm-flex-1">
						<input type="text" class=" srfm-classic-address-element !srfm-top-[1px] " id="srfm-address-city-' . esc_attr( $block_id ) . '"
							aria-required=' . esc_attr( $required ? 'true' : 'false' ) . '
							placeholder="' . esc_attr( $city_placeholder ) . '">
						</div>
						<div class="srfm-min-w-0 srfm-flex-1">
						<input type="text" class=" srfm-classic-address-element !srfm-top-[1px] " id="srfm-address-state-' . esc_attr( $block_id ) . '"
							aria-required=' . esc_attr( $required ? 'true' : 'false' ) . '
							placeholder="' . esc_attr( $state_placeholder ) . '">
						</div>
					</div>
					<div class="-space-y-px srfm-rounded-md srfm-shadow-sm">
						<div>
							<label for="srfm-address-country-' . esc_attr( $block_id ) . '" class="srfm-sr-only">Country</label>
							<select id="srfm-address-country-' . esc_attr( $block_id ) . '" autocomplete="country-name" class="srfm-classic-adress-select">';

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
							<label for="srfm-address-postal-' . esc_attr( $block_id ) . '" class="srfm-sr-only">ZIP / Postal code</label>
							<input type="text" autocomplete="postal-code" class=" srfm-classic-address-element !srfm-rounded-b-md  " id="srfm-address-postal-' . esc_attr( $block_id ) . '"
							aria-required=' . esc_attr( $required ? 'true' : 'fasle' ) . '
							placeholder="' . esc_attr( $postal_placeholder ) . '">
						</div>
					</div>' .
					( '' !== $help ? '<p class="srfm-text-secondary srfm-helper-txt">' . esc_html( $help ) . '</p>' : '' ) .
					'<p style="display:none" class="srfm-error-message">' . esc_html( $error_msg ) . '</p>
				</div>
		';
		return $output;
	}

}
