<?php
/**
 * Tests for the Url_Markup field class.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Fields\Url_Markup;

class Test_Url_Markup extends TestCase {

	protected $url_markup;

	protected function setUp(): void {
		$attributes = [
			'required'     => true,
			'fieldWidth'   => '',
			'label'        => 'Website',
			'help'         => '',
			'block_id'     => 'url001',
			'formId'       => '1',
			'slug'         => 'website',
			'placeholder'  => 'https://example.com',
			'defaultValue' => '',
			'checked'      => '',
			'isUnique'     => false,
			'errorMsg'     => 'URL is required.',
			'readOnly'     => false,
		];
		$this->url_markup = new Url_Markup( $attributes );
	}

	/**
	 * Test markup contains url input with correct class and attributes.
	 */
	public function test_markup_contains_url_input() {
		$markup = $this->url_markup->markup();
		$this->assertStringContainsString( 'type="text"', $markup );
		$this->assertStringContainsString( 'srfm-input-url', $markup );
		$this->assertStringContainsString( 'srfm-url-block', $markup );
		$this->assertStringContainsString( 'data-block-id="url001"', $markup );
		$this->assertStringContainsString( 'data-required="true"', $markup );
	}

	/**
	 * Test markup with readonly and default value.
	 */
	public function test_markup_with_readonly_default_value() {
		$attributes = [
			'required'     => false,
			'fieldWidth'   => '',
			'label'        => 'Website',
			'help'         => '',
			'block_id'     => 'url002',
			'formId'       => '1',
			'slug'         => 'website',
			'placeholder'  => '',
			'defaultValue' => 'https://default.com',
			'checked'      => '',
			'isUnique'     => false,
			'errorMsg'     => '',
			'readOnly'     => true,
		];
		$url    = new Url_Markup( $attributes );
		$markup = $url->markup();
		$this->assertStringContainsString( 'readonly', $markup );
		$this->assertStringContainsString( 'value="https://default.com"', $markup );
		$this->assertStringContainsString( 'srfm-read-only', $markup );
	}
}
