<?php
/**
 * Tests for the Dropdown_Markup field class.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Fields\Dropdown_Markup;

class Test_Dropdown_Markup extends TestCase {

	protected $dropdown_markup;

	protected function setUp(): void {
		$attributes = [
			'required'            => true,
			'fieldWidth'          => '',
			'label'               => 'Select Country',
			'help'                => '',
			'block_id'            => 'dd001',
			'formId'              => '1',
			'slug'                => 'select-country',
			'placeholder'         => 'Select an option',
			'defaultValue'        => '',
			'checked'             => '',
			'isUnique'            => false,
			'options'             => [
				[ 'label' => 'USA', 'icon' => '' ],
				[ 'label' => 'Canada', 'icon' => '' ],
				[ 'label' => 'UK', 'icon' => '' ],
			],
			'multiSelect'         => false,
			'searchable'          => false,
			'preselectedOptions'  => [],
			'errorMsg'            => 'Please select an option.',
			'minValue'            => '',
			'maxValue'            => '',
		];
		$this->dropdown_markup = new Dropdown_Markup( $attributes );
	}

	/**
	 * Test markup contains select element with options.
	 */
	public function test_markup_contains_select_with_options() {
		$markup = $this->dropdown_markup->markup();
		$this->assertStringContainsString( '<select', $markup );
		$this->assertStringContainsString( 'srfm-dropdown-input', $markup );
		$this->assertStringContainsString( 'data-block-id="dd001"', $markup );
		$this->assertStringContainsString( 'USA', $markup );
		$this->assertStringContainsString( 'Canada', $markup );
		$this->assertStringContainsString( 'UK', $markup );
		$this->assertStringContainsString( '<fieldset>', $markup );
	}

	/**
	 * Test data_attribute_markup returns empty for non-multiselect.
	 */
	public function test_data_attribute_markup_empty_for_single_select() {
		$result = $this->call_private_method( $this->dropdown_markup, 'data_attribute_markup' );
		$this->assertSame( '', $result );
	}

	private function call_private_method( $object, $method_name, $parameters = [] ) {
		$reflection = new \ReflectionClass( get_class( $object ) );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $object, $parameters );
	}
}
