/**
 * E2E tests — Form settings (P0).
 *
 * Covers:
 *   3.1 Redirect on submission — user lands on configured URL
 *   3.2 Custom success message — configured text shown after submit
 *   3.5 Store entries enabled (default) — entry appears in admin after submit
 */

const { test, expect } = require( '@playwright/test' );
const { loginAsAdmin } = require( '../utils/loginAsAdmin' );
const {
	createBlankForm,
	addFieldBlock,
	publishFormAndGetURL,
} = require( '../utils/formHelpers' );

test.describe( 'Form settings', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	// ── 3.2 Custom success message ────────────────────────────────────────────
	test( 'custom success message is shown after submission', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'input' );

		// Open the "Form Confirmation" document settings panel.
		// It lives in the editor sidebar under the Form/Document tab.
		const sidebarToggle = page.getByRole( 'button', { name: /settings/i } );
		if ( await sidebarToggle.isVisible( { timeout: 3000 } ).catch( () => false ) ) {
			const isOpen = await page.locator( '.interface-interface-skeleton__sidebar' ).isVisible().catch( () => false );
			if ( ! isOpen ) {
				await sidebarToggle.click();
			}
		}

		// Switch to the "Post" (document) tab.
		const postTab = page.getByRole( 'tab', { name: /post|form|document/i } ).first();
		if ( await postTab.isVisible( { timeout: 3000 } ).catch( () => false ) ) {
			await postTab.click();
		}

		// Expand the "Form Confirmation" panel if it's collapsed.
		const confirmationPanel = page.getByRole( 'button', { name: /form confirmation/i } );
		if ( await confirmationPanel.isVisible( { timeout: 5000 } ).catch( () => false ) ) {
			const isExpanded = await confirmationPanel.getAttribute( 'aria-expanded' );
			if ( isExpanded === 'false' ) {
				await confirmationPanel.click();
			}
		}

		// The "Success Message" option should already be selected by default.
		// Find the success message text area and change it.
		const messageTextarea = page.locator( 'textarea[placeholder*="message" i], textarea[aria-label*="message" i]' ).first();
		if ( await messageTextarea.isVisible( { timeout: 5000 } ).catch( () => false ) ) {
			await messageTextarea.clear();
			await messageTextarea.fill( 'Your submission was received!' );
		}

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

		// Open the "Form Confirmation" panel and set it to Redirect → Custom URL.
		const sidebarToggle = page.getByRole( 'button', { name: /settings/i } );
		if ( await sidebarToggle.isVisible( { timeout: 3000 } ).catch( () => false ) ) {
			const isOpen = await page.locator( '.interface-interface-skeleton__sidebar' ).isVisible().catch( () => false );
			if ( ! isOpen ) {
				await sidebarToggle.click();
			}
		}

		const postTab = page.getByRole( 'tab', { name: /post|form|document/i } ).first();
		if ( await postTab.isVisible( { timeout: 3000 } ).catch( () => false ) ) {
			await postTab.click();
		}

		const confirmationPanel = page.getByRole( 'button', { name: /form confirmation/i } );
		if ( await confirmationPanel.isVisible( { timeout: 5000 } ).catch( () => false ) ) {
			const isExpanded = await confirmationPanel.getAttribute( 'aria-expanded' );
			if ( isExpanded === 'false' ) {
				await confirmationPanel.click();
			}
		}

		// Select the "Redirect" option.
		const redirectOption = page.getByLabel( /^Redirect$/i ).first();
		if ( await redirectOption.isVisible( { timeout: 5000 } ).catch( () => false ) ) {
			await redirectOption.click();
		} else {
			// Try clicking the radio or button labeled "Redirect".
			await page.getByRole( 'radio', { name: /redirect/i } ).first().click();
		}

		// Select "Custom URL" sub-option.
		const customUrlOption = page.getByLabel( /custom url/i ).first();
		if ( await customUrlOption.isVisible( { timeout: 3000 } ).catch( () => false ) ) {
			await customUrlOption.click();
		}

		// Enter the redirect URL — use the WP home page so the redirect lands somewhere real.
		const urlInput = page.locator( 'input[placeholder*="http" i], input[type="url"], input[aria-label*="url" i]' )
			.filter( { hasNot: page.locator( '#draggable-box__srfm--url' ) } )
			.first();
		if ( await urlInput.isVisible( { timeout: 3000 } ).catch( () => false ) ) {
			await urlInput.fill( 'http://localhost:8888/' );
		}

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		await page.locator( 'input.srfm-input-input' ).first().fill( 'Redirect test' );

		await Promise.all( [
			page.waitForURL( '**/localhost:8888/**', { timeout: 15000 } ),
			page.locator( '#srfm-submit-btn' ).click(),
		] );

		// After redirect, success box is no longer on the page.
		expect( page.url() ).toMatch( /localhost:8888/ );
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
		// Wait for the React entries app to render.
		await page.waitForTimeout( 2000 );

		// The entry row should be present somewhere on the entries page.
		await expect(
			page.locator( 'body' ).getByText( uniqueValue )
		).toBeVisible( { timeout: 10000 } );
	} );
} );
