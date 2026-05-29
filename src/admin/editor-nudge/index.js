/**
 * SureForms — Editor Nudge.
 *
 * Shows a dismissible notice in the block editor when the user is editing
 * a post/page whose title hints at a form ("contact", "form", "forms")
 * AND the post does not already contain a `srfm/form` block.
 *
 * Uses the native Gutenberg notice API (core/notices) so we do not have
 * to touch the editor iframe DOM. Dismissal is persisted server-side via
 * AJAX so the banner does not reappear across sessions. Inserting a
 * `srfm/form` block auto-hides the notice without persisting dismissal.
 */

import domReady from '@wordpress/dom-ready';
import { dispatch, select, subscribe } from '@wordpress/data';

const NOTICE_ID = 'srfm-editor-nudge';
const NOTICE_CLASS = 'srfm-editor-nudge';
const CTA_CLASS = 'srfm-editor-nudge__cta';
const ICON_CLASS = 'srfm-editor-nudge__icon';
const TITLE_KEYWORDS = /\b(contact|forms?)\b/i;
const FORM_BLOCK_NAME = 'srfm/form';
const SUREFORMS_CPT = 'sureforms_form';
const DECORATE_INTERVAL_MS = 100;
const DECORATE_MAX_ATTEMPTS = 30;

const CORE_EDITOR = 'core/editor';
const CORE_EDIT_SITE = 'core/edit-site';
const CORE_BLOCK_EDITOR = 'core/block-editor';
const CORE_NOTICES = 'core/notices';
const CORE = 'core';

let isNoticeVisible = false;
let hasDismissed = false;
let isProgrammaticRemoval = false;
let noticeWatcher = null;
let decorateInterval = null;

/**
 * Normalize a title value to a plain string.
 *
 * The REST entity record can expose `title` as `{ raw, rendered }` rather
 * than a string in some Gutenberg/WP versions, which would otherwise slip
 * past `isTitleMatch` silently.
 *
 * @param {*} value Raw title value pulled from a store.
 * @return {string} Normalized title string.
 */
function normalizeTitle( value ) {
	if ( typeof value === 'string' ) {
		return value;
	}
	if ( value && typeof value === 'object' ) {
		return value.raw ?? value.rendered ?? '';
	}
	return '';
}

/**
 * Read the post title from whichever editor store is active.
 *
 * @return {string} Current post title, empty string when unavailable.
 */
function getEditorTitle() {
	if ( select( CORE_EDIT_SITE ) && typeof select( CORE_EDIT_SITE ).getPage === 'function' ) {
		const context = select( CORE_EDIT_SITE ).getPage()?.context;
		if ( context?.postType && context?.postId ) {
			const record = select( CORE ).getEditedEntityRecord(
				'postType',
				context.postType,
				context.postId
			);
			return normalizeTitle( record?.title );
		}
	}

	return normalizeTitle( select( CORE_EDITOR )?.getEditedPostAttribute( 'title' ) );
}

/**
 * Read the current post type — used to skip the SureForms form editor.
 *
 * @return {string} Current post type slug, empty string when unavailable.
 */
function getEditorPostType() {
	if ( select( CORE_EDIT_SITE ) && typeof select( CORE_EDIT_SITE ).getPage === 'function' ) {
		const context = select( CORE_EDIT_SITE ).getPage()?.context;
		if ( context?.postType ) {
			return context.postType;
		}
	}

	return select( CORE_EDITOR )?.getCurrentPostType() ?? '';
}

/**
 * Read the current post ID — used to scope dismissal persistence per-post.
 *
 * @return {number} Current post ID, or 0 when unavailable.
 */
function getEditorPostId() {
	if ( select( CORE_EDIT_SITE ) && typeof select( CORE_EDIT_SITE ).getPage === 'function' ) {
		const context = select( CORE_EDIT_SITE ).getPage()?.context;
		if ( context?.postId ) {
			return Number( context.postId ) || 0;
		}
	}

	return Number( select( CORE_EDITOR )?.getCurrentPostId() ?? 0 ) || 0;
}

/**
 * Read the top-level block list from the block-editor store. Returns the
 * memoized array reference so callers can reference-compare across ticks
 * to skip a redundant tree walk when the list has not changed.
 *
 * @return {Array} Top-level blocks, empty array when the store is missing.
 */
function getEditorBlocks() {
	return select( CORE_BLOCK_EDITOR )?.getBlocks() ?? [];
}

/**
 * Recursively check whether a `srfm/form` block is present anywhere in
 * the given block list — including nested inside group/columns blocks.
 *
 * @param {Array} blocks Block list from `getBlocks()`.
 * @return {boolean} True when a `srfm/form` block is found.
 */
function walkForSrfmFormBlock( blocks ) {
	for ( const block of blocks ) {
		if ( block?.name === FORM_BLOCK_NAME ) {
			return true;
		}
		if ( block?.innerBlocks?.length && walkForSrfmFormBlock( block.innerBlocks ) ) {
			return true;
		}
	}
	return false;
}

function isTitleMatch( title ) {
	return typeof title === 'string' && TITLE_KEYWORDS.test( title );
}

/**
 * Mark the nudge as dismissed in-memory and tear down the user-dismissal
 * watcher in the same step. Centralizing both side effects makes it
 * impossible for a future caller to set `hasDismissed = true` while
 * leaving the watcher subscribed indefinitely.
 */
function markDismissed() {
	hasDismissed = true;
	if ( noticeWatcher ) {
		noticeWatcher();
		noticeWatcher = null;
	}
}

/**
 * Persist the dismissed state for the current post.
 *
 * Resolves to true only when the server confirmed the dismissal — on
 * network failures, non-2xx responses, or `success: false` payloads we
 * resolve to false so the caller can keep the in-memory flag clear and
 * stay consistent with the server (the nudge will reappear on reload).
 *
 * Bails early when no post ID is in scope (e.g. before an auto-draft
 * has materialized) — the caller leaves the in-memory flag clear so the
 * nudge can re-evaluate once the post settles.
 *
 * @return {Promise<boolean>} True when the dismissal was persisted.
 */
function persistDismissal() {
	const settings = window.srfm_editor_nudge;
	if ( ! settings?.ajax_url ) {
		return Promise.resolve( false );
	}

	const postId = getEditorPostId();
	if ( postId <= 0 ) {
		return Promise.resolve( false );
	}

	const body = new URLSearchParams();
	body.append( 'action', 'srfm_editor_nudge_dismiss' );
	body.append( 'nonce', settings.nonce ?? '' );
	body.append( 'post_id', String( postId ) );

	return window
		.fetch( settings.ajax_url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString(),
		} )
		.then( ( response ) => {
			if ( ! response.ok ) {
				return false;
			}
			return response.json().then( ( data ) => !! data?.success );
		} )
		.catch( () => false );
}

/**
 * Show the notice and start watching specifically for the user clicking
 * its dismiss button. The watcher unsubscribes itself once the notice
 * is gone from the store.
 */
function showNotice() {
	const settings = window.srfm_editor_nudge;
	if ( ! settings?.message || isNoticeVisible ) {
		return;
	}

	dispatch( CORE_NOTICES ).createInfoNotice( settings.message, {
		id: NOTICE_ID,
		isDismissible: true,
		actions: [
			{
				label: settings.button_label,
				url: settings.create_form_url,
				variant: 'primary',
				className: CTA_CLASS,
			},
		],
	} );

	isNoticeVisible = true;
	decorateWrapper();

	if ( noticeWatcher ) {
		noticeWatcher();
		noticeWatcher = null;
	}

	noticeWatcher = subscribe( () => {
		const notices = select( CORE_NOTICES )?.getNotices() ?? [];
		const stillVisible = notices.some( ( notice ) => notice.id === NOTICE_ID );

		if ( stillVisible ) {
			return;
		}

		const wasProgrammatic = isProgrammaticRemoval;
		isProgrammaticRemoval = false;
		isNoticeVisible = false;
		stopDecorating();

		if ( noticeWatcher ) {
			noticeWatcher();
			noticeWatcher = null;
		}

		if ( ! wasProgrammatic ) {
			persistDismissal().then( ( ok ) => {
				if ( ok ) {
					markDismissed();
				}
				// On failure leave hasDismissed false: the server has
				// not stored anything, so the next reload will surface
				// the notice again — matching server state.
			} );
		}
	} );
}

/**
 * Remove the notice programmatically — e.g. after the user inserted a
 * `srfm/form` block. Flagged so the user-dismissal watcher does not
 * treat this as a user dismissal.
 */
function hideNotice() {
	if ( ! isNoticeVisible ) {
		return;
	}
	isProgrammaticRemoval = true;
	stopDecorating();
	dispatch( CORE_NOTICES ).removeNotice( NOTICE_ID );
}

/**
 * The notices store silently drops a `className` option on the wrapper,
 * so we add it ourselves after the notice renders. We locate the wrapper
 * via the CTA button's class (which IS respected) and walk up the DOM.
 */
function decorateWrapper() {
	stopDecorating();

	const tryDecorate = () => {
		const cta = document.querySelector( '.' + CTA_CLASS );
		const wrapper = cta?.closest( '.components-notice' );
		if ( ! wrapper ) {
			return false;
		}
		wrapper.classList.add( NOTICE_CLASS );
		insertIcon( wrapper );
		return true;
	};

	if ( tryDecorate() ) {
		return;
	}

	let attempts = 0;
	decorateInterval = window.setInterval( () => {
		attempts += 1;
		if ( tryDecorate() || attempts >= DECORATE_MAX_ATTEMPTS ) {
			stopDecorating();
		}
	}, DECORATE_INTERVAL_MS );
}

function stopDecorating() {
	if ( decorateInterval ) {
		window.clearInterval( decorateInterval );
		decorateInterval = null;
	}
}

/**
 * Inject the SureForms logo as the first child of the notice wrapper so
 * it renders to the left of the message text. Idempotent — only inserts
 * once per wrapper.
 *
 * @param {HTMLElement} wrapper The .components-notice DOM node.
 */
function insertIcon( wrapper ) {
	if ( wrapper.querySelector( '.' + ICON_CLASS ) ) {
		return;
	}
	const logoUrl = window.srfm_editor_nudge?.logo_url;
	if ( ! logoUrl ) {
		return;
	}
	const content = wrapper.querySelector( '.components-notice__content' );
	if ( ! content ) {
		return;
	}
	const img = document.createElement( 'img' );
	img.src = logoUrl;
	img.alt = '';
	img.className = ICON_CLASS;
	wrapper.insertBefore( img, content );
}

function evaluate( title, postType, hasForm ) {
	if ( hasDismissed ) {
		return;
	}

	const shouldShow =
		SUREFORMS_CPT !== postType &&
		isTitleMatch( title ) &&
		! hasForm;

	if ( shouldShow ) {
		showNotice();
	} else {
		hideNotice();
	}
}

/**
 * Subscribe to editor state — re-evaluates whether the notice should
 * appear whenever the title, post type, or block list changes.
 *
 * The `@wordpress/data` subscriber fires on every dispatch to any
 * registered store (keypresses, sidebar toggles, undo pushes), so we
 * fast-path on the block list reference: `getBlocks()` is memoized and
 * returns the same array reference until the tree actually changes,
 * letting us skip the recursive walk on the vast majority of ticks.
 */
function watchEditorState() {
	let lastTitle = getEditorTitle();
	let lastPostType = getEditorPostType();
	let lastBlocks = getEditorBlocks();
	let lastHasForm = walkForSrfmFormBlock( lastBlocks );

	evaluate( lastTitle, lastPostType, lastHasForm );

	subscribe( () => {
		if ( hasDismissed ) {
			return;
		}

		const title = getEditorTitle();
		const postType = getEditorPostType();
		const blocks = getEditorBlocks();

		// Reference-equality fast path — only walk the tree when the
		// block list reference has changed since the last evaluation.
		let hasForm = lastHasForm;
		if ( blocks !== lastBlocks ) {
			hasForm = walkForSrfmFormBlock( blocks );
		}

		if (
			title === lastTitle &&
			postType === lastPostType &&
			hasForm === lastHasForm
		) {
			return;
		}

		lastTitle = title;
		lastPostType = postType;
		lastBlocks = blocks;
		lastHasForm = hasForm;

		evaluate( title, postType, hasForm );
	} );
}

domReady( () => {
	watchEditorState();
} );
