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

	/**
	 * Test render returns complete HTML email with header, body, and footer.
	 */
	public function test_render() {
		$fields     = [];
		$email_body = '<p>Test email body content</p>';
		$result     = $this->email_template->render( $fields, $email_body );

		$this->assertIsString( $result );
		$this->assertStringContainsString( '<html>', $result );
		$this->assertStringContainsString( 'Test email body content', $result );
		$this->assertStringContainsString( '</html>', $result );
	}

	/**
	 * Test remove_border_from_last_tr_td_table removes border from last row.
	 */
	public function test_remove_border_from_last_tr_td_table() {
		$content = '<table><tr><td style="border-bottom: 1px solid #ccc;">Row 1</td></tr><tr><td style="border-bottom: 1px solid #ccc;">Row 2</td></tr></table>';
		$result  = $this->email_template->remove_border_from_last_tr_td_table( $content );

		$this->assertIsString( $result );
		// The last row's td should not have border-bottom.
		// First row should still have it.
		$this->assertStringContainsString( 'Row 1', $result );
		$this->assertStringContainsString( 'Row 2', $result );
	}

	/**
	 * Test get_raw_header returns the raw HTML header template.
	 */
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

	/**
	 * Test get_raw_footer returns the raw HTML footer template.
	 */
	public function test_get_raw_footer() {
		$result = $this->email_template->get_raw_footer();
		$this->assertIsString( $result );
		$this->assertStringContainsString( '</body>', $result );
		$this->assertStringContainsString( '</html>', $result );
		$this->assertStringContainsString( 'srfm_raw_footer_credit', $result );
		$this->assertStringContainsString( gmdate( 'Y' ), $result );
	}

	/**
	 * Test render_raw returns complete HTML email with raw header, body, and footer.
	 */
	public function test_render_raw() {
		$email_body = '<p>Test submission content</p>';
		$result     = $this->email_template->render_raw( [], $email_body );
		$this->assertIsString( $result );
		$this->assertStringContainsString( '<!DOCTYPE html>', $result );
		$this->assertStringContainsString( $email_body, $result );
		$this->assertStringContainsString( '</html>', $result );
	}
}
