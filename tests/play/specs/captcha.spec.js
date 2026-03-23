/**
 * E2E tests — CAPTCHA (P0).
 *
 * Covers:
 *   7.2 reCAPTCHA v2 — submit without solving is blocked  (requires CAPTCHA keys)
 *   7.7 CAPTCHA disabled — form submits normally without CAPTCHA widget
 *
 * Test 7.2 requires Google reCAPTCHA v2 test site/secret keys:
 *   Site key:   6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI  (always passes)
 *   Secret key: 6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe  (always passes)
 *
 * HOW TO ENABLE 7.2:
 *   1. Set the reCAPTCHA v2 test keys in SureForms global settings
 *      (manually or via wp-cli in initial-setup.sh).
 *   2. Enable CAPTCHA on the test form in the Block settings panel.
 *   3. Set env var: RECAPTCHA_TEST_KEYS_CONFIGURED=true
 */

const { test, expect } = require( '@playwright/test' );
const { loginAsAdmin } = require( '../utils/loginAsAdmin' );
const {
	createBlankForm,
	addFieldBlock,
	publishFormAndGetURL,
} = require( '../utils/formHelpers' );

const CAPTCHA_CONFIGURED = process.env.RECAPTCHA_TEST_KEYS_CONFIGURED === 'true';

test.describe( 'CAPTCHA', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	// ── 7.7 No CAPTCHA — form submits normally ────────────────────────────────
	// This is the P0 test that always runs (no external keys needed).
	test( 'form without CAPTCHA submits normally', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'input' );

		// CAPTCHA is off by default — do not enable it.
		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		// No CAPTCHA widget should be present.
		await expect( page.locator( '.g-recaptcha, .h-captcha, .cf-turnstile' ) ).toHaveCount( 0 );

		await page.locator( 'input.srfm-input-input' ).first().fill( 'No CAPTCHA test' );
		await page.locator( '#srfm-submit-btn' ).click();

		await expect( page.locator( '.srfm-success-box' ) ).toBeVisible( { timeout: 15000 } );
		await expect( page.locator( '.srfm-success-box' ) ).toContainText( /Thank you/i );
	} );

	// ── 7.2 reCAPTCHA v2 — submit without solving is blocked ─────────────────
	test( 'reCAPTCHA v2 — submit without solving is blocked', async ( { page } ) => {
		test.skip(
			! CAPTCHA_CONFIGURED,
			'Requires reCAPTCHA test keys configured in WordPress. Set RECAPTCHA_TEST_KEYS_CONFIGURED=true to enable.'
		);

		await createBlankForm( page );
		await addFieldBlock( page, 'input' );

		// Enable reCAPTCHA v2 on the form via the Block settings panel.
		// The CAPTCHA setting is on the form root, accessible from the editor sidebar.
		// (Implementation note: selector may vary — inspect the sidebar panel for
		// the security type dropdown and select "Google reCAPTCHA v2".)
		//
		// For now this is a placeholder — update selectors after verifying in the editor.
		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		// The reCAPTCHA widget should be visible.
		await expect( page.locator( '.g-recaptcha' ) ).toBeVisible( { timeout: 10000 } );

		// Attempt to submit without completing the CAPTCHA challenge.
		await page.locator( 'input.srfm-input-input' ).first().fill( 'CAPTCHA test' );
		await page.locator( '#srfm-submit-btn' ).click();

		// An error should appear; the form should NOT succeed.
		await expect(
			page.locator( '.srfm-error-message, #srfm-error-message, .srfm-captcha-error' ).first()
		).toBeVisible( { timeout: 10000 } );
		await expect( page.locator( '.srfm-success-box' ) ).not.toBeVisible();
	} );
} );
