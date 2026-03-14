/**
 * Shared Playwright helpers for SureForms E2E tests.
 *
 * Re-used across all spec files to keep boilerplate out of individual tests.
 */

const { expect } = require( '@playwright/test' );

// ─── Editor helpers ──────────────────────────────────────────────────────────

/**
 * Navigate to the Gutenberg form editor and wait until the draggable sidebar
 * is visible (signals the editor JS is ready).
 *
 * @param {import('@playwright/test').Page} page
 */
async function createBlankForm( page ) {
	await page.goto( '/wp-admin/post-new.php?post_type=sureforms_form' );
	await page.waitForLoadState( 'load' );

	// Wait for the quick-action sidebar (draggable block panel) to appear.
	await page.locator( '#draggable-box__srfm--input' ).waitFor( { state: 'visible', timeout: 30000 } );

	// Dismiss the Gutenberg welcome guide if it appears.
	const welcomeGuide = page.getByRole( 'button', { name: /close/i } ).filter( { hasText: /close/i } );
	if ( await welcomeGuide.isVisible( { timeout: 3000 } ).catch( () => false ) ) {
		await welcomeGuide.click();
	}
}

/**
 * Publish the form currently open in the editor and return its frontend URL.
 *
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<string>} Frontend URL of the published form.
 */
async function publishFormAndGetURL( page ) {
	await page.getByRole( 'button', { name: 'Publish', exact: true } ).click();

	// Gutenberg may show a pre-publish confirmation panel.
	const confirmPublish = page
		.getByLabel( 'Editor publish' )
		.getByRole( 'button', { name: 'Publish', exact: true } );

	if ( await confirmPublish.isVisible( { timeout: 3000 } ).catch( () => false ) ) {
		await confirmPublish.click();
	}

	const viewFormLink = page.getByRole( 'link', { name: 'View Form' } ).first();
	await expect( viewFormLink ).toBeVisible( { timeout: 15000 } );

	return await viewFormLink.getAttribute( 'href' );
}

/**
 * Add a field block to the current form by clicking its draggable box in the
 * quick-action sidebar, then wait for it to appear in the editor canvas.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} blockSlug  The part after `srfm/` — e.g. 'textarea', 'number', 'multi-choice'.
 */
async function addFieldBlock( page, blockSlug ) {
	await page.locator( `#draggable-box__srfm--${ blockSlug }` ).click();
	await expect(
		page.locator( `.wp-block[data-type="srfm/${ blockSlug }"]` )
	).toBeVisible( { timeout: 15000 } );
}

/**
 * Open the Block settings tab in the Gutenberg sidebar.
 * Call this after clicking on a block in the editor canvas.
 *
 * @param {import('@playwright/test').Page} page
 */
async function openBlockSettingsTab( page ) {
	await page.getByRole( 'tab', { name: 'Block' } ).click();
}

/**
 * Enable the Required toggle on the currently selected block.
 * Assumes the Block settings tab is already open.
 *
 * @param {import('@playwright/test').Page} page
 */
async function enableRequiredField( page ) {
	const requiredToggle = page
		.locator( '.components-toggle-control' )
		.filter( { hasText: /^Required$/i } )
		.first();
	await expect( requiredToggle ).toBeVisible( { timeout: 5000 } );

	const isChecked = await requiredToggle.locator( 'input[type="checkbox"]' ).isChecked();
	if ( ! isChecked ) {
		await requiredToggle.locator( 'input[type="checkbox"]' ).click();
	}
}

// ─── REST API helpers (run in page context while logged-in) ──────────────────

/**
 * Create a published WordPress page with arbitrary content using the WP
 * REST API.  Must be called while the page is already logged in to wp-admin
 * so that `wpApiSettings.nonce` is available.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} title   Page title.
 * @param {string} content Page content (HTML / shortcode string).
 * @returns {Promise<{id: number, link: string}>}
 */
async function createWPPage( page, title, content ) {
	// Ensure we're on a wp-admin page so wpApiSettings is loaded.
	if ( ! page.url().includes( '/wp-admin' ) ) {
		await page.goto( '/wp-admin/' );
		await page.waitForLoadState( 'load' );
	}

	const result = await page.evaluate(
		async ( { title: t, content: c } ) => {
			const nonce = window.wpApiSettings?.nonce ?? '';
			const res = await fetch( '/wp-json/wp/v2/pages', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': nonce,
				},
				body: JSON.stringify( { title: t, content: c, status: 'publish' } ),
			} );
			const data = await res.json();
			return { id: data.id, link: data.link };
		},
		{ title, content }
	);

	if ( ! result?.link ) {
		throw new Error( `Failed to create page: ${ JSON.stringify( result ) }` );
	}

	return result;
}

module.exports = {
	createBlankForm,
	publishFormAndGetURL,
	addFieldBlock,
	openBlockSettingsTab,
	enableRequiredField,
	createWPPage,
};
