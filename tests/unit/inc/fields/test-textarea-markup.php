<?php
/**
 * Tests for the Textarea_Markup field class.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Fields\Textarea_Markup;

class Test_Textarea_Markup extends TestCase {

	protected $textarea_markup;

	protected function setUp(): void {
		$attributes = [
			'required'     => false,
			'fieldWidth'   => '',
			'label'        => 'Message',
			'help'         => '',
			'block_id'     => 'ta001',
			'formId'       => '1',
			'slug'         => 'message',
			'placeholder'  => 'Type your message',
			'defaultValue' => '',
			'checked'      => '',
			'isUnique'     => false,
			'maxLength'    => '500',
			'rows'         => '5',
			'errorMsg'     => '',
			'isRichText'   => false,
			'readOnly'     => false,
		];
		$this->textarea_markup = new Textarea_Markup( $attributes );
	}

	/**
	 * Test markup contains textarea element with correct attributes.
	 */
	public function test_markup_contains_textarea_element() {
		$markup = $this->textarea_markup->markup();
		$this->assertStringContainsString( '<textarea', $markup );
		$this->assertStringContainsString( 'srfm-input-textarea', $markup );
		$this->assertStringContainsString( 'data-block-id="ta001"', $markup );
		$this->assertStringContainsString( 'maxLength="500"', $markup );
		$this->assertStringContainsString( 'rows="5"', $markup );
	}

	/**
	 * Test markup with rich text editor enabled.
	 */
	public function test_markup_with_richtext_enabled() {
		$attributes = [
			'required'     => false,
			'fieldWidth'   => '',
			'label'        => 'Rich Message',
			'help'         => '',
			'block_id'     => 'ta002',
			'formId'       => '1',
			'slug'         => 'rich-message',
			'placeholder'  => '',
			'defaultValue' => '',
			'checked'      => '',
			'isUnique'     => false,
			'maxLength'    => '',
			'rows'         => '',
			'errorMsg'     => '',
			'isRichText'   => true,
			'readOnly'     => false,
		];
		$textarea = new Textarea_Markup( $attributes );
		$markup   = $textarea->markup();
		$this->assertStringContainsString( 'data-is-richtext="true"', $markup );
		$this->assertStringContainsString( 'srfm-richtext', $markup );
		$this->assertStringContainsString( 'quill-editor-container', $markup );
	}
}
