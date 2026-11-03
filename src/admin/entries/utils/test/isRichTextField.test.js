/* eslint-env jest */
/**
 * Which entry values are rendered as HTML, and which are escaped as text.
 *
 * This is a data-integrity test as much as a security one. The previous
 * discriminator content-sniffed the value with `/<[^>]+>/g`, so any plain-text
 * answer containing `<` followed by a name character and a later `>` was routed
 * into the HTML branch — where DOMPurify quite correctly removed it, silently
 * blanking real submitted data in the screen site owners use to read leads.
 */

import { createElement } from '@wordpress/element';
import { isRichTextField, withBlockName } from '../sanitizeEntryValue';

// A textarea the server reports as using the rich-text editor.
const richText = ( value ) => ( {
	block_name: 'srfm-textarea',
	is_rich_text: true,
	value,
} );

// A textarea without the rich-text editor: its answer is plain text.
const plainTextarea = ( value ) => ( {
	block_name: 'srfm-textarea',
	is_rich_text: false,
	value,
} );

describe( 'isRichTextField', () => {
	it( 'selects the HTML branch only for the rich-text textarea block', () => {
		expect( isRichTextField( richText( '<p>hi</p>' ) ) ).toBe( true );
		expect(
			isRichTextField( {
				block_name: 'srfm-input',
				value: '<p>hi</p>',
			} )
		).toBe( false );
	} );

	it( 'keeps a plain textarea on the text path whatever it contains', () => {
		// BUG-03: a visitor typing markup into a plain textarea saw it rendered.
		for ( const value of [
			'<i>italic</i>',
			'<img src="x"/>',
			'<p>para</p>',
			'<widget>',
		] ) {
			expect( isRichTextField( plainTextarea( value ) ) ).toBe( false );
		}

		// A textarea with no flag at all (an older response) is text too.
		expect(
			isRichTextField( { block_name: 'srfm-textarea', value: '<i>x</i>' } )
		).toBe( false );
	} );

	it( 'keeps plain-text answers that merely look like markup on the text path', () => {
		// Each of these was destroyed by the old content-sniffing regex:
		// `<not a tag>` rendered as an empty cell, `if a<b then c>d` as `if a`+`d`.
		for ( const value of [
			'<not a tag>',
			'if a<b then c>d',
			'x<y>z',
			'5 < 10 and 3 > 1',
			'I paid <$50 for it>',
		] ) {
			expect(
				isRichTextField( { block_name: 'srfm-input', value } )
			).toBe( false );
		}
	} );

	it( 'documents the limit of the tag heuristic on the markup blocks', () => {
		// Spaced comparisons and plain text are rejected.
		expect( isRichTextField( richText( '5 < 10 and 3 > 1' ) ) ).toBe(
			false
		);
		expect( isRichTextField( richText( 'plain answer' ) ) ).toBe( false );

		// But the heuristic is NOT a parser: an unspaced comparison typed into a
		// rich-text field still looks like a tag and still gets mangled by the
		// sanitizer. Telling these apart requires actually parsing HTML, so this
		// is pinned as known behaviour rather than claimed as fixed. `block_name`
		// is what keeps every OTHER field type safe from this.
		expect( isRichTextField( richText( 'if a<b then c>d' ) ) ).toBe( true );
		expect( isRichTextField( richText( 'x<y>z' ) ) ).toBe( true );
	} );

	it( 'recognises the markup the rich-text editor actually emits', () => {
		for ( const value of [
			'<p>para</p>',
			'<strong>bold</strong>',
			'<ul><li>item</li></ul>',
			'<span style="color: rgb(230, 0, 0);">red</span>',
			'<br>',
			'</p>',
		] ) {
			expect( isRichTextField( richText( value ) ) ).toBe( true );
		}
	} );

	it( 'is safe for non-string and missing values', () => {
		expect( isRichTextField( richText( null ) ) ).toBe( false );
		expect( isRichTextField( richText( 42 ) ) ).toBe( false );
		expect( isRichTextField( richText( [ 'a' ] ) ) ).toBe( false );
		expect( isRichTextField( undefined ) ).toBe( false );
		expect( isRichTextField( {} ) ).toBe( false );
	} );

	it( 'requires block_name, which Pro must not drop', () => {
		// sureforms-pro's render-pro-fields formatter returns a bare
		// `{ label, value }`; withBlockName() grafts block_name back on. Without
		// that graft this returns false and Pro sites show raw tags.
		expect( isRichTextField( { value: '<p>hi</p>' } ) ).toBe( false );
	} );
} );

/**
 * The graft across the Pro filter boundary.
 *
 * This is the single line keeping every Pro site's rich text rendering, so each
 * shape sureforms-pro's formatFields() can return is pinned here. Shapes taken
 * from sureforms-pro/src/admin/entries/components/EntryDataSection.js.
 */
describe( 'withBlockName', () => {
	const field = {
		block_name: 'srfm-textarea',
		is_rich_text: true,
		value: '<p>hi</p>',
	};

	it( 'restores block_name on the default object shape', () => {
		// Pro's `default:` branch — the load-bearing case.
		const out = withBlockName( field, {
			label: 'Message',
			value: '<p>hi</p>',
		} );

		expect( out ).toEqual( {
			block_name: 'srfm-textarea',
			label: 'Message',
			value: '<p>hi</p>',
			is_rich_text: true,
		} );
		expect( isRichTextField( out ) ).toBe( true );
	} );

	it( 'takes is_rich_text from the server field only', () => {
		// A formatter cannot turn a plain textarea's value into HTML.
		const out = withBlockName( plainTextarea( '<i>x</i>' ), {
			value: '<i>x</i>',
			is_rich_text: true,
		} );

		expect( out.is_rich_text ).toBe( false );
		expect( isRichTextField( out ) ).toBe( false );
	} );

	it( 'never widens a block_name Pro set itself', () => {
		// `formatted` wins on conflict, so the graft can only ever fill a gap.
		expect(
			withBlockName( { block_name: 'srfm-textarea' }, {
				block_name: 'srfm-input',
				value: '<p>x</p>',
			} ).block_name
		).toBe( 'srfm-input' );
	} );

	it( 'passes repeater arrays through untouched', () => {
		// Pro's `srfm-repeater` branch returns an array of rows. Rows are separate
		// fields and deliberately do NOT inherit the parent's block_name.
		const rows = [ { label: 'Repeater 1', value: [ { label: 'a', value: 'b' } ] } ];

		expect( withBlockName( field, rows ) ).toBe( rows );
	} );

	it( 'passes React elements through untouched', () => {
		// Pro's `srfm-signature` branch wraps a JSX element as the value; the
		// upload branch returns an array of them.
		const element = createElement( 'div', null, 'signature' );
		const wrapped = withBlockName( field, {
			label: 'Signature',
			value: element,
		} );

		expect( wrapped.value ).toBe( element );
		expect( isRichTextField( wrapped ) ).toBe( false );

		expect( withBlockName( field, element ) ).toBe( element );
	} );

	it( 'passes null through untouched', () => {
		// Pro's `srfm-password` / `srfm-button` branches return null, which the
		// component filters out.
		expect( withBlockName( field, null ) ).toBeNull();
		expect( withBlockName( field, undefined ) ).toBeUndefined();
	} );

	it( 'passes primitives through untouched', () => {
		expect( withBlockName( field, 'plain' ) ).toBe( 'plain' );
		expect( withBlockName( field, 0 ) ).toBe( 0 );
	} );

	it( 'tolerates a missing source field', () => {
		expect( withBlockName( undefined, { value: 'x' } ) ).toEqual( {
			block_name: undefined,
			value: 'x',
			is_rich_text: false,
		} );
	} );
} );
