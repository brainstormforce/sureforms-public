<?php
/**
 * Class Test_Email_Template
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Email\Email_Template;

class Test_Email_Template extends TestCase {

	protected $email_template;

	protected function setUp(): void {
		$this->email_template = new Email_Template();
	}

	public function test_get_header_returns_html() {
		$result = $this->email_template->get_header();
		$this->assertIsString( $result );
		$this->assertStringContainsString( '<html>', $result );
		$this->assertStringContainsString( '<head>', $result );
		$this->assertStringContainsString( '<body', $result );
		$this->assertStringContainsString( 'srfm_wrapper', $result );
	}

	public function test_get_footer_returns_html() {
		$result = $this->email_template->get_footer();
		$this->assertIsString( $result );
		$this->assertStringContainsString( '</html>', $result );
		$this->assertStringContainsString( '</body>', $result );
		$this->assertStringContainsString( '</table>', $result );
	}

	public function test_get_raw_header() {
		$result = $this->email_template->get_raw_header();
		$this->assertIsString( $result );
		$this->assertStringContainsString( '<!DOCTYPE html>', $result );
		$this->assertStringContainsString( '<html dir=', $result );
		$this->assertStringContainsString( '<head>', $result );
		$this->assertStringContainsString( '<body', $result );
		$this->assertStringContainsString( 'srfm_raw_wrapper', $result );
		$this->assertStringContainsString( 'srfm_raw_body_content_inner', $result );
	}

	public function test_get_raw_footer() {
		$result = $this->email_template->get_raw_footer();
		$this->assertIsString( $result );
		$this->assertStringContainsString( '</body>', $result );
		$this->assertStringContainsString( '</html>', $result );
		$this->assertStringContainsString( 'srfm_raw_footer_credit', $result );
		$this->assertStringContainsString( gmdate( 'Y' ), $result );
	}

	public function test_render_raw() {
		$email_body = '<p>Test submission content</p>';
		$result     = $this->email_template->render_raw( [], $email_body );
		$this->assertIsString( $result );
		$this->assertStringContainsString( '<!DOCTYPE html>', $result );
		$this->assertStringContainsString( $email_body, $result );
		$this->assertStringContainsString( '</html>', $result );
	}
}
