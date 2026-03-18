/**
 * E2E tests — Form embedding (P0).
 *
 * Covers:
 *   5.1 Shortcode embed on a page — form renders
 *   5.2 Shortcode embed — form submits successfully
 *   5.3 Gutenberg block embed on a page — form renders
 *   5.4 Gutenberg block embed — form submits successfully
 *
 * Pages are created via the WordPress REST API (using the logged-in session
 * cookies + nonce) to avoid slow Gutenberg-based page creation.
 */

const { test, expect } = require( '@playwright/test' );
const { loginAsAdmin } = require( '../utils/loginAsAdmin' );
const {
	createBlankForm,
	addFieldBlock,
	publishFormAndGetURL,
	createWPPage,
} = require( '../utils/formHelpers' );

/**
 * Create a form with a text field and return its ID and frontend URL.
 *
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<{formId: string, formURL: string}>}
 */
async function setupSimpleForm( page ) {
	await createBlankForm( page );
	await addFieldBlock( page, 'input' );
	const formURL = await publishFormAndGetURL( page );

	// The editor URL after publishing is post.php?post=X&action=edit.
	const editorURL = page.url();
	const match = editorURL.match( /[?&]post=(\d+)/ );
	const formId = match ? match[ 1 ] : null;

	if ( ! formId ) {
		throw new Error( `Could not extract form ID from editor URL: ${ editorURL }` );
	}

	return { formId, formURL };
}

test.describe( 'Form embedding', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	// ── 5.1 Shortcode embed — form renders ────────────────────────────────────
	test( 'shortcode-embedded form renders on a page', async ( { page } ) => {
		const { formId } = await setupSimpleForm( page );

		// Create a WordPress page containing the shortcode.
		const { link: pageURL } = await createWPPage(
			page,
			`Shortcode Test ${ Date.now() }`,
			`[sureforms id="${ formId }"]`
		);

		await page.goto( pageURL );
		await page.waitForLoadState( 'load' );

		// The form's submit button should be visible — confirms the form rendered.
		await expect( page.locator( '#srfm-submit-btn' ) ).toBeVisible( { timeout: 15000 } );
	} );

	// ── 5.2 Shortcode embed — form submits successfully ───────────────────────
	test( 'shortcode-embedded form submits successfully', async ( { page } ) => {
		const { formId } = await setupSimpleForm( page );

		const { link: pageURL } = await createWPPage(
			page,
			`Shortcode Submit Test ${ Date.now() }`,
			`[sureforms id="${ formId }"]`
		);

		await page.goto( pageURL );
		await page.waitForLoadState( 'load' );

		await page.locator( 'input.srfm-input-input' ).first().fill( 'Shortcode submission' );
		await page.locator( '#srfm-submit-btn' ).click();

		await expect( page.locator( '.srfm-success-box' ) ).toBeVisible( { timeout: 15000 } );
		await expect( page.locator( '.srfm-success-box' ) ).toContainText( /Thank you/i );
	} );

	// ── 5.3 Gutenberg block embed — form renders ──────────────────────────────
	test( 'Gutenberg block-embedded form renders on a page', async ( { page } ) => {
		const { formId } = await setupSimpleForm( page );

		// WordPress serialises the block as <!-- wp:srfm/form {"id":X} /-->
		const blockContent = `<!-- wp:srfm/form {"id":${ formId }} /-->`;

		const { link: pageURL } = await createWPPage(
			page,
			`Block Embed Test ${ Date.now() }`,
			blockContent
		);

		await page.goto( pageURL );
		await page.waitForLoadState( 'load' );

		await expect( page.locator( '#srfm-submit-btn' ) ).toBeVisible( { timeout: 15000 } );
	} );

	// ── 5.4 Gutenberg block embed — form submits successfully ─────────────────
	test( 'Gutenberg block-embedded form submits successfully', async ( { page } ) => {
		const { formId } = await setupSimpleForm( page );

		const blockContent = `<!-- wp:srfm/form {"id":${ formId }} /-->`;

		const { link: pageURL } = await createWPPage(
			page,
			`Block Submit Test ${ Date.now() }`,
			blockContent
		);

		await page.goto( pageURL );
		await page.waitForLoadState( 'load' );

		await page.locator( 'input.srfm-input-input' ).first().fill( 'Block submission' );
		await page.locator( '#srfm-submit-btn' ).click();

		await expect( page.locator( '.srfm-success-box' ) ).toBeVisible( { timeout: 15000 } );
		await expect( page.locator( '.srfm-success-box' ) ).toContainText( /Thank you/i );
	} );
} );
