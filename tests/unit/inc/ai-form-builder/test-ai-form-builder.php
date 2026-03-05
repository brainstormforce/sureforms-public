<?php
/**
 * Class Test_AI_Form_Builder
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\AI_Form_Builder\AI_Form_Builder;

class Test_AI_Form_Builder extends TestCase {

	protected $ai_form_builder;

	protected function setUp(): void {
		$this->ai_form_builder = new AI_Form_Builder();
	}

	public function test_class_exists() {
		$this->assertTrue( class_exists( 'SRFM\Inc\AI_Form_Builder\AI_Form_Builder' ) );
	}

	public function test_generate_ai_form_method_exists() {
		$this->assertTrue( method_exists( $this->ai_form_builder, 'generate_ai_form' ) );
	}
}
