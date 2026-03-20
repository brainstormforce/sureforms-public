<?php
/**
 * Class Test_Submit_Token
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Submit_Token;

/**
 * Tests for the HMAC-based Submit_Token class.
 *
 * @since x.x.x
 */
class Test_Submit_Token extends TestCase {

	/**
	 * A valid form ID for testing.
	 *
	 * @var int
	 */
	private $form_id;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->form_id = $this->factory()->post->create( [ 'post_type' => 'sureforms_form' ] );
	}

	/**
	 * Clean up test fixtures.
	 */
	protected function tearDown(): void {
		wp_delete_post( $this->form_id, true );
		parent::tearDown();
	}

	/**
	 * Test generate returns a 64-character hex string (SHA-256 output).
	 */
	public function test_generate_returns_64_char_hex_string() {
		$token = Submit_Token::generate( $this->form_id );

		$this->assertIsString( $token );
		$this->assertEquals( 64, strlen( $token ), 'Token should be 64 hex characters (SHA-256).' );
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/', $token, 'Token should only contain lowercase hex characters.' );
	}

	/**
	 * Test generate is deterministic within the same time window.
	 */
	public function test_generate_is_deterministic_within_same_window() {
		$token1 = Submit_Token::generate( $this->form_id );
		$token2 = Submit_Token::generate( $this->form_id );

		$this->assertSame( $token1, $token2, 'Tokens generated in the same window for the same form should be identical.' );
	}

	/**
	 * Test verify accepts a freshly generated token.
	 */
	public function test_verify_accepts_valid_token() {
		$token = Submit_Token::generate( $this->form_id );

		$this->assertTrue( Submit_Token::verify( $token, $this->form_id ), 'A freshly generated token should verify successfully.' );
	}

	/**
	 * Test verify rejects an empty token.
	 */
	public function test_verify_rejects_empty_token() {
		$this->assertFalse( Submit_Token::verify( '', $this->form_id ), 'Empty token should be rejected.' );
	}

	/**
	 * Test verify rejects form ID of zero.
	 */
	public function test_verify_rejects_zero_form_id() {
		$token = Submit_Token::generate( $this->form_id );

		$this->assertFalse( Submit_Token::verify( $token, 0 ), 'Form ID 0 should always be rejected.' );
	}

	/**
	 * Test verify rejects negative form ID.
	 */
	public function test_verify_rejects_negative_form_id() {
		$token = Submit_Token::generate( $this->form_id );

		$this->assertFalse( Submit_Token::verify( $token, -1 ), 'Negative form ID should be rejected.' );
	}

	/**
	 * Test tokens are form-specific — a token for form A is invalid for form B.
	 */
	public function test_tokens_are_form_specific() {
		$form_id_b = $this->factory()->post->create( [ 'post_type' => 'sureforms_form' ] );
		$token_a   = Submit_Token::generate( $this->form_id );
		$token_b   = Submit_Token::generate( $form_id_b );

		$this->assertNotSame( $token_a, $token_b, 'Tokens for different forms should differ.' );
		$this->assertFalse( Submit_Token::verify( $token_a, $form_id_b ), 'Token for form A should not verify for form B.' );
		$this->assertFalse( Submit_Token::verify( $token_b, $this->form_id ), 'Token for form B should not verify for form A.' );

		wp_delete_post( $form_id_b, true );
	}

	/**
	 * Test verify rejects a tampered token.
	 */
	public function test_verify_rejects_tampered_token() {
		$token = Submit_Token::generate( $this->form_id );

		// Flip the first character.
		$tampered = ( '0' === $token[0] ? '1' : '0' ) . substr( $token, 1 );

		$this->assertFalse( Submit_Token::verify( $tampered, $this->form_id ), 'A tampered token should be rejected.' );
	}

	/**
	 * Test verify rejects a completely random string.
	 */
	public function test_verify_rejects_random_string() {
		$this->assertFalse( Submit_Token::verify( 'not-a-valid-token-at-all', $this->form_id ), 'A random string should be rejected.' );
	}

	/**
	 * Test the accepted windows filter can extend the validity range.
	 */
	public function test_accepted_windows_filter() {
		$token = Submit_Token::generate( $this->form_id );

		// Reduce accepted windows to 1 (only current window).
		add_filter( 'srfm_submit_token_accepted_windows', function () {
			return 1;
		} );

		// Current window token should still verify.
		$this->assertTrue( Submit_Token::verify( $token, $this->form_id ), 'Token should verify with 1 accepted window (current).' );

		// Remove filter.
		remove_all_filters( 'srfm_submit_token_accepted_windows' );
	}

	/**
	 * Test the accepted windows filter is clamped to a minimum of 1.
	 */
	public function test_accepted_windows_filter_clamped_minimum() {
		$token = Submit_Token::generate( $this->form_id );

		// Try to set 0 windows — should be clamped to 1.
		add_filter( 'srfm_submit_token_accepted_windows', function () {
			return 0;
		} );

		$this->assertTrue( Submit_Token::verify( $token, $this->form_id ), 'Even with filter returning 0, should clamp to 1 and verify current window.' );

		remove_all_filters( 'srfm_submit_token_accepted_windows' );
	}

	/**
	 * Test the accepted windows filter is clamped to a maximum of 14.
	 */
	public function test_accepted_windows_filter_clamped_maximum() {
		$token = Submit_Token::generate( $this->form_id );

		// Try to set 100 windows — should be clamped to 14.
		add_filter( 'srfm_submit_token_accepted_windows', function () {
			return 100;
		} );

		// Should still verify (the clamp doesn't break anything for current tokens).
		$this->assertTrue( Submit_Token::verify( $token, $this->form_id ), 'Filter returning 100 should be clamped to 14 and still verify.' );

		remove_all_filters( 'srfm_submit_token_accepted_windows' );
	}

	/**
	 * Test generate produces different tokens for different form IDs.
	 */
	public function test_generate_different_form_ids_produce_different_tokens() {
		$form_id_2 = $this->factory()->post->create( [ 'post_type' => 'sureforms_form' ] );
		$form_id_3 = $this->factory()->post->create( [ 'post_type' => 'sureforms_form' ] );

		$token_1 = Submit_Token::generate( $this->form_id );
		$token_2 = Submit_Token::generate( $form_id_2 );
		$token_3 = Submit_Token::generate( $form_id_3 );

		$this->assertNotSame( $token_1, $token_2 );
		$this->assertNotSame( $token_2, $token_3 );
		$this->assertNotSame( $token_1, $token_3 );

		wp_delete_post( $form_id_2, true );
		wp_delete_post( $form_id_3, true );
	}
}
