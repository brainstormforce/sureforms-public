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
 * Tests for Form_Widget::get_resolved_color() and Form_Widget::build_gradient_css().
 */
class Test_Form_Widget extends TestCase {

	/**
	 * Test get_resolved_color returns direct color value.
	 */
	public function test_get_resolved_color_direct_value() {
		$settings = [
			'primaryColor' => '#FF0000',
		];

		$result = Form_Widget::get_resolved_color( $settings, 'primaryColor' );
		$this->assertSame( '#FF0000', $result );
	}

	/**
	 * Test get_resolved_color returns null for empty string value.
	 */
	public function test_get_resolved_color_empty_string() {
		$settings = [
			'primaryColor' => '',
		];

		$result = Form_Widget::get_resolved_color( $settings, 'primaryColor' );
		$this->assertNull( $result );
	}

	/**
	 * Test get_resolved_color returns null for 'default' value.
	 */
	public function test_get_resolved_color_default_value() {
		$settings = [
			'primaryColor' => 'default',
		];

		$result = Form_Widget::get_resolved_color( $settings, 'primaryColor' );
		$this->assertNull( $result );
	}

	/**
	 * Test get_resolved_color returns null when key is missing.
	 */
	public function test_get_resolved_color_missing_key() {
		$settings = [];

		$result = Form_Widget::get_resolved_color( $settings, 'primaryColor' );
		$this->assertNull( $result );
	}

	/**
	 * Test get_resolved_color returns null for non-string value.
	 */
	public function test_get_resolved_color_non_string_value() {
		$settings = [
			'primaryColor' => 123,
		];

		$result = Form_Widget::get_resolved_color( $settings, 'primaryColor' );
		$this->assertNull( $result );
	}

	/**
	 * Test get_resolved_color with empty globals array.
	 */
	public function test_get_resolved_color_empty_globals() {
		$settings = [
			'__globals__'  => [],
			'primaryColor' => '#00FF00',
		];

		$result = Form_Widget::get_resolved_color( $settings, 'primaryColor' );
		$this->assertSame( '#00FF00', $result );
	}

	/**
	 * Test get_resolved_color with non-array globals falls back to direct value.
	 */
	public function test_get_resolved_color_non_array_globals() {
		$settings = [
			'__globals__'  => 'not-an-array',
			'primaryColor' => '#0000FF',
		];

		$result = Form_Widget::get_resolved_color( $settings, 'primaryColor' );
		$this->assertSame( '#0000FF', $result );
	}

	/**
	 * Test get_resolved_color with globals key but no matching global value falls back to direct.
	 */
	public function test_get_resolved_color_globals_key_empty_value() {
		$settings = [
			'__globals__'  => [
				'primaryColor' => '',
			],
			'primaryColor' => '#AABBCC',
		];

		$result = Form_Widget::get_resolved_color( $settings, 'primaryColor' );
		$this->assertSame( '#AABBCC', $result );
	}

	/**
	 * Test get_resolved_color with globals key containing non-string value falls back to direct.
	 */
	public function test_get_resolved_color_globals_non_string_value() {
		$settings = [
			'__globals__'  => [
				'primaryColor' => 123,
			],
			'primaryColor' => '#DDEEFF',
		];

		$result = Form_Widget::get_resolved_color( $settings, 'primaryColor' );
		$this->assertSame( '#DDEEFF', $result );
	}

	/**
	 * Test build_gradient_css returns null when both colors are missing.
	 */
	public function test_build_gradient_css_no_colors() {
		$settings = [];

		$result = Form_Widget::build_gradient_css( $settings );
		$this->assertNull( $result );
	}

	/**
	 * Test build_gradient_css returns null when only color 1 is set.
	 */
	public function test_build_gradient_css_only_color_1() {
		$settings = [
			'bgGradient_color' => '#FF0000',
		];

		$result = Form_Widget::build_gradient_css( $settings );
		$this->assertNull( $result );
	}

	/**
	 * Test build_gradient_css returns null when only color 2 is set.
	 */
	public function test_build_gradient_css_only_color_2() {
		$settings = [
			'bgGradient_color_b' => '#00FF00',
		];

		$result = Form_Widget::build_gradient_css( $settings );
		$this->assertNull( $result );
	}

	/**
	 * Test build_gradient_css with defaults produces a linear gradient.
	 */
	public function test_build_gradient_css_default_linear() {
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
	public function test_build_gradient_css_custom_linear() {
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
	public function test_build_gradient_css_radial() {
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
	public function test_build_gradient_css_radial_default_stops() {
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
	public function test_build_gradient_css_empty_type_defaults_linear() {
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
	public function test_build_gradient_css_zero_angle() {
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
	public function test_build_gradient_css_zero_stops() {
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
	 * Test build_gradient_css with global colors resolved.
	 */
	public function test_build_gradient_css_with_global_colors_fallback() {
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
	 * Test build_gradient_css escapes HTML entities in color values.
	 */
	public function test_build_gradient_css_escapes_values() {
		$settings = [
			'bgGradient_color'   => '#FF0000',
			'bgGradient_color_b' => '#00FF00',
		];

		$result = Form_Widget::build_gradient_css( $settings );
		$this->assertIsString( $result );
		$this->assertStringNotContainsString( '<', $result );
		$this->assertStringNotContainsString( '>', $result );
	}

	/**
	 * Test build_gradient_css with non-array stop settings uses defaults.
	 */
	public function test_build_gradient_css_non_array_stops() {
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
	public function test_build_gradient_css_non_array_angle() {
		$settings = [
			'bgGradient_color'          => '#CCCCCC',
			'bgGradient_color_b'        => '#DDDDDD',
			'bgGradient_gradient_angle' => 'not-an-array',
		];

		$result = Form_Widget::build_gradient_css( $settings );
		$this->assertSame( 'linear-gradient(180deg, #CCCCCC 0%, #DDDDDD 100%)', $result );
	}
}
