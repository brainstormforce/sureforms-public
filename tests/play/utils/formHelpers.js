/**
 * Shared Playwright helpers for SureForms E2E tests.
 *
 * Re-used across all spec files to keep boilerplate out of individual tests.
 */

const { expect } = require( '@playwright/test' );

// ─── Blocks available in the quick-action sidebar (default list) ──────────────
// Source: admin/admin.php srfm_quick_sidebar_allowed_blocks filter default.
const SIDEBAR_BLOCKS = [
	'input',
	'email',
	'textarea',
	'checkbox',
	'number',
	'inline-button',
	'advanced-heading',
	'payment',
];

// Block titles used to search in the Gutenberg inserter for non-sidebar blocks.
const INSERTER_BLOCK_TITLES = {
	phone: 'Phone Number',
	url: 'URL',
	'multi-choice': 'Multiple Choice',
	dropdown: 'Dropdown',
	gdpr: 'GDPR Agreement',
	address: 'Address',
};

// ─── Editor canvas helper ───────────────────────────────────────────────────
// WP 6.5+ renders the block editor content inside an iframe (name="editor-canvas").
// This helper returns a locator scope that works for both iframe and non-iframe editors.

/**
 * Get the editor canvas scope.
 *
 * @param {import('@playwright/test').Page} page
 * @returns {import('@playwright/test').FrameLocator|import('@playwright/test').Page}
 */
function getEditorCanvas( page ) {
	return page.frameLocator( '[aria-label="Editor content"] iframe, iframe[name="editor-canvas"]' );
}

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
	await page
		.locator( '#draggable-box__srfm--input' )
		.waitFor( { state: 'visible', timeout: 30000 } );

	// Dismiss the Gutenberg welcome guide if it appears.
	const welcomeGuide = page
		.getByRole( 'button', { name: /close/i } )
		.filter( { hasText: /close/i } );
	if (
		await welcomeGuide.isVisible( { timeout: 3000 } ).catch( () => false )
	) {
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
	await page
		.getByRole( 'button', { name: 'Publish', exact: true } )
		.click();

	// Gutenberg may show a pre-publish confirmation panel.
	const confirmPublish = page
		.getByLabel( 'Editor publish' )
		.getByRole( 'button', { name: 'Publish', exact: true } );

	if (
		await confirmPublish
			.isVisible( { timeout: 3000 } )
			.catch( () => false )
	) {
		await confirmPublish.click();
	}

	const viewFormLink = page.getByRole( 'link', { name: 'View Form' } ).first();
	await expect( viewFormLink ).toBeVisible( { timeout: 15000 } );

	return await viewFormLink.getAttribute( 'href' );
}

/**
 * Add a field block to the current form.
 *
 * For blocks in the quick-action sidebar the draggable box is clicked directly.
 * For other blocks the Gutenberg block inserter is used.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} blockSlug  The part after `srfm/` — e.g. 'textarea', 'phone', 'multi-choice'.
 */
async function addFieldBlock( page, blockSlug ) {
	if ( SIDEBAR_BLOCKS.includes( blockSlug ) ) {
		await page.locator( `#draggable-box__srfm--${ blockSlug }` ).click();
	} else {
		await addFieldBlockViaInserter(
			page,
			blockSlug,
			INSERTER_BLOCK_TITLES[ blockSlug ] || blockSlug
		);
	}
	// Verify the block was inserted via the editor data store.
	// WP 6.9+ removed data-type from block wrappers, so DOM selectors are unreliable.
	await expect( async () => {
		const hasBlock = await page.evaluate( ( slug ) => {
			const blocks = window.wp.data.select( 'core/block-editor' ).getBlocks();
			const find = ( list ) => list.some( ( b ) =>
				b.name === `srfm/${ slug }` ||
				( b.innerBlocks?.length && find( b.innerBlocks ) )
			);
			return find( blocks );
		}, blockSlug );
		if ( ! hasBlock ) {
			throw new Error( `Block srfm/${ blockSlug } not found in editor` );
		}
	} ).toPass( { timeout: 15000 } );
}

/**
 * Add a field block programmatically via the WordPress block-editor data store.
 * Used for blocks that are not in the quick-action sidebar.
 *
 * This approach bypasses UI flakiness and directly calls Gutenberg's
 * `insertBlock` dispatch, which is the same underlying operation the
 * quick-action sidebar draggable blocks use.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} blockSlug   The part after `srfm/`.
 */
async function addFieldBlockViaInserter( page, blockSlug ) {
	await page.evaluate( async ( slug ) => {
		const { dispatch, select } = window.wp.data;
		const { createBlock } = window.wp.blocks;

		// Find the SureForms form block's clientId so we insert inside it.
		const allBlocks = select( 'core/block-editor' ).getBlocks();
		const findFormClientId = ( blocks ) => {
			for ( const b of blocks ) {
				if ( b.name === 'srfm/sureforms-form' ) {
					return b.clientId;
				}
				if ( b.innerBlocks?.length ) {
					const found = findFormClientId( b.innerBlocks );
					if ( found ) {
						return found;
					}
				}
			}
			return null;
		};

		const formClientId = findFormClientId( allBlocks );
		const newBlock = createBlock( `srfm/${ slug }`, {} );

		if ( formClientId ) {
			const inner =
				select( 'core/block-editor' ).getBlock( formClientId )
					?.innerBlocks ?? [];
			dispatch( 'core/block-editor' ).insertBlock(
				newBlock,
				inner.length,
				formClientId
			);
		} else {
			dispatch( 'core/block-editor' ).insertBlock( newBlock );
		}
	}, blockSlug );
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

	const isChecked = await requiredToggle
		.locator( 'input[type="checkbox"]' )
		.isChecked();
	if ( ! isChecked ) {
		await requiredToggle.locator( 'input[type="checkbox"]' ).click();
	}
}

/**
 * Open the "Form Settings" popover in the editor header, then click the
 * specified settings item to open the Form Behavior dialog.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} itemLabel  Label of the settings item, e.g. 'Form Confirmation'.
 */
async function openFormSettingsDialog( page, itemLabel ) {
	// Click the "Form Settings" button in the SureForms editor header.
	await page.locator( '.srfm-form-settings-button' ).click();

	// Wait for the popover to appear and click the desired item.
	const item = page
		.locator( '.srfm-form-settings-nav-item-label' )
		.filter( { hasText: itemLabel } )
		.first();
	await expect( item ).toBeVisible( { timeout: 5000 } );
	await item.click();

	// Wait for the dialog to open (indicated by "Form Behavior" title).
	await expect(
		page.locator( '.srfm-dialog-panel' )
	).toBeVisible( { timeout: 10000 } );
}

/**
 * Select a SureForms block in the editor by its slug via the data store.
 *
 * Works regardless of iframe embedding or `data-type` attribute presence
 * (both changed in WP 6.9+).
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} blockSlug  The part after `srfm/` — e.g. 'input', 'email'.
 */
async function selectBlock( page, blockSlug ) {
	await page.evaluate( ( slug ) => {
		const { dispatch, select } = window.wp.data;
		const blocks = select( 'core/block-editor' ).getBlocks();
		const findClientId = ( list ) => {
			for ( const b of list ) {
				if ( b.name === `srfm/${ slug }` ) {
					return b.clientId;
				}
				if ( b.innerBlocks?.length ) {
					const found = findClientId( b.innerBlocks );
					if ( found ) {
						return found;
					}
				}
			}
			return null;
		};
		const clientId = findClientId( blocks );
		if ( clientId ) {
			dispatch( 'core/block-editor' ).selectBlock( clientId );
		}
	}, blockSlug );
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

/**
 * Set the Gutenberg post title via the editor data store.
 *
 * Using the UI title input is fragile because Gutenberg re-renders it on
 * every keystroke and the caret can jump.  Dispatching `editPost` directly
 * is the same operation the title input uses internally and is fully
 * synchronous from Playwright's perspective.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} title  The title to set on the current post.
 */
async function setFormTitle( page, title ) {
	await page.evaluate( ( t ) => {
		window.wp.data.dispatch( 'core/editor' ).editPost( { title: t } );
	}, title );
}

module.exports = {
	getEditorCanvas,
	createBlankForm,
	publishFormAndGetURL,
	addFieldBlock,
	selectBlock,
	openBlockSettingsTab,
	enableRequiredField,
	openFormSettingsDialog,
	createWPPage,
	setFormTitle,
};
