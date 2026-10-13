<?php
/**
 * Tests for the Base field class.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Fields\Base;
use SRFM\Inc\Helper;

class Test_Base extends TestCase {

	protected $base;

	protected function setUp(): void {
		$this->base = new Base();
	}

	/**
	 * Test that markup() returns empty string by default.
	 */
	public function test_markup_returns_empty_string() {
		$result = $this->base->markup();
		$this->assertSame( '', $result );
	}

	/**
	 * Test set_properties correctly sets common properties.
	 */
	public function test_set_properties_sets_common_attributes() {
		$attributes = [
			'required'     => true,
			'fieldWidth'   => '50',
			'label'        => 'Test Label',
			'help'         => 'Help text',
			'block_id'     => 'abc123',
			'formId'       => '42',
			'slug'         => 'test-slug',
			'placeholder'  => 'Enter value',
			'defaultValue' => 'default',
			'checked'      => true,
			'isUnique'     => true,
			'className'    => 'custom-class',
			'minValue'     => '2',
			'maxValue'     => '10',
		];

		$this->call_private_method( $this->base, 'set_properties', [ $attributes ] );

		$this->assertTrue( $this->get_private_property( $this->base, 'required' ) );
		$this->assertSame( '50', $this->get_private_property( $this->base, 'field_width' ) );
		$this->assertSame( 'Test Label', $this->get_private_property( $this->base, 'label' ) );
		$this->assertSame( 'Help text', $this->get_private_property( $this->base, 'help' ) );
		$this->assertSame( 'abc123', $this->get_private_property( $this->base, 'block_id' ) );
		$this->assertSame( '42', $this->get_private_property( $this->base, 'form_id' ) );
		$this->assertSame( 'test-slug', $this->get_private_property( $this->base, 'block_slug' ) );
		$this->assertSame( 'Enter value', $this->get_private_property( $this->base, 'placeholder' ) );
		$this->assertSame( 'default', $this->get_private_property( $this->base, 'default' ) );
		$this->assertSame( 'true', $this->get_private_property( $this->base, 'data_require_attr' ) );
		$this->assertStringContainsString( 'srfm-block-width-50', $this->get_private_property( $this->base, 'block_width' ) );
		$this->assertSame( 'checked', $this->get_private_property( $this->base, 'checked_attr' ) );
		$this->assertSame( 'true', $this->get_private_property( $this->base, 'aria_unique' ) );
		$this->assertSame( '2', $this->get_private_property( $this->base, 'min_selection' ) );
		$this->assertSame( '10', $this->get_private_property( $this->base, 'max_selection' ) );
	}

	/**
	 * Test set_input_label builds the -lbl- segment with Helper::encode().
	 *
	 * The label is carried inside the field name as unkeyed base64 (padding stripped),
	 * and the field name is the label segment plus the block slug.
	 */
	public function test_set_input_label() {
		$this->call_private_method( $this->base, 'set_properties', [
			[
				'label' => 'Full Name',
				'slug'  => 'name',
			],
		] );
		$this->call_private_method( $this->base, 'set_input_label', [ 'Fallback' ] );

		$this->assertSame( 'Full Name', $this->get_private_property( $this->base, 'input_label_fallback' ) );
		$this->assertSame( '-lbl-' . Helper::encode( 'Full Name' ), $this->get_private_property( $this->base, 'input_label' ) );
		$this->assertSame( '-lbl-' . Helper::encode( 'Full Name' ) . '-name', $this->get_private_property( $this->base, 'field_name' ) );

		// The encoding is plain base64 with padding stripped — no key, no integrity.
		$this->assertSame(
			'Full Name',
			Helper::decode( explode( '-', $this->get_private_property( $this->base, 'input_label' ) )[2] )
		);
	}

	/**
	 * Test set_input_label falls back to the passed value when no label is set.
	 */
	public function test_set_input_label_uses_fallback_without_label() {
		$this->call_private_method( $this->base, 'set_properties', [ [ 'slug' => 'payment' ] ] );
		$this->call_private_method( $this->base, 'set_input_label', [ 'Payment' ] );

		$this->assertSame( 'Payment', $this->get_private_property( $this->base, 'input_label_fallback' ) );
		$this->assertSame( '-lbl-' . Helper::encode( 'Payment' ), $this->get_private_property( $this->base, 'input_label' ) );
	}

	/**
	 * Test set_aria_described_by references only the help text, which is always rendered.
	 */
	public function test_set_aria_described_by() {
		$this->call_private_method( $this->base, 'set_properties', [ [ 'block_id' => 'b1' ] ] );
		$this->call_private_method( $this->base, 'set_aria_described_by' );
		$this->assertSame( '', trim( (string) $this->get_private_property( $this->base, 'aria_described_by' ) ) );

		$base = new Base();
		$this->call_private_method( $base, 'set_properties', [ [ 'block_id' => 'b1', 'help' => 'Some help' ] ] );
		$this->call_private_method( $base, 'set_aria_described_by' );
		$this->assertSame( 'srfm-description-b1', trim( $this->get_private_property( $base, 'aria_described_by' ) ) );
	}

	private function call_private_method( $object, $method_name, $parameters = [] ) {
		$reflection = new \ReflectionClass( get_class( $object ) );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $object, $parameters );
	}

	private function get_private_property( $object, $property_name ) {
		$reflection = new \ReflectionClass( get_class( $object ) );
		$property   = $reflection->getProperty( $property_name );
		$property->setAccessible( true );
		return $property->getValue( $object );
	}
}
