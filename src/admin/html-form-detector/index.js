/**
 * SureForms — HTML Form Detector.
 *
 * Adds a "Convert to SureForms" toolbar button to every `core/html`
 * block whose content contains a `<form>` element. Clicking the button
 * posts the parsed schema (or the raw HTML when local parsing was
 * low-confidence) to the `sureforms/v1/convert-html-form` REST
 * endpoint, which creates the SureForms form and returns a shortcode.
 * The source `core/html` block is then replaced with a `core/shortcode`
 * block holding `[sureforms id="X"]`.
 *
 * Why a per-block toolbar button instead of a global editor notice:
 * a page can hold any number of raw HTML forms (contact + newsletter +
 * footer subscribe, etc.). A single banner cannot disambiguate which
 * block it acts on. Mounting the affordance on the block itself —
 * exactly where the user is editing the form — also makes it
 * discoverable in context: the user does not have to scroll up to a
 * banner to invoke the action. Each `core/html` instance gets its own
 * toolbar item independently, so the N-forms case is handled by the
 * block editor's own per-block render lifecycle without any
 * reconciliation logic on our side.
 */

import apiFetch from '@wordpress/api-fetch';
import { BlockControls } from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import { ToolbarButton, ToolbarGroup } from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { dispatch, select } from '@wordpress/data';
import { Fragment, useEffect, useMemo, useState } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import { __, sprintf } from '@wordpress/i18n';

// The SureForms brand logomark — the same component registered as the
// `srfm/form` block icon (see `src/blocks/sureforms-form/index.js`).
// Reusing it keeps the toolbar affordance visually consistent with the
// block inserter entry users already associate with SureForms.
import SureFormsIcon from '@Image/Logo.js';

import { parseFormHtml } from './parse.js';

const HTML_BLOCK_NAME = 'core/html';
const SHORTCODE_BLOCK_NAME = 'core/shortcode';
const CORE_BLOCK_EDITOR = 'core/block-editor';
const CORE_NOTICES = 'core/notices';
const CONVERT_REST_PATH = '/sureforms/v1/convert-html-form';
const FILTER_NAMESPACE = 'sureforms/html-form-detector';

/**
 * Build a sensible default title for the converted form. Falls back to
 * the current post title when there is nothing better — we deliberately
 * avoid scraping the page's `<h1>` because that is often the page's
 * hero copy, not the form's purpose.
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
 * POST the parsed schema (and raw HTML for the AI fallback path) to
 * the conversion endpoint, swap the source `core/html` block for a
 * `core/shortcode` block, and surface a success/error snackbar.
 *
 * Failure-mode: we leave the original `core/html` block intact on any
 * error so the user can either retry or build the form manually —
 * losing the source markup just because the API hiccupped would be a
 * bad trade.
 *
 * @param {string} clientId Block clientId being converted.
 * @param {string} content  Raw HTML content of the block.
 * @param {Object} parsed   Result of `parseFormHtml( content )`.
 * @return {Promise<boolean>} Resolves true on a successful conversion.
 */
async function convertBlock( clientId, content, parsed ) {
	const noticesDispatch = dispatch( CORE_NOTICES );

	const restNonce = window.srfm_html_form_detector?.rest_nonce;

	// Fail fast when the localized nonce is missing — without it the
	// request would just bounce off the server's `wp_verify_nonce`
	// check and surface as a confusing 403 snackbar. A focused
	// message tells the user exactly what to do.
	if ( ! restNonce ) {
		noticesDispatch.createErrorNotice(
			__(
				'SureForms could not authenticate this request. Please reload the editor and try again.',
				'sureforms'
			),
			{ type: 'snackbar' }
		);
		return false;
	}

	const blockEditorDispatch = dispatch( CORE_BLOCK_EDITOR );

	try {
		const response = await apiFetch( {
			path: CONVERT_REST_PATH,
			method: 'POST',
			headers: { 'X-WP-Nonce': restNonce },
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
		const shortcode =
			typeof response?.shortcode === 'string' ? response.shortcode : '';

		if ( ! formId || ! shortcode ) {
			// Throw an Error rather than a plain object so the rejection
			// carries a stack trace (useful in editor console traces),
			// satisfies `eslint(no-throw-literal)`, and still surfaces
			// `code` through the catch block below alongside any other
			// REST rejection.
			const badPayload = new Error(
				'SureForms convert endpoint returned an unexpected payload.'
			);
			badPayload.code = 'srfm_html_convert_bad_payload';
			throw badPayload;
		}

		const shortcodeBlock = createBlock( SHORTCODE_BLOCK_NAME, {
			text: shortcode,
		} );

		// Use the server-computed preserved markup ONLY. The server
		// runs `wp_kses_post` on the remnant for users without the
		// `unfiltered_html` cap, so multisite site admins (who have
		// `manage_options` but not `unfiltered_html`) can't smuggle
		// `<script>` / `<iframe>` markup into the new `core/html`
		// block by hiding it behind the source `<form>`.
		//
		// No client-side fallback to `stripFormFromHtml(content)` here
		// because the client has no equivalent cap-gated sanitizer —
		// dropping back to it on a proxy-stripped response would let
		// the un-sanitized branch run in production. When the server
		// omits `preserved_html` we treat it as "nothing surrounding"
		// and fall through to the one-block swap path below; the user
		// loses the wrapper markup but never gets a stored-XSS path
		// they couldn't already reach via a `core/html` block.
		const surroundingHtml =
			typeof response?.preserved_html === 'string'
				? response.preserved_html
				: '';

		// When the source `core/html` block held nothing but the `<form>`
		// (and optional whitespace), the historical behavior is correct:
		// swap one block for one block. When the block contained other
		// markup — a wrapping `<div>`, a heading above the form, a
		// post-submit message below it, an inline `<script>` — replacing
		// the whole block would silently delete that markup. Keep it as
		// a separate `core/html` block and put the shortcode after it
		// so the user does not lose context they almost certainly meant
		// to keep alongside the form.
		if ( surroundingHtml ) {
			const preservedBlock = createBlock( HTML_BLOCK_NAME, {
				content: surroundingHtml,
			} );
			blockEditorDispatch.replaceBlock( clientId, [
				preservedBlock,
				shortcodeBlock,
			] );
		} else {
			blockEditorDispatch.replaceBlock( clientId, shortcodeBlock );
		}

		noticesDispatch.createSuccessNotice(
			response?.used_ai
				? __( 'Form converted to SureForms using AI. Review the new form for any tweaks.', 'sureforms' )
				: __( 'Form converted to SureForms.', 'sureforms' ),
			{ type: 'snackbar' }
		);
		return true;
	} catch ( error ) {
		noticesDispatch.createErrorNotice( errorMessageForCode( error?.code ), {
			type: 'snackbar',
		} );
		return false;
	}
}

/**
 * Map a known REST error code to a user-facing translated string.
 *
 * Why we whitelist by `code` rather than forwarding `error.message`
 * verbatim: `apiFetch` rejections can contain raw server messages
 * (DB error fragments, file paths from misconfigured plugins, etc.).
 * Surfacing those in a snackbar to the editor is both ugly and a
 * small info-leak vector. Everything outside the known set collapses
 * to a generic message; the precise cause is still logged server-side.
 *
 * @param {string|undefined} code REST error code returned by the endpoint.
 * @return {string} Translated message safe to display.
 */
function errorMessageForCode( code ) {
	switch ( code ) {
		case 'srfm_html_convert_nonce_failed':
		case 'rest_cookie_invalid_nonce':
			return __(
				'SureForms could not authenticate this request. Please reload the editor and try again.',
				'sureforms'
			);
		case 'srfm_html_convert_forbidden':
		case 'rest_forbidden':
			return __(
				'You do not have permission to convert this form.',
				'sureforms'
			);
		case 'srfm_html_convert_too_large':
			return __(
				'This form is too large to convert. Try simplifying the markup or building the form manually.',
				'sureforms'
			);
		case 'srfm_html_convert_no_fields':
			return __(
				'SureForms could not derive any fields from this form.',
				'sureforms'
			);
		case 'srfm_html_convert_ai_failed':
		case 'srfm_html_convert_ai_empty':
			return __(
				'The SureForms AI service could not process this form. Please try again or build the form manually.',
				'sureforms'
			);
		case 'srfm_html_convert_bad_payload':
			return __(
				'SureForms received an unexpected response. Please try again.',
				'sureforms'
			);
		default:
			return __(
				'Could not convert this form to SureForms. Please try again.',
				'sureforms'
			);
	}
}

/**
 * Higher-order component that adds a "Convert to SureForms" toolbar
 * button to every `core/html` block that contains a `<form>` element.
 *
 * The HOC is mounted once per block instance by Gutenberg, so multiple
 * HTML form blocks on the same page each render their own toolbar
 * button — no manual reconciliation is needed.
 *
 * Parsing is memoized against the block's current `content` attribute
 * so re-renders driven by unrelated state (selection changes, sidebar
 * toggles) do not re-run `DOMParser`. The parser itself short-circuits
 * cheaply for HTML blocks that hold non-form markup.
 */
const withConvertToSureForms = createHigherOrderComponent(
	( BlockEdit ) => ( props ) => {
		// We only decorate `core/html` blocks — every other block type
		// passes through untouched. Putting the type guard up here
		// avoids running the memo / parser for blocks we will never
		// add a button to.
		if ( props.name !== HTML_BLOCK_NAME ) {
			return <BlockEdit { ...props } />;
		}

		const content = props.attributes?.content ?? '';

		// Debounce the parse so typing inside the source HTML block does
		// not run `DOMParser` + the per-field walk on every keystroke.
		// Seeded with `content` so a block that already holds a form gets
		// its toolbar button on first paint with no delay.
		const [ debouncedContent, setDebouncedContent ] = useState( content );
		useEffect( () => {
			const timer = setTimeout(
				() => setDebouncedContent( content ),
				250
			);
			return () => clearTimeout( timer );
		}, [ content ] );

		// Guard the parser so malformed or hostile HTML degrades to "no
		// Convert button" instead of throwing an uncaught error that would
		// crash this block's edit render.
		const parsed = useMemo( () => {
			try {
				return parseFormHtml( debouncedContent );
			} catch ( error ) {
				return null;
			}
		}, [ debouncedContent ] );
		const [ isConverting, setIsConverting ] = useState( false );

		// Render the unmodified block when there is no form to convert,
		// so we never light up an empty toolbar group on regular HTML
		// blocks (e.g. a custom embed snippet, a Tailwind playground).
		if ( ! parsed || parsed.fields.length === 0 ) {
			return <BlockEdit { ...props } />;
		}

		const handleConvert = async () => {
			if ( isConverting ) {
				return;
			}
			setIsConverting( true );
			// `convertBlock` swaps the block on success — after which
			// React unmounts this HOC for the (gone) `core/html`
			// clientId, so clearing `isConverting` would never run.
			// That is fine; the only path where the cleanup matters is
			// the failure case, where the original block is preserved.
			const ok = await convertBlock( props.clientId, content, parsed );
			if ( ! ok ) {
				setIsConverting( false );
			}
		};

		// Drive the accessible name from the same string as the visible
		// child text so screen readers announce the state change when
		// the button flips to the busy state. `isBusy` alone is a
		// purely visual cue.
		const buttonText = isConverting
			? __( 'Converting…', 'sureforms' )
			: __( 'Convert to SureForms', 'sureforms' );

		return (
			<Fragment>
				<BlockControls group="other">
					<ToolbarGroup>
						<ToolbarButton
							icon={ <SureFormsIcon /> }
							label={ buttonText }
							onClick={ handleConvert }
							disabled={ isConverting }
							isBusy={ isConverting }
						>
							{ buttonText }
						</ToolbarButton>
					</ToolbarGroup>
				</BlockControls>
				<BlockEdit { ...props } />
			</Fragment>
		);
	},
	'withConvertToSureForms'
);

addFilter(
	'editor.BlockEdit',
	`${ FILTER_NAMESPACE }/with-convert-button`,
	withConvertToSureForms
);
