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

		// When auto country is enabled, detect the visitor's country via server-side
		// IP geolocation (ipapi.co) instead of a client-side fetch.
		//
		// Why not get_locale()?
		// WordPress get_locale() returns the *site's* configured language (e.g. 'en_US'),
		// not the visitor's physical location. A site set to English would show 'US' for
		// every visitor worldwide — defeating the purpose of auto-country detection.
		//
		// Why server-side instead of client-side?
		// The previous client-side fetch('https://ipapi.co/json') caused CORS failures,
		// 429 rate limits on high-traffic sites, and exposed visitor IPs to a third party
		// directly from the browser. Moving it server-side eliminates all three issues.
		//
		// Performance: The API is called only once per visitor IP and cached in a transient
		// for 24 hours — subsequent page loads for the same IP resolve instantly from cache.
		if ( $this->auto_country ) {
			$this->default_country = $this->get_geo_country();
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

	/**
	 * Detect the visitor's 2-letter country code via server-side IP geolocation.
	 *
	 * Calls ipapi.co once per visitor IP and caches the result in a transient for
	 * 24 hours so subsequent page loads resolve instantly without any API call.
	 *
	 * Failure responses (network error, non-200, malformed body, invalid country code)
	 * are also cached as 'us' for 1 hour to prevent a thundering-herd retry storm
	 * if ipapi.co goes down or rate-limits us.
	 *
	 * Private/reserved IPs (e.g., 10.x, 192.168.x, 127.0.0.1, ::1) are rejected up
	 * front — ipapi.co cannot geolocate them, and accepting them would let spoofed
	 * X-Forwarded-For headers flood the transient cache.
	 *
	 * A site-wide hourly cap (default 40, filterable via `srfm_geo_api_hourly_cap`)
	 * bounds outbound calls so a determined attacker can't exhaust the ipapi free-tier
	 * quota (1,000/day) by rotating spoofed public IPs.
	 *
	 * Note on page caching: When a full-page cache plugin is active, the HTML
	 * (including default-country) is served from cache. The first visitor's country
	 * is baked into the cached page. This is an acceptable tradeoff — the alternative
	 * (client-side fetch) caused CORS failures and 429 rate limits. Sites needing
	 * per-visitor precision can set a specific default country per field.
	 *
	 * @since 2.8.0
	 * @return string Lowercase 2-letter country code, defaults to 'us'.
	 */
	private function get_geo_country() {
		$ip = Helper::get_visitor_ip();
		if ( empty( $ip ) ) {
			return 'us';
		}

		// Reject private/reserved IPs: ipapi.co returns "Reserved IP Address" for them,
		// and accepting them would let spoofed X-Forwarded-For headers flood the cache.
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
			return 'us';
		}

		$cache_key = 'srfm_geo_' . md5( $ip );
		$cached    = get_transient( $cache_key );
		if ( is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		// Site-wide hourly cap on outbound ipapi calls. The counter rolls over every hour
		// (key includes YmdH) so we never need to explicitly reset it. Default 40 stays
		// well under ipapi's 1,000/day free tier; paid-tier sites can raise via filter.
		$quota_key = 'srfm_geo_quota_' . gmdate( 'YmdH' );
		$quota_cap = Helper::get_integer_value( apply_filters( 'srfm_geo_api_hourly_cap', 40 ) );
		$count     = Helper::get_integer_value( get_transient( $quota_key ) );
		if ( $count >= $quota_cap ) {
			set_transient( $cache_key, 'us', HOUR_IN_SECONDS );
			return 'us';
		}
		set_transient( $quota_key, $count + 1, HOUR_IN_SECONDS );

		// ipapi.co's /json/ endpoint geolocates the *caller's* IP. Since this request
		// originates from the WordPress server (not the visitor's browser), we must
		// pass the visitor's IP explicitly via /{ip}/json/ — otherwise ipapi.co
		// returns the hosting datacenter's country for every visitor.
		$url      = 'https://ipapi.co/' . rawurlencode( $ip ) . '/json/';
		$response = wp_remote_get(
			$url,
			[
				'timeout'    => 3,
				'user-agent' => 'SureForms/' . SRFM_VER . ' (+https://sureforms.com)',
			]
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			set_transient( $cache_key, 'us', HOUR_IN_SECONDS );
			return 'us';
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || empty( $body['country_code'] ) || ! is_string( $body['country_code'] ) ) {
			set_transient( $cache_key, 'us', HOUR_IN_SECONDS );
			return 'us';
		}

		$country = strtolower( $body['country_code'] );

		// Validate the external API response is a valid 2-letter country code.
		if ( ! preg_match( '/^[a-z]{2}$/', $country ) ) {
			set_transient( $cache_key, 'us', HOUR_IN_SECONDS );
			return 'us';
		}

		set_transient( $cache_key, $country, DAY_IN_SECONDS );

		return $country;
	}
}
