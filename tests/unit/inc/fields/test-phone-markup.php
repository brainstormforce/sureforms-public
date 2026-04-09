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
		$test_ip                    = '127.0.0.1';
		$_SERVER['REMOTE_ADDR']     = $test_ip;
		set_transient( 'srfm_geo_' . md5( $test_ip ), 'in', DAY_IN_SECONDS );

		$phone  = new Phone_Markup( $this->get_auto_country_attributes() );
		$markup = $phone->markup();

		$this->assertStringContainsString( 'type="tel"', $markup );
		$this->assertStringContainsString( 'srfm-input-phone', $markup );
		$this->assertStringContainsString( 'auto-country="true"', $markup );
		$this->assertStringContainsString( 'default-country="in"', $markup );
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
