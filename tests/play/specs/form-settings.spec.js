/**
 * E2E tests — Form settings (P0).
 *
 * Covers:
 *   3.1 Redirect on submission — user lands on configured URL
 *   3.2 Custom success message — configured text shown after submit
 *   3.3 Store entries disabled — submission succeeds but no entry is stored
 *   3.4 Page confirmation type — redirect to a configured WordPress page
 *   3.5 Store entries enabled (default) — entry appears in admin after submit
 *
 * Form Confirmation settings live inside the "Form Behavior" dialog opened via:
 *   Editor header → "Form Settings" button → "Form Confirmation" nav item
 */

const { test, expect } = require( '@playwright/test' );
const { loginAsAdmin } = require( '../utils/loginAsAdmin' );
const {
	createBlankForm,
	addFieldBlock,
	publishFormAndGetURL,
	openFormSettingsDialog,
} = require( '../utils/formHelpers' );

test.describe( 'Form settings', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	// ── 3.2 Custom success message ────────────────────────────────────────────
	test( 'custom success message is shown after submission', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'input' );

		// Open Form Behavior dialog → Form Confirmation tab.
		await openFormSettingsDialog( page, 'Form Confirmation' );

		// "Success Message" type should already be selected by default.
		// Edit the success message in the Quill rich-text editor.
		const quillEditor = page.locator( '.ql-editor' ).first();
		await expect( quillEditor ).toBeVisible( { timeout: 10000 } );
		await quillEditor.click();
		await page.keyboard.press( 'Control+a' );
		await page.keyboard.type( 'Your submission was received!' );

		// Wait for the debounced auto-save (500 ms) to update the editor store.
		await page.waitForTimeout( 1000 );

		// Close the dialog — the dialog has exitOnEsc so pressing Escape works
		// reliably without hitting the WP admin bar click-intercept issue.
		await page.keyboard.press( 'Escape' );

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		await page.locator( 'input.srfm-input-input' ).first().fill( 'Test value' );
		await page.locator( '#srfm-submit-btn' ).click();

		await expect( page.locator( '.srfm-success-box' ) ).toBeVisible( { timeout: 15000 } );
		await expect( page.locator( '.srfm-success-box' ) ).toContainText( 'Your submission was received!' );
	} );

	// ── 3.1 Redirect on submission ────────────────────────────────────────────
	test( 'redirect after submission navigates to configured URL', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'input' );

		// Set the confirmation meta directly via the editor store.
		// The dialog UI rejects http:// URLs with an HTTPS validation error,
		// so we bypass it and call editPost — the same function the dialog uses internally.
		await page.evaluate( () => {
			const { dispatch } = window.wp.data;
			dispatch( 'core/editor' ).editPost( {
				meta: {
					_srfm_form_confirmation: [
						{
							confirmation_type: 'custom url',
							custom_url: 'http://localhost:8888/wp-admin/',
						},
					],
				},
			} );
		} );

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		await page.locator( 'input.srfm-input-input' ).first().fill( 'Redirect test' );

		await Promise.all( [
			page.waitForURL( '**/wp-admin/**', { timeout: 15000 } ),
			page.locator( '#srfm-submit-btn' ).click(),
		] );

		expect( page.url() ).toContain( '/wp-admin/' );
	} );

	// ── 3.3 Store entries disabled ────────────────────────────────────────────
	test( 'store entries disabled: submission succeeds but no entry is created', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'input' );

		// Disable entry storage via the compliance meta.
		// The dialog UI is not used here — we set the meta directly via editPost
		// to avoid any UI-level restrictions.
		await page.evaluate( () => {
			window.wp.data.dispatch( 'core/editor' ).editPost( {
				meta: {
					_srfm_compliance: [
						{
							id: 'gdpr',
							gdpr: true,
							do_not_store_entries: true,
							auto_delete_entries: false,
							auto_delete_days: '',
						},
					],
				},
			} );
		} );

		const formURL = await publishFormAndGetURL( page );

		// Capture the form post ID from the editor URL before navigating away.
		const editorURL = page.url();
		const formIdMatch = editorURL.match( /[?&]post=(\d+)/ );
		const formId = formIdMatch ? formIdMatch[ 1 ] : null;

		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		const uniqueVal = `No-Store-${ Date.now() }`;
		await page.locator( 'input.srfm-input-input' ).first().fill( uniqueVal );
		await page.locator( '#srfm-submit-btn' ).click();

		// Submission itself should succeed — success box must be visible.
		await expect( page.locator( '.srfm-success-box' ) ).toBeVisible( { timeout: 15000 } );

		// Navigate to the entries page for this specific form.
		const entriesURL = formId
			? `/wp-admin/admin.php?page=sureforms_entries#/?form=${ formId }`
			: '/wp-admin/admin.php?page=sureforms_entries';
		await page.goto( entriesURL );
		await page.waitForLoadState( 'load' );

		// Allow React to finish rendering the entries table.
		await page.waitForTimeout( 3000 );

		// The submitted value must NOT appear — no entry should have been stored.
		await expect(
			page.locator( 'body' ).getByText( uniqueVal )
		).not.toBeVisible( { timeout: 5000 } );
	} );

	// ── 3.4 Page confirmation type ────────────────────────────────────────────
	test( 'page confirmation type redirects to configured WordPress page', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'input' );

		// Set confirmation to "different page" via editPost to bypass the HTTPS
		// validation that the dialog UI enforces on redirect URLs.
		await page.evaluate( () => {
			window.wp.data.dispatch( 'core/editor' ).editPost( {
				meta: {
					_srfm_form_confirmation: [
						{
							confirmation_type: 'different page',
							page_url: 'http://localhost:8888/wp-admin/',
							custom_url: '',
							message: '',
						},
					],
				},
			} );
		} );

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		await page.locator( 'input.srfm-input-input' ).first().fill( 'Page redirect test' );

		await Promise.all( [
			page.waitForURL( '**/wp-admin/**', { timeout: 15000 } ),
			page.locator( '#srfm-submit-btn' ).click(),
		] );

		expect( page.url() ).toContain( '/wp-admin/' );
	} );

	// ── 3.5 Store entries — entry appears in admin after submission ───────────
	test( 'submitted form creates an entry in the admin entries list', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'input' );

		const formURL = await publishFormAndGetURL( page );

		// Extract form ID from the editor URL (post.php?post=X&action=edit).
		const editorURL = page.url();
		const formIdMatch = editorURL.match( /[?&]post=(\d+)/ );
		const formId = formIdMatch ? formIdMatch[ 1 ] : null;

		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		const uniqueValue = `E2E-Entry-${ Date.now() }`;
		await page.locator( 'input.srfm-input-input' ).first().fill( uniqueValue );
		await page.locator( '#srfm-submit-btn' ).click();

		await expect( page.locator( '.srfm-success-box' ) ).toBeVisible( { timeout: 15000 } );

		// Navigate to the admin entries page.
		const entriesURL = formId
			? `/wp-admin/admin.php?page=sureforms_entries#/?form=${ formId }`
			: '/wp-admin/admin.php?page=sureforms_entries';
		await page.goto( entriesURL );
		await page.waitForLoadState( 'load' );

		// The entry row should be present somewhere on the entries page.
		await expect(
			page.locator( 'body' ).getByText( uniqueValue )
		).toBeVisible( { timeout: 10000 } );
	} );
} );
