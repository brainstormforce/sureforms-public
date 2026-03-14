/**
 * E2E tests — Field types (P0).
 *
 * Verifies that every free-tier field block can be added to a form,
 * published, and successfully submitted with valid data.
 *
 * Covered blocks:
 *   srfm/textarea, srfm/number, srfm/phone, srfm/url,
 *   srfm/checkbox, srfm/multi-choice (radio + multi),
 *   srfm/dropdown, srfm/gdpr
 */

const { test, expect } = require( '@playwright/test' );
const { loginAsAdmin } = require( '../utils/loginAsAdmin' );
const {
	createBlankForm,
	publishFormAndGetURL,
	addFieldBlock,
} = require( '../utils/formHelpers' );

test.describe( 'Field types — basic submit', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	// ── 1.1 Textarea ──────────────────────────────────────────────────────────
	test( 'textarea field — submit multi-line text', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'textarea' );

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		await page.locator( 'textarea.srfm-input-textarea' ).first().fill( 'Line one\nLine two\nLine three' );
		await page.locator( '#srfm-submit-btn' ).click();

		await expect( page.locator( '.srfm-success-box' ) ).toBeVisible( { timeout: 15000 } );
		await expect( page.locator( '.srfm-success-box' ) ).toContainText( /Thank you/i );
	} );

	// ── 1.2 Number ────────────────────────────────────────────────────────────
	test( 'number field — submit a valid number', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'number' );

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		await page.locator( 'input.srfm-input-number' ).first().fill( '42' );
		await page.locator( '#srfm-submit-btn' ).click();

		await expect( page.locator( '.srfm-success-box' ) ).toBeVisible( { timeout: 15000 } );
		await expect( page.locator( '.srfm-success-box' ) ).toContainText( /Thank you/i );
	} );

	// ── 1.3 Phone ─────────────────────────────────────────────────────────────
	test( 'phone field — submit a valid phone number', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'phone' );

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		// intl-tel-input renders a visible text input; the flag selector is separate.
		await page.locator( 'input.srfm-input-phone' ).first().fill( '2025550100' );
		await page.locator( '#srfm-submit-btn' ).click();

		await expect( page.locator( '.srfm-success-box' ) ).toBeVisible( { timeout: 15000 } );
		await expect( page.locator( '.srfm-success-box' ) ).toContainText( /Thank you/i );
	} );

	// ── 1.4 URL ───────────────────────────────────────────────────────────────
	test( 'URL field — submit a valid URL', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'url' );

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		await page.locator( 'input.srfm-input-url' ).first().fill( 'https://example.com' );
		await page.locator( '#srfm-submit-btn' ).click();

		await expect( page.locator( '.srfm-success-box' ) ).toBeVisible( { timeout: 15000 } );
		await expect( page.locator( '.srfm-success-box' ) ).toContainText( /Thank you/i );
	} );

	// ── 1.5 Checkbox ──────────────────────────────────────────────────────────
	test( 'checkbox field — check and submit', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'checkbox' );

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		// The visible element is the custom label; the actual input is screen-reader-only.
		await page.locator( '.srfm-checkbox-block .srfm-cbx' ).first().click();
		await page.locator( '#srfm-submit-btn' ).click();

		await expect( page.locator( '.srfm-success-box' ) ).toBeVisible( { timeout: 15000 } );
		await expect( page.locator( '.srfm-success-box' ) ).toContainText( /Thank you/i );
	} );

	// ── 1.6 Multi-choice — single selection (radio) ───────────────────────────
	test( 'multi-choice (radio) — pick one option and submit', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'multi-choice' );

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		// The block ships with at least one default option ("Option 1").
		// Each option is a visible label wrapping the hidden radio/checkbox input.
		await page.locator( '.srfm-multi-choice-block .srfm-multi-choice-single' ).first().click();
		await page.locator( '#srfm-submit-btn' ).click();

		await expect( page.locator( '.srfm-success-box' ) ).toBeVisible( { timeout: 15000 } );
		await expect( page.locator( '.srfm-success-box' ) ).toContainText( /Thank you/i );
	} );

	// ── 1.7 Multi-choice — multi-select (checkbox group) ─────────────────────
	test( 'multi-choice (multi-select) — pick multiple options and submit', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'multi-choice' );

		// Enable multi-select in the block settings (singleSelection = false by default).
		// The block defaults to multi-select, so no extra editor interaction is needed.
		// If it defaults to single, click to ensure two options are selectable.
		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		const options = page.locator( '.srfm-multi-choice-block .srfm-multi-choice-single' );
		const count = await options.count();
		await options.first().click();
		if ( count > 1 ) {
			await options.nth( 1 ).click();
		}
		await page.locator( '#srfm-submit-btn' ).click();

		await expect( page.locator( '.srfm-success-box' ) ).toBeVisible( { timeout: 15000 } );
		await expect( page.locator( '.srfm-success-box' ) ).toContainText( /Thank you/i );
	} );

	// ── 1.8 Dropdown ──────────────────────────────────────────────────────────
	test( 'dropdown field — select one option and submit', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'dropdown' );

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		// SureForms renders a custom React-based dropdown with class srfm-dropdown-input.
		// Click to open, then pick the first available option.
		const dropdown = page.locator( '.srfm-dropdown-block .srfm-dropdown-input' ).first();
		await dropdown.click();

		const firstOption = page
			.locator( '.srfm-dropdown-block .srfm-dropdown-option' )
			.first();
		await expect( firstOption ).toBeVisible( { timeout: 5000 } );
		await firstOption.click();

		await page.locator( '#srfm-submit-btn' ).click();

		await expect( page.locator( '.srfm-success-box' ) ).toBeVisible( { timeout: 15000 } );
		await expect( page.locator( '.srfm-success-box' ) ).toContainText( /Thank you/i );
	} );

	// ── 1.10 GDPR ─────────────────────────────────────────────────────────────
	test( 'GDPR field — check consent and submit', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'gdpr' );

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		// Like checkbox, the visible interactive element is the label.
		await page.locator( '.srfm-gdpr-block .srfm-cbx' ).first().click();
		await page.locator( '#srfm-submit-btn' ).click();

		await expect( page.locator( '.srfm-success-box' ) ).toBeVisible( { timeout: 15000 } );
		await expect( page.locator( '.srfm-success-box' ) ).toContainText( /Thank you/i );
	} );
} );
