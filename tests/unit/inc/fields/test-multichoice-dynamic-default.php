<?php
/**
 * Tests for the dynamic default value feature in Multichoice_Markup.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Fields\Multichoice_Markup;

class Test_Multichoice_Dynamic_Default extends TestCase {

	/**
	 * Base attributes for multi-choice field.
	 *
	 * @var array<mixed>
	 */
	private $base_attributes;

	protected function setUp(): void {
		$this->base_attributes = [
			'required'            => false,
			'fieldWidth'          => '',
			'label'               => 'Select Service',
			'help'                => '',
			'block_id'            => 'mc-dyn-001',
			'formId'              => '1',
			'slug'                => 'select-service',
			'placeholder'         => '',
			'defaultValue'        => '',
			'checked'             => '',
			'isUnique'            => false,
			'options'             => [
				[ 'optionTitle' => 'Development', 'icon' => '', 'image' => '' ],
				[ 'optionTitle' => 'Design', 'icon' => '', 'image' => '' ],
				[ 'optionTitle' => 'Consulting', 'icon' => '', 'image' => '' ],
			],
			'singleSelection'     => true,
			'choiceWidth'         => '',
			'verticalLayout'      => false,
			'optionType'          => 'icon',
			'errorMsg'            => '',
			'minValue'            => '',
			'maxValue'            => '',
			'preselectedOptions'  => [],
			'dynamicDefaultValue' => '',
		];
	}

	protected function tearDown(): void {
		unset( $_SERVER['QUERY_STRING'] );
	}

	/**
	 * Test that GET param smart tag pre-selects the matching option.
	 */
	public function test_get_param_preselects_matching_option() {
		$_SERVER['QUERY_STRING'] = 'service=Design';

		$attributes                       = $this->base_attributes;
		$attributes['dynamicDefaultValue'] = '{get_input:service}';

		$mc     = new Multichoice_Markup( $attributes );
		$markup = $mc->markup();

		// "Design" is at index 1, its input should be checked.
		$this->assertStringContainsString( 'type="radio"', $markup );
		$this->assert_option_checked( $markup, 'mc-dyn-001', 1 );
	}

	/**
	 * Test case-insensitive matching of GET param value against option titles.
	 */
	public function test_get_param_case_insensitive_match() {
		$_SERVER['QUERY_STRING'] = 'service=CONSULTING';

		$attributes                       = $this->base_attributes;
		$attributes['dynamicDefaultValue'] = '{get_input:service}';

		$mc     = new Multichoice_Markup( $attributes );
		$markup = $mc->markup();

		// "Consulting" is at index 2.
		$this->assert_option_checked( $markup, 'mc-dyn-001', 2 );
	}

	/**
	 * Test that no option is checked when GET param value doesn't match any option.
	 */
	public function test_get_param_no_match_leaves_unchecked() {
		$_SERVER['QUERY_STRING'] = 'service=Marketing';

		$attributes                       = $this->base_attributes;
		$attributes['dynamicDefaultValue'] = '{get_input:service}';

		$mc     = new Multichoice_Markup( $attributes );
		$markup = $mc->markup();

		$this->assertStringNotContainsString( 'checked', $markup );
	}

	/**
	 * Test that empty GET param does not pre-select any option.
	 */
	public function test_empty_get_param_no_preselection() {
		$_SERVER['QUERY_STRING'] = '';

		$attributes                       = $this->base_attributes;
		$attributes['dynamicDefaultValue'] = '{get_input:service}';

		$mc     = new Multichoice_Markup( $attributes );
		$markup = $mc->markup();

		$this->assertStringNotContainsString( 'checked', $markup );
	}

	/**
	 * Test that dynamic default is ignored when singleSelection is false (multi-select mode).
	 */
	public function test_dynamic_default_ignored_in_multi_select_mode() {
		$_SERVER['QUERY_STRING'] = 'service=Design';

		$attributes                       = $this->base_attributes;
		$attributes['singleSelection']     = false;
		$attributes['dynamicDefaultValue'] = '{get_input:service}';

		$mc     = new Multichoice_Markup( $attributes );
		$markup = $mc->markup();

		$this->assertStringNotContainsString( 'checked', $markup );
	}

	/**
	 * Test that dynamic default overrides static preselected options.
	 */
	public function test_dynamic_default_overrides_static_preselection() {
		$_SERVER['QUERY_STRING'] = 'service=Consulting';

		$attributes                        = $this->base_attributes;
		$attributes['preselectedOptions']  = [ 0 ]; // Static: "Development" preselected.
		$attributes['dynamicDefaultValue'] = '{get_input:service}';

		$mc     = new Multichoice_Markup( $attributes );
		$markup = $mc->markup();

		// "Consulting" (index 2) should be checked, not "Development" (index 0).
		$this->assert_option_checked( $markup, 'mc-dyn-001', 2 );
		$this->assert_option_not_checked( $markup, 'mc-dyn-001', 0 );
	}

	/**
	 * Test that empty dynamicDefaultValue preserves static preselected options.
	 */
	public function test_empty_dynamic_default_preserves_static_preselection() {
		$attributes                        = $this->base_attributes;
		$attributes['preselectedOptions']  = [ 0 ];
		$attributes['dynamicDefaultValue'] = '';

		$mc     = new Multichoice_Markup( $attributes );
		$markup = $mc->markup();

		$this->assert_option_checked( $markup, 'mc-dyn-001', 0 );
	}

	/**
	 * Test the first matching option is selected when GET param matches.
	 */
	public function test_get_param_selects_first_option() {
		$_SERVER['QUERY_STRING'] = 'service=Development';

		$attributes                       = $this->base_attributes;
		$attributes['dynamicDefaultValue'] = '{get_input:service}';

		$mc     = new Multichoice_Markup( $attributes );
		$markup = $mc->markup();

		$this->assert_option_checked( $markup, 'mc-dyn-001', 0 );
	}

	/**
	 * Assert that a specific multi-choice option is checked in the markup.
	 *
	 * @param string $markup   The rendered HTML markup.
	 * @param string $block_id The block ID.
	 * @param int    $index    The option index.
	 */
	private function assert_option_checked( $markup, $block_id, $index ) {
		$pattern = '/id="srfm-multi-choice-' . preg_quote( $block_id . '-' . $index, '/' ) . '"[^>]*checked/';
		$this->assertMatchesRegularExpression( $pattern, $markup, "Option at index {$index} should be checked." );
	}

	/**
	 * Assert that a specific multi-choice option is NOT checked in the markup.
	 *
	 * @param string $markup   The rendered HTML markup.
	 * @param string $block_id The block ID.
	 * @param int    $index    The option index.
	 */
	private function assert_option_not_checked( $markup, $block_id, $index ) {
		$pattern = '/id="srfm-multi-choice-' . preg_quote( $block_id . '-' . $index, '/' ) . '"[^>]*checked/';
		$this->assertDoesNotMatchRegularExpression( $pattern, $markup, "Option at index {$index} should NOT be checked." );
	}
}
