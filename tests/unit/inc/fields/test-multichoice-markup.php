<?php
/**
 * Tests for the Multichoice_Markup field class.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Fields\Multichoice_Markup;

class Test_Multichoice_Markup extends TestCase {

	protected $multichoice_markup;

	protected function setUp(): void {
		$attributes = [
			'required'            => true,
			'fieldWidth'          => '',
			'label'               => 'Choose Color',
			'help'                => '',
			'block_id'            => 'mc001',
			'formId'              => '1',
			'slug'                => 'choose-color',
			'placeholder'         => '',
			'defaultValue'        => '',
			'checked'             => '',
			'isUnique'            => false,
			'options'             => [
				[ 'optionTitle' => 'Red', 'icon' => '', 'image' => '' ],
				[ 'optionTitle' => 'Blue', 'icon' => '', 'image' => '' ],
				[ 'optionTitle' => 'Green', 'icon' => '', 'image' => '' ],
			],
			'singleSelection'     => false,
			'choiceWidth'         => '',
			'verticalLayout'      => false,
			'optionType'          => 'icon',
			'errorMsg'            => 'Select at least one.',
			'minValue'            => '',
			'maxValue'            => '',
			'preselectedOptions'  => [],
		];
		$this->multichoice_markup = new Multichoice_Markup( $attributes );
	}

	/**
	 * Build a multi-choice field whose options carry descriptions.
	 *
	 * @param bool $enabled Whether the block opts into descriptions.
	 * @return string Rendered markup.
	 */
	private function markup_with_descriptions( $enabled ) {
		$field = new Multichoice_Markup(
			[
				'label'                  => 'Ticket',
				'block_id'               => 'mc002',
				'formId'                 => '1',
				'slug'                   => 'ticket',
				'help'                   => 'Pick one ticket.',
				'singleSelection'        => true,
				'optionType'             => 'icon',
				'showDescriptions' => $enabled,
				'options'                => [
					[ 'optionTitle' => 'Early Bird', 'description' => '$149 until 1 March', 'icon' => '', 'image' => '' ],
					[ 'optionTitle' => 'Standard', 'icon' => '', 'image' => '' ],
				],
			]
		);

		return (string) $field->markup();
	}

	/**
	 * The description renders as its own element, and only once the block opts in.
	 */
	public function test_markup_renders_option_descriptions() {
		$off = $this->markup_with_descriptions( false );
		$this->assertStringNotContainsString( 'srfm-option-description', $off );
		$this->assertStringNotContainsString( '$149 until 1 March', $off );
		$this->assertStringNotContainsString( 'srfm-option-text', $off );

		$on = $this->markup_with_descriptions( true );
		$this->assertStringContainsString( 'class="srfm-option-description" id="srfm-multi-choice-mc002-0-description"', $on );
		$this->assertStringContainsString( '$149 until 1 March', $on );

		// The option that has no description gets no wrapper and no stray element.
		$this->assertSame( 1, substr_count( $on, 'srfm-option-description' ) );
		$this->assertSame( 1, substr_count( $on, 'srfm-option-text' ) );
	}

	/**
	 * The submitted value is read from the label, so the description must stay
	 * out of it: data-option-text and the label text remain the option title
	 * alone, whether or not a description is shown.
	 */
	public function test_markup_keeps_option_description_out_of_the_label() {
		$markup = $this->markup_with_descriptions( true );

		$this->assertStringContainsString( 'data-option-text="Early Bird"', $markup );

		$this->assertSame(
			1,
			preg_match( '#<label[^>]*data-option-text="Early Bird"[^>]*>Early Bird</label>#', $markup ),
			'the label must hold the title and nothing else'
		);
	}

	/**
	 * Each option points at its own description, and the first option keeps the
	 * field-level help id it carried before descriptions existed.
	 */
	public function test_markup_links_option_descriptions_for_screen_readers() {
		$markup = $this->markup_with_descriptions( true );

		$this->assertStringContainsString(
			'aria-describedby="srfm-description-mc002 srfm-multi-choice-mc002-0-description"',
			$markup
		);

		// The second option has no description, so it gains no attribute at all.
		$this->assertSame( 1, substr_count( $markup, 'aria-describedby=' ) );
	}

	/**
	 * Descriptions are author input and are escaped on output.
	 */
	public function test_markup_escapes_option_descriptions() {
		$field = new Multichoice_Markup(
			[
				'label'                  => 'Ticket',
				'block_id'               => 'mc003',
				'formId'                 => '1',
				'slug'                   => 'ticket',
				'showDescriptions' => true,
				'options'                => [
					[ 'optionTitle' => 'Early Bird', 'description' => '<script>alert(1)</script>', 'icon' => '', 'image' => '' ],
				],
			]
		);

		$markup = (string) $field->markup();

		$this->assertStringNotContainsString( '<script>', $markup );
		$this->assertStringContainsString( '&lt;script&gt;', $markup );
	}

	/**
	 * Test markup contains checkbox inputs for multi-select mode.
	 */
	public function test_markup_shows_the_translated_title_but_keeps_the_value() {
		$field = new Multichoice_Markup(
			[
				'label'           => 'Ticket',
				'block_id'        => 'mc003',
				'formId'          => '1',
				'slug'            => 'ticket',
				'singleSelection' => true,
				'optionType'      => 'icon',
				'options'         => [
					[
						'optionTitle'     => 'Early Bird',
						'translatedTitle' => 'Frühbucher',
						'icon'            => '',
					],
				],
			]
		);
		$markup = $field->markup();

		$this->assertStringContainsString( 'data-option-text="Early Bird"', $markup );
		$this->assertStringContainsString( '>Frühbucher</label>', $markup );
		$this->assertStringNotContainsString( '>Early Bird</label>', $markup );
	}

	public function test_markup_contains_checkbox_options() {
		$markup = $this->multichoice_markup->markup();
		$this->assertStringContainsString( 'type="checkbox"', $markup );
		$this->assertStringContainsString( 'srfm-multi-choice-single', $markup );
		$this->assertStringContainsString( 'Red', $markup );
		$this->assertStringContainsString( 'Blue', $markup );
		$this->assertStringContainsString( 'Green', $markup );
		$this->assertStringContainsString( '<fieldset>', $markup );
		$this->assertStringContainsString( 'data-block-id="mc001"', $markup );
		$this->assertStringContainsString( 'srfm-checkbox-mode', $markup );
	}

	/**
	 * Test markup uses radio inputs in single selection mode.
	 */
	public function test_markup_uses_radio_in_single_selection() {
		$attributes = [
			'required'            => false,
			'fieldWidth'          => '',
			'label'               => 'Pick One',
			'help'                => '',
			'block_id'            => 'mc002',
			'formId'              => '1',
			'slug'                => 'pick-one',
			'placeholder'         => '',
			'defaultValue'        => '',
			'checked'             => '',
			'isUnique'            => false,
			'options'             => [
				[ 'optionTitle' => 'Option A', 'icon' => '', 'image' => '' ],
				[ 'optionTitle' => 'Option B', 'icon' => '', 'image' => '' ],
			],
			'singleSelection'     => true,
			'choiceWidth'         => '',
			'verticalLayout'      => false,
			'optionType'          => 'icon',
			'errorMsg'            => '',
			'minValue'            => '',
			'maxValue'            => '',
			'preselectedOptions'  => [],
		];
		$mc     = new Multichoice_Markup( $attributes );
		$markup = $mc->markup();
		$this->assertStringContainsString( 'type="radio"', $markup );
		$this->assertStringContainsString( 'srfm-radio-mode', $markup );
		$this->assertStringContainsString( 'name="srfm-input-multi-choice-mc002"', $markup );
	}

	/**
	 * Test resolve_dynamic_default pre-selects the matching option via GET param.
	 */
	public function test_resolve_dynamic_default() {
		$_SERVER['QUERY_STRING'] = 'service=Blue';

		$attributes = [
			'required'            => false,
			'fieldWidth'          => '',
			'label'               => 'Pick Color',
			'help'                => '',
			'block_id'            => 'mc003',
			'formId'              => '1',
			'slug'                => 'pick-color',
			'placeholder'         => '',
			'defaultValue'        => '',
			'checked'             => '',
			'isUnique'            => false,
			'options'             => [
				[ 'optionTitle' => 'Red', 'icon' => '', 'image' => '' ],
				[ 'optionTitle' => 'Blue', 'icon' => '', 'image' => '' ],
				[ 'optionTitle' => 'Green', 'icon' => '', 'image' => '' ],
			],
			'singleSelection'     => true,
			'choiceWidth'         => '',
			'verticalLayout'      => false,
			'optionType'          => 'icon',
			'errorMsg'            => '',
			'minValue'            => '',
			'maxValue'            => '',
			'preselectedOptions'  => [],
			'dynamicDefaultValue' => '{get_input:service}',
		];
		$mc     = new Multichoice_Markup( $attributes );
		$markup = $mc->markup();

		// "Blue" is at index 1, its input should be checked.
		$this->assertMatchesRegularExpression(
			'/id="srfm-multi-choice-mc003-1"[^>]*checked/',
			$markup,
			'Option "Blue" at index 1 should be checked.'
		);

		unset( $_SERVER['QUERY_STRING'] );
	}
}
