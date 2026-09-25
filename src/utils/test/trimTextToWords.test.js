/* eslint-env jest */
/**
 * trimTextToWords() is the shared label formatter for the form editor's smart
 * tag list. pushSmartTagToArray() hands it block.attributes.label for every
 * block that carries a slug, and not every such block declares a label:
 * srfm/register and srfm/login (Pro) have a slug attribute and no label one.
 *
 * A label-less block therefore passed undefined into text.split(), which threw
 * inside the editor's render pass and took the whole React tree down with
 * "The editor has encountered an unexpected error." -- the form could still be
 * viewed and submitted on the front end, but never opened again in the editor.
 *
 * Pinned because the guard lives one level above its reason: nothing in this
 * function names the blocks that depend on it.
 */
// Helpers.js pulls in the editor-only @wordpress packages, which are webpack
// externals (wp.* globals) rather than installed modules, so jest cannot
// resolve them. Nothing below touches them -- stub them so the module loads.
jest.mock( '@wordpress/editor', () => ( { store: {} } ), { virtual: true } );
jest.mock( '@wordpress/block-editor', () => ( { store: {} } ), {
	virtual: true,
} );
jest.mock( '@wordpress/blocks', () => ( { store: {} } ), { virtual: true } );
// force-ui ships an ESM-only transitive dependency that jest will not parse.
jest.mock( '@bsf/force-ui', () => ( { toast: {} } ) );
jest.mock(
	'@wordpress/data',
	() => ( { select: () => ( {} ), useSelect: () => ( {} ) } ),
	{ virtual: true }
);

import { trimTextToWords } from '../Helpers';

describe( 'trimTextToWords', () => {
	it.each( [ [ undefined ], [ null ] ] )(
		'returns an empty string for %p instead of throwing',
		( value ) => {
			expect( () => trimTextToWords( value, 5 ) ).not.toThrow();
			expect( trimTextToWords( value, 5 ) ).toBe( '' );
		}
	);

	it( 'returns text unchanged when it is within the word limit', () => {
		expect( trimTextToWords( 'First Name', 5 ) ).toBe( 'First Name' );
	} );

	it( 'trims to the word limit and appends the ending', () => {
		expect( trimTextToWords( 'one two three four five six', 5 ) ).toBe(
			'one two three four five...'
		);
	} );
} );
