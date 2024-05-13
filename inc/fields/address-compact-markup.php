<?php
/**
 * Sureforms Address Compact Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sureforms Address Compact Markup Class.
 *
 * @since 0.0.1
 */
class Address_Compact_Markup extends Base {

	/**
	 * Stores the placeholder text for the first line of the address input field.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $line_one_placeholder;

	/**
	 * Stores the placeholder text for the second line of the address input field.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $line_two_placeholder;

	/**
	 * Stores the placeholder text for the city input field.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $city_placeholder;

	/**
	 * Stores the placeholder text for the state input field.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $state_placeholder;

	/**
	 * Stores the placeholder text for the postal code input field.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $postal_placeholder;

	/**
	 * Stores the placeholder text for the country input field.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $country_placeholder;

	/**
	 * HTML attribute string for the first line of the address input field.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $line_one_placeholder_attr;

	/**
	 * HTML attribute string for the second line of the address input field.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $line_two_placeholder_attr;

	/**
	 * HTML attribute string for the city input field.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $city_placeholder_attr;

	/**
	 * HTML attribute string for the state input field.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $state_placeholder_attr;

	/**
	 * HTML attribute string for the postal code input field.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $postal_placeholder_attr;

	/**
	 * Stores the data returned by the get_countries function.
	 *
	 * @var mixed|array<mixed|string>
	 * @since 0.0.2
	 */
	protected $data;

	/**
	 * Initialize the properties based on block attributes.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @since 0.0.2
	 */
	public function __construct( $attributes ) {
		$this->set_properties( $attributes );
		$this->set_input_label( __( 'Address', 'sureforms' ) );
		$this->set_error_msg( $attributes, 'srfm_address_compact_block_required_text' );
		$this->slug                 = 'address-compact';
		$this->line_one_placeholder = isset( $attributes['lineOnePlaceholder'] ) ? $attributes['lineOnePlaceholder'] : '';
		$this->line_two_placeholder = isset( $attributes['lineTwoPlaceholder'] ) ? $attributes['lineTwoPlaceholder'] : '';
		$this->city_placeholder     = isset( $attributes['cityPlaceholder'] ) ? $attributes['cityPlaceholder'] : '';
		$this->state_placeholder    = isset( $attributes['statePlaceholder'] ) ? $attributes['statePlaceholder'] : '';
		$this->postal_placeholder   = isset( $attributes['postalPlaceholder'] ) ? $attributes['postalPlaceholder'] : '';
		$this->country_placeholder  = isset( $attributes['countryPlaceholder'] ) ? $attributes['countryPlaceholder'] : '';

		// html attributes.
		$this->line_one_placeholder_attr = $this->line_one_placeholder ? ' placeholder="' . esc_attr( $this->line_one_placeholder ) . '" ' : '';
		$this->line_two_placeholder_attr = $this->line_two_placeholder ? ' placeholder="' . esc_attr( $this->line_two_placeholder ) . '" ' : '';
		$this->city_placeholder_attr     = $this->city_placeholder ? ' placeholder="' . esc_attr( $this->city_placeholder ) . '" ' : '';
		$this->state_placeholder_attr    = $this->state_placeholder ? ' placeholder="' . esc_attr( $this->state_placeholder ) . '" ' : '';
		$this->postal_placeholder_attr   = $this->postal_placeholder ? ' placeholder="' . esc_attr( $this->postal_placeholder ) . '" ' : '';

		$this->data = $this->get_countries();
		$this->set_markup_properties();
	}

	/**
	 * Return Phone codes
	 *
	 * @since 0.0.2
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
	 * Render the sureforms address compact styling
	 *
	 * @since 0.0.2
	 * @return string|boolean
	 */
	public function markup() {
		ob_start(); ?>
		<div data-block-id="<?php echo esc_attr( $this->block_id ); ?>" class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $this->slug ); ?>-block srf-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?>-block<?php echo esc_attr( $this->block_width ); ?><?php echo esc_attr( $this->class_name ); ?> <?php echo esc_attr( $this->conditional_class ); ?>">
			<?php echo wp_kses_post( $this->label_markup ); ?>
			<input class="srfm-input-common srfm-input-<?php echo esc_attr( $this->slug ); ?>-hidden" type="hidden" name="srfm-<?php echo esc_attr( $this->slug ); ?>-hidden-<?php echo esc_attr( $this->block_id ); ?><?php echo esc_attr( $this->field_name ); ?>"/>
			<div class="srfm-block-wrap">
				<input class="srfm-input-common srfm-input-<?php echo esc_attr( $this->slug ); ?>-line-1" type="text" name="srfm-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?>-line-1" aria-required="<?php echo esc_attr( $this->aria_require_attr ); ?>" <?php echo wp_kses_post( $this->line_one_placeholder_attr ); ?> />
				<input class="srfm-input-common srfm-input-<?php echo esc_attr( $this->slug ); ?>-line-2" type="text" name="srfm-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?>-line-2" <?php echo wp_kses_post( $this->line_two_placeholder_attr ); ?> />
				<input class="srfm-input-common srfm-input-<?php echo esc_attr( $this->slug ); ?>-city" type="text" name="srfm-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?>-city" aria-required="<?php echo esc_attr( $this->aria_require_attr ); ?>" <?php echo wp_kses_post( $this->city_placeholder_attr ); ?> />
				<input class="srfm-input-common srfm-input-<?php echo esc_attr( $this->slug ); ?>-state" type="text" name="srfm-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?>-state" aria-required="<?php echo esc_attr( $this->aria_require_attr ); ?>" <?php echo wp_kses_post( $this->state_placeholder_attr ); ?> />

				<?php
				if ( is_array( $this->data ) ) {
					?>
				<div class="srfm-<?php echo esc_attr( $this->slug ); ?>-country-wrap srfm-dropdown-common-wrap">
					<select class="srfm-input-common srfm-input-<?php echo esc_attr( $this->slug ); ?>-country srfm-dropdown-common" autocomplete="country-name" aria-required="<?php echo esc_attr( $this->aria_require_attr ); ?>" aria-hidden="true">
					<?php if ( $this->country_placeholder ) { ?>
							<option value="" selected disabled hidden><?php echo esc_attr( $this->country_placeholder ); ?></option>
						<?php } ?>
					<?php
					foreach ( $this->data as $country ) {
						if ( is_array( $country ) && isset( $country['name'] ) ) {
							?>
						<option value="<?php echo esc_attr( strval( $country['name'] ) ); ?>"><?php echo esc_html( strval( $country['name'] ) ); ?></option>
							<?php
						}
					}
					?>
					</select>
				</div>
				<?php } ?>
				<input class="srfm-input-common srfm-input-<?php echo esc_attr( $this->slug ); ?>-postal-code" autocomplete="postal-code" type="text" name="srfm-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?>-postal-code" aria-required="<?php echo esc_attr( $this->aria_require_attr ); ?>" <?php echo wp_kses_post( $this->postal_placeholder_attr ); ?> />
			</div>
			<?php echo wp_kses_post( $this->help_markup ); ?>
			<?php echo wp_kses_post( $this->error_msg_markup ); ?>
		</div>
		<?php

		return ob_get_clean();

	}

}
