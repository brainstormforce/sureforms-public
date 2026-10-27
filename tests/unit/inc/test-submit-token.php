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
 * @since 2.6.0
 */
class Test_Submit_Token extends SRFM_Unit_Test_Case {

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

	/**
	 * Tokens are bound to the purpose they were minted for.
	 *
	 * The payload prefix used to be hardcoded, so one token authorised both form
	 * submission and the view beacon. That made a token scraped from public markup
	 * reusable against the submit endpoint, and made the view endpoint a cheap
	 * oracle for whether a submit token was still inside an accepted window.
	 *
	 * The default namespace is deliberately the historical literal, so tokens
	 * already embedded in cached HTML keep verifying across an upgrade.
	 */
	public function test_sign_is_namespaced_per_purpose() {
		$form_id = $this->form_id;

		$submit = Submit_Token::generate( $form_id );
		$view   = Submit_Token::generate( $form_id, Submit_Token::NAMESPACE_VIEW );

		$this->assertNotSame( $submit, $view, 'Different purposes must produce different tokens.' );

		// Each verifies only within its own namespace.
		$this->assertTrue( Submit_Token::verify( $submit, $form_id ) );
		$this->assertTrue( Submit_Token::verify( $view, $form_id, Submit_Token::NAMESPACE_VIEW ) );
		$this->assertFalse( Submit_Token::verify( $view, $form_id ), 'A view token must not authorise a submission.' );
		$this->assertFalse( Submit_Token::verify( $submit, $form_id, Submit_Token::NAMESPACE_VIEW ), 'A submit token must not authorise the beacon.' );

		// Backward compatibility: the default is the original payload prefix, so a
		// token minted before this change still verifies.
		$this->assertSame( 'srfm_submit', Submit_Token::NAMESPACE_SUBMIT, 'Changing this rejects every token in cached HTML.' );
		$this->assertSame( $submit, Submit_Token::generate( $form_id, Submit_Token::NAMESPACE_SUBMIT ) );
	}

	/**
	 * A token only authorises a form that is currently published.
	 *
	 * Tokens live in cached HTML for up to 48 hours, so one minted while the form
	 * was public must stop verifying as soon as the form leaves `publish`.
	 *
	 * @dataProvider unpublished_status_provider
	 *
	 * @param string $status Status the form is moved to after the token is minted.
	 */
	public function test_verify_rejects_form_that_is_no_longer_published( $status ) {
		$token = Submit_Token::generate( $this->form_id );
		$this->assertTrue( Submit_Token::verify( $token, $this->form_id ), 'Precondition: token verifies while the form is published.' );

		if ( 'trash' === $status ) {
			wp_trash_post( $this->form_id );
		} else {
			wp_update_post(
				[
					'ID'            => $this->form_id,
					'post_status'   => $status,
					'post_date'     => 'future' === $status ? gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ) : '',
					'post_date_gmt' => 'future' === $status ? gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ) : '',
				]
			);
		}
		$this->assertSame( $status, get_post_status( $this->form_id ), 'Precondition: status change applied.' );

		$this->assertFalse( Submit_Token::verify( $token, $this->form_id ), "A token must not verify for a {$status} form." );
		$this->assertFalse( Submit_Token::verify( Submit_Token::generate( $this->form_id, Submit_Token::NAMESPACE_VIEW ), $this->form_id, Submit_Token::NAMESPACE_VIEW ), "A view token must not verify for a {$status} form." );
	}

	/**
	 * Statuses that take a form offline.
	 *
	 * @return array<string, array<string>>
	 */
	public function unpublished_status_provider() {
		return [
			'draft'   => [ 'draft' ],
			'pending' => [ 'pending' ],
			'private' => [ 'private' ],
			'future'  => [ 'future' ],
			'trash'   => [ 'trash' ],
		];
	}

	/**
	 * Republishing a form makes its outstanding tokens valid again.
	 */
	public function test_verify_accepts_again_after_republish() {
		$token = Submit_Token::generate( $this->form_id );

		wp_update_post( [ 'ID' => $this->form_id, 'post_status' => 'draft' ] );
		$this->assertFalse( Submit_Token::verify( $token, $this->form_id ) );

		wp_update_post( [ 'ID' => $this->form_id, 'post_status' => 'publish' ] );
		$this->assertTrue( Submit_Token::verify( $token, $this->form_id ) );
	}

	/**
	 * A token only authorises an existing SureForms form.
	 */
	public function test_verify_rejects_non_form_or_missing_post() {
		$post_id = $this->factory()->post->create( [ 'post_type' => 'post' ] );
		$this->assertFalse( Submit_Token::verify( Submit_Token::generate( $post_id ), $post_id ), 'A published post of another type is not a form.' );

		$token = Submit_Token::generate( $this->form_id );
		wp_delete_post( $this->form_id, true );
		$this->assertFalse( Submit_Token::verify( $token, $this->form_id ), 'A deleted form must not verify.' );

		$this->assertFalse( Submit_Token::verify( Submit_Token::generate( 987654 ), 987654 ), 'A form id that never existed must not verify.' );
	}

	/**
	 * signature_matches() checks only the token's signature, whatever the form's status.
	 */
	public function test_signature_matches() {
		$token = Submit_Token::generate( $this->form_id );
		wp_update_post( [ 'ID' => $this->form_id, 'post_status' => 'draft' ] );

		$this->assertTrue( Submit_Token::signature_matches( $token, $this->form_id ), 'A genuine token matches even while the form is unpublished.' );
		$this->assertFalse( Submit_Token::signature_matches( $token, $this->form_id + 1 ), 'A token is bound to its form.' );
		$this->assertFalse( Submit_Token::signature_matches( str_repeat( 'a', 64 ), $this->form_id ), 'A forged token does not match.' );
		$this->assertFalse( Submit_Token::signature_matches( '', $this->form_id ), 'An empty token does not match.' );
		$this->assertFalse( Submit_Token::signature_matches( $token, 0 ), 'A zero form id does not match.' );
	}

	/**
	 * is_form_open() is true only for a published SureForms form.
	 */
	public function test_is_form_open() {
		$this->assertTrue( Submit_Token::is_form_open( $this->form_id ), 'A published form is open.' );

		wp_update_post( [ 'ID' => $this->form_id, 'post_status' => 'draft' ] );
		$this->assertFalse( Submit_Token::is_form_open( $this->form_id ), 'A draft form is not open.' );

		$post_id = $this->factory()->post->create( [ 'post_type' => 'post' ] );
		$this->assertFalse( Submit_Token::is_form_open( $post_id ), 'A published post of another type is not a form.' );
		$this->assertFalse( Submit_Token::is_form_open( 987654 ), 'A missing post is not open.' );
	}
}
