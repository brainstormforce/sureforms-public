/**
 * E2E tests — Form lifecycle (P0).
 *
 * Covers:
 *   8.1 Duplicate form  — copy appears in the forms list with "(Copy)" suffix
 *   8.2 Trash form      — form disappears from the All Forms view
 *   8.3 Restore form    — form reappears in All Forms after restore from Trash
 *
 * ── Forms list UI architecture ────────────────────────────────────────────────
 *
 * The forms list at `/wp-admin/admin.php?page=sureforms_forms` is a React
 * app built on `@bsf/force-ui` Table.  It renders as a standard `<table>`
 * so normal Playwright table helpers work, but there are a few gotchas:
 *
 * Row action buttons:
 *   - The Edit action renders as <a href="..."> NOT <button>, so it is excluded
 *     when using row.locator('button').nth(N).
 *   - The Entries column renders a <Button> (link variant, shows entry count) which
 *     IS counted as button[0] in every row — shifting all action buttons by 1.
 *   - Order for ACTIVE forms (button index):
 *       EntriesCount(0) · View(1) · Export(2) · Duplicate(3) · Trash(4)
 *   - Order for TRASHED forms (button index):
 *       EntriesCount(0) · Restore(1) · Delete Permanently(2)
 *   - Buttons are only visible on hover; Playwright triggers the hover automatically
 *     when it scrolls to click, so no explicit `.hover()` call is needed.
 *
 * Confirmation dialogs:
 *   - Duplicate and Trash open a `[role="dialog"]` ConfirmationDialog.
 *   - Restore fires immediately — no confirmation dialog.
 *   - Confirm button text matches the action: "Duplicate" or "Move to Trash".
 *
 * Status filter:
 *   - A `@bsf/force-ui` Select rendered as a button showing the current
 *     filter label (All Forms / Published / Drafts / Trash).
 *   - Clicking the button opens a portal with `[role="option"]` / `<li>` items.
 *
 * Data refresh:
 *   - After every mutating action the table re-fetches from the REST API.
 *   - Wait for the expected row state rather than using fixed sleeps.
 */

const { test, expect } = require( '@playwright/test' );
const { loginAsAdmin } = require( '../utils/loginAsAdmin' );
const {
	createBlankForm,
	addFieldBlock,
	publishFormAndGetURL,
	setFormTitle,
} = require( '../utils/formHelpers' );

// ── Button index constants for ACTIVE form rows ───────────────────────────────
// The Edit action renders as <a href="..."> (not <button>), so it is excluded
// from the button index count.  BUT the Entries column renders a <Button> for
// the entry count (button[0]) — so all action buttons are shifted by 1.
// Actual button order: EntriesCount(0) · View(1) · Export(2) · Duplicate(3) · Trash(4)
const ACTION = {
	VIEW: 1,
	EXPORT: 2,
	DUPLICATE: 3,
	TRASH: 4,
};

// Button index constants for TRASHED form rows.
// Trashed rows still have the EntriesCount button at index 0.
// Actual button order: EntriesCount(0) · Restore(1) · DeletePermanently(2)
const TRASH_ACTION = {
	RESTORE: 1,
	DELETE_PERMANENTLY: 2,
};

// ── Local helpers (not added to formHelpers — lifecycle-specific only) ────────

/**
 * Navigate to the forms list admin page and wait for the table to render.
 *
 * @param {import('@playwright/test').Page} page
 */
async function goToFormsList( page ) {
	await page.goto( '/wp-admin/admin.php?page=sureforms_forms' );
	// Wait for the React app to finish rendering by waiting for the status-filter
	// combobox (the @bsf/force-ui Select.Button renders as [role="combobox"]).
	// Waiting for a plain <table> can fire on a WP loading skeleton before the
	// interactive controls appear.
	await page.locator( '[role="combobox"]' ).first().waitFor( { state: 'visible', timeout: 20000 } );
}

/**
 * Return the first `<tr>` that contains the given title text.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} title
 * @returns {import('@playwright/test').Locator}
 */
function getFormRow( page, title ) {
	return page.locator( 'tr' ).filter( { hasText: title } ).first();
}

/**
 * Click an icon action button (by index) on the row matching `title`.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} title       Text that uniquely identifies the row.
 * @param {number} buttonIndex 0-based index among buttons inside that row.
 */
async function clickRowAction( page, title, buttonIndex ) {
	const row = getFormRow( page, title );
	await expect( row ).toBeVisible( { timeout: 10000 } );
	await row.locator( 'button' ).nth( buttonIndex ).click();
}

/**
 * Find the open ConfirmationDialog and click the button with the given label,
 * then wait for the dialog to close.
 *
 * There are multiple [role="dialog"] elements on the page (WP overlay, floating-ui
 * panel, "what's new" flyout) so we filter to the one that contains our button.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} confirmLabel  Exact visible text of the confirm button, e.g. "Duplicate".
 */
async function confirmDialog( page, confirmLabel ) {
	// Scope to the dialog that actually contains our confirm button to avoid
	// strict-mode violations from multiple [role="dialog"] elements.
	const confirmBtn = page.getByRole( 'button', { name: confirmLabel, exact: true } );
	await expect( confirmBtn ).toBeVisible( { timeout: 10000 } );
	await confirmBtn.click();

	// Wait for the button (and thus the dialog) to disappear before continuing.
	await expect( confirmBtn ).not.toBeVisible( { timeout: 10000 } );
}

/**
 * Change the status filter on the forms list.
 *
 * Clicks the button that shows the currently-active filter text (e.g.
 * "All Forms"), then clicks the option matching `optionLabel` from the
 * portal that opens.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} optionLabel  Visible text of the target option, e.g. "Trash".
 */
async function setStatusFilter( page, optionLabel ) {
	// The @bsf/force-ui Select.Button renders as [role="combobox"], NOT a plain
	// <button>.  Filter to the one that shows a status label.
	const filterButton = page
		.locator( '[role="combobox"]' )
		.filter( { hasText: /All Forms|Published|Drafts|Trash/ } )
		.first();
	await expect( filterButton ).toBeVisible( { timeout: 10000 } );
	await filterButton.click();

	// Options appear in a portal (role="option") outside the normal DOM tree.
	const option = page
		.locator( '[role="option"]' )
		.filter( { hasText: optionLabel } )
		.first();
	await expect( option ).toBeVisible( { timeout: 5000 } );
	await option.click();
}

// ── Test suite ────────────────────────────────────────────────────────────────

test.describe( 'Form lifecycle', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	// ── 8.1 Duplicate form ────────────────────────────────────────────────────
	test( 'duplicate form — copy appears in forms list with "(Copy)" suffix', async ( { page } ) => {
		await createBlankForm( page );

		// Use a timestamp-based title so we can locate this exact row later.
		const title = `Lifecycle Duplicate ${ Date.now() }`;
		await setFormTitle( page, title );
		await addFieldBlock( page, 'input' );
		await publishFormAndGetURL( page );

		await goToFormsList( page );

		// Trigger the Duplicate action (button index 3 on active rows, after entries count at 0).
		await clickRowAction( page, title, ACTION.DUPLICATE );

		// A ConfirmationDialog appears — confirm it.
		await confirmDialog( page, 'Duplicate' );

		// After the table re-fetches, a copy row with "(Copy)" should be present.
		// Note: the REST API applies sanitize_text_field to the title_suffix, which strips
		// the leading space from ' (Copy)' → the stored title is "{title}(Copy)" (no space).
		// The row is a Draft so the table also appends " (Draft)" in the cell text.
		// Use the raw title as the hasText needle — it matches as a substring.
		const copyTitle = `${ title }(Copy)`;
		await expect( getFormRow( page, copyTitle ) ).toBeVisible( { timeout: 15000 } );
	} );

	// ── 8.2 Trash form ────────────────────────────────────────────────────────
	test( 'trash form — form disappears from All Forms view', async ( { page } ) => {
		await createBlankForm( page );

		const title = `Lifecycle Trash ${ Date.now() }`;
		await setFormTitle( page, title );
		await addFieldBlock( page, 'input' );
		await publishFormAndGetURL( page );

		await goToFormsList( page );

		// Confirm the row is present before trashing.
		await expect( getFormRow( page, title ) ).toBeVisible( { timeout: 10000 } );

		// Trigger the Trash action (button index 4 on active rows).
		await clickRowAction( page, title, ACTION.TRASH );

		// A ConfirmationDialog appears — confirm it.
		await confirmDialog( page, 'Move to Trash' );

		// The row should disappear from the "All Forms" view after the re-fetch.
		await expect( getFormRow( page, title ) ).not.toBeVisible( { timeout: 15000 } );
	} );

	// ── 8.3 Restore form ─────────────────────────────────────────────────────
	test( 'restore form — form reappears in All Forms view after restore from Trash', async ( { page } ) => {
		await createBlankForm( page );

		const title = `Lifecycle Restore ${ Date.now() }`;
		await setFormTitle( page, title );
		await addFieldBlock( page, 'input' );
		await publishFormAndGetURL( page );

		// Extract the form ID from the editor URL (post.php?post=X&action=edit)
		// so we can trash it via REST API without going through the list UI.
		const formId = page.url().match( /[?&]post=(\d+)/ )?.[ 1 ];
		if ( ! formId ) {
			throw new Error( 'Could not extract form ID from editor URL: ' + page.url() );
		}

		// Trash the form via REST API — faster and more reliable than the list UI,
		// and we want the "restore" action itself to be the thing under test.
		// WP REST DELETE without `?force=true` moves to trash (does NOT permanently delete).
		await page.evaluate( async ( id ) => {
			const nonce = window.wpApiSettings?.nonce ?? '';
			await fetch( `/wp-json/wp/v2/sureforms_form/${ id }`, {
				method: 'DELETE',
				headers: { 'X-WP-Nonce': nonce },
			} );
		}, formId );

		// Navigate to the forms list and switch to the Trash filter.
		await goToFormsList( page );
		await setStatusFilter( page, 'Trash' );

		// Confirm the trashed form appears under the Trash filter.
		await expect( getFormRow( page, title ) ).toBeVisible( { timeout: 15000 } );

		// Restore fires immediately — no confirmation dialog.
		// Button index 0 on trashed rows is "Restore".
		await clickRowAction( page, title, TRASH_ACTION.RESTORE );

		// The row should disappear from the Trash view after the re-fetch.
		await expect( getFormRow( page, title ) ).not.toBeVisible( { timeout: 15000 } );

		// Switch back to All Forms and confirm the form is visible again.
		await setStatusFilter( page, 'All Forms' );
		await expect( getFormRow( page, title ) ).toBeVisible( { timeout: 15000 } );
	} );
} );
