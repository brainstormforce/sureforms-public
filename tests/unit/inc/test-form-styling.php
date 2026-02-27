<?php
/**
 * Class Test_Form_Styling
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

use SRFM\Inc\Form_Styling;

/**
 * Tests for the Form_Styling class.
 */
class Test_Form_Styling extends TestCase {

	/**
	 * Existing form styling used as base for tests.
	 *
	 * @var array<string,mixed>
	 */
	private $base_styling;

	protected function setUp(): void {
		parent::setUp();

		$this->base_styling = [
			'primary_color' => '#000000',
			'text_color'    => '#333333',
		];
	}

	// ─── map_block_attrs_to_styling ───────────────────────────────

	/**
	 * Test that empty block attributes return original styling unchanged.
	 */
	public function test_map_block_attrs_returns_original_when_attrs_empty() {
		$result = Form_Styling::map_block_attrs_to_styling( $this->base_styling, [] );
		$this->assertSame( $this->base_styling, $result );
	}

	/**
	 * Test color attributes are mapped correctly.
	 */
	public function test_map_block_attrs_maps_colors() {
		$attrs = [
			'primaryColor'       => '#FF0000',
			'textColor'          => '#00FF00',
			'textOnPrimaryColor' => '#FFFFFF',
		];

		$result = Form_Styling::map_block_attrs_to_styling( $this->base_styling, $attrs );

		$this->assertSame( '#FF0000', $result['primary_color'] );
		$this->assertSame( '#00FF00', $result['text_color'] );
		$this->assertSame( '#FFFFFF', $result['text_color_on_primary'] );
	}

	/**
	 * Test padding attributes are mapped and cast to float.
	 */
	public function test_map_block_attrs_maps_padding() {
		$attrs = [
			'formPaddingTop'    => '10',
			'formPaddingRight'  => 20,
			'formPaddingBottom' => '30.5',
			'formPaddingLeft'   => 0,
			'formPaddingUnit'   => 'px',
		];

		$result = Form_Styling::map_block_attrs_to_styling( [], $attrs );

		$this->assertSame( 10.0, $result['form_padding_top'] );
		$this->assertSame( 20.0, $result['form_padding_right'] );
		$this->assertSame( 30.5, $result['form_padding_bottom'] );
		$this->assertSame( 0.0, $result['form_padding_left'] );
		$this->assertSame( 'px', $result['form_padding_unit'] );
	}

	/**
	 * Test that padding zero value is accepted (isset check, not empty).
	 */
	public function test_map_block_attrs_accepts_zero_padding() {
		$attrs = [
			'formPaddingTop' => 0,
		];

		$result = Form_Styling::map_block_attrs_to_styling( [], $attrs );

		$this->assertArrayHasKey( 'form_padding_top', $result );
		$this->assertSame( 0.0, $result['form_padding_top'] );
	}

	/**
	 * Test that non-scalar padding values are skipped.
	 */
	public function test_map_block_attrs_skips_non_scalar_padding() {
		$attrs = [
			'formPaddingTop' => [ 10 ],
		];

		$result = Form_Styling::map_block_attrs_to_styling( [], $attrs );

		$this->assertArrayNotHasKey( 'form_padding_top', $result );
	}

	/**
	 * Test border radius attributes are mapped and cast to float.
	 */
	public function test_map_block_attrs_maps_border_radius() {
		$attrs = [
			'formBorderRadiusTop'    => '5',
			'formBorderRadiusRight'  => 10,
			'formBorderRadiusBottom' => '15.5',
			'formBorderRadiusLeft'   => 0,
			'formBorderRadiusUnit'   => 'rem',
		];

		$result = Form_Styling::map_block_attrs_to_styling( [], $attrs );

		$this->assertSame( 5.0, $result['form_border_radius_top'] );
		$this->assertSame( 10.0, $result['form_border_radius_right'] );
		$this->assertSame( 15.5, $result['form_border_radius_bottom'] );
		$this->assertSame( 0.0, $result['form_border_radius_left'] );
		$this->assertSame( 'rem', $result['form_border_radius_unit'] );
	}

	/**
	 * Test that non-scalar border radius values are skipped.
	 */
	public function test_map_block_attrs_skips_non_scalar_border_radius() {
		$attrs = [
			'formBorderRadiusTop' => [ 5 ],
		];

		$result = Form_Styling::map_block_attrs_to_styling( [], $attrs );

		$this->assertArrayNotHasKey( 'form_border_radius_top', $result );
	}

	/**
	 * Test background attributes are mapped correctly.
	 */
	public function test_map_block_attrs_maps_background() {
		$attrs = [
			'bgType'            => 'image',
			'bgColor'           => '#EEEEEE',
			'bgGradient'        => 'linear-gradient(90deg, #000, #fff)',
			'bgImage'           => 'https://example.com/image.jpg',
			'bgImagePosition'   => 'center center',
			'bgImageSize'       => 'cover',
			'bgImageRepeat'     => 'no-repeat',
			'bgImageAttachment' => 'fixed',
		];

		$result = Form_Styling::map_block_attrs_to_styling( [], $attrs );

		$this->assertSame( 'image', $result['bg_type'] );
		$this->assertSame( '#EEEEEE', $result['bg_color'] );
		$this->assertSame( 'linear-gradient(90deg, #000, #fff)', $result['bg_gradient'] );
		$this->assertSame( 'https://example.com/image.jpg', $result['bg_image'] );
		$this->assertSame( 'center center', $result['bg_image_position'] );
		$this->assertSame( 'cover', $result['bg_image_size'] );
		$this->assertSame( 'no-repeat', $result['bg_image_repeat'] );
		$this->assertSame( 'fixed', $result['bg_image_attachment'] );
	}

	/**
	 * Test field spacing and button alignment are mapped.
	 */
	public function test_map_block_attrs_maps_spacing_and_alignment() {
		$attrs = [
			'fieldSpacing'    => 'medium',
			'buttonAlignment' => 'center',
		];

		$result = Form_Styling::map_block_attrs_to_styling( [], $attrs );

		$this->assertSame( 'medium', $result['field_spacing'] );
		$this->assertSame( 'center', $result['submit_button_alignment'] );
	}

	/**
	 * Test that empty string attributes are not mapped (empty check).
	 */
	public function test_map_block_attrs_skips_empty_string_values() {
		$attrs = [
			'primaryColor'    => '',
			'bgType'          => '',
			'fieldSpacing'    => '',
			'buttonAlignment' => '',
		];

		$result = Form_Styling::map_block_attrs_to_styling( $this->base_styling, $attrs );

		// Original values should be preserved, empty ones not overwritten.
		$this->assertSame( '#000000', $result['primary_color'] );
		$this->assertArrayNotHasKey( 'bg_type', $result );
		$this->assertArrayNotHasKey( 'field_spacing', $result );
		$this->assertArrayNotHasKey( 'submit_button_alignment', $result );
	}

	/**
	 * Test that original styling keys are preserved alongside new ones.
	 */
	public function test_map_block_attrs_preserves_existing_styling() {
		$existing = [
			'custom_key'    => 'custom_value',
			'primary_color' => '#000000',
		];

		$attrs = [
			'textColor' => '#FF0000',
		];

		$result = Form_Styling::map_block_attrs_to_styling( $existing, $attrs );

		$this->assertSame( 'custom_value', $result['custom_key'] );
		$this->assertSame( '#000000', $result['primary_color'] );
		$this->assertSame( '#FF0000', $result['text_color'] );
	}

	/**
	 * Test form theme attribute triggers apply_theme_styling.
	 */
	public function test_map_block_attrs_maps_form_theme() {
		$attrs = [
			'formTheme' => 'modern',
		];

		$result = Form_Styling::map_block_attrs_to_styling( [], $attrs );

		$this->assertSame( 'modern', $result['form_theme'] );
	}

	/**
	 * Test the srfm_embed_block_attrs_to_styling filter is applied.
	 */
	public function test_map_block_attrs_applies_embed_filter() {
		add_filter(
			'srfm_embed_block_attrs_to_styling',
			function ( $form_styling, $block_attrs ) {
				$form_styling['from_filter'] = true;
				return $form_styling;
			},
			10,
			2
		);

		$result = Form_Styling::map_block_attrs_to_styling( [], [ 'primaryColor' => '#FF0000' ] );

		$this->assertTrue( $result['from_filter'] );

		remove_all_filters( 'srfm_embed_block_attrs_to_styling' );
	}

	// ─── apply_theme_styling ──────────────────────────────────────

	/**
	 * Test empty theme slug returns original styling.
	 */
	public function test_apply_theme_styling_returns_original_for_empty_slug() {
		$result = Form_Styling::apply_theme_styling( $this->base_styling, '' );
		$this->assertSame( $this->base_styling, $result );
	}

	/**
	 * Test 'default' theme slug returns original styling.
	 */
	public function test_apply_theme_styling_returns_original_for_default_slug() {
		$result = Form_Styling::apply_theme_styling( $this->base_styling, 'default' );
		$this->assertSame( $this->base_styling, $result );
	}

	/**
	 * Test non-default theme slug applies the filter.
	 */
	public function test_apply_theme_styling_applies_filter_for_non_default_theme() {
		add_filter(
			'srfm_apply_form_theme_styling',
			function ( $form_styling, $theme_slug ) {
				$form_styling['theme_applied'] = $theme_slug;
				return $form_styling;
			},
			10,
			2
		);

		$result = Form_Styling::apply_theme_styling( $this->base_styling, 'modern' );

		$this->assertSame( 'modern', $result['theme_applied'] );
		// Original values are still present since the filter merges.
		$this->assertSame( '#000000', $result['primary_color'] );

		remove_all_filters( 'srfm_apply_form_theme_styling' );
	}

	/**
	 * Test that without a filter, the styling is returned unchanged for non-default theme.
	 */
	public function test_apply_theme_styling_returns_unchanged_without_filter() {
		remove_all_filters( 'srfm_apply_form_theme_styling' );

		$result = Form_Styling::apply_theme_styling( $this->base_styling, 'modern' );

		$this->assertSame( $this->base_styling, $result );
	}

	// ─── has_custom_styling ───────────────────────────────────────

	/**
	 * Test returns true when formTheme is 'default'.
	 */
	public function test_has_custom_styling_returns_true_when_form_theme_default() {
		$attrs = [ 'formTheme' => 'default' ];

		$this->assertTrue( Form_Styling::has_custom_styling( $attrs ) );
	}

	/**
	 * Test returns true when formTheme is 'custom'.
	 */
	public function test_has_custom_styling_returns_true_when_form_theme_custom() {
		$attrs = [ 'formTheme' => 'custom' ];

		$this->assertTrue( Form_Styling::has_custom_styling( $attrs ) );
	}

	/**
	 * Test returns false when formTheme is 'inherit'.
	 */
	public function test_has_custom_styling_returns_false_when_form_theme_inherit() {
		$attrs = [ 'formTheme' => 'inherit' ];

		$this->assertFalse( Form_Styling::has_custom_styling( $attrs ) );
	}

	/**
	 * Test returns false when formTheme is not set (defaults to 'inherit').
	 */
	public function test_has_custom_styling_returns_false_when_not_set() {
		$this->assertFalse( Form_Styling::has_custom_styling( [] ) );
	}
}
