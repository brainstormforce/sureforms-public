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
import { Fragment, useMemo, useState } from '@wordpress/element';
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
		const shortcode =
			typeof response?.shortcode === 'string' ? response.shortcode : '';

		if ( ! formId || ! shortcode ) {
			throw new Error( 'Conversion endpoint returned an unexpected payload.' );
		}

		blockEditorDispatch.replaceBlock(
			clientId,
			createBlock( SHORTCODE_BLOCK_NAME, { text: shortcode } )
		);

		noticesDispatch.createSuccessNotice(
			response?.used_ai
				? __( 'Form converted to SureForms using AI. Review the new form for any tweaks.', 'sureforms' )
				: __( 'Form converted to SureForms.', 'sureforms' ),
			{ type: 'snackbar' }
		);
		return true;
	} catch ( error ) {
		const message =
			error?.message ||
			__( 'Could not convert this form to SureForms. Please try again.', 'sureforms' );
		noticesDispatch.createErrorNotice( message, { type: 'snackbar' } );
		return false;
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
		const parsed = useMemo( () => parseFormHtml( content ), [ content ] );
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

		return (
			<Fragment>
				<BlockControls group="other">
					<ToolbarGroup>
						<ToolbarButton
							icon={ <SureFormsIcon /> }
							label={ __( 'Convert to SureForms', 'sureforms' ) }
							onClick={ handleConvert }
							disabled={ isConverting }
							isBusy={ isConverting }
						>
							{ isConverting
								? __( 'Converting…', 'sureforms' )
								: __( 'Convert to SureForms', 'sureforms' ) }
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
