<?php
/**
 * Tests for the Checkbox_Markup field class.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Fields\Checkbox_Markup;

class Test_Checkbox_Markup extends TestCase {

	protected $checkbox_markup;

	protected function setUp(): void {
		$attributes = [
			'required'     => true,
			'fieldWidth'   => '',
			'label'        => 'I agree to terms',
			'help'         => '',
			'block_id'     => 'cb001',
			'formId'       => '1',
			'slug'         => 'i-agree-to-terms',
			'placeholder'  => '',
			'defaultValue' => '',
			'checked'      => true,
			'isUnique'     => false,
			'errorMsg'     => 'You must agree.',
		];
		$this->checkbox_markup = new Checkbox_Markup( $attributes );
	}

	/**
	 * Test markup contains checkbox input with correct attributes.
	 */
	public function test_markup_contains_checkbox_input() {
		$markup = $this->checkbox_markup->markup();
		$this->assertStringContainsString( 'type="checkbox"', $markup );
		$this->assertStringContainsString( 'srfm-input-checkbox', $markup );
		$this->assertStringContainsString( 'srfm-checkbox-block', $markup );
		$this->assertStringContainsString( 'data-block-id="cb001"', $markup );
		$this->assertStringContainsString( 'data-required="true"', $markup );
		$this->assertStringContainsString( 'checked', $markup );
	}

	/**
	 * Test markup contains label text and required indicator.
	 */
	public function test_markup_contains_label_and_required_star() {
		$markup = $this->checkbox_markup->markup();
		$this->assertStringContainsString( 'I agree to terms', $markup );
		$this->assertStringContainsString( 'srfm-required', $markup );
		$this->assertStringContainsString( 'srfm-cbx', $markup );
		$this->assertStringContainsString( 'srfm-check-icon', $markup );
	}
}
