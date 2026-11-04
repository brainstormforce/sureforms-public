/* eslint-env jest */
/**
 * Renders the entry details section with the REST payload of a real entry, so
 * the whole display path is covered: REST field, formatter (free or Pro),
 * RenderField and the rich-text decision.
 */
import { createElement, renderToString } from '@wordpress/element';
import { addFilter, removeFilter } from '@wordpress/hooks';
import EntryDataSection from '../EntryDataSection';
import { decodeHTMLEntities } from '../../utils/entryHelpers';

jest.mock( '../EntryEdit', () => () => null );

// The unit-test transform compiles JSX to React.createElement.
global.React = require( '@wordpress/element' );

// GET /sureforms/v1/entry/{id}/details form_data for an entry whose plain
// Textarea held QA's BUG-03 lines and whose rich-text Textarea held markup.
const formData = [
	{
		field_name: 'srfm-textarea-qa3plain-lbl-TWVzc2FnZQ-message',
		label: 'Message',
		value: '&lt;i&gt;italic&lt;/i&gt;\r\n&lt;a href=&quot;https://example.com&quot;&gt;link&lt;/a&gt;\r\n&amp;lt;b&amp;gt;entity&amp;lt;/b&amp;gt;\r\nA &amp;amp; B\r\n2 &amp;lt; 3\r\n',
		block_name: 'srfm-textarea',
		is_rich_text: false,
	},
	{
		field_name: 'srfm-textarea-qa3rich1-lbl-Tm90ZXM-notes',
		label: 'Notes',
		value: '&lt;p&gt;&lt;strong&gt;Bold rich&lt;/strong&gt; text&lt;/p&gt;',
		block_name: 'srfm-textarea',
		is_rich_text: true,
	},
];

// Rendered markup parsed back into the DOM, one entry row per field label.
const render = () => {
	const root = document.createElement( 'div' );
	root.innerHTML = renderToString(
		createElement( EntryDataSection, { entryData: { formData } } )
	);
	const row = ( label ) =>
		Array.from( root.querySelectorAll( '.flex.gap-4' ) ).find(
			( el ) => el.firstChild?.textContent === label
		).lastChild;
	return { message: row( 'Message' ), notes: row( 'Notes' ) };
};

const expectPlainAsText = ( message ) => {
	expect( message.querySelector( 'i, a, b' ) ).toBeNull();
	// Parsing folds CRLF to LF. `&lt;b&gt;` typed as entities reads back as
	// `<b>`: the save path stores the kses-normalised value, so the display
	// decodes twice (decodeHTMLEntities). It is still text, never markup.
	expect( message.textContent ).toBe(
		'<i>italic</i>\n<a href="https://example.com">link</a>\n<b>entity</b>\nA & B\n2 < 3\n'
	);
};

describe( 'EntryDataSection plain and rich-text Textareas', () => {
	it( 'shows a plain Textarea as typed and a rich-text one as markup', () => {
		const { message, notes } = render();

		expectPlainAsText( message );
		expect( notes.querySelector( 'strong' )?.textContent ).toBe( 'Bold rich' );
	} );

	it( 'does the same through the SureForms Pro formatter', () => {
		// Pro returns a bare { label, value } with the value decoded.
		addFilter(
			'srfm-pro.entry-details.render-pro-fields',
			'test/pro-formatter',
			() => ( field ) => ( {
				label: field.label,
				value: decodeHTMLEntities( field.value ),
			} )
		);
		try {
			const { message, notes } = render();

			expectPlainAsText( message );
			expect( notes.querySelector( 'strong' )?.textContent ).toBe( 'Bold rich' );
		} finally {
			removeFilter( 'srfm-pro.entry-details.render-pro-fields', 'test/pro-formatter' );
		}
	} );
} );
