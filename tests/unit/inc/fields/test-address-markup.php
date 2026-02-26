<?php
/**
 * Tests for the Address_Markup field class.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Fields\Address_Markup;

class Test_Address_Markup extends TestCase {

	protected $address_markup;

	protected function setUp(): void {
		$attributes = [
			'required'     => false,
			'fieldWidth'   => '',
			'label'        => 'Mailing Address',
			'help'         => 'Enter your full address',
			'block_id'     => 'addr001',
			'formId'       => '1',
			'slug'         => 'mailing-address',
			'placeholder'  => '',
			'defaultValue' => '',
			'checked'      => '',
			'isUnique'     => false,
		];
		$this->address_markup = new Address_Markup( $attributes );
	}

	/**
	 * Test markup contains fieldset and legend structure.
	 */
	public function test_markup_contains_fieldset_structure() {
		$markup = $this->address_markup->markup();
		$this->assertStringContainsString( '<fieldset>', $markup );
		$this->assertStringContainsString( '<legend', $markup );
		$this->assertStringContainsString( 'srfm-block-legend', $markup );
		$this->assertStringContainsString( 'data-block-id="addr001"', $markup );
		$this->assertStringContainsString( 'data-slug="mailing-address"', $markup );
	}

	/**
	 * Test markup includes inner block content.
	 */
	public function test_markup_includes_inner_content() {
		$inner_content = '<div class="inner-field">Street Line 1</div>';
		$markup        = $this->address_markup->markup( $inner_content );
		$this->assertStringContainsString( $inner_content, $markup );
		$this->assertStringContainsString( 'srfm-block-wrap', $markup );
	}
}
