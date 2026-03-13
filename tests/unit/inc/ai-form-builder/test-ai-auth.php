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

	/**
	 * Admin user ID for tests that need authentication.
	 *
	 * @var int
	 */
	protected $admin_id;

	protected function setUp(): void {
		$this->ai_auth  = new AI_Auth();
		$this->admin_id = wp_insert_user(
			[
				'user_login' => 'ai_auth_admin_' . wp_rand(),
				'user_pass'  => 'test',
				'role'       => 'administrator',
			]
		);
	}

	protected function tearDown(): void {
		wp_set_current_user( 0 );
		delete_transient( 'srfm_ai_auth_key_' . $this->admin_id );
		wp_delete_user( $this->admin_id );
	}

	public function test_decrypt_access_key_empty_data() {
		$result = $this->ai_auth->decrypt_access_key( '', 'somekey' );
		$this->assertFalse( $result );
	}

	public function test_decrypt_access_key_method_exists() {
		$this->assertTrue( method_exists( $this->ai_auth, 'decrypt_access_key' ) );
	}

	/**
	 * Test that get_auth_url rejects requests without a valid nonce.
	 */
	public function test_get_auth_url_rejects_invalid_nonce() {
		$request = new \WP_REST_Request( 'GET', '/sureforms/v1/ai-auth' );

		ob_start();
		try {
			$this->ai_auth->get_auth_url( $request );
		} catch ( \WPDieException $e ) {
			$output = ob_get_clean();
			$data   = json_decode( $output, true );
			$this->assertIsArray( $data );
			$this->assertFalse( $data['success'] );
			return;
		}
		ob_end_clean();
		$this->fail( 'Expected WPDieException was not thrown.' );
	}

	/**
	 * Test that get_auth_url stores a transient and returns a billing portal URL.
	 */
	public function test_get_auth_url_stores_transient_and_returns_url() {
		wp_set_current_user( $this->admin_id );
		$nonce = wp_create_nonce( 'wp_rest' );

		$request = new \WP_REST_Request( 'GET', '/sureforms/v1/ai-auth' );
		$request->set_header( 'X-WP-Nonce', $nonce );

		ob_start();
		try {
			$this->ai_auth->get_auth_url( $request );
		} catch ( \WPDieException $e ) {
			$output = ob_get_clean();
			$data   = json_decode( $output, true );
			$this->assertIsArray( $data );
			$this->assertTrue( $data['success'] );
			$this->assertStringContainsString( 'auth/?token=', $data['data'] );

			// Verify transient was stored with a 16-char key.
			$stored_key = get_transient( 'srfm_ai_auth_key_' . $this->admin_id );
			$this->assertNotEmpty( $stored_key );
			$this->assertEquals( 16, strlen( $stored_key ) );
			return;
		}
		ob_end_clean();
		$this->fail( 'Expected WPDieException was not thrown.' );
	}

	/**
	 * Test that handle_access_key rejects requests without a valid nonce.
	 */
	public function test_handle_access_key_rejects_invalid_nonce() {
		$request = new \WP_REST_Request( 'POST', '/sureforms/v1/ai-auth/access-key' );

		ob_start();
		try {
			$this->ai_auth->handle_access_key( $request );
		} catch ( \WPDieException $e ) {
			$output = ob_get_clean();
			$data   = json_decode( $output, true );
			$this->assertIsArray( $data );
			$this->assertFalse( $data['success'] );
			return;
		}
		ob_end_clean();
		$this->fail( 'Expected WPDieException was not thrown.' );
	}

	/**
	 * Test that handle_access_key rejects an empty body.
	 */
	public function test_handle_access_key_rejects_empty_body() {
		wp_set_current_user( $this->admin_id );
		$nonce = wp_create_nonce( 'wp_rest' );

		$request = new \WP_REST_Request( 'POST', '/sureforms/v1/ai-auth/access-key' );
		$request->set_header( 'X-WP-Nonce', $nonce );
		// Empty body — no JSON payload.

		ob_start();
		try {
			$this->ai_auth->handle_access_key( $request );
		} catch ( \WPDieException $e ) {
			$output = ob_get_clean();
			$data   = json_decode( $output, true );
			$this->assertIsArray( $data );
			$this->assertFalse( $data['success'] );
			return;
		}
		ob_end_clean();
		$this->fail( 'Expected WPDieException was not thrown.' );
	}

	/**
	 * Test that handle_access_key rejects when auth session has expired (no transient).
	 */
	public function test_handle_access_key_rejects_expired_session() {
		wp_set_current_user( $this->admin_id );
		$nonce = wp_create_nonce( 'wp_rest' );

		// Ensure no transient exists.
		delete_transient( 'srfm_ai_auth_key_' . $this->admin_id );

		$request = new \WP_REST_Request( 'POST', '/sureforms/v1/ai-auth/access-key' );
		$request->set_header( 'X-WP-Nonce', $nonce );
		$request->set_body( wp_json_encode( [ 'accessKey' => 'some-encrypted-data' ] ) );

		ob_start();
		try {
			$this->ai_auth->handle_access_key( $request );
		} catch ( \WPDieException $e ) {
			$output = ob_get_clean();
			$data   = json_decode( $output, true );
			$this->assertIsArray( $data );
			$this->assertFalse( $data['success'] );
			$this->assertStringContainsString( 'expired', $data['data']['message'] );
			return;
		}
		ob_end_clean();
		$this->fail( 'Expected WPDieException was not thrown.' );
	}
}
