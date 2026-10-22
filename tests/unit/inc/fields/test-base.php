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

	/**
	 * The description only renders once the block opts in, so an option array
	 * saved before the setting existed renders exactly as it did before.
	 */
	public function test_get_option_description() {
		$option = [ 'optionTitle' => 'Early Bird', 'description' => '  $149 until 1 March  ' ];

		// Opted out: the stored description is ignored.
		$this->assertSame( '', $this->call_private_method( $this->base, 'get_option_description', [ $option ] ) );

		$this->set_private_property( $this->base, 'show_descriptions', true );

		$this->assertSame( '$149 until 1 March', $this->call_private_method( $this->base, 'get_option_description', [ $option ] ) );

		// Nothing to show: absent, blank, non-scalar, and a non-array option all
		// land on the same empty string rather than on a notice or on "Array".
		$this->assertSame( '', $this->call_private_method( $this->base, 'get_option_description', [ [ 'optionTitle' => 'Plain' ] ] ) );
		$this->assertSame( '', $this->call_private_method( $this->base, 'get_option_description', [ [ 'description' => '   ' ] ] ) );
		$this->assertSame( '', $this->call_private_method( $this->base, 'get_option_description', [ [ 'description' => [ 'nested' ] ] ] ) );
		$this->assertSame( '', $this->call_private_method( $this->base, 'get_option_description', [ 'not-an-array' ] ) );

		// A numeric description is still a description.
		$this->assertSame( '0', $this->call_private_method( $this->base, 'get_option_description', [ [ 'description' => 0 ] ] ) );
	}

	/**
	 * The visible title prefers the render-time translation, and falls back to
	 * the entered optionTitle when there is none.
	 */
	public function test_get_option_display_title() {
		$this->assertSame( 'Frühbucher', $this->call_private_method( $this->base, 'get_option_display_title', [ [ 'optionTitle' => 'Early Bird', 'translatedTitle' => 'Frühbucher' ] ] ) );
		$this->assertSame( 'Early Bird', $this->call_private_method( $this->base, 'get_option_display_title', [ [ 'optionTitle' => 'Early Bird' ] ] ) );

		// A blank or non-string translation never hides the title.
		$this->assertSame( 'Early Bird', $this->call_private_method( $this->base, 'get_option_display_title', [ [ 'optionTitle' => 'Early Bird', 'translatedTitle' => '' ] ] ) );
		$this->assertSame( 'Early Bird', $this->call_private_method( $this->base, 'get_option_display_title', [ [ 'optionTitle' => 'Early Bird', 'translatedTitle' => [ 'x' ] ] ] ) );

		$this->assertSame( '5', $this->call_private_method( $this->base, 'get_option_display_title', [ [ 'optionTitle' => 5 ] ] ) );
		$this->assertSame( '', $this->call_private_method( $this->base, 'get_option_display_title', [ [ 'optionTitle' => [ 'nested' ] ] ] ) );
		$this->assertSame( '', $this->call_private_method( $this->base, 'get_option_display_title', [ 'not-an-array' ] ) );
	}

	/**
	 * Only the first option carries the field-level ids, which is what the field
	 * did before descriptions existed; each option points at its own description.
	 */
	public function test_get_option_described_by() {
		$this->set_private_property( $this->base, 'aria_described_by', ' srfm-description-b1' );

		$this->assertSame(
			'aria-describedby="srfm-description-b1 opt-0-desc" ',
			$this->call_private_method( $this->base, 'get_option_described_by', [ 0, 'opt-0-desc' ] )
		);

		// Later options never repeat the field-level ids.
		$this->assertSame(
			'aria-describedby="opt-2-desc" ',
			$this->call_private_method( $this->base, 'get_option_described_by', [ 2, 'opt-2-desc' ] )
		);

		// First option without a description keeps exactly the old attribute.
		$this->assertSame(
			'aria-describedby="srfm-description-b1" ',
			$this->call_private_method( $this->base, 'get_option_described_by', [ 0, '' ] )
		);

		// Nothing to point at anywhere: no empty attribute left in the markup.
		$this->assertSame( '', $this->call_private_method( $this->base, 'get_option_described_by', [ 1, '' ] ) );

		$plain = new Base();
		$this->assertSame( '', $this->call_private_method( $plain, 'get_option_described_by', [ 0, '' ] ) );
	}

	private function set_private_property( $object, $property_name, $value ) {
		$reflection = new \ReflectionClass( get_class( $object ) );
		$property   = $reflection->getProperty( $property_name );
		$property->setAccessible( true );
		$property->setValue( $object, $value );
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
