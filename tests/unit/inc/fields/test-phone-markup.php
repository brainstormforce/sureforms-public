<?php
/**
 * Tests for the Phone_Markup field class.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Fields\Phone_Markup;

class Test_Phone_Markup extends TestCase {

	/**
	 * Get default attributes for auto-country phone field tests.
	 *
	 * @return array
	 */
	private function get_auto_country_attributes() {
		return [
			'required'            => true,
			'fieldWidth'          => '',
			'label'               => 'Phone Number',
			'help'                => '',
			'block_id'            => 'phone001',
			'formId'              => '1',
			'slug'                => 'phone-number',
			'placeholder'         => '',
			'defaultValue'        => '',
			'checked'             => '',
			'isUnique'            => false,
			'autoCountry'         => true,
			'defaultCountry'      => 'US',
			'enableCountryFilter' => false,
			'countryFilterType'   => 'include',
			'includeCountries'    => [],
			'excludeCountries'    => [],
			'errorMsg'            => 'Phone is required.',
			'duplicateMsg'        => '',
		];
	}

	/**
	 * Test markup contains telephone input with country attributes.
	 */
	public function test_markup_contains_tel_input_with_country() {
		// Pre-seed the geolocation transient to avoid a real API call in tests.
		// Use a non-reserved, non-private public IP (RFC 5737 TEST-NET-3 range)
		// so it passes the FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE guard.
		$test_ip                    = '203.0.113.1';
		$_SERVER['REMOTE_ADDR']     = $test_ip;
		set_transient( 'srfm_geo_' . md5( $test_ip ), 'in', DAY_IN_SECONDS );

		$phone  = new Phone_Markup( $this->get_auto_country_attributes() );
		$markup = $phone->markup();

		$this->assertStringContainsString( 'type="tel"', $markup );
		$this->assertStringContainsString( 'srfm-input-phone', $markup );
		$this->assertStringContainsString( 'default-country="in"', $markup );
		// The auto-country attribute is no longer emitted — frontend JS reads default-country directly.
		$this->assertStringNotContainsString( 'auto-country=', $markup );
		$this->assertStringContainsString( 'data-block-id="phone001"', $markup );
		$this->assertStringContainsString( 'data-required="true"', $markup );

		// Clean up.
		delete_transient( 'srfm_geo_' . md5( $test_ip ) );
	}

	/**
	 * Test that auto country falls back to 'us' when IP is unavailable.
	 */
	public function test_auto_country_falls_back_to_us_on_unknown_ip() {
		unset( $_SERVER['REMOTE_ADDR'] );

		$attributes                = $this->get_auto_country_attributes();
		$attributes['block_id']    = 'phone003';
		$attributes['defaultCountry'] = 'GB';

		$phone  = new Phone_Markup( $attributes );
		$markup = $phone->markup();

		$this->assertStringContainsString( 'default-country="us"', $markup );
	}

	/**
	 * Test that cached transient is used on subsequent requests for the same IP.
	 */
	public function test_auto_country_uses_cached_transient() {
		$test_ip                = '203.0.113.45';
		$_SERVER['REMOTE_ADDR'] = $test_ip;
		$cache_key              = 'srfm_geo_' . md5( $test_ip );

		// Seed transient with 'de' (Germany).
		set_transient( $cache_key, 'de', DAY_IN_SECONDS );

		$phone  = new Phone_Markup( $this->get_auto_country_attributes() );
		$markup = $phone->markup();

		$this->assertStringContainsString( 'default-country="de"', $markup );

		// Clean up.
		delete_transient( $cache_key );
	}

	/**
	 * Test that when autoCountry is disabled, the provided defaultCountry is used as-is.
	 */
	public function test_manual_country_uses_provided_default() {
		$attributes                 = $this->get_auto_country_attributes();
		$attributes['autoCountry']  = false;
		$attributes['defaultCountry'] = 'GB';
		$attributes['block_id']     = 'phone004';

		$phone  = new Phone_Markup( $attributes );
		$markup = $phone->markup();

		$this->assertStringContainsString( 'default-country="GB"', $markup );
		// The auto-country attribute is no longer emitted — frontend JS reads default-country directly.
		$this->assertStringNotContainsString( 'auto-country=', $markup );
	}

	/**
	 * Test markup with country filter enabled.
	 */
	public function test_markup_with_country_filter_enabled() {
		$attributes = [
			'required'            => false,
			'fieldWidth'          => '',
			'label'               => 'Phone',
			'help'                => '',
			'block_id'            => 'phone002',
			'formId'              => '1',
			'slug'                => 'phone',
			'placeholder'         => '',
			'defaultValue'        => '',
			'checked'             => '',
			'isUnique'            => false,
			'autoCountry'         => false,
			'defaultCountry'      => '',
			'enableCountryFilter' => true,
			'countryFilterType'   => 'include',
			'includeCountries'    => [ 'US', 'CA', 'GB' ],
			'excludeCountries'    => [],
			'errorMsg'            => '',
			'duplicateMsg'        => '',
		];
		$phone  = new Phone_Markup( $attributes );
		$markup = $phone->markup();
		$this->assertStringContainsString( 'data-enable-country-filter="true"', $markup );
		$this->assertStringContainsString( 'data-country-filter-type="include"', $markup );
		$this->assertStringContainsString( 'data-include-countries=', $markup );
	}
}
