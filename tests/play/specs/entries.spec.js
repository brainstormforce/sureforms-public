/**
 * E2E tests — Entries management (P0).
 *
 * Covers:
 *   4.1 Entry is created after a form submission and appears in the entries list
 *   4.2 Entry detail contains the correct submitted field values
 */

const { test, expect } = require( '@playwright/test' );
const { loginAsAdmin } = require( '../utils/loginAsAdmin' );
const {
	createBlankForm,
	addFieldBlock,
	publishFormAndGetURL,
} = require( '../utils/formHelpers' );

test.describe( 'Entries management', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	// ── 4.1 Entry appears in the entries list ─────────────────────────────────
	test( 'submitted entry appears in the admin entries list', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'input' );

		const formURL = await publishFormAndGetURL( page );

		// Capture the form post ID from the editor URL before navigating away.
		const editorURL = page.url();
		const formIdMatch = editorURL.match( /[?&]post=(\d+)/ );
		const formId = formIdMatch ? formIdMatch[ 1 ] : null;

		// Submit the form.
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		const uniqueValue = `Entry-${ Date.now() }`;
		await page.locator( 'input.srfm-input-input' ).first().fill( uniqueValue );
		await page.locator( '#srfm-submit-btn' ).click();
		await expect( page.locator( '.srfm-success-box' ) ).toBeVisible( { timeout: 15000 } );

		// Go to the admin entries page.
		const entriesURL = formId
			? `/wp-admin/admin.php?page=sureforms_entries#/?form=${ formId }`
			: '/wp-admin/admin.php?page=sureforms_entries';
		await page.goto( entriesURL );
		await page.waitForLoadState( 'load' );
		// Allow the React app to fetch and render the entries list.
		await page.waitForTimeout( 2500 );

		// The unique value should appear somewhere in the entries table.
		await expect(
			page.locator( 'body' ).getByText( uniqueValue )
		).toBeVisible( { timeout: 10000 } );
	} );

	// ── 4.2 Entry detail shows correct submitted data ─────────────────────────
	test( 'entry detail contains the correct submitted field values', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'input' );
		await addFieldBlock( page, 'email' );

		const formURL = await publishFormAndGetURL( page );

		const editorURL = page.url();
		const formIdMatch = editorURL.match( /[?&]post=(\d+)/ );
		const formId = formIdMatch ? formIdMatch[ 1 ] : null;

		// Submit the form with known values.
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		const textValue = `John-${ Date.now() }`;
		const emailValue = `test-${ Date.now() }@example.com`;

		await page.locator( 'input.srfm-input-input' ).first().fill( textValue );
		await page.locator( 'input.srfm-input-email' ).first().fill( emailValue );
		await page.locator( '#srfm-submit-btn' ).click();
		await expect( page.locator( '.srfm-success-box' ) ).toBeVisible( { timeout: 15000 } );

		// Navigate to the entries list.
		const entriesURL = formId
			? `/wp-admin/admin.php?page=sureforms_entries#/?form=${ formId }`
			: '/wp-admin/admin.php?page=sureforms_entries';
		await page.goto( entriesURL );
		await page.waitForLoadState( 'load' );
		await page.waitForTimeout( 2500 );

		// Click the first (most recent) entry row to open its detail view.
		const entryRow = page.locator( 'tr, [role="row"]' )
			.filter( { hasText: textValue } )
			.first();
		await expect( entryRow ).toBeVisible( { timeout: 10000 } );
		await entryRow.click();

		// Wait for the detail page / panel to load.
		await page.waitForTimeout( 1500 );

		// Both submitted values should be visible in the entry detail.
		await expect(
			page.locator( 'body' ).getByText( textValue ).first()
		).toBeVisible( { timeout: 10000 } );

		await expect(
			page.locator( 'body' ).getByText( emailValue ).first()
		).toBeVisible( { timeout: 10000 } );
	} );
} );
