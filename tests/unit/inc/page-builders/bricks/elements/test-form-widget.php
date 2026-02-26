<?php
/**
 * Tests for SRFM\Inc\Page_Builders\Bricks\Elements\Form_Widget static utilities.
 *
 * @package sureforms
 */

// Stub bricks_is_builder() in the global namespace for Form_Widget constructor.
namespace {
	if ( ! function_exists( 'bricks_is_builder' ) ) {
		function bricks_is_builder() { // phpcs:ignore
			return false;
		}
	}
}

// Stub \Bricks\Element so Form_Widget can be autoloaded without Bricks Builder.
namespace Bricks {
	if ( ! class_exists( 'Bricks\Element' ) ) {
		class Element { // phpcs:ignore
			public $settings       = [];
			public $id             = null;
			protected $controls       = []; // phpcs:ignore
			protected $control_groups = []; // phpcs:ignore
			protected $scripts        = []; // phpcs:ignore
			public function __construct( $element = null ) {} // phpcs:ignore
		}
	}
}

namespace SRFM\Inc\Page_Builders\Bricks\Elements {

	use Yoast\PHPUnitPolyfills\TestCases\TestCase;

	/**
	 * Testable subclass that exposes the protected get_block_attrs method.
	 */
	class Testable_Form_Widget extends Form_Widget {
		/**
		 * Public wrapper around the protected get_block_attrs method.
		 *
		 * @param array<string, mixed> $settings Bricks element settings.
		 * @return array<string, mixed>
		 */
		public function test_get_block_attrs( $settings ) {
			return $this->get_block_attrs( $settings );
		}
	}

	/**
	 * Unit tests for Form_Widget static utility methods.
	 *
	 * Covers:
	 *   - resolve_bricks_color()
	 *   - map_bricks_spacing()
	 *   - build_bricks_gradient_css()
	 *   - get_block_attrs()
	 */
	class Test_Form_Widget extends TestCase {

		// -----------------------------------------------------------------------
		// resolve_bricks_color
		// -----------------------------------------------------------------------

		public function test_resolve_color_returns_null_for_null() {
			$this->assertNull( Form_Widget::resolve_bricks_color( null ) );
		}

		public function test_resolve_color_returns_null_for_empty_string() {
			$this->assertNull( Form_Widget::resolve_bricks_color( '' ) );
		}

		public function test_resolve_color_returns_null_for_empty_array() {
			$this->assertNull( Form_Widget::resolve_bricks_color( [] ) );
		}

		public function test_resolve_color_returns_hex_from_string() {
			$this->assertSame( '#111C44', Form_Widget::resolve_bricks_color( '#111C44' ) );
		}

		public function test_resolve_color_returns_shorthand_hex_from_string() {
			$this->assertSame( '#abc', Form_Widget::resolve_bricks_color( '#abc' ) );
		}

		public function test_resolve_color_prefers_hex_key_over_raw_in_array() {
			$this->assertSame(
				'#FFFFFF',
				Form_Widget::resolve_bricks_color( [ 'hex' => '#FFFFFF', 'raw' => '#000000' ] )
			);
		}

		public function test_resolve_color_falls_back_to_raw_key_when_hex_absent() {
			$this->assertSame(
				'#1E1E1E',
				Form_Widget::resolve_bricks_color( [ 'raw' => '#1E1E1E', 'rgb' => 'rgb(30,30,30)' ] )
			);
		}

		public function test_resolve_color_returns_null_for_array_with_no_usable_key() {
			$this->assertNull( Form_Widget::resolve_bricks_color( [ 'rgb' => 'rgb(0,0,0)' ] ) );
		}

		public function test_resolve_color_returns_rgb_string() {
			$this->assertSame(
				'rgb(255, 0, 0)',
				Form_Widget::resolve_bricks_color( 'rgb(255, 0, 0)' )
			);
		}

		public function test_resolve_color_returns_rgba_string() {
			$this->assertSame(
				'rgba(0, 0, 0, 0.5)',
				Form_Widget::resolve_bricks_color( 'rgba(0, 0, 0, 0.5)' )
			);
		}

		public function test_resolve_color_returns_hsl_string() {
			$this->assertSame(
				'hsl(120, 100%, 50%)',
				Form_Widget::resolve_bricks_color( 'hsl(120, 100%, 50%)' )
			);
		}

		public function test_resolve_color_returns_hsla_string() {
			$this->assertSame(
				'hsla(120, 100%, 50%, 0.3)',
				Form_Widget::resolve_bricks_color( 'hsla(120, 100%, 50%, 0.3)' )
			);
		}

		public function test_resolve_color_returns_null_for_named_color() {
			// Named CSS colors are not accepted — only hex and functional notation.
			$this->assertNull( Form_Widget::resolve_bricks_color( 'red' ) );
		}

		public function test_resolve_color_returns_null_for_javascript_scheme() {
			$this->assertNull( Form_Widget::resolve_bricks_color( 'javascript:alert(1)' ) );
		}

		public function test_resolve_color_returns_null_for_css_injection_attempt() {
			$this->assertNull( Form_Widget::resolve_bricks_color( '#fff; color: red' ) );
		}

		public function test_resolve_color_returns_null_for_integer_input() {
			$this->assertNull( Form_Widget::resolve_bricks_color( 12345 ) );
		}

		// -----------------------------------------------------------------------
		// map_bricks_spacing
		// -----------------------------------------------------------------------

		public function test_map_spacing_returns_empty_array_when_key_missing() {
			$attrs = Form_Widget::map_bricks_spacing( [], 'formPadding', 'formPadding' );
			$this->assertSame( [], $attrs );
		}

		public function test_map_spacing_returns_empty_array_when_value_is_not_array() {
			$attrs = Form_Widget::map_bricks_spacing( [ 'formPadding' => 'invalid' ], 'formPadding', 'formPadding' );
			$this->assertSame( [], $attrs );
		}

		public function test_map_spacing_maps_all_four_sides_with_px_unit() {
			$settings = [
				'formPadding' => [
					'top'    => '10',
					'right'  => '20',
					'bottom' => '30',
					'left'   => '40',
					'unit'   => 'px',
				],
			];

			$attrs = Form_Widget::map_bricks_spacing( $settings, 'formPadding', 'formPadding' );

			$this->assertSame( '10px', $attrs['formPaddingTop'] );
			$this->assertSame( '20px', $attrs['formPaddingRight'] );
			$this->assertSame( '30px', $attrs['formPaddingBottom'] );
			$this->assertSame( '40px', $attrs['formPaddingLeft'] );
		}

		/**
		 * @dataProvider provideValidUnits
		 */
		public function test_map_spacing_accepts_valid_units( $unit ) {
			$settings = [
				'box' => [ 'top' => '5', 'right' => '5', 'bottom' => '5', 'left' => '5', 'unit' => $unit ],
			];

			$attrs = Form_Widget::map_bricks_spacing( $settings, 'box', 'box' );

			$this->assertSame( "5{$unit}", $attrs['boxTop'] );
		}

		public function provideValidUnits() {
			return [
				'px'  => [ 'px' ],
				'em'  => [ 'em' ],
				'rem' => [ 'rem' ],
				'%'   => [ '%' ],
				'vw'  => [ 'vw' ],
				'vh'  => [ 'vh' ],
			];
		}

		public function test_map_spacing_falls_back_to_px_for_invalid_unit() {
			$settings = [
				'formPadding' => [ 'top' => '10', 'right' => '', 'bottom' => '', 'left' => '', 'unit' => 'bad' ],
			];

			$attrs = Form_Widget::map_bricks_spacing( $settings, 'formPadding', 'formPadding' );

			$this->assertSame( '10px', $attrs['formPaddingTop'] );
		}

		public function test_map_spacing_skips_empty_string_values() {
			$settings = [
				'formPadding' => [ 'top' => '10', 'right' => '', 'bottom' => '', 'left' => '', 'unit' => 'px' ],
			];

			$attrs = Form_Widget::map_bricks_spacing( $settings, 'formPadding', 'formPadding' );

			$this->assertArrayHasKey( 'formPaddingTop', $attrs );
			$this->assertArrayNotHasKey( 'formPaddingRight', $attrs );
			$this->assertArrayNotHasKey( 'formPaddingBottom', $attrs );
			$this->assertArrayNotHasKey( 'formPaddingLeft', $attrs );
		}

		public function test_map_spacing_preserves_zero_values() {
			$settings = [
				'formBorderRadius' => [ 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'unit' => 'px' ],
			];

			$attrs = Form_Widget::map_bricks_spacing( $settings, 'formBorderRadius', 'formBorderRadius' );

			$this->assertSame( '0px', $attrs['formBorderRadiusTop'] );
			$this->assertSame( '0px', $attrs['formBorderRadiusRight'] );
			$this->assertSame( '0px', $attrs['formBorderRadiusBottom'] );
			$this->assertSame( '0px', $attrs['formBorderRadiusLeft'] );
		}

		public function test_map_spacing_applies_custom_prefix() {
			$settings = [
				'myControl' => [ 'top' => '8', 'right' => '16', 'bottom' => '8', 'left' => '16', 'unit' => 'em' ],
			];

			$attrs = Form_Widget::map_bricks_spacing( $settings, 'myControl', 'labelPadding' );

			$this->assertArrayHasKey( 'labelPaddingTop', $attrs );
			$this->assertArrayHasKey( 'labelPaddingRight', $attrs );
			$this->assertSame( '8em', $attrs['labelPaddingTop'] );
			$this->assertSame( '16em', $attrs['labelPaddingRight'] );
		}

		// -----------------------------------------------------------------------
		// build_bricks_gradient_css
		// -----------------------------------------------------------------------

		public function test_build_gradient_returns_null_when_color1_missing() {
			$settings = [
				'bgGradientColor2'     => [ 'hex' => '#000000' ],
				'bgGradientColor2Stop' => 100,
			];

			$this->assertNull( Form_Widget::build_bricks_gradient_css( $settings, 'bg' ) );
		}

		public function test_build_gradient_returns_null_when_color2_missing() {
			$settings = [
				'bgGradientColor1'     => [ 'hex' => '#FFFFFF' ],
				'bgGradientColor1Stop' => 0,
			];

			$this->assertNull( Form_Widget::build_bricks_gradient_css( $settings, 'bg' ) );
		}

		public function test_build_gradient_returns_null_when_both_colors_missing() {
			$this->assertNull( Form_Widget::build_bricks_gradient_css( [], 'bg' ) );
		}

		public function test_build_gradient_returns_linear_gradient_by_default() {
			$settings = [
				'bgGradientColor1' => [ 'hex' => '#FFFFFF' ],
				'bgGradientColor2' => [ 'hex' => '#000000' ],
			];

			$result = Form_Widget::build_bricks_gradient_css( $settings, 'bg' );

			$this->assertStringStartsWith( 'linear-gradient(', $result );
		}

		public function test_build_gradient_uses_default_angle_and_stops_when_not_set() {
			$settings = [
				'bgGradientColor1' => [ 'hex' => '#FFFFFF' ],
				'bgGradientColor2' => [ 'hex' => '#000000' ],
				'bgGradientType'   => 'linear',
			];

			$result = Form_Widget::build_bricks_gradient_css( $settings, 'bg' );

			$this->assertSame( 'linear-gradient(90deg, #FFFFFF 0%, #000000 100%)', $result );
		}

		public function test_build_gradient_includes_correct_angle() {
			$settings = [
				'bgGradientColor1' => [ 'hex' => '#FFFFFF' ],
				'bgGradientColor2' => [ 'hex' => '#000000' ],
				'bgGradientType'   => 'linear',
				'bgGradientAngle'  => 45,
			];

			$result = Form_Widget::build_bricks_gradient_css( $settings, 'bg' );

			$this->assertSame( 'linear-gradient(45deg, #FFFFFF 0%, #000000 100%)', $result );
		}

		public function test_build_gradient_includes_correct_color_stops() {
			$settings = [
				'bgGradientColor1'     => [ 'hex' => '#FF0000' ],
				'bgGradientColor1Stop' => 20,
				'bgGradientColor2'     => [ 'hex' => '#0000FF' ],
				'bgGradientColor2Stop' => 80,
				'bgGradientType'       => 'linear',
				'bgGradientAngle'      => 90,
			];

			$result = Form_Widget::build_bricks_gradient_css( $settings, 'bg' );

			$this->assertSame( 'linear-gradient(90deg, #FF0000 20%, #0000FF 80%)', $result );
		}

		public function test_build_gradient_builds_radial_gradient() {
			$settings = [
				'bgGradientColor1'     => [ 'hex' => '#FF0000' ],
				'bgGradientColor1Stop' => 0,
				'bgGradientColor2'     => [ 'hex' => '#0000FF' ],
				'bgGradientColor2Stop' => 100,
				'bgGradientType'       => 'radial',
			];

			$result = Form_Widget::build_bricks_gradient_css( $settings, 'bg' );

			$this->assertSame( 'radial-gradient(at center center, #FF0000 0%, #0000FF 100%)', $result );
		}

		public function test_build_gradient_returns_null_when_colors_are_invalid() {
			$settings = [
				'bgGradientColor1' => 'not-a-color',
				'bgGradientColor2' => 'javascript:alert(1)',
			];

			$this->assertNull( Form_Widget::build_bricks_gradient_css( $settings, 'bg' ) );
		}

		public function test_build_gradient_works_with_string_hex_colors() {
			$settings = [
				'bgGradientColor1' => '#AABBCC',
				'bgGradientColor2' => '#112233',
				'bgGradientType'   => 'linear',
				'bgGradientAngle'  => 0,
			];

			$result = Form_Widget::build_bricks_gradient_css( $settings, 'bg' );

			$this->assertSame( 'linear-gradient(0deg, #AABBCC 0%, #112233 100%)', $result );
		}

		public function test_build_gradient_falls_back_to_linear_for_unknown_type() {
			$settings = [
				'bgGradientColor1' => '#FFFFFF',
				'bgGradientColor2' => '#000000',
				'bgGradientType'   => 'conical', // unknown — should default to linear.
				'bgGradientAngle'  => 90,
			];

			$result = Form_Widget::build_bricks_gradient_css( $settings, 'bg' );

			$this->assertStringStartsWith( 'linear-gradient(', $result );
		}

		// -----------------------------------------------------------------------
		// get_block_attrs (via Testable_Form_Widget)
		// -----------------------------------------------------------------------

		/**
		 * Helper to create a Testable_Form_Widget instance with a fixed ID.
		 *
		 * @return Testable_Form_Widget
		 */
		private function create_widget() {
			$widget     = new Testable_Form_Widget();
			$widget->id = 'test123';
			return $widget;
		}

		public function test_get_block_attrs_returns_blockId() {
			$widget = $this->create_widget();
			$attrs  = $widget->test_get_block_attrs( [] );

			$this->assertSame( 'bricks-test123', $attrs['blockId'] );
		}

		public function test_get_block_attrs_returns_only_blockId_and_inheritStyling_when_inherit_is_on() {
			$widget = $this->create_widget();
			$attrs  = $widget->test_get_block_attrs( [
				'inheritStyling' => true,
				'primaryColor'   => '#FF0000',
				'textColor'      => '#00FF00',
			] );

			$this->assertTrue( $attrs['inheritStyling'] );
			$this->assertArrayHasKey( 'blockId', $attrs );
			// Custom styling keys should NOT be present.
			$this->assertArrayNotHasKey( 'primaryColor', $attrs );
			$this->assertArrayNotHasKey( 'textColor', $attrs );
		}

		public function test_get_block_attrs_maps_color_controls() {
			$widget = $this->create_widget();
			$attrs  = $widget->test_get_block_attrs( [
				'primaryColor'       => '#111C44',
				'textColor'          => '#1E1E1E',
				'textOnPrimaryColor' => '#FFFFFF',
				'bgColor'            => '#F5F5F5',
			] );

			$this->assertSame( '#111C44', $attrs['primaryColor'] );
			$this->assertSame( '#1E1E1E', $attrs['textColor'] );
			$this->assertSame( '#FFFFFF', $attrs['textOnPrimaryColor'] );
			$this->assertSame( '#F5F5F5', $attrs['bgColor'] );
		}

		public function test_get_block_attrs_resolves_bricks_array_color() {
			$widget = $this->create_widget();
			$attrs  = $widget->test_get_block_attrs( [
				'primaryColor' => [ 'hex' => '#AABBCC', 'raw' => '#AABBCC' ],
			] );

			$this->assertSame( '#AABBCC', $attrs['primaryColor'] );
		}

		public function test_get_block_attrs_skips_empty_color() {
			$widget = $this->create_widget();
			$attrs  = $widget->test_get_block_attrs( [
				'primaryColor' => '',
				'textColor'    => null,
			] );

			$this->assertArrayNotHasKey( 'primaryColor', $attrs );
			$this->assertArrayNotHasKey( 'textColor', $attrs );
		}

		public function test_get_block_attrs_maps_passthrough_keys() {
			$widget = $this->create_widget();
			$attrs  = $widget->test_get_block_attrs( [
				'fieldSpacing'    => 'large',
				'buttonAlignment' => 'center',
				'bgType'          => 'color',
			] );

			$this->assertSame( 'large', $attrs['fieldSpacing'] );
			$this->assertSame( 'center', $attrs['buttonAlignment'] );
			$this->assertSame( 'color', $attrs['bgType'] );
		}

		public function test_get_block_attrs_skips_empty_passthrough_keys() {
			$widget = $this->create_widget();
			$attrs  = $widget->test_get_block_attrs( [
				'fieldSpacing' => '',
			] );

			$this->assertArrayNotHasKey( 'fieldSpacing', $attrs );
		}

		public function test_get_block_attrs_maps_spacing_controls() {
			$widget = $this->create_widget();
			$attrs  = $widget->test_get_block_attrs( [
				'formPadding' => [
					'top'    => '10',
					'right'  => '20',
					'bottom' => '30',
					'left'   => '40',
					'unit'   => 'px',
				],
			] );

			$this->assertSame( '10px', $attrs['formPaddingTop'] );
			$this->assertSame( '20px', $attrs['formPaddingRight'] );
			$this->assertSame( '30px', $attrs['formPaddingBottom'] );
			$this->assertSame( '40px', $attrs['formPaddingLeft'] );
		}

		public function test_get_block_attrs_maps_form_border_radius() {
			$widget = $this->create_widget();
			$attrs  = $widget->test_get_block_attrs( [
				'formBorderRadius' => [
					'top'    => '8',
					'right'  => '8',
					'bottom' => '8',
					'left'   => '8',
					'unit'   => 'px',
				],
			] );

			$this->assertSame( '8px', $attrs['formBorderRadiusTop'] );
			$this->assertSame( '8px', $attrs['formBorderRadiusRight'] );
		}

		public function test_get_block_attrs_builds_gradient_when_bgtype_is_gradient() {
			$widget = $this->create_widget();
			$attrs  = $widget->test_get_block_attrs( [
				'bgType'           => 'gradient',
				'bgGradientColor1' => '#FF0000',
				'bgGradientColor2' => '#0000FF',
				'bgGradientType'   => 'linear',
				'bgGradientAngle'  => 90,
			] );

			$this->assertArrayHasKey( 'bgGradient', $attrs );
			$this->assertStringStartsWith( 'linear-gradient(', $attrs['bgGradient'] );
		}

		public function test_get_block_attrs_does_not_build_gradient_when_bgtype_is_color() {
			$widget = $this->create_widget();
			$attrs  = $widget->test_get_block_attrs( [
				'bgType'           => 'color',
				'bgGradientColor1' => '#FF0000',
				'bgGradientColor2' => '#0000FF',
			] );

			$this->assertArrayNotHasKey( 'bgGradient', $attrs );
		}

		public function test_get_block_attrs_maps_bgimage_url() {
			$widget = $this->create_widget();
			$attrs  = $widget->test_get_block_attrs( [
				'bgImage' => [
					'url' => 'https://example.com/image.jpg',
					'id'  => 42,
				],
			] );

			$this->assertSame( 'https://example.com/image.jpg', $attrs['bgImage'] );
		}

		public function test_get_block_attrs_skips_bgimage_when_url_missing() {
			$widget = $this->create_widget();
			$attrs  = $widget->test_get_block_attrs( [
				'bgImage' => [ 'id' => 42 ],
			] );

			$this->assertArrayNotHasKey( 'bgImage', $attrs );
		}

		public function test_get_block_attrs_rejects_invalid_color_values() {
			$widget = $this->create_widget();
			$attrs  = $widget->test_get_block_attrs( [
				'primaryColor' => 'javascript:alert(1)',
				'textColor'    => 'red',
				'bgColor'      => '#fff; color: red',
			] );

			$this->assertArrayNotHasKey( 'primaryColor', $attrs );
			$this->assertArrayNotHasKey( 'textColor', $attrs );
			$this->assertArrayNotHasKey( 'bgColor', $attrs );
		}

		public function test_get_block_attrs_generates_blockid_when_id_is_null() {
			$widget     = new Testable_Form_Widget();
			$widget->id = null;
			$attrs      = $widget->test_get_block_attrs( [] );

			$this->assertStringStartsWith( 'bricks-', $attrs['blockId'] );
		}
	}
}
