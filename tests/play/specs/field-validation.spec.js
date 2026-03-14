/**
 * E2E tests — Field validation (P0).
 *
 * Verifies that each field type rejects invalid data and shows an error
 * without submitting the form.
 *
 * Covered rules:
 *   - Email format validation
 *   - Email confirmation mismatch / match
 *   - URL format validation
 *   - Number min / max bounds
 *   - Required: checkbox, GDPR, dropdown, multi-choice
 */

const { test, expect } = require( '@playwright/test' );
const { loginAsAdmin } = require( '../utils/loginAsAdmin' );
const {
	createBlankForm,
	publishFormAndGetURL,
	addFieldBlock,
	openBlockSettingsTab,
	enableRequiredField,
} = require( '../utils/formHelpers' );

test.describe( 'Field validation', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	// ── 2.1 Email — invalid format ────────────────────────────────────────────
	test( 'email field — invalid format shows error', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'email' );

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		await page.locator( 'input.srfm-input-email' ).first().fill( 'not-an-email' );
		await page.locator( '#srfm-submit-btn' ).click();

		await expect( page.locator( '.srfm-email-block .srfm-error-wrap' ).first() )
			.toBeVisible( { timeout: 10000 } );
		await expect( page.locator( '.srfm-success-box' ) ).not.toBeVisible();
	} );

	// ── 2.2 Email confirmation — mismatch ─────────────────────────────────────
	test( 'email confirmation — mismatch shows error', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'email' );

		// Enable the "Confirm Email" option in the Block settings panel.
		await page.locator( '.wp-block[data-type="srfm/email"]' ).click();
		await openBlockSettingsTab( page );

		const confirmToggle = page
			.locator( '.components-toggle-control' )
			.filter( { hasText: /Enable Email Confirmation/i } )
			.first();
		await expect( confirmToggle ).toBeVisible( { timeout: 5000 } );
		const isChecked = await confirmToggle.locator( 'input[type="checkbox"]' ).isChecked();
		if ( ! isChecked ) {
			await confirmToggle.locator( 'input[type="checkbox"]' ).click();
		}

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		await page.locator( 'input.srfm-input-email' ).first().fill( 'user@example.com' );
		await page.locator( 'input.srfm-input-email-confirm' ).first().fill( 'different@example.com' );
		await page.locator( '#srfm-submit-btn' ).click();

		await expect( page.locator( '.srfm-email-block .srfm-error-wrap' ).first() )
			.toBeVisible( { timeout: 10000 } );
		await expect( page.locator( '.srfm-success-box' ) ).not.toBeVisible();
	} );

	// ── 2.3 Email confirmation — matching emails submit successfully ───────────
	test( 'email confirmation — matching emails submit successfully', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'email' );

		await page.locator( '.wp-block[data-type="srfm/email"]' ).click();
		await openBlockSettingsTab( page );

		const confirmToggle = page
			.locator( '.components-toggle-control' )
			.filter( { hasText: /Enable Email Confirmation/i } )
			.first();
		await expect( confirmToggle ).toBeVisible( { timeout: 5000 } );
		const isChecked = await confirmToggle.locator( 'input[type="checkbox"]' ).isChecked();
		if ( ! isChecked ) {
			await confirmToggle.locator( 'input[type="checkbox"]' ).click();
		}

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		await page.locator( 'input.srfm-input-email' ).first().fill( 'user@example.com' );
		await page.locator( 'input.srfm-input-email-confirm' ).first().fill( 'user@example.com' );
		await page.locator( '#srfm-submit-btn' ).click();

		await expect( page.locator( '.srfm-success-box' ) ).toBeVisible( { timeout: 15000 } );
	} );

	// ── 2.4 URL — invalid format ──────────────────────────────────────────────
	test( 'URL field — invalid value shows error', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'url' );

		// Make the field required so submission is blocked (validation fires on submit).
		await page.locator( '.wp-block[data-type="srfm/url"]' ).click();
		await openBlockSettingsTab( page );
		await enableRequiredField( page );

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		await page.locator( 'input.srfm-input-url' ).first().fill( 'not a url' );
		await page.locator( '#srfm-submit-btn' ).click();

		await expect( page.locator( '.srfm-url-block .srfm-error-wrap' ).first() )
			.toBeVisible( { timeout: 10000 } );
		await expect( page.locator( '.srfm-success-box' ) ).not.toBeVisible();
	} );

	// ── 2.5 Number — below minimum ────────────────────────────────────────────
	test( 'number field — value below minimum shows error', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'number' );

		// Set minValue = 10 in Block settings.
		await page.locator( '.wp-block[data-type="srfm/number"]' ).click();
		await openBlockSettingsTab( page );

		const minInput = page.locator( 'input[aria-label*="min" i], input[placeholder*="min" i]' ).first();
		if ( await minInput.isVisible( { timeout: 3000 } ).catch( () => false ) ) {
			await minInput.fill( '10' );
		}

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		await page.locator( 'input.srfm-input-number' ).first().fill( '5' );
		await page.locator( '#srfm-submit-btn' ).click();

		await expect( page.locator( '.srfm-number-block .srfm-error-wrap' ).first() )
			.toBeVisible( { timeout: 10000 } );
		await expect( page.locator( '.srfm-success-box' ) ).not.toBeVisible();
	} );

	// ── 2.6 Number — above maximum ────────────────────────────────────────────
	test( 'number field — value above maximum shows error', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'number' );

		await page.locator( '.wp-block[data-type="srfm/number"]' ).click();
		await openBlockSettingsTab( page );

		const maxInput = page.locator( 'input[aria-label*="max" i], input[placeholder*="max" i]' ).first();
		if ( await maxInput.isVisible( { timeout: 3000 } ).catch( () => false ) ) {
			await maxInput.fill( '100' );
		}

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		await page.locator( 'input.srfm-input-number' ).first().fill( '999' );
		await page.locator( '#srfm-submit-btn' ).click();

		await expect( page.locator( '.srfm-number-block .srfm-error-wrap' ).first() )
			.toBeVisible( { timeout: 10000 } );
		await expect( page.locator( '.srfm-success-box' ) ).not.toBeVisible();
	} );

	// ── 2.11 Required checkbox — submit unchecked ─────────────────────────────
	test( 'required checkbox — submit unchecked shows error', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'checkbox' );

		await page.locator( '.wp-block[data-type="srfm/checkbox"]' ).click();
		await openBlockSettingsTab( page );
		await enableRequiredField( page );

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		// Do NOT check the checkbox — submit immediately.
		await page.locator( '#srfm-submit-btn' ).click();

		await expect(
			page.locator( '.srfm-checkbox-block .srfm-error-wrap' ).first()
		).toBeVisible( { timeout: 10000 } );
		await expect( page.locator( '.srfm-success-box' ) ).not.toBeVisible();
	} );

	// ── 2.12 Required GDPR — submit without consent ───────────────────────────
	test( 'required GDPR — submit without consent shows error', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'gdpr' );

		// GDPR is always required (hardcoded in GDPR_Markup constructor — no toggle).
		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		// Do NOT check GDPR consent.
		await page.locator( '#srfm-submit-btn' ).click();

		await expect(
			page.locator( '.srfm-gdpr-block .srfm-error-wrap' ).first()
		).toBeVisible( { timeout: 10000 } );
		await expect( page.locator( '.srfm-success-box' ) ).not.toBeVisible();
	} );

	// ── 2.13 Required dropdown — submit without selection ─────────────────────
	test( 'required dropdown — submit without selection shows error', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'dropdown' );

		await page.locator( '.wp-block[data-type="srfm/dropdown"]' ).click();
		await openBlockSettingsTab( page );
		await enableRequiredField( page );

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		// Do NOT select any option.
		await page.locator( '#srfm-submit-btn' ).click();

		await expect(
			page.locator( '.srfm-dropdown-block .srfm-error-wrap' ).first()
		).toBeVisible( { timeout: 10000 } );
		await expect( page.locator( '.srfm-success-box' ) ).not.toBeVisible();
	} );

	// ── 2.14 Required multi-choice — submit without selection ─────────────────
	test( 'required multi-choice — submit without selection shows error', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'multi-choice' );

		await page.locator( '.wp-block[data-type="srfm/multi-choice"]' ).click();
		await openBlockSettingsTab( page );
		await enableRequiredField( page );

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		// Do NOT select any option.
		await page.locator( '#srfm-submit-btn' ).click();

		await expect(
			page.locator( '.srfm-multi-choice-block .srfm-error-wrap' ).first()
		).toBeVisible( { timeout: 10000 } );
		await expect( page.locator( '.srfm-success-box' ) ).not.toBeVisible();
	} );
} );
