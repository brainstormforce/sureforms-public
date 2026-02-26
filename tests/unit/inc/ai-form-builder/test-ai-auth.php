<?php
/**
 * Class Test_AI_Auth
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\AI_Form_Builder\AI_Auth;

class Test_AI_Auth extends TestCase {

	protected $ai_auth;

	protected function setUp(): void {
		$this->ai_auth = new AI_Auth();
	}

	public function test_decrypt_access_key_empty_data() {
		$result = $this->ai_auth->decrypt_access_key( '', 'somekey' );
		$this->assertFalse( $result );
	}

	public function test_decrypt_access_key_method_exists() {
		$this->assertTrue( method_exists( $this->ai_auth, 'decrypt_access_key' ) );
	}
}
