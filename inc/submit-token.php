<?php
/**
 * Form Submission Token.
 *
 * Generates and verifies time-windowed HMAC tokens used to authenticate
 * public form submissions. Unlike WordPress nonces (12–24 h expiry), these
 * tokens are keyed on half-day windows and remain valid across multiple
 * windows, making them safe to embed in cached pages without any client-side
 * refresh logic.
 *
 * @package SureForms
 * @since   2.6.0
 */

namespace SRFM\Inc;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Submit_Token
 *
 * Security properties:
 *  - HMAC-SHA256 keyed on the site's WordPress auth salt (unique per install).
 *  - Tokens are form-specific: a token issued for form 5 is invalid for form 6.
 *  - Each token encodes a half-day time window (43 200 s). Verification accepts
 *    the current window plus the previous SRFM_SUBMIT_TOKEN_ACCEPTED_WINDOWS − 1
 *    windows, giving up to 48 hours of validity. This covers even aggressive CDN
 *    or full-page-cache TTLs without any refresh round-trip from the browser.
 *  - Constant-time comparison (`hash_equals`) prevents timing-based oracle attacks.
 *
 * @since 2.6.0
 */
class Submit_Token {
	/**
	 * Length of a single time window in seconds (12 hours).
	 *
	 * Rotating every 12 hours keeps the attack window short while staying
	 * far below typical cache TTLs.
	 *
	 * @since 2.6.0
	 */
	public const WINDOW_SECONDS = 43200;

	/**
	 * Number of consecutive windows that are considered valid.
	 *
	 * 4 windows × 12 hours = 48 hours maximum token lifetime.
	 * Raise via the `srfm_submit_token_accepted_windows` filter if your
	 * deployment uses unusually long cache TTLs.
	 *
	 * @since 2.6.0
	 */
	public const DEFAULT_ACCEPTED_WINDOWS = 4;

	/**
	 * Namespace for tokens that authorise a form submission.
	 *
	 * The value is the historical payload prefix, so existing tokens keep verifying
	 * across an upgrade — changing it would reject every token already embedded in
	 * cached HTML.
	 *
	 * @since 2.12.6
	 */
	public const NAMESPACE_SUBMIT = 'srfm_submit';

	/**
	 * Namespace for tokens that authorise the page-view beacon.
	 *
	 * Separate from NAMESPACE_SUBMIT so the two cannot stand in for each other: a
	 * view token scraped from the page must not authorise a submission, and the
	 * view endpoint must not double as an oracle for whether a submit token is
	 * still inside an accepted window.
	 *
	 * @since 2.12.6
	 */
	public const NAMESPACE_VIEW = 'srfm_view';

	/**
	 * Generate a submission token for a given form.
	 *
	 * The token encodes the form ID and the current half-day window, signed
	 * with the site's auth salt. It is safe to embed in cached HTML because
	 * `verify()` accepts several consecutive past windows.
	 *
	 * @since  2.6.0
	 * @since  2.12.6 Added the $namespace parameter.
	 * @param  int    $form_id   The form post ID.
	 * @param  string $namespace Purpose the token is minted for. Defaults to form submission.
	 * @return string 64-character lowercase hex HMAC-SHA256 token.
	 */
	public static function generate( int $form_id, string $namespace = self::NAMESPACE_SUBMIT ): string {
		return self::sign( $form_id, self::current_window(), $namespace );
	}

	/**
	 * Verify a token submitted with a form.
	 *
	 * A token is accepted only when its signature matches (see signature_matches())
	 * and the form is currently open (see is_form_open()). Tokens outlive the page
	 * that embedded them, so publication state is re-read on every verification
	 * rather than trusted from when the token was minted.
	 *
	 * @since  2.6.0
	 * @since  2.12.6 Added the $namespace parameter.
	 * @since  2.13.1 Returns false unless the form is currently published.
	 * @param  string $token     Token value received from the client.
	 * @param  int    $form_id   Form post ID extracted from the request body.
	 * @param  string $namespace Purpose the token must have been minted for.
	 * @return bool True if the token is valid for the given, currently published form, false otherwise.
	 */
	public static function verify( string $token, int $form_id, string $namespace = self::NAMESPACE_SUBMIT ): bool {
		return self::signature_matches( $token, $form_id, $namespace ) && self::is_form_open( $form_id );
	}

	/**
	 * Whether a token was minted for this form and namespace within an accepted window.
	 *
	 * Checks every accepted window, from newest to oldest, using constant-time
	 * comparison throughout. Says nothing about the form's current state; callers
	 * authorising a request must use verify().
	 *
	 * @since  2.13.1
	 * @param  string $token     Token value received from the client.
	 * @param  int    $form_id   Form post ID extracted from the request body.
	 * @param  string $namespace Purpose the token must have been minted for.
	 * @return bool True if the signature matches, false otherwise.
	 */
	public static function signature_matches( string $token, int $form_id, string $namespace = self::NAMESPACE_SUBMIT ): bool {
		if ( '' === $token || $form_id <= 0 ) {
			return false;
		}

		// Clamp to [1, 14]: must accept at least one window; cap at 14 (7 days)
		// to prevent a misconfigured filter from making tokens effectively permanent.
		$accepted = max( 1, min( 14, (int) apply_filters( 'srfm_submit_token_accepted_windows', self::DEFAULT_ACCEPTED_WINDOWS ) ) );

		// Walk backwards through accepted windows; current window first.
		for ( $offset = 0; $offset < $accepted; $offset++ ) {
			if ( hash_equals( self::sign( $form_id, self::current_window() - $offset, $namespace ), $token ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a form currently accepts tokens: a SureForms form with `publish` status.
	 *
	 * @since  2.13.1
	 * @param  int $form_id Form post ID.
	 * @return bool
	 */
	public static function is_form_open( int $form_id ): bool {
		$form = get_post( $form_id );
		return $form instanceof \WP_Post && SRFM_FORMS_POST_TYPE === $form->post_type && 'publish' === $form->post_status;
	}

	/**
	 * Return the index of the current half-day window.
	 *
	 * Dividing Unix time by WINDOW_SECONDS yields an integer that increments
	 * once every 12 hours, regardless of the server's local timezone.
	 *
	 * @since  2.6.0
	 * @return int
	 */
	private static function current_window(): int {
		return (int) floor( time() / self::WINDOW_SECONDS );
	}

	/**
	 * Build an HMAC-SHA256 signature for a specific form / window pair.
	 *
	 * The payload deliberately combines a namespace prefix, the form ID, and
	 * the window index so that tokens cannot be repurposed across forms or
	 * replayed across time windows.
	 *
	 * @since  2.6.0
	 * @param  int    $form_id   Post ID of the form.
	 * @param  int    $window    Half-day window index.
	 * @param  string $namespace Purpose prefix; keeps tokens for one action from authorising another.
	 * @return string 64-character lowercase hex digest.
	 */
	private static function sign( int $form_id, int $window, string $namespace = self::NAMESPACE_SUBMIT ): string {
		// Derive a plugin-specific sub-key from the site's auth salt so this
		// system has an independent key surface from WordPress session cookies.
		// Rotating wp-config.php secrets invalidates all outstanding tokens, which
		// is intentional — a cache purge should follow any secret key rotation.
		$signing_key = hash_hmac( 'sha256', 'srfm-submit-token-v1', wp_salt( 'auth' ) );
		$payload     = implode( '|', [ $namespace, $form_id, $window ] );
		return hash_hmac( 'sha256', $payload, $signing_key );
	}
}
