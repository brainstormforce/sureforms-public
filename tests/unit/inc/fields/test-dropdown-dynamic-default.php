<?php
/**
 * Tests for the dynamic default value feature in Dropdown_Markup.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Fields\Dropdown_Markup;

class Test_Dropdown_Dynamic_Default extends TestCase {

	/**
	 * Base attributes for dropdown field.
	 *
	 * @var array<mixed>
	 */
	private $base_attributes;

	protected function setUp(): void {
		$this->base_attributes = [
			'required'            => false,
			'fieldWidth'          => '',
			'label'               => 'Select Plan',
			'help'                => '',
			'block_id'            => 'dd-dyn-001',
			'formId'              => '1',
			'slug'                => 'select-plan',
			'placeholder'         => 'Select an option',
			'defaultValue'        => '',
			'checked'             => '',
			'isUnique'            => false,
			'options'             => [
				[ 'label' => 'Basic', 'icon' => '' ],
				[ 'label' => 'Professional', 'icon' => '' ],
				[ 'label' => 'Enterprise', 'icon' => '' ],
			],
			'multiSelect'         => false,
			'searchable'          => false,
			'preselectedOptions'  => [],
			'errorMsg'            => '',
			'minValue'            => '',
			'maxValue'            => '',
			'dynamicDefaultValue' => '',
		];
	}

	protected function tearDown(): void {
		unset( $_SERVER['QUERY_STRING'] );
	}

	/**
	 * Test that GET param smart tag pre-selects the matching dropdown option.
	 */
	public function test_get_param_preselects_matching_option() {
		$_SERVER['QUERY_STRING'] = 'plan=Professional';

		$attributes                       = $this->base_attributes;
		$attributes['dynamicDefaultValue'] = '{get_input:plan}';

		$dd     = new Dropdown_Markup( $attributes );
		$markup = $dd->markup();

		// "Professional" is at index 1, should have "selected" attribute.
		$this->assert_option_selected( $markup, 'Professional' );
	}

	/**
	 * Test case-insensitive matching of GET param value against option labels.
	 */
	public function test_get_param_case_insensitive_match() {
		$_SERVER['QUERY_STRING'] = 'plan=ENTERPRISE';

		$attributes                       = $this->base_attributes;
		$attributes['dynamicDefaultValue'] = '{get_input:plan}';

		$dd     = new Dropdown_Markup( $attributes );
		$markup = $dd->markup();

		$this->assert_option_selected( $markup, 'Enterprise' );
	}

	/**
	 * Test that no option is selected when GET param value doesn't match any option.
	 */
	public function test_get_param_no_match_leaves_unselected() {
		$_SERVER['QUERY_STRING'] = 'plan=Premium';

		$attributes                       = $this->base_attributes;
		$attributes['dynamicDefaultValue'] = '{get_input:plan}';

		$dd     = new Dropdown_Markup( $attributes );
		$markup = $dd->markup();

		// Only the placeholder should have "selected", not any real option.
		$this->assert_no_option_selected( $markup );
	}

	/**
	 * Test that empty GET param does not pre-select any option.
	 */
	public function test_empty_get_param_no_preselection() {
		$_SERVER['QUERY_STRING'] = '';

		$attributes                       = $this->base_attributes;
		$attributes['dynamicDefaultValue'] = '{get_input:plan}';

		$dd     = new Dropdown_Markup( $attributes );
		$markup = $dd->markup();

		$this->assert_no_option_selected( $markup );
	}

	/**
	 * Test that dynamic default is ignored when multiSelect is true.
	 */
	public function test_dynamic_default_ignored_in_multi_select_mode() {
		$_SERVER['QUERY_STRING'] = 'plan=Professional';

		$attributes                       = $this->base_attributes;
		$attributes['multiSelect']         = true;
		$attributes['dynamicDefaultValue'] = '{get_input:plan}';

		$dd     = new Dropdown_Markup( $attributes );
		$markup = $dd->markup();

		$this->assert_no_option_selected( $markup );
	}

	/**
	 * Test that dynamic default overrides static preselected options.
	 */
	public function test_dynamic_default_overrides_static_preselection() {
		$_SERVER['QUERY_STRING'] = 'plan=Enterprise';

		$attributes                        = $this->base_attributes;
		$attributes['preselectedOptions']  = [ 0 ]; // Static: "Basic" preselected.
		$attributes['dynamicDefaultValue'] = '{get_input:plan}';

		$dd     = new Dropdown_Markup( $attributes );
		$markup = $dd->markup();

		// "Enterprise" should be selected, not "Basic".
		$this->assert_option_selected( $markup, 'Enterprise' );
		$this->assert_option_not_selected( $markup, 'Basic' );
	}

	/**
	 * Test that empty dynamicDefaultValue preserves static preselected options.
	 */
	public function test_empty_dynamic_default_preserves_static_preselection() {
		$attributes                        = $this->base_attributes;
		$attributes['preselectedOptions']  = [ 0 ];
		$attributes['dynamicDefaultValue'] = '';

		$dd     = new Dropdown_Markup( $attributes );
		$markup = $dd->markup();

		$this->assert_option_selected( $markup, 'Basic' );
	}

	/**
	 * Test the first option can be selected via GET param.
	 */
	public function test_get_param_selects_first_option() {
		$_SERVER['QUERY_STRING'] = 'plan=Basic';

		$attributes                       = $this->base_attributes;
		$attributes['dynamicDefaultValue'] = '{get_input:plan}';

		$dd     = new Dropdown_Markup( $attributes );
		$markup = $dd->markup();

		$this->assert_option_selected( $markup, 'Basic' );
	}

	/**
	 * Test that data-preselected attribute is set when dynamic default resolves.
	 */
	public function test_data_preselected_attribute_set_on_match() {
		$_SERVER['QUERY_STRING'] = 'plan=Professional';

		$attributes                       = $this->base_attributes;
		$attributes['dynamicDefaultValue'] = '{get_input:plan}';

		$dd     = new Dropdown_Markup( $attributes );
		$markup = $dd->markup();

		$this->assertStringContainsString( 'data-preselected', $markup );
		$this->assertStringContainsString( 'Professional', $markup );
	}

	/**
	 * Assert that a dropdown option with the given label is selected.
	 *
	 * @param string $markup The rendered HTML markup.
	 * @param string $label  The option label to check.
	 */
	private function assert_option_selected( $markup, $label ) {
		$pattern = '/<option[^>]*value="' . preg_quote( $label, '/' ) . '"[^>]*selected/';
		$this->assertMatchesRegularExpression( $pattern, $markup, "Option '{$label}' should be selected." );
	}

	/**
	 * Assert that a dropdown option with the given label is NOT selected.
	 *
	 * @param string $markup The rendered HTML markup.
	 * @param string $label  The option label to check.
	 */
	private function assert_option_not_selected( $markup, $label ) {
		$pattern = '/<option[^>]*value="' . preg_quote( $label, '/' ) . '"[^>]*selected/';
		$this->assertDoesNotMatchRegularExpression( $pattern, $markup, "Option '{$label}' should NOT be selected." );
	}

	/**
	 * Assert that no real option (excluding placeholder) is selected.
	 *
	 * @param string $markup The rendered HTML markup.
	 */
	private function assert_no_option_selected( $markup ) {
		// Count "selected" occurrences — only the placeholder should have it.
		$count = substr_count( $markup, 'selected' );
		$this->assertLessThanOrEqual( 1, $count, 'Only the placeholder option should have "selected" attribute.' );
	}
}
