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
}
