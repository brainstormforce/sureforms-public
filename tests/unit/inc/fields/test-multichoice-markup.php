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
	 * Test markup contains checkbox inputs for multi-select mode.
	 */
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
}
