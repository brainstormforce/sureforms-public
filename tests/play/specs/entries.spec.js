/**
 * E2E tests — Entries management (P0).
 *
 * Covers:
 *   4.1 Entry is created after a form submission and appears in the entries list
 *   4.2 Entry detail contains the correct submitted field values
 *   4.3 Bulk delete removes selected entries
 *   4.4 CSV export downloads a file
 *   4.5 Entry read/unread — clicking entry detail marks the entry as read
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

		// Click the entry ID link in the row that contains our submitted text.
		// The entries table renders clickable "#ID" links: href="#/entry/{id}".
		const entryRow = page.locator( 'tr, [role="row"]' )
			.filter( { hasText: textValue } )
			.first();
		await expect( entryRow ).toBeVisible( { timeout: 10000 } );
		// Click the entry link (the "#ID" cell) to open the detail view.
		const entryLink = entryRow.locator( 'a[href*="#/entry/"]' ).first();
		await entryLink.click();

		// Both submitted values should be visible in the entry detail.
		await expect(
			page.locator( 'body' ).getByText( textValue ).first()
		).toBeVisible( { timeout: 10000 } );

		await expect(
			page.locator( 'body' ).getByText( emailValue ).first()
		).toBeVisible( { timeout: 10000 } );
	} );

	// ── 4.3 Bulk delete removes entries from the list ─────────────────────────
	test( 'bulk delete removes entries from the list', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'input' );

		const formURL = await publishFormAndGetURL( page );

		const editorURL = page.url();
		const formIdMatch = editorURL.match( /[?&]post=(\d+)/ );
		const formId = formIdMatch ? formIdMatch[ 1 ] : null;

		// Submit the form twice so there are multiple entries to bulk-delete.
		const ts = Date.now();
		for ( let i = 0; i < 2; i++ ) {
			await page.goto( formURL );
			await page.waitForLoadState( 'load' );
			await page.locator( 'input.srfm-input-input' ).first().fill( `BulkDel-${ i }-${ ts }` );
			await page.locator( '#srfm-submit-btn' ).click();
			await expect( page.locator( '.srfm-success-box' ) ).toBeVisible( { timeout: 15000 } );
		}

		// Navigate to the entries page filtered by this form.
		const entriesURL = formId
			? `/wp-admin/admin.php?page=sureforms_entries#/?form=${ formId }`
			: '/wp-admin/admin.php?page=sureforms_entries';
		await page.goto( entriesURL );
		await page.waitForLoadState( 'load' );

		// Wait for a real data row to appear (not just skeleton rows).
		// Skeleton rows are also <tr> elements, so we wait for an actual entry link.
		await expect( page.locator( 'tbody tr a[href*="#/entry/"]' ).first() ).toBeVisible( { timeout: 10000 } );

		// Select all entries using the thead checkbox.
		await page.locator( 'thead input[type="checkbox"]' ).first().click();

		// Wait for the bulk action bar to appear, then click Delete.
		await expect(
			page.getByRole( 'button', { name: /^Delete$/i } ).first()
		).toBeVisible( { timeout: 10000 } );
		await page.getByRole( 'button', { name: /^Delete$/i } ).first().click();

		// Confirm in the confirmation dialog.
		// Non-trashed entries show "Move to Trash" as the confirm label.
		// There are multiple [role="dialog"] elements on the page so we target
		// the confirm button directly to avoid strict-mode violations.
		const confirmBtn = page.getByRole( 'button', { name: /Move to Trash/i } );
		await expect( confirmBtn ).toBeVisible( { timeout: 5000 } );
		await confirmBtn.click();

		// Wait for the dialog to close.
		await expect( confirmBtn ).not.toBeVisible( { timeout: 10000 } );

		// All entry rows should be gone (moved to trash, hidden from default "all" view).
		await expect( page.locator( 'tbody tr' ) ).toHaveCount( 0, { timeout: 10000 } );
	} );

	// ── 4.4 CSV export downloads a file ───────────────────────────────────────
	test( 'CSV export downloads a file', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'input' );

		const formURL = await publishFormAndGetURL( page );

		const editorURL = page.url();
		const formIdMatch = editorURL.match( /[?&]post=(\d+)/ );
		const formId = formIdMatch ? formIdMatch[ 1 ] : null;

		// Submit once so there is an entry to export.
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );
		const uniqueVal = `CsvExport-${ Date.now() }`;
		await page.locator( 'input.srfm-input-input' ).first().fill( uniqueVal );
		await page.locator( '#srfm-submit-btn' ).click();
		await expect( page.locator( '.srfm-success-box' ) ).toBeVisible( { timeout: 15000 } );

		// Navigate to the entries page.
		const entriesURL = formId
			? `/wp-admin/admin.php?page=sureforms_entries#/?form=${ formId }`
			: '/wp-admin/admin.php?page=sureforms_entries';
		await page.goto( entriesURL );
		await page.waitForLoadState( 'load' );

		// Wait for a real data row to appear (not just skeleton rows).
		// Skeleton rows are also <tr> elements, so we wait for an actual entry link.
		await expect( page.locator( 'tbody tr a[href*="#/entry/"]' ).first() ).toBeVisible( { timeout: 10000 } );

		// Select all entries using the "Select all" aria-labeled checkbox in the header.
		// Using getByRole is more reliable than the raw input selector for controlled checkboxes.
		await page.getByRole( 'checkbox', { name: 'Select all' } ).click();

		// The Export action lives inside a DropdownMenu (three-dot / MoreVertical icon
		// button).  Wait for the Delete button to confirm the bulk-action bar is shown,
		// then open the dropdown by clicking the aria-haspopup="menu" trigger button.
		await expect(
			page.getByRole( 'button', { name: /^Delete$/i } ).first()
		).toBeVisible( { timeout: 10000 } );

		// Click the MoreVertical ("...") icon button that triggers the dropdown.
		// The DropdownMenu from @bsf/force-ui uses floating-ui's useRole({ role: "menu" })
		// which sets aria-haspopup="menu" on the trigger button — the most stable selector.
		await page.locator( 'button[aria-haspopup="menu"]' ).first().click();

		// "Export Selected" appears in the dropdown when entries are selected.
		await expect(
			page.getByText( /Export Selected|Export All/i ).first()
		).toBeVisible( { timeout: 5000 } );

		// The export fires window.location.href → admin-ajax.php with
		// Content-Disposition: attachment — Playwright fires the download event.
		const [ download ] = await Promise.all( [
			page.waitForEvent( 'download' ),
			page.getByText( /Export Selected|Export All/i ).first().click(),
		] );

		expect( download.suggestedFilename() ).toMatch( /\.(csv|zip)$/i );
	} );

	// ── 4.5 Entry read/unread status ──────────────────────────────────────────
	test( 'entry read/unread — clicking entry detail marks the entry as read', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'input' );

		const formURL = await publishFormAndGetURL( page );

		const editorURL = page.url();
		const formIdMatch = editorURL.match( /[?&]post=(\d+)/ );
		const formId = formIdMatch ? formIdMatch[ 1 ] : null;

		// Submit the form — entries are created with 'unread' status by default.
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		const uniqueValue = `ReadTest-${ Date.now() }`;
		await page.locator( 'input.srfm-input-input' ).first().fill( uniqueValue );
		await page.locator( '#srfm-submit-btn' ).click();
		await expect( page.locator( '.srfm-success-box' ) ).toBeVisible( { timeout: 15000 } );

		// Go to the entries list filtered by this form.
		const entriesURL = formId
			? `/wp-admin/admin.php?page=sureforms_entries#/?form=${ formId }`
			: '/wp-admin/admin.php?page=sureforms_entries';
		await page.goto( entriesURL );
		await page.waitForLoadState( 'load' );

		// Wait for real data rows (not skeleton rows).
		await expect(
			page.locator( 'tbody tr a[href*="#/entry/"]' ).first()
		).toBeVisible( { timeout: 10000 } );

		// The entry row should display an "Unread" status badge.
		const entryRow = page.locator( 'tr, [role="row"]' )
			.filter( { hasText: uniqueValue } )
			.first();
		await expect( entryRow ).toBeVisible( { timeout: 10000 } );
		await expect( entryRow.getByText( 'Unread' ) ).toBeVisible( { timeout: 5000 } );

		// Click the entry ID link. When the entry is unread, SureForms appends
		// ?read=true to the href, which triggers the read-status API call when
		// the entry detail component mounts.
		await entryRow.locator( 'a[href*="#/entry/"]' ).first().click();

		// Wait for all network requests to settle — this ensures the read-status
		// PATCH request completes before we navigate away to reload the list.
		await page.waitForLoadState( 'networkidle', { timeout: 15000 } );

		// Navigate back to the entries list (full page reload to get fresh data).
		await page.goto( entriesURL );
		await page.waitForLoadState( 'load' );
		await expect(
			page.locator( 'tbody tr a[href*="#/entry/"]' ).first()
		).toBeVisible( { timeout: 10000 } );

		// The entry should now show "Read" status.
		// Use exact: true to avoid matching "Unread" (which contains "Read").
		const updatedRow = page.locator( 'tr, [role="row"]' )
			.filter( { hasText: uniqueValue } )
			.first();
		await expect( updatedRow ).toBeVisible( { timeout: 10000 } );
		await expect( updatedRow.getByText( 'Read', { exact: true } ) ).toBeVisible( { timeout: 10000 } );
	} );
} );
