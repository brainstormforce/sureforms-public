<?php
/**
 * Sureforms Address Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc\Fields;

use SRFM\Inc\Traits\SRFM_Get_Instance;
use SRFM\Inc\SRFM_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sureforms Address Markup Class.
 *
 * @since 0.0.1
 */
class SRFM_Address_Markup extends Base {
	use SRFM_Get_Instance;

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
			$data = [];
		}

		return $data;
	}

	/**
	 * Render the sureforms address classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param int|string   $form_id form id.
	 *
	 * @return string|boolean
	 */
	public function markup( $attributes, $form_id ) {
			$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$field_width = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
			$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help        = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$error_msg   = isset( $attributes['errorMsg'] ) && $attributes['errorMsg'] ? $attributes['errorMsg'] : SRFM_Helper::get_default_dynamic_block_option( 'srfm_address_block_required_text' );

			$line_one_placeholder = isset( $attributes['lineOnePlaceholder'] ) ? $attributes['lineOnePlaceholder'] : '';
			$line_two_placeholder = isset( $attributes['lineTwoPlaceholder'] ) ? $attributes['lineTwoPlaceholder'] : '';
			$city_placeholder     = isset( $attributes['cityPlaceholder'] ) ? $attributes['cityPlaceholder'] : '';
			$state_placeholder    = isset( $attributes['statePlaceholder'] ) ? $attributes['statePlaceholder'] : '';
			$postal_placeholder   = isset( $attributes['postalPlaceholder'] ) ? $attributes['postalPlaceholder'] : '';
			$country_placeholder  = isset( $attributes['countryPlaceholder'] ) ? $attributes['countryPlaceholder'] : '';
			$class_name           = isset( $attributes['className'] ) ? ' ' . $attributes['className'] : '';
			$block_id             = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';
			$help                 = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$slug                 = 'address';

			$block_width = $field_width ? ' srfm-block-width-' . str_replace( '.', '-', $field_width ) : '';

			// html attributes.
			$line_one_placeholder_attr = $line_one_placeholder ? ' placeholder="' . esc_attr( $line_one_placeholder ) . '" ' : '';
			$line_two_placeholder_attr = $line_two_placeholder ? ' placeholder="' . esc_attr( $line_two_placeholder ) . '" ' : '';
			$city_placeholder_attr     = $city_placeholder ? ' placeholder="' . esc_attr( $city_placeholder ) . '" ' : '';
			$state_placeholder_attr    = $state_placeholder ? ' placeholder="' . esc_attr( $state_placeholder ) . '" ' : '';
			$postal_placeholder_attr   = $postal_placeholder ? ' placeholder="' . esc_attr( $postal_placeholder ) . '" ' : '';
			$input_label_fallback      = $label ? $label : __( 'Address', 'sureforms' );
			$input_label               = '-lbl-' . SRFM_Helper::encrypt( $input_label_fallback );

			$aria_require_attr = $required ? 'true' : 'false';

			$data = $this->get_countries();

		ob_start(); ?>
		<div class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block srf-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-block<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $class_name ); ?>">
			<?php echo wp_kses_post( SRFM_Helper::generate_common_form_markup( $form_id, 'label', $label, $slug, $block_id, boolval( $required ) ) ); ?>
			<input class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>-hidden" type="hidden" name="srfm-<?php echo esc_attr( $slug ); ?>-hidden-<?php echo esc_attr( $block_id ); ?><?php echo esc_attr( $input_label ); ?>"/>
			<div class="srfm-block-wrap">
				<input class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>-line-1" type="text" name="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-line-1" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>" <?php echo wp_kses_post( $line_one_placeholder_attr ); ?> />	
				<input class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>-line-2" type="text" name="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-line-2" <?php echo wp_kses_post( $line_two_placeholder_attr ); ?> />	
				<input class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>-city" type="text" name="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-city" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>" <?php echo wp_kses_post( $city_placeholder_attr ); ?> />	
				<input class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>-state" type="text" name="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-state" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>" <?php echo wp_kses_post( $state_placeholder_attr ); ?> />	

				<?php
				if ( is_array( $data ) ) {
					?>
				<div class="srfm-address-country-wrap srfm-dropdown-common-wrap">
					<select class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>-country srfm-dropdown-common" autocomplete="country-name" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>" aria-hidden="true">
					<?php if ( $country_placeholder ) { ?>
							<option value="" selected disabled hidden><?php echo esc_attr( $country_placeholder ); ?></option>
						<?php } ?>
					<?php
					foreach ( $data as $country ) {
						if ( is_array( $country ) && isset( $country['name'] ) ) {
							?>
						<option value="<?php echo esc_attr( strval( $country['name'] ) ); ?>"><?php echo esc_html( strval( $country['name'] ) ); ?></option>
								<?php
						}
					}
					?>
					</select>
				</div>
					<?php
				}
				?>
				<input class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>-postal-code" autocomplete="postal-code" type="text" name="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-postal-code" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>" <?php echo wp_kses_post( $postal_placeholder_attr ); ?> />	
			</div>
			<?php echo wp_kses_post( SRFM_Helper::generate_common_form_markup( $form_id, 'help', '', '', '', false, $help ) ); ?>
			<?php echo wp_kses_post( SRFM_Helper::generate_common_form_markup( $form_id, 'error', '', '', '', boolval( $required ), '', $error_msg ) ); ?>
		</div>
		<?php

		return ob_get_clean();

	}

}
