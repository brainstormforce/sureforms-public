<?php
/**
 * Tests for the GDPR_Markup field class.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Fields\GDPR_Markup;

class Test_GDPR_Markup extends TestCase {

	protected $gdpr_markup;

	protected function setUp(): void {
		$attributes = [
			'required'     => false,
			'fieldWidth'   => '',
			'label'        => 'I consent to data storage.',
			'help'         => '',
			'block_id'     => 'gdpr001',
			'formId'       => '1',
			'slug'         => 'gdpr-consent',
			'placeholder'  => '',
			'defaultValue' => '',
			'checked'      => false,
			'isUnique'     => false,
			'errorMsg'     => 'You must consent.',
		];
		$this->gdpr_markup = new GDPR_Markup( $attributes );
	}

	/**
	 * Test markup always shows required indicator (GDPR is always required).
	 */
	public function test_markup_is_always_required() {
		$markup = $this->gdpr_markup->markup();
		$this->assertStringContainsString( 'data-required="true"', $markup );
		$this->assertStringContainsString( 'aria-required="true"', $markup );
		$this->assertStringContainsString( 'srfm-required', $markup );
		$this->assertStringContainsString( 'type="checkbox"', $markup );
	}

	/**
	 * Test markup contains GDPR-specific elements and label.
	 */
	public function test_markup_contains_gdpr_elements() {
		$markup = $this->gdpr_markup->markup();
		$this->assertStringContainsString( 'srfm-gdpr-block', $markup );
		$this->assertStringContainsString( 'srfm-input-gdpr', $markup );
		$this->assertStringContainsString( 'I consent to data storage.', $markup );
		$this->assertStringContainsString( 'data-block-id="gdpr001"', $markup );
		$this->assertStringContainsString( 'srfm-cbx', $markup );
		$this->assertStringContainsString( 'srfm-check-icon', $markup );
	}
}
