<?php
/**
 * Tests for the Phone_Markup field class.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Fields\Phone_Markup;

class Test_Phone_Markup extends TestCase {

	protected $phone_markup;

	protected function setUp(): void {
		$attributes = [
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
		$this->phone_markup = new Phone_Markup( $attributes );
	}

	/**
	 * Test markup contains telephone input with country attributes.
	 */
	public function test_markup_contains_tel_input_with_country() {
		$markup = $this->phone_markup->markup();
		$this->assertStringContainsString( 'type="tel"', $markup );
		$this->assertStringContainsString( 'srfm-input-phone', $markup );
		$this->assertStringContainsString( 'auto-country="true"', $markup );
		$this->assertStringContainsString( 'default-country="US"', $markup );
		$this->assertStringContainsString( 'data-block-id="phone001"', $markup );
		$this->assertStringContainsString( 'data-required="true"', $markup );
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
