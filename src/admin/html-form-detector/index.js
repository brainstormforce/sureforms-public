/**
 * SureForms — HTML Form Detector.
 *
 * Walks the block list in the active editor, finds `core/html` blocks that
 * contain a `<form>` element, and surfaces a "Convert to SureForms" notice
 * per detected block. Clicking the notice action posts the parsed schema
 * (or the raw HTML when local parsing was low-confidence) to the
 * `sureforms/v1/convert-html-form` REST endpoint, which creates the
 * SureForms form and returns a shortcode. The source `core/html` block is
 * then replaced with a `core/shortcode` block holding `[sureforms id="X"]`.
 *
 * Why per-block notices instead of one global banner: a page can hold more
 * than one raw HTML form (e.g. a contact form and a newsletter signup), and
 * each needs its own conversion target so the user knows which block will
 * change when they click.
 */

import apiFetch from '@wordpress/api-fetch';
import { createBlock } from '@wordpress/blocks';
import domReady from '@wordpress/dom-ready';
import { dispatch, select, subscribe } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';

import { parseFormHtml } from './parse.js';

const CORE_BLOCK_EDITOR = 'core/block-editor';
const CORE_NOTICES = 'core/notices';
const HTML_BLOCK_NAME = 'core/html';
const SHORTCODE_BLOCK_NAME = 'core/shortcode';
const NOTICE_ID_PREFIX = 'srfm-html-form-detector:';
const CONVERT_REST_PATH = '/sureforms/v1/convert-html-form';

/**
 * Set of `clientId`s currently mid-conversion. Used to debounce double
 * clicks on the Convert button — `core/notices` actions do not toggle a
 * loading state for us, so without this guard a slow network round-trip
 * could create two duplicate SureForms forms.
 *
 * @type {Set<string>}
 */
const inFlightConversions = new Set();

/**
 * Map of `clientId` → last `content` we parsed for that block. Lets us skip
 * the DOMParser pass when the block content has not changed across ticks
 * (which is the overwhelmingly common case under `subscribe`).
 *
 * @type {Map<string, string>}
 */
const lastContentByClientId = new Map();

/**
 * Set of `clientId`s that currently have a visible notice. Used so we can
 * tear down notices for blocks that have been removed or had their `<form>`
 * deleted, without iterating the entire core/notices store.
 *
 * @type {Set<string>}
 */
const activeNoticeClientIds = new Set();

function noticeId( clientId ) {
	return NOTICE_ID_PREFIX + clientId;
}

/**
 * Recursively walk the block tree collecting every `core/html` block. We
 * cannot use `getBlocksByName` for this without pulling in `@wordpress/blocks`
 * just to make the call, and the tree walk is cheap enough that doing it
 * inline keeps the bundle small.
 *
 * @param {Array} blocks Top-level block list from `getBlocks()`.
 * @param {Array} acc    Accumulator (defaults to a new array).
 * @return {Array<{clientId: string, attributes: object}>} Collected blocks.
 */
function collectHtmlBlocks( blocks, acc = [] ) {
	for ( const block of blocks ) {
		if ( ! block ) {
			continue;
		}
		if ( block.name === HTML_BLOCK_NAME ) {
			acc.push( block );
		}
		if ( block.innerBlocks?.length ) {
			collectHtmlBlocks( block.innerBlocks, acc );
		}
	}
	return acc;
}

/**
 * Build a sensible default title for the converted form. Falls back to the
 * current post title when the parser cannot surface anything better — we
 * deliberately avoid scraping the page's `<h1>` because that is often the
 * page's hero copy, not the form's purpose.
 *
 * @return {string} Form title for the created `sureforms_form` CPT.
 */
function deriveFormTitle() {
	const postTitle =
		select( 'core/editor' )?.getEditedPostAttribute?.( 'title' ) ?? '';
	const trimmed = typeof postTitle === 'string' ? postTitle.trim() : '';
	if ( trimmed ) {
		return sprintf(
			// translators: %s = the page/post title where the form was detected.
			__( '%s — Converted form', 'sureforms' ),
			trimmed
		);
	}
	return __( 'Converted form', 'sureforms' );
}

/**
 * POST the parsed schema (and raw HTML for the AI fallback path) to the
 * conversion endpoint, swap the source `core/html` block for a
 * `core/shortcode` block, and surface a success/error snackbar.
 *
 * Failure handling: we leave the original `core/html` block intact on any
 * error so the user can either retry or build the form manually — losing
 * the source markup just because the API hiccupped would be a bad trade.
 *
 * @param {string} clientId Block clientId being converted.
 * @param {string} content  Raw HTML content of the block.
 * @param {Object} parsed   Result of `parseFormHtml( content )`.
 */
async function convertBlock( clientId, content, parsed ) {
	if ( inFlightConversions.has( clientId ) ) {
		return;
	}
	inFlightConversions.add( clientId );

	const noticesDispatch = dispatch( CORE_NOTICES );
	const blockEditorDispatch = dispatch( CORE_BLOCK_EDITOR );

	try {
		const restNonce = window.srfm_html_form_detector?.rest_nonce;

		const response = await apiFetch( {
			path: CONVERT_REST_PATH,
			method: 'POST',
			headers: restNonce ? { 'X-WP-Nonce': restNonce } : {},
			data: {
				parsed_fields: parsed.fields,
				submit_text: parsed.submitText,
				styling: parsed.styling || {},
				confidence: parsed.confidence,
				html: content,
				form_title: deriveFormTitle(),
			},
		} );

		const formId = Number( response?.form_id ?? 0 );
		const shortcode = typeof response?.shortcode === 'string' ? response.shortcode : '';

		if ( ! formId || ! shortcode ) {
			throw new Error( 'Conversion endpoint returned an unexpected payload.' );
		}

		blockEditorDispatch.replaceBlock(
			clientId,
			createBlock( SHORTCODE_BLOCK_NAME, { text: shortcode } )
		);

		// Block is gone — drop its notice + cached content so the reconciler
		// does not try to remove it again on the next tick.
		activeNoticeClientIds.delete( clientId );
		lastContentByClientId.delete( clientId );
		noticesDispatch.removeNotice( noticeId( clientId ) );

		noticesDispatch.createSuccessNotice(
			response?.used_ai
				? __( 'Form converted to SureForms using AI. Review the new form for any tweaks.', 'sureforms' )
				: __( 'Form converted to SureForms.', 'sureforms' ),
			{ type: 'snackbar' }
		);
	} catch ( error ) {
		const message =
			error?.message ||
			__( 'Could not convert this form to SureForms. Please try again.', 'sureforms' );
		noticesDispatch.createErrorNotice( message, { type: 'snackbar' } );
	} finally {
		inFlightConversions.delete( clientId );
	}
}

/**
 * Show the conversion notice for a single detected HTML-form block.
 *
 * @param {string} clientId Block clientId.
 * @param {string} content  Raw HTML content of the block.
 * @param {Object} parsed   Result of `parseFormHtml( content )`.
 */
function showNoticeFor( clientId, content, parsed ) {
	// Avoid spamming an identical notice on every keystroke — the notices
	// store de-dupes by id, but creating a notice still triggers store
	// dispatches and any subscriber side effects.
	if ( activeNoticeClientIds.has( clientId ) ) {
		return;
	}

	const id = noticeId( clientId );

	const message =
		parsed.confidence === 'low'
			? __( 'SureForms detected a raw HTML form. Convert it to a SureForms form for entries, validation, and notifications.', 'sureforms' )
			: __( 'SureForms detected a raw HTML form. Convert it in one click — fields and styling will be preserved.', 'sureforms' );

	dispatch( CORE_NOTICES ).createInfoNotice( message, {
		id,
		isDismissible: true,
		actions: [
			{
				label: __( 'Convert to SureForms', 'sureforms' ),
				variant: 'primary',
				onClick: () => {
					convertBlock( clientId, content, parsed );
				},
			},
		],
	} );

	activeNoticeClientIds.add( clientId );
}

function hideNoticeFor( clientId ) {
	if ( ! activeNoticeClientIds.has( clientId ) ) {
		return;
	}
	dispatch( CORE_NOTICES ).removeNotice( noticeId( clientId ) );
	activeNoticeClientIds.delete( clientId );
	lastContentByClientId.delete( clientId );
}

/**
 * Reconcile detector state against the live block list.
 *
 * - For each `core/html` block whose content has changed since last tick,
 *   re-run the parser; show/hide its notice based on whether a form is now
 *   present.
 * - For any `clientId` we previously decorated but is no longer in the
 *   block list (block deleted, post switched), drop the notice.
 */
function reconcile() {
	const blockEditor = select( CORE_BLOCK_EDITOR );
	if ( ! blockEditor ) {
		return;
	}

	const htmlBlocks = collectHtmlBlocks( blockEditor.getBlocks() );
	const liveIds = new Set( htmlBlocks.map( ( b ) => b.clientId ) );

	for ( const block of htmlBlocks ) {
		const content = block.attributes?.content ?? '';
		const previous = lastContentByClientId.get( block.clientId );
		if ( previous === content ) {
			continue;
		}
		lastContentByClientId.set( block.clientId, content );

		const parsed = parseFormHtml( content );
		if ( parsed && parsed.fields.length > 0 ) {
			// Force a refresh when content changed: the notice itself does
			// not display field data, so we only need to re-create it if it
			// was missing — but a stale notice for a now-different form
			// would be confusing. Cheapest correct path: drop and recreate.
			if ( activeNoticeClientIds.has( block.clientId ) ) {
				dispatch( CORE_NOTICES ).removeNotice( noticeId( block.clientId ) );
				activeNoticeClientIds.delete( block.clientId );
			}
			showNoticeFor( block.clientId, content, parsed );
		} else {
			hideNoticeFor( block.clientId );
		}
	}

	// Clean up notices for blocks that no longer exist (deleted, post change).
	for ( const clientId of Array.from( activeNoticeClientIds ) ) {
		if ( ! liveIds.has( clientId ) ) {
			hideNoticeFor( clientId );
		}
	}
}

/**
 * Subscribe to editor state. The subscriber fires on every dispatch — we
 * fast-path on the block-list reference (memoized by core/block-editor),
 * so the recursive walk only runs when the tree actually changes.
 */
function watch() {
	let lastBlocks = select( CORE_BLOCK_EDITOR )?.getBlocks();
	reconcile();

	subscribe( () => {
		const blocks = select( CORE_BLOCK_EDITOR )?.getBlocks();
		if ( blocks === lastBlocks ) {
			return;
		}
		lastBlocks = blocks;
		reconcile();
	} );
}

domReady( () => {
	watch();
} );
