<?php
/**
 * Tests for the Input_Markup field class.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Fields\Input_Markup;

class Test_Input_Markup extends TestCase {

	protected $input_markup;

	protected function setUp(): void {
		$attributes = [
			'required'     => true,
			'fieldWidth'   => '',
			'label'        => 'Full Name',
			'help'         => '',
			'block_id'     => 'input001',
			'formId'       => '1',
			'slug'         => 'full-name',
			'placeholder'  => 'Enter your name',
			'defaultValue' => '',
			'checked'      => '',
			'isUnique'     => false,
			'textLength'   => '100',
			'inputMask'    => '',
			'errorMsg'     => 'This field is required.',
			'duplicateMsg' => '',
			'readOnly'     => false,
		];
		$this->input_markup = new Input_Markup( $attributes );
	}

	/**
	 * Test markup contains correct input type and class.
	 */
	public function test_markup_contains_input_type_text() {
		$markup = $this->input_markup->markup();
		$this->assertStringContainsString( 'type="text"', $markup );
		$this->assertStringContainsString( 'srfm-input-input', $markup );
		$this->assertStringContainsString( 'data-block-id="input001"', $markup );
		$this->assertStringContainsString( 'maxlength="100"', $markup );
		$this->assertStringContainsString( 'data-required="true"', $markup );
	}

	/**
	 * Test markup with custom input mask attributes.
	 */
	public function test_markup_with_input_mask() {
		$attributes = [
			'required'        => false,
			'fieldWidth'      => '',
			'label'           => 'Masked Field',
			'help'            => '',
			'block_id'        => 'input002',
			'formId'          => '1',
			'slug'            => 'masked-field',
			'placeholder'     => '',
			'defaultValue'    => '',
			'checked'         => '',
			'isUnique'        => false,
			'textLength'      => '',
			'inputMask'       => 'custom-mask',
			'customInputMask' => '999-999-9999',
			'errorMsg'        => '',
			'duplicateMsg'    => '',
			'readOnly'        => false,
		];
		$input = new Input_Markup( $attributes );
		$markup = $input->markup();
		$this->assertStringContainsString( 'data-srfm-mask="custom-mask"', $markup );
		$this->assertStringContainsString( 'data-custom-srfm-mask="999-999-9999"', $markup );
		$this->assertStringContainsString( 'data-required="false"', $markup );
	}
}
