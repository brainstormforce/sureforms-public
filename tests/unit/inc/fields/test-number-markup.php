<?php
/**
 * Tests for the Number_Markup field class.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Fields\Number_Markup;

class Test_Number_Markup extends TestCase {

	protected $number_markup;

	protected function setUp(): void {
		$attributes = [
			'required'     => true,
			'fieldWidth'   => '',
			'label'        => 'Quantity',
			'help'         => '',
			'block_id'     => 'num001',
			'formId'       => '1',
			'slug'         => 'quantity',
			'placeholder'  => '0',
			'defaultValue' => '5',
			'checked'      => '',
			'isUnique'     => false,
			'minValue'     => '1',
			'maxValue'     => '100',
			'formatType'   => '',
			'prefix'       => '$',
			'suffix'       => 'USD',
			'errorMsg'     => 'Required',
			'readOnly'     => false,
		];
		$this->number_markup = new Number_Markup( $attributes );
	}

	/**
	 * Test markup contains number input with min/max attributes.
	 */
	public function test_markup_contains_number_input_with_constraints() {
		$markup = $this->number_markup->markup();
		$this->assertStringContainsString( 'srfm-input-number', $markup );
		$this->assertStringContainsString( 'min="1"', $markup );
		$this->assertStringContainsString( 'max="100"', $markup );
		$this->assertStringContainsString( 'value="5"', $markup );
		$this->assertStringContainsString( 'data-block-id="num001"', $markup );
	}

	/**
	 * Test markup contains prefix and suffix elements.
	 */
	public function test_markup_contains_prefix_and_suffix() {
		$markup = $this->number_markup->markup();
		$this->assertStringContainsString( 'srfm-number-prefix', $markup );
		$this->assertStringContainsString( '$', $markup );
		$this->assertStringContainsString( 'srfm-number-suffix', $markup );
		$this->assertStringContainsString( 'USD', $markup );
	}
}
