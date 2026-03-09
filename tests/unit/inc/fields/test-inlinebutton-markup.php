<?php
/**
 * Tests for the Inlinebutton_Markup field class.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Fields\Inlinebutton_Markup;

class Test_Inlinebutton_Markup extends TestCase {

	protected $button_markup;

	protected function setUp(): void {
		$form_id = wp_insert_post( [
			'post_type'   => 'sureforms_form',
			'post_status' => 'publish',
			'post_title'  => 'Test Form',
		] );

		update_post_meta( $form_id, '_srfm_inherit_theme_button', '' );
		update_post_meta( $form_id, '_srfm_captcha_security_type', 'none' );
		update_post_meta( $form_id, '_srfm_form_recaptcha', '' );

		$attributes = [
			'required'     => false,
			'fieldWidth'   => '',
			'label'        => '',
			'help'         => '',
			'block_id'     => 'btn001',
			'formId'       => (string) $form_id,
			'slug'         => 'submit',
			'placeholder'  => '',
			'defaultValue' => '',
			'checked'      => '',
			'isUnique'     => false,
			'buttonText'   => 'Submit Form',
		];
		$this->button_markup = new Inlinebutton_Markup( $attributes );
	}

	/**
	 * Test markup contains submit button with text.
	 */
	public function test_markup_contains_submit_button() {
		$markup = $this->button_markup->markup();
		$this->assertStringContainsString( '<button', $markup );
		$this->assertStringContainsString( 'srfm-submit-btn', $markup );
		$this->assertStringContainsString( 'Submit Form', $markup );
		$this->assertStringContainsString( 'srfm-submit-wrap', $markup );
		$this->assertStringContainsString( 'srfm-loader', $markup );
	}

	/**
	 * Test markup does not contain captcha when security type is none.
	 */
	public function test_markup_no_captcha_when_none() {
		$markup = $this->button_markup->markup();
		$this->assertStringNotContainsString( 'srfm-captcha-container', $markup );
		$this->assertStringNotContainsString( 'g-recaptcha', $markup );
		$this->assertStringNotContainsString( 'cf-turnstile', $markup );
		$this->assertStringNotContainsString( 'h-captcha', $markup );
	}
}
