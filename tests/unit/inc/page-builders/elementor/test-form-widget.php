<?php
/**
 * Class Test_Form_Widget
 *
 * Tests for the Elementor Form Widget static methods.
 *
 * @package sureforms
 */

// Load Elementor stubs so Form_Widget can extend Widget_Base.
require_once SRFM_DIR . 'tests/php/stubs/srfm-elementor-stubs.php';

// Now it's safe to load the Form_Widget class.
require_once SRFM_DIR . 'inc/page-builders/elementor/form-widget.php';

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Page_Builders\Elementor\Form_Widget;

/**
 * Tests for Form_Widget::get_resolved_color(), Form_Widget::build_gradient_css(), and Form_Widget::get_block_attrs().
 *
 * @covers \SRFM\Inc\Page_Builders\Elementor\Form_Widget::get_resolved_color
 * @covers \SRFM\Inc\Page_Builders\Elementor\Form_Widget::build_gradient_css
 * @covers \SRFM\Inc\Page_Builders\Elementor\Form_Widget::get_block_attrs
 * @covers \SRFM\Inc\Page_Builders\Elementor\Form_Widget::map_elementor_dimensions
 */
class Test_Form_Widget extends TestCase {

	/**
	 * Create a Form_Widget instance without invoking the constructor.
	 *
	 * Form_Widget's constructor references Elementor\Plugin::$instance which
	 * is not available in unit tests. We use ReflectionClass to skip it.
	 *
	 * @return Form_Widget
	 */
	private function create_widget_instance() {
		$reflection = new ReflectionClass( Form_Widget::class );
		return $reflection->newInstanceWithoutConstructor();
	}

	/**
	 * Invoke the protected get_block_attrs method via reflection.
	 *
	 * @param Form_Widget          $widget   Widget instance.
	 * @param array<string, mixed> $settings Widget settings.
	 * @return array<string, mixed> Block attributes.
	 */
	private function invoke_get_block_attrs( Form_Widget $widget, array $settings ) {
		$reflection = new ReflectionMethod( Form_Widget::class, 'get_block_attrs' );
		$reflection->setAccessible( true );
		return $reflection->invoke( $widget, $settings );
	}

	/**
	 * Test get_resolved_color returns direct color value.
	 */
	public function test_get_resolved_color_direct_value(): void {
		$settings = [
			'primaryColor' => '#FF0000',
		];

		$result = Form_Widget::get_resolved_color( $settings, 'primaryColor' );
		$this->assertSame( '#FF0000', $result );
	}

	/**
	 * Data provider for get_resolved_color null-returning cases.
	 *
	 * @return array<string, array{0: array<string, mixed>, 1: string|null}>
	 */
	public function data_get_resolved_color_returns_null() {
		return [
			'empty string'       => [ [ 'primaryColor' => '' ], null ],
			'default value'      => [ [ 'primaryColor' => 'default' ], null ],
			'missing key'        => [ [], null ],
			'non-string (int)'   => [ [ 'primaryColor' => 123 ], null ],
		];
	}

	/**
	 * Test get_resolved_color returns null for invalid/empty/missing values.
	 *
	 * @dataProvider data_get_resolved_color_returns_null
	 *
	 * @param array<string, mixed> $settings Widget settings.
	 * @param string|null          $expected Expected result.
	 */
	public function test_get_resolved_color_returns_null( array $settings, $expected ): void {
		$result = Form_Widget::get_resolved_color( $settings, 'primaryColor' );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Data provider for get_resolved_color fallback-to-direct cases.
	 *
	 * @return array<string, array{0: array<string, mixed>, 1: string}>
	 */
	public function data_get_resolved_color_falls_back_to_direct() {
		return [
			'empty globals array'           => [
				[ '__globals__' => [], 'primaryColor' => '#00FF00' ],
				'#00FF00',
			],
			'non-array globals'             => [
				[ '__globals__' => 'not-an-array', 'primaryColor' => '#0000FF' ],
				'#0000FF',
			],
			'globals key with empty value'  => [
				[ '__globals__' => [ 'primaryColor' => '' ], 'primaryColor' => '#AABBCC' ],
				'#AABBCC',
			],
			'globals key with non-string'   => [
				[ '__globals__' => [ 'primaryColor' => 123 ], 'primaryColor' => '#DDEEFF' ],
				'#DDEEFF',
			],
		];
	}

	/**
	 * Test get_resolved_color falls back to direct value when globals can't resolve.
	 *
	 * @dataProvider data_get_resolved_color_falls_back_to_direct
	 *
	 * @param array<string, mixed> $settings Widget settings.
	 * @param string               $expected Expected color value.
	 */
	public function test_get_resolved_color_falls_back_to_direct( array $settings, string $expected ): void {
		$result = Form_Widget::get_resolved_color( $settings, 'primaryColor' );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Test build_gradient_css returns null when both colors are missing.
	 */
	public function test_build_gradient_css_no_colors(): void {
		$settings = [];

		$result = Form_Widget::build_gradient_css( $settings );
		$this->assertNull( $result );
	}

	/**
	 * Test build_gradient_css returns null when only color 1 is set.
	 */
	public function test_build_gradient_css_only_color_1(): void {
		$settings = [
			'bgGradient_color' => '#FF0000',
		];

		$result = Form_Widget::build_gradient_css( $settings );
		$this->assertNull( $result );
	}

	/**
	 * Test build_gradient_css returns null when only color 2 is set.
	 */
	public function test_build_gradient_css_only_color_2(): void {
		$settings = [
			'bgGradient_color_b' => '#00FF00',
		];

		$result = Form_Widget::build_gradient_css( $settings );
		$this->assertNull( $result );
	}

	/**
	 * Test build_gradient_css with defaults produces a linear gradient.
	 */
	public function test_build_gradient_css_default_linear(): void {
		$settings = [
			'bgGradient_color'   => '#FFC9B2',
			'bgGradient_color_b' => '#C7CBFF',
		];

		$result = Form_Widget::build_gradient_css( $settings );
		$this->assertSame( 'linear-gradient(180deg, #FFC9B2 0%, #C7CBFF 100%)', $result );
	}

	/**
	 * Test build_gradient_css with custom linear gradient settings.
	 */
	public function test_build_gradient_css_custom_linear(): void {
		$settings = [
			'bgGradient_color'          => '#FF0000',
			'bgGradient_color_b'        => '#0000FF',
			'bgGradient_gradient_type'  => 'linear',
			'bgGradient_gradient_angle' => [
				'size' => '45',
				'unit' => 'deg',
			],
			'bgGradient_color_stop'     => [
				'size' => '10',
				'unit' => '%',
			],
			'bgGradient_color_b_stop'   => [
				'size' => '90',
				'unit' => '%',
			],
		];

		$result = Form_Widget::build_gradient_css( $settings );
		$this->assertSame( 'linear-gradient(45deg, #FF0000 10%, #0000FF 90%)', $result );
	}

	/**
	 * Test build_gradient_css with radial gradient.
	 */
	public function test_build_gradient_css_radial(): void {
		$settings = [
			'bgGradient_color'         => '#000000',
			'bgGradient_color_b'       => '#FFFFFF',
			'bgGradient_gradient_type' => 'radial',
			'bgGradient_color_stop'    => [
				'size' => '20',
				'unit' => '%',
			],
			'bgGradient_color_b_stop'  => [
				'size' => '80',
				'unit' => '%',
			],
		];

		$result = Form_Widget::build_gradient_css( $settings );
		$this->assertSame( 'radial-gradient(at center center, #000000 20%, #FFFFFF 80%)', $result );
	}

	/**
	 * Test build_gradient_css with radial and default stops.
	 */
	public function test_build_gradient_css_radial_default_stops(): void {
		$settings = [
			'bgGradient_color'         => '#AABB00',
			'bgGradient_color_b'       => '#00BBAA',
			'bgGradient_gradient_type' => 'radial',
		];

		$result = Form_Widget::build_gradient_css( $settings );
		$this->assertSame( 'radial-gradient(at center center, #AABB00 0%, #00BBAA 100%)', $result );
	}

	/**
	 * Test build_gradient_css with empty gradient type defaults to linear.
	 */
	public function test_build_gradient_css_empty_type_defaults_linear(): void {
		$settings = [
			'bgGradient_color'         => '#111111',
			'bgGradient_color_b'       => '#222222',
			'bgGradient_gradient_type' => '',
		];

		$result = Form_Widget::build_gradient_css( $settings );
		$this->assertSame( 'linear-gradient(180deg, #111111 0%, #222222 100%)', $result );
	}

	/**
	 * Test build_gradient_css with zero angle.
	 */
	public function test_build_gradient_css_zero_angle(): void {
		$settings = [
			'bgGradient_color'          => '#AAAAAA',
			'bgGradient_color_b'        => '#BBBBBB',
			'bgGradient_gradient_angle' => [
				'size' => '0',
				'unit' => 'deg',
			],
		];

		$result = Form_Widget::build_gradient_css( $settings );
		$this->assertSame( 'linear-gradient(0deg, #AAAAAA 0%, #BBBBBB 100%)', $result );
	}

	/**
	 * Test build_gradient_css with zero stops.
	 */
	public function test_build_gradient_css_zero_stops(): void {
		$settings = [
			'bgGradient_color'        => '#123456',
			'bgGradient_color_b'      => '#654321',
			'bgGradient_color_stop'   => [
				'size' => '0',
				'unit' => '%',
			],
			'bgGradient_color_b_stop' => [
				'size' => '0',
				'unit' => '%',
			],
		];

		$result = Form_Widget::build_gradient_css( $settings );
		$this->assertSame( 'linear-gradient(180deg, #123456 0%, #654321 0%)', $result );
	}

	/**
	 * Test build_gradient_css falls back to direct values when globals cannot resolve.
	 */
	public function test_build_gradient_css_global_colors_falls_back_to_direct_values(): void {
		// When __globals__ has keys but can't resolve (no Elementor data manager), falls back to direct values.
		$settings = [
			'__globals__'        => [
				'bgGradient_color'   => 'globals/colors?id=primary',
				'bgGradient_color_b' => 'globals/colors?id=secondary',
			],
			'bgGradient_color'   => '#FF5500',
			'bgGradient_color_b' => '#0055FF',
		];

		$result = Form_Widget::build_gradient_css( $settings );
		// Without Elementor data manager, globals can't resolve, so falls back to direct values.
		$this->assertSame( 'linear-gradient(180deg, #FF5500 0%, #0055FF 100%)', $result );
	}

	/**
	 * Test build_gradient_css with non-array stop settings uses defaults.
	 */
	public function test_build_gradient_css_non_array_stops(): void {
		$settings = [
			'bgGradient_color'        => '#AAAAAA',
			'bgGradient_color_b'      => '#BBBBBB',
			'bgGradient_color_stop'   => 'not-an-array',
			'bgGradient_color_b_stop' => 'not-an-array',
		];

		$result = Form_Widget::build_gradient_css( $settings );
		$this->assertSame( 'linear-gradient(180deg, #AAAAAA 0%, #BBBBBB 100%)', $result );
	}

	/**
	 * Test build_gradient_css with non-array angle settings uses defaults.
	 */
	public function test_build_gradient_css_non_array_angle(): void {
		$settings = [
			'bgGradient_color'          => '#CCCCCC',
			'bgGradient_color_b'        => '#DDDDDD',
			'bgGradient_gradient_angle' => 'not-an-array',
		];

		$result = Form_Widget::build_gradient_css( $settings );
		$this->assertSame( 'linear-gradient(180deg, #CCCCCC 0%, #DDDDDD 100%)', $result );
	}

	/**
	 * Test get_block_attrs returns early with only blockId and formTheme when theme is 'inherit'.
	 */
	public function test_get_block_attrs_inherit_theme_returns_early(): void {
		$widget   = $this->create_widget_instance();
		$settings = [
			'formTheme' => 'inherit',
		];

		$result = $this->invoke_get_block_attrs( $widget, $settings );

		$this->assertArrayHasKey( 'blockId', $result );
		$this->assertArrayHasKey( 'formTheme', $result );
		$this->assertSame( 'inherit', $result['formTheme'] );
		// Should only have blockId and formTheme — no styling keys.
		$this->assertCount( 2, $result );
	}

	/**
	 * Test get_block_attrs defaults to 'inherit' when formTheme is not set.
	 */
	public function test_get_block_attrs_missing_form_theme_defaults_to_inherit(): void {
		$widget   = $this->create_widget_instance();
		$settings = [];

		$result = $this->invoke_get_block_attrs( $widget, $settings );

		$this->assertSame( 'inherit', $result['formTheme'] );
		$this->assertCount( 2, $result );
	}

	/**
	 * Test get_block_attrs maps colors via get_resolved_color.
	 */
	public function test_get_block_attrs_color_mapping(): void {
		$widget   = $this->create_widget_instance();
		$settings = [
			'formTheme'         => 'classic',
			'primaryColor'      => '#FF0000',
			'textColor'         => '#333333',
			'textOnPrimaryColor' => '#FFFFFF',
			'bgColor'           => '#FAFAFA',
		];

		$result = $this->invoke_get_block_attrs( $widget, $settings );

		$this->assertSame( '#FF0000', $result['primaryColor'] );
		$this->assertSame( '#333333', $result['textColor'] );
		$this->assertSame( '#FFFFFF', $result['textOnPrimaryColor'] );
		$this->assertSame( '#FAFAFA', $result['bgColor'] );
	}

	/**
	 * Test get_block_attrs skips empty/default color values.
	 */
	public function test_get_block_attrs_skips_empty_colors(): void {
		$widget   = $this->create_widget_instance();
		$settings = [
			'formTheme'    => 'classic',
			'primaryColor' => '',
			'textColor'    => 'default',
		];

		$result = $this->invoke_get_block_attrs( $widget, $settings );

		$this->assertArrayNotHasKey( 'primaryColor', $result );
		$this->assertArrayNotHasKey( 'textColor', $result );
	}

	/**
	 * Test get_block_attrs decomposes padding dimensions into individual keys.
	 */
	public function test_get_block_attrs_dimension_decomposition_padding(): void {
		$widget   = $this->create_widget_instance();
		$settings = [
			'formTheme'   => 'classic',
			'formPadding' => [
				'top'    => '10',
				'right'  => '20',
				'bottom' => '30',
				'left'   => '40',
				'unit'   => 'px',
			],
		];

		$result = $this->invoke_get_block_attrs( $widget, $settings );

		$this->assertSame( '10', $result['formPaddingTop'] );
		$this->assertSame( '20', $result['formPaddingRight'] );
		$this->assertSame( '30', $result['formPaddingBottom'] );
		$this->assertSame( '40', $result['formPaddingLeft'] );
		$this->assertArrayHasKey( 'formPaddingUnit', $result );
		$this->assertSame( 'px', $result['formPaddingUnit'] );
	}

	/**
	 * Test get_block_attrs decomposes border-radius dimensions into individual keys.
	 */
	public function test_get_block_attrs_dimension_decomposition_border_radius(): void {
		$widget   = $this->create_widget_instance();
		$settings = [
			'formTheme'        => 'classic',
			'formBorderRadius' => [
				'top'    => '5',
				'right'  => '5',
				'bottom' => '5',
				'left'   => '5',
				'unit'   => 'em',
			],
		];

		$result = $this->invoke_get_block_attrs( $widget, $settings );

		$this->assertSame( '5', $result['formBorderRadiusTop'] );
		$this->assertSame( '5', $result['formBorderRadiusRight'] );
		$this->assertSame( '5', $result['formBorderRadiusBottom'] );
		$this->assertSame( '5', $result['formBorderRadiusLeft'] );
		$this->assertArrayHasKey( 'formBorderRadiusUnit', $result );
		$this->assertSame( 'em', $result['formBorderRadiusUnit'] );
	}

	/**
	 * Test get_block_attrs falls back to px when dimension unit is invalid.
	 */
	public function test_get_block_attrs_invalid_unit_defaults_to_px(): void {
		$widget   = $this->create_widget_instance();
		$settings = [
			'formTheme'   => 'classic',
			'formPadding' => [
				'top'    => '15',
				'right'  => '15',
				'bottom' => '15',
				'left'   => '15',
				'unit'   => 'vw',
			],
		];

		$result = $this->invoke_get_block_attrs( $widget, $settings );

		$this->assertSame( '15', $result['formPaddingTop'] );
		$this->assertSame( '15', $result['formPaddingRight'] );
		$this->assertSame( '15', $result['formPaddingBottom'] );
		$this->assertSame( '15', $result['formPaddingLeft'] );
		$this->assertArrayHasKey( 'formPaddingUnit', $result );
		$this->assertSame( 'px', $result['formPaddingUnit'] );
	}

	/**
	 * Test get_block_attrs sanitizes background image URL with esc_url_raw.
	 */
	public function test_get_block_attrs_bg_image_url_sanitized(): void {
		$widget   = $this->create_widget_instance();
		$settings = [
			'formTheme' => 'classic',
			'bgImage'   => [
				'url' => 'https://example.com/image.jpg?x=1&y=2',
				'id'  => 42,
			],
		];

		$result = $this->invoke_get_block_attrs( $widget, $settings );

		// esc_url_raw should keep the URL valid.
		$this->assertSame( 'https://example.com/image.jpg?x=1&y=2', $result['bgImage'] );
		$this->assertSame( 42, $result['bgImageId'] );
	}

	/**
	 * Test get_block_attrs sanitizes a malicious background image URL.
	 */
	public function test_get_block_attrs_bg_image_url_xss_sanitized(): void {
		$widget   = $this->create_widget_instance();
		$settings = [
			'formTheme' => 'classic',
			'bgImage'   => [
				'url' => 'javascript:alert(1)',
				'id'  => 1,
			],
		];

		$result = $this->invoke_get_block_attrs( $widget, $settings );

		// esc_url_raw should strip javascript: protocol.
		$this->assertArrayHasKey( 'bgImage', $result );
		$this->assertStringNotContainsString( 'javascript', $result['bgImage'] );
	}

	/**
	 * Test get_block_attrs populates bgGradient when bgType is 'gradient'.
	 */
	public function test_get_block_attrs_gradient_integration(): void {
		$widget   = $this->create_widget_instance();
		$settings = [
			'formTheme'          => 'classic',
			'bgType'             => 'gradient',
			'bgGradient_color'   => '#FF0000',
			'bgGradient_color_b' => '#0000FF',
		];

		$result = $this->invoke_get_block_attrs( $widget, $settings );

		$this->assertArrayHasKey( 'bgGradient', $result );
		$this->assertSame( 'linear-gradient(180deg, #FF0000 0%, #0000FF 100%)', $result['bgGradient'] );
	}

	/**
	 * Test get_block_attrs does not set bgGradient when bgType is not 'gradient'.
	 */
	public function test_get_block_attrs_no_gradient_when_bg_type_is_color(): void {
		$widget   = $this->create_widget_instance();
		$settings = [
			'formTheme'          => 'classic',
			'bgType'             => 'color',
			'bgGradient_color'   => '#FF0000',
			'bgGradient_color_b' => '#0000FF',
		];

		$result = $this->invoke_get_block_attrs( $widget, $settings );

		$this->assertArrayNotHasKey( 'bgGradient', $result );
	}

	/**
	 * Test build_gradient_css falls back to linear for unknown gradient type.
	 */
	public function test_build_gradient_css_unknown_type_falls_back_to_linear(): void {
		$settings = [
			'bgGradient_color'         => '#111111',
			'bgGradient_color_b'       => '#222222',
			'bgGradient_gradient_type' => 'conic',
		];

		$result = Form_Widget::build_gradient_css( $settings );
		// 'conic' is not 'radial', so the code falls through to the linear branch.
		$this->assertIsString( $result );
		$this->assertStringContainsString( 'linear-gradient(', $result );
		$this->assertStringNotContainsString( 'conic', $result );
	}

	/**
	 * Test get_block_attrs applies the srfm_elementor_block_attrs filter.
	 */
	public function test_get_block_attrs_applies_filter(): void {
		$widget   = $this->create_widget_instance();
		$settings = [
			'formTheme' => 'classic',
		];

		// Add a filter that injects a custom attribute.
		$callback = function ( $block_attrs ) {
			$block_attrs['customAttr'] = 'filtered';
			return $block_attrs;
		};
		add_filter( 'srfm_elementor_block_attrs', $callback );

		$result = $this->invoke_get_block_attrs( $widget, $settings );

		$this->assertArrayHasKey( 'customAttr', $result );
		$this->assertSame( 'filtered', $result['customAttr'] );

		// Clean up.
		remove_filter( 'srfm_elementor_block_attrs', $callback );
	}

	/**
	 * Test get_block_attrs passes through 'justify' button alignment.
	 */
	public function test_get_block_attrs_passes_through_button_alignment_justify(): void {
		$widget   = $this->create_widget_instance();
		$settings = [
			'formTheme'       => 'default',
			'buttonAlignment' => 'justify',
		];

		$result = $this->invoke_get_block_attrs( $widget, $settings );

		$this->assertArrayHasKey( 'buttonAlignment', $result );
		$this->assertSame( 'justify', $result['buttonAlignment'] );
	}

	// ─── Coverage: widget interface methods ──────────────────────────

	/**
	 * Test get_style_depends returns an array.
	 */
	public function test_get_style_depends_returns_array(): void {
		$widget = $this->create_widget_instance();
		$result = $widget->get_style_depends();
		$this->assertIsArray( $result );
	}

	/**
	 * Test get_script_depends returns an array when not in preview mode.
	 */
	public function test_get_script_depends_returns_empty_when_not_preview(): void {
		$widget = $this->create_widget_instance();
		$result = $widget->get_script_depends();
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test get_keywords returns expected keywords array.
	 */
	public function test_get_keywords_returns_expected(): void {
		$widget   = $this->create_widget_instance();
		$keywords = $widget->get_keywords();
		$this->assertIsArray( $keywords );
		$this->assertContains( 'sureforms', $keywords );
		$this->assertContains( 'form', $keywords );
	}

	/**
	 * Test register_controls can be invoked without fatal error.
	 */
	public function test_register_controls_runs_without_error(): void {
		$widget = $this->create_widget_instance();
		$method = new \ReflectionMethod( $widget, 'register_controls' );
		$method->setAccessible( true );
		$method->invoke( $widget );
		$this->assertTrue( true );
	}

	/**
	 * Test render outputs string content.
	 */
	public function test_render_outputs_content(): void {
		$widget = $this->create_widget_instance();
		$method = new \ReflectionMethod( $widget, 'render' );
		$method->setAccessible( true );
		ob_start();
		$result = $method->invoke( $widget );
		$output = ob_get_clean();
		// render() may return void or string — either way no fatal.
		$this->assertIsString( $output );
	}
}
