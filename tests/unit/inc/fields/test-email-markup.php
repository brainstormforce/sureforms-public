<?php
/**
 * Tests for the Email_Markup field class.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Fields\Email_Markup;

class Test_Email_Markup extends TestCase {

	protected $email_markup;

	protected function setUp(): void {
		$attributes = [
			'required'       => true,
			'fieldWidth'     => '',
			'label'          => 'Email Address',
			'help'           => '',
			'block_id'       => 'email001',
			'formId'         => '1',
			'slug'           => 'email-address',
			'placeholder'    => 'you@example.com',
			'defaultValue'   => '',
			'checked'        => '',
			'isUnique'       => false,
			'isConfirmEmail' => false,
			'confirmLabel'   => '',
			'errorMsg'       => 'Please enter a valid email.',
			'duplicateMsg'   => '',
			'readOnly'       => false,
		];
		$this->email_markup = new Email_Markup( $attributes );
	}

	/**
	 * Test markup outputs email input type.
	 */
	public function test_markup_contains_email_type() {
		$markup = $this->email_markup->markup();
		$this->assertStringContainsString( 'type="email"', $markup );
		$this->assertStringContainsString( 'srfm-input-email', $markup );
		$this->assertStringContainsString( 'data-block-id="email001"', $markup );
		$this->assertStringContainsString( 'data-required="true"', $markup );
	}

	/**
	 * Test markup with confirm email enabled.
	 */
	public function test_markup_with_confirm_email() {
		$attributes = [
			'required'       => true,
			'fieldWidth'     => '',
			'label'          => 'Email',
			'help'           => '',
			'block_id'       => 'email002',
			'formId'         => '1',
			'slug'           => 'email',
			'placeholder'    => '',
			'defaultValue'   => '',
			'checked'        => '',
			'isUnique'       => false,
			'isConfirmEmail' => true,
			'confirmLabel'   => 'Confirm Email',
			'errorMsg'       => 'Required',
			'duplicateMsg'   => '',
			'readOnly'       => false,
		];
		$email = new Email_Markup( $attributes );
		$markup = $email->markup();
		$this->assertStringContainsString( 'srfm-email-confirm-block', $markup );
		$this->assertStringContainsString( 'srfm-input-email-confirm', $markup );
	}
}
