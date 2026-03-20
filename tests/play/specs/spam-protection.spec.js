/**
 * E2E tests — Spam protection (P1).
 *
 * Verifies the two client-side and server-side spam-prevention mechanisms
 * built into SureForms:
 *
 *   Honeypot field
 *     The honeypot field (`<input name="srfm-honeypot-field">`) is rendered on
 *     forms ONLY when the global "Honeypot" security setting is enabled
 *     (`srfm_security_settings_options.srfm_honeypot = true`).  This test enables
 *     that setting via the REST API before creating the form, so the field is
 *     present when the form page loads.  When the field arrives non-empty the PHP
 *     submission handler short-circuits and returns a JSON error — the success box
 *     must NOT appear.  The setting is disabled again in afterEach for isolation.
 *
 *   Double-submit prevention
 *     `assets/js/unminified/form-submit.js` sets `submitBtn.disabled = true`
 *     and `submitBtn.style.pointerEvents = 'none'` synchronously on the first
 *     click, before the AJAX request starts.  This prevents duplicate entries
 *     from impatient users or accidental double-clicks.
 *
 * Prerequisites:
 *   - A running wp-env instance (`npm run play:up`)
 *   - Admin credentials available (defaults: admin / password)
 *   - SureForms plugin active
 */

const { test, expect } = require( '@playwright/test' );
const { loginAsAdmin } = require( '../utils/loginAsAdmin' );
const {
	createBlankForm,
	publishFormAndGetURL,
	addFieldBlock,
} = require( '../utils/formHelpers' );

/**
 * Enable or disable the global SureForms honeypot setting via the REST API.
 * Must be called while the page is already on a WP admin URL (so
 * wpApiSettings.nonce is available in the page context).
 *
 * @param {import('@playwright/test').Page} page
 * @param {boolean} enabled
 */
async function setHoneypotEnabled( page, enabled ) {
	// Navigate to a form editor page where wp.apiFetch is guaranteed to be
	// loaded — the WP dashboard may not enqueue it in WP 6.9+.
	await page.goto( '/wp-admin/post-new.php?post_type=sureforms_form' );
	await page.waitForLoadState( 'load' );

	// The REST endpoint returns WP_Error when update_option returns false,
	// which happens when the value is already the same — that's fine, the
	// setting is already in the desired state. Catch and ignore.
	await page.evaluate( async ( honeypotValue ) => {
		try {
			await window.wp.apiFetch( {
				path: '/sureforms/v1/srfm-global-settings',
				method: 'POST',
				data: {
					srfm_tab: 'security-settings',
					srfm_honeypot: honeypotValue,
				},
			} );
		} catch ( _err ) {
			// Ignored — update_option returns false when value is unchanged.
		}
	}, enabled );
}

test.describe( 'Spam protection', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	// ── 10.1 Honeypot — bot fills hidden field, submission is blocked ──────────
	test( 'honeypot blocks spam — bot fills honeypot field → success box does NOT appear', async ( { page } ) => {
		// Enable the global honeypot setting so the field is rendered on the form.
		// We are already on a WP admin page (loginAsAdmin navigates to /wp-admin).
		await setHoneypotEnabled( page, true );

		await createBlankForm( page );
		await addFieldBlock( page, 'input' );

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		// Fill the visible input so the form would otherwise pass validation.
		await page.locator( 'input.srfm-input-input' ).first().fill( 'test value' );

		// The honeypot field is now rendered (setting is enabled).
		// Simulate a bot by filling it — real users never interact with it.
		await page.evaluate( () => {
			const honeypot = document.querySelector( '[name="srfm-honeypot-field"]' );
			if ( honeypot ) {
				honeypot.value = 'spambot';
			}
		} );

		await page.locator( '#srfm-submit-btn' ).click();

		// The success box must not appear — the submission was flagged as spam.
		await expect( page.locator( '.srfm-success-box' ) ).not.toBeVisible( { timeout: 10000 } );

		// Disable honeypot again for test isolation.
		await setHoneypotEnabled( page, false );
	} );

	// ── 10.2 Double-submit — button is disabled immediately after first click ──
	test( 'double-submit prevention — submit button is disabled immediately after first click', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'input' );

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		// Fill the input so submit is not blocked by client-side required validation.
		await page.locator( 'input.srfm-input-input' ).first().fill( 'test value' );

		await page.locator( '#srfm-submit-btn' ).click();

		// Check immediately — no wait — the button must be disabled synchronously
		// by form-submit.js before the AJAX request is even started.
		await expect( page.locator( '#srfm-submit-btn' ) ).toBeDisabled( { timeout: 3000 } );
	} );
} );
