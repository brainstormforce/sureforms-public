<?php
/**
 * Sureforms Phone Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc\Fields;

use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sureforms_Phone_Markup Class.
 *
 * @since 0.0.1
 */
class Phone_Markup extends Base {
	/**
	 * Stores the boolean string indicating if the country should be automatically determined.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $auto_country;

	/**
	 * Stores the default country code when auto country is disabled.
	 *
	 * @var string
	 * @since 1.12.1
	 */
	protected $default_country;

	/**
	 * Enable country filter toggle.
	 *
	 * @var bool
	 * @since 2.3.0
	 */
	protected $enable_country_filter;

	/**
	 * Country filter type (include or exclude).
	 *
	 * @var string
	 * @since 2.3.0
	 */
	protected $country_filter_type;

	/**
	 * Array of country codes to include.
	 *
	 * @var array
	 * @since 2.3.0
	 */
	protected $include_countries;

	/**
	 * Array of country codes to exclude.
	 *
	 * @var array
	 * @since 2.3.0
	 */
	protected $exclude_countries;

	/**
	 * Initialize the properties based on block attributes.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @since 0.0.2
	 */
	public function __construct( $attributes ) {
		$this->set_properties( $attributes );
		$this->set_input_label( __( 'Phone', 'sureforms' ) );
		$this->set_error_msg( $attributes, 'srfm_phone_block_required_text' );
		$this->set_duplicate_msg( $attributes, 'srfm_phone_block_unique_text' );
		$this->slug                  = 'phone';
		$this->auto_country          = $attributes['autoCountry'] ?? '';
		$this->default_country       = $attributes['defaultCountry'] ?? '';
		$this->enable_country_filter = $attributes['enableCountryFilter'] ?? false;
		$this->country_filter_type   = $attributes['countryFilterType'] ?? 'include';
		$this->include_countries     = $attributes['includeCountries'] ?? [];
		$this->exclude_countries     = $attributes['excludeCountries'] ?? [];

		// When auto country is enabled, resolve a best-effort country here at render
		// time to seed the baked `default-country` attribute (a sensible flag before
		// any JS runs). The authoritative per-visitor detection happens client-side:
		// phone.js applies an immediate network-free Intl guess and then refines it
		// from the same-origin geo-country REST endpoint — so it stays correct even on
		// full-page-cached sites, where this baked value would otherwise be the first
		// visitor's country.
		//
		// Why not get_locale()?
		// WordPress get_locale() returns the *site's* configured language (e.g. 'en_US'),
		// not the visitor's physical location. A site set to English would show 'US' for
		// every visitor worldwide — defeating the purpose of auto-country detection.
		//
		// Why resolve server-side at all (vs. a browser geo-IP fetch)?
		// A client-side fetch('https://ipapi.co/json') caused CORS failures, 429 rate
		// limits on high-traffic sites, and exposed visitor IPs to a third party from the
		// browser. The server path (CDN header first, then a capped, cached ipapi.co
		// lookup in Helper::get_geo_country()) avoids all three.
		if ( $this->auto_country ) {
			// Pass the configured default as the fallback so a detection failure
			// degrades to the user's chosen country instead of a hardcoded 'us'.
			$fallback              = ! empty( $this->default_country ) && is_string( $this->default_country )
				? strtolower( $this->default_country )
				: 'us';
			$this->default_country = Helper::get_geo_country( $fallback );
		}
		$this->set_unique_slug();
		$this->set_field_name( $this->unique_slug );
		$this->set_markup_properties( $this->input_label, true );
		$this->set_aria_described_by();
		$this->set_label_as_placeholder( $this->input_label );
	}

	/**
	 * Render the sureforms phone classic styling
	 *
	 * @since 0.0.2
	 * @return string|bool
	 */
	public function markup() {
		ob_start(); ?>
		<div data-block-id="<?php echo esc_attr( $this->block_id ); ?>" class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $this->slug ); ?>-block srf-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?>-block<?php echo esc_attr( $this->block_width ); ?><?php echo esc_attr( $this->class_name ); ?> <?php echo esc_attr( $this->conditional_class ); ?>">
			<?php echo wp_kses_post( $this->label_markup ); ?>
			<?php echo wp_kses_post( $this->help_markup ); ?>
			<div class="srfm-block-wrap">
				<input type="tel"
					class="srfm-input-common srfm-input-<?php echo esc_attr( $this->slug ); ?>"
					name="<?php echo esc_attr( $this->field_name ); ?>"
					id="<?php echo esc_attr( $this->unique_slug ); ?>"
					<?php echo ! empty( $this->aria_described_by ) ? "aria-describedby='" . esc_attr( trim( $this->aria_described_by ) ) . "'" : ''; ?>
					data-required="<?php echo esc_attr( $this->data_require_attr ); ?>"
					aria-required="<?php echo esc_attr( $this->data_require_attr ); ?>"
					default-country="<?php echo esc_attr( $this->default_country ); ?>"
					<?php if ( $this->auto_country ) { ?>
						data-auto-country="true"
					<?php } ?>
					<?php if ( $this->enable_country_filter ) { ?>
						data-enable-country-filter="true"
						data-country-filter-type="<?php echo esc_attr( $this->country_filter_type ); ?>"
						<?php if ( 'include' === $this->country_filter_type && ! empty( $this->include_countries ) ) { ?>
							data-include-countries="<?php echo esc_attr( Helper::get_string_value( wp_json_encode( $this->include_countries ) ) ); ?>"
						<?php } elseif ( 'exclude' === $this->country_filter_type && ! empty( $this->exclude_countries ) ) { ?>
							data-exclude-countries="<?php echo esc_attr( Helper::get_string_value( wp_json_encode( $this->exclude_countries ) ) ); ?>"
						<?php } ?>
					<?php } ?>
					value="<?php echo esc_attr( $this->default ); ?>"
					<?php echo wp_kses_post( $this->placeholder_attr ); ?>
					data-unique="<?php echo esc_attr( $this->aria_unique ); ?>">
			</div>
			<div class="srfm-error-wrap">
				<?php echo wp_kses_post( $this->duplicate_msg_markup ); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

}
