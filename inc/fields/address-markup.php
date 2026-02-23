<?php
/**
 * Sureforms Address Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sureforms Address Markup Class.
 *
 * @since 0.0.1
 */
class Address_Markup extends Base {
	/**
	 * Whether Google autocomplete is enabled for this field.
	 *
	 * @var bool
	 * @since x.x.x
	 */
	protected $enable_autocomplete = false;

	/**
	 * Whether to show interactive map preview.
	 *
	 * @var bool
	 * @since x.x.x
	 */
	protected $show_map = false;

	/**
	 * Initialize the properties based on block attributes.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @since 0.0.2
	 */
	public function __construct( $attributes ) {
		$this->set_properties( $attributes );
		$this->set_input_label( __( 'Address', 'sureforms' ) );
		$this->slug = 'address';
		$this->set_markup_properties();

		// Autocomplete attributes.
		$this->enable_autocomplete = ! empty( $attributes['enableAutocomplete'] );
		$this->show_map            = ! empty( $attributes['showMap'] );
	}

	/**
	 * Render the sureforms address classic styling
	 *
	 * @param string $content inner block content.
	 * @since 0.0.2
	 * @return string|bool
	 */
	public function markup( $content = '' ) {
		$extra_classes  = [];
		$google_api_key = '';

		if ( $this->enable_autocomplete ) {
			// Static cache: get_option is already object-cached by WordPress per-request,
			// but a static variable avoids repeated array lookups when multiple address blocks render.
			static $cached_api_key = null;
			if ( null === $cached_api_key ) {
				$google_maps_settings = get_option( 'srfm_google_maps_settings', [] );
				$google_maps_settings = is_array( $google_maps_settings ) ? $google_maps_settings : [];
				$cached_api_key       = isset( $google_maps_settings['srfm_google_maps_api_key'] )
					? strval( $google_maps_settings['srfm_google_maps_api_key'] )
					: '';
			}
			$google_api_key = $cached_api_key;

			if ( ! empty( $google_api_key ) ) {
				$extra_classes[] = 'srfm-address-autocomplete-block';
			}
		}

		$this->class_name     = $this->get_field_classes( $extra_classes );
		$autocomplete_enabled = $this->enable_autocomplete && ! empty( $google_api_key );

		ob_start(); ?>
			<div data-block-id="<?php echo esc_attr( $this->block_id ); ?>" class="<?php echo esc_attr( $this->class_name ); ?>" data-slug="<?php echo esc_attr( $this->block_slug ); ?>"
			<?php
			if ( $autocomplete_enabled ) {
				?>
				data-autocomplete="true" data-show-map="<?php echo esc_attr( $this->show_map ? 'true' : 'false' ); ?>" data-api-key="<?php echo esc_attr( $google_api_key ); ?>"
				<?php
			}
			?>
			>
				<fieldset>
					<legend class="srfm-block-legend">
						<?php echo wp_kses_post( $this->label_markup ); ?>
						<?php echo wp_kses_post( $this->help_markup ); ?>
					</legend>
					<?php if ( $autocomplete_enabled ) { ?>
					<div class="srfm-address-autocomplete-search-wrap">
						<input
							type="text"
							class="srfm-input-address-autocomplete srfm-input-common"
							placeholder="<?php echo esc_attr__( 'Start typing an address...', 'sureforms' ); ?>"
							autocomplete="off"
						/>
					</div>
					<?php } ?>
					<div class="srfm-block-wrap">
					<?php
						// phpcs:ignore
						echo $content;
						// phpcs:ignoreEnd
					?>
					</div>
					<?php if ( $autocomplete_enabled ) { ?>
						<?php
						/*
						* These hidden fields are populated client-side by address-autocomplete.js
						* when a user selects a place or drags the map marker.
						*
						* - srfm-address-structured-*: Full parsed address as JSON (street, city, state, postal, country, lat, lng, place_id).
						* - srfm-address-place-id-*:   Google Place ID of the selected location.
						* - srfm-address-lat-*:         Latitude in decimal degrees.
						* - srfm-address-lng-*:         Longitude in decimal degrees.
						*
						* These are intentionally not processed by process_form_fields() (which handles
						* labelled block fields). They are available as raw POST values for third-party
						* integrations via srfm_before_fields_processing or srfm_update_prepared_submission_data.
						* Future: extend prepare_submission_data() when geocoding-aware integrations are needed.
						*/
						?>
					<input type="hidden" class="srfm-address-autocomplete-structured" name="srfm-address-structured-<?php echo esc_attr( $this->block_id ); ?>" value="" />
					<input type="hidden" class="srfm-address-autocomplete-place-id" name="srfm-address-place-id-<?php echo esc_attr( $this->block_id ); ?>" value="" />
					<input type="hidden" class="srfm-address-autocomplete-lat" name="srfm-address-lat-<?php echo esc_attr( $this->block_id ); ?>" value="" />
					<input type="hidden" class="srfm-address-autocomplete-lng" name="srfm-address-lng-<?php echo esc_attr( $this->block_id ); ?>" value="" />
						<?php if ( $this->show_map ) { ?>
					<div class="srfm-address-autocomplete-map-preview" style="display:none; height:200px;"></div>
						<?php } ?>
					<?php } ?>
				</fieldset>
			</div>
		<?php
		return ob_get_clean();
	}
}
