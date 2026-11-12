/* eslint-env jest */
/**
 * Elementor Pro builds every opened popup from an outerHTML copy of markup that
 * the field scripts already initialised on DOMContentLoaded. The copy keeps the
 * attributes and DOM those scripts added but none of their listeners or library
 * instances, so the fields must bind to the copy when the popup is shown, and
 * binding the same form again must not add a second set of handlers.
 */

const mask = jest.fn();
global.Inputmask = jest.fn( () => ( { mask } ) );

// Like Quill's snow theme: a toolbar goes in front of the target and the editor
// goes inside it, after whatever the target already holds.
const quillTargets = [];
global.Quill = class {
	constructor( selector ) {
		const target = document.querySelector( selector );
		quillTargets.push( target );
		target.insertAdjacentHTML( 'beforebegin', '<div class="ql-toolbar"></div>' );
		target.classList.add( 'ql-container' );
		target.insertAdjacentHTML( 'beforeend', '<div class="ql-editor"></div>' );
		this.clipboard = { dangerouslyPasteHTML: () => {} };
		this.root = target.lastChild;
	}
	static import() {
		return {};
	}
	static register() {}
	blur() {}
	on() {}
	getLength() {
		return 1;
	}
};

require( '../frontend.js' );
require( '../blocks/multi-choice.js' );
require( '../blocks/input.js' );
require( '../blocks/textarea.js' );
require( '../form-submit.js' );

const FORM = `
	<div class="srfm-form-container">
	<form class="srfm-form" form-id="1">
		<div class="srfm-multi-choice-block srfm-radio-mode">
			<input class="srfm-input-multi-choice-hidden" name="choice" type="hidden" value="" />
			<div class="srfm-multi-choice-single">
				<input class="srfm-input-multi-choice-single" type="radio" name="choice-radio" />
				<label data-option-text="Red">Red</label>
			</div>
		</div>
		<div class="srfm-block srfm-input-block">
			<input class="srfm-input-input" data-srfm-mask="(###) ###-####" />
		</div>
		<div class="srfm-block srfm-textarea-block">
			<textarea id="rich" data-is-richtext="true"></textarea>
			<div class="quill-editor-container">
				<div id="quill-rich"></div>
			</div>
		</div>
	</form>
	</div>`;

// Mimics elements-handlers.js: initialise the page, take outerHTML of the popup
// document, drop the original and insert a fresh copy when the popup opens.
const openPopup = () => {
	document.body.innerHTML = `<div class="popup">${ FORM }</div>`;
	document.dispatchEvent( new Event( 'DOMContentLoaded' ) );
	const html = document.querySelector( '.popup' ).outerHTML;
	document.body.innerHTML = '';

	const modal = document.createElement( 'div' );
	modal.innerHTML = html;
	document.body.appendChild( modal );
	window.dispatchEvent(
		new CustomEvent( 'elementor/popup/show', {
			detail: { instance: { $element: [ modal.firstElementChild ] } },
		} )
	);
	return modal.querySelector( '.srfm-form' );
};

const showAgain = ( form ) =>
	window.dispatchEvent(
		new CustomEvent( 'elementor/popup/show', {
			detail: { instance: { $element: [ form.closest( '.popup' ) ] } },
		} )
	);

beforeEach( () => {
	mask.mockClear();
	global.Inputmask.mockClear();
	quillTargets.length = 0;
} );

describe( 'fields in an Elementor Pro popup', () => {
	it( 'writes a picked choice to the hidden input of the copied form', () => {
		const form = openPopup();
		const hidden = form.querySelector( '.srfm-input-multi-choice-hidden' );
		const onChange = jest.fn();
		hidden.addEventListener( 'change', onChange );

		form.querySelector( '.srfm-input-multi-choice-single' ).click();

		expect( hidden.getAttribute( 'value' ) ).toBe( 'Red' );
		expect( onChange ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'binds a choice once when the same popup form is initialised again', () => {
		const form = openPopup();
		showAgain( form );
		const hidden = form.querySelector( '.srfm-input-multi-choice-hidden' );
		const onChange = jest.fn();
		hidden.addEventListener( 'change', onChange );

		// A second click handler would read the first one's value as the current
		// selection and deselect it.
		form.querySelector( '.srfm-input-multi-choice-single' ).click();

		expect( hidden.getAttribute( 'value' ) ).toBe( 'Red' );
		expect( onChange ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'masks the copied input once', () => {
		const form = openPopup();
		showAgain( form );

		const copied = form.querySelector( '.srfm-input-input' );
		expect(
			mask.mock.calls.filter( ( [ el ] ) => el === copied )
		).toHaveLength( 1 );
	} );

	it( 'rebuilds the rich text editor instead of keeping the dead copy', () => {
		const form = openPopup();
		showAgain( form );

		const target = form.querySelector( '#quill-rich' );
		expect( quillTargets.filter( ( el ) => el === target ) ).toHaveLength(
			1
		);
		// Without a reset the copied toolbar and editor sit beside the new ones.
		expect(
			target.parentElement.querySelectorAll( '.ql-toolbar' )
		).toHaveLength( 1 );
		expect( target.querySelectorAll( '.ql-editor' ) ).toHaveLength( 1 );
	} );
} );
