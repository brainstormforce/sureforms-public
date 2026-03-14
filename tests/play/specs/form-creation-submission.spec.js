/**
 * E2E tests — Basic form creation and submission.
 *
 * Covers:
 *  1. Create a form from scratch with a Text field → publish → submit → success
 *  2. Create a form with an Email field → submit valid email → success
 *  3. Required field validation — submit empty required field → error shown
 */

const { test, expect } = require( '@playwright/test' );
const { loginAsAdmin } = require( '../utils/loginAsAdmin' );

// ─── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Navigate to the SureForms dashboard and open a blank form in the editor.
 * Returns the page still on the editor URL.
 *
 * @param {import('@playwright/test').Page} page
 */
async function createBlankForm( page ) {
	// Navigate directly to the Gutenberg form editor — bypasses the React
	// dashboard and the AI builder (which fails on localhost).
	// Use 'load' not 'networkidle' — Gutenberg polls the REST API continuously.
	await page.goto( '/wp-admin/post-new.php?post_type=sureforms_form' );
	await page.waitForLoadState( 'load' );

	// Wait for the draggable block panel to appear (signals JS is ready).
	await page.locator( '#draggable-box__srfm--input' ).waitFor( { state: 'visible', timeout: 30000 } );

	// Dismiss the Gutenberg welcome guide if it appears.
	const welcomeGuide = page.getByRole( 'button', { name: /close/i } ).filter( { hasText: /close/i } );
	if ( await welcomeGuide.isVisible( { timeout: 3000 } ).catch( () => false ) ) {
		await welcomeGuide.click();
	}
}

/**
 * Publish the form currently open in the editor and return the frontend URL.
 *
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<string>} Frontend URL of the published form.
 */
async function publishFormAndGetURL( page ) {
	await page.getByRole( 'button', { name: 'Publish', exact: true } ).click();

	// WordPress may show a pre-publish confirmation panel (depends on user preferences).
	// If it appears, click the confirm "Publish" button inside it.
	const confirmPublish = page
		.getByLabel( 'Editor publish' )
		.getByRole( 'button', { name: 'Publish', exact: true } );

	if ( await confirmPublish.isVisible( { timeout: 3000 } ).catch( () => false ) ) {
		await confirmPublish.click();
	}

	// After publishing, a "View Form" link appears in the editor (top bar or panel).
	const viewFormLink = page.getByRole( 'link', { name: 'View Form' } ).first();
	await expect( viewFormLink ).toBeVisible( { timeout: 15000 } );

	return await viewFormLink.getAttribute( 'href' );
}

// ─── Tests ────────────────────────────────────────────────────────────────────

test.describe( 'Form creation and submission', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	// ── Test 1 ──────────────────────────────────────────────────────────────
	test( 'can create a form with a text field and submit it', async ( { page } ) => {
		await createBlankForm( page );

		// Add Text Field block via the quick-action sidebar.
		// The draggable block id pattern is: draggable-box__srfm--{blockName}
		await page.locator( '#draggable-box__srfm--input' ).click();
		await expect(
			page.locator( '.wp-block[data-type="srfm/input"]' )
		).toBeVisible( { timeout: 15000 } );

		const formURL = await publishFormAndGetURL( page );

		// Open form on the frontend.
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		// Fill in the text field and submit.
		await page.locator( 'input.srfm-input-input' ).first().fill( 'Hello World' );
		await page.locator( '#srfm-submit-btn' ).click();

		// Confirm the success / thank-you message appears.
		await expect(
			page.locator( '.srfm-success-box' )
		).toBeVisible( { timeout: 15000 } );
		await expect(
			page.locator( '.srfm-success-box' )
		).toContainText( /Thank you/i );
	} );

	// ── Test 2 ──────────────────────────────────────────────────────────────
	test( 'can create a form with an email field and submit a valid email', async ( { page } ) => {
		await createBlankForm( page );

		// Add Email Field block.
		await page.locator( '#draggable-box__srfm--email' ).click();
		await expect(
			page.locator( '.wp-block[data-type="srfm/email"]' )
		).toBeVisible( { timeout: 15000 } );

		const formURL = await publishFormAndGetURL( page );

		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		await page.locator( 'input.srfm-input-email' ).first().fill( 'test@example.com' );
		await page.locator( '#srfm-submit-btn' ).click();

		await expect(
			page.locator( '.srfm-success-box' )
		).toBeVisible( { timeout: 15000 } );
		await expect(
			page.locator( '.srfm-success-box' )
		).toContainText( /Thank you/i );
	} );

	// ── Test 3 ──────────────────────────────────────────────────────────────
	test( 'shows validation error when required text field is left empty', async ( { page } ) => {
		await createBlankForm( page );

		// Add Text Field block and make it required via the block settings panel.
		await page.locator( '#draggable-box__srfm--input' ).click();
		await expect(
			page.locator( '.wp-block[data-type="srfm/input"]' )
		).toBeVisible( { timeout: 15000 } );

		// Click the block to select it, then switch to the Block settings tab.
		await page.locator( '.wp-block[data-type="srfm/input"]' ).click();
		await page.getByRole( 'tab', { name: 'Block' } ).click();

		// Enable Required via the toggle in the Block settings panel.
		const requiredToggle = page.locator(
			'.components-toggle-control'
		).filter( { hasText: /^Required$/i } ).first();
		await expect( requiredToggle ).toBeVisible( { timeout: 5000 } );

		const isChecked = await requiredToggle.locator( 'input[type="checkbox"]' ).isChecked();
		if ( ! isChecked ) {
			await requiredToggle.locator( 'input[type="checkbox"]' ).click();
		}

		const formURL = await publishFormAndGetURL( page );

		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		// Submit without filling the required field.
		await page.locator( '#srfm-submit-btn' ).click();

		// Validation error should appear — SureForms renders it inside .srfm-error-wrap.
		await expect(
			page.locator( '.srfm-error-wrap' ).filter( { hasText: /required|field is required/i } ).first()
		).toBeVisible( { timeout: 10000 } );

		// Success box must NOT appear.
		await expect(
			page.locator( '.srfm-success-box' )
		).not.toBeVisible();
	} );
} );
