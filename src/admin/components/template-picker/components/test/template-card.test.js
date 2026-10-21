/* eslint-env jest */
jest.mock( 'lucide-react', () => new Proxy( {}, { get: () => () => null } ) );
jest.mock( '@bsf/force-ui', () => ( {} ), { virtual: true } );
jest.mock( '@Utils/Helpers', () => ( { cn: ( ...classes ) => classes.join( ' ' ) } ), {
	virtual: true,
} );
jest.mock( '@Utils/form-templates', () => ( { trackTemplateAction: () => {} } ), {
	virtual: true,
} );

import { createElement, createRoot } from '@wordpress/element';
// React's own act(): react-dom/test-utils' is deprecated in React 18.3.
// eslint-disable-next-line import/no-extraneous-dependencies
import { act } from 'react';
import {
	CardPicture,
	getMiniRows,
	getPreviewLabel,
	getUnlockLabel,
} from '../TemplateCard';

const field = ( l, w = 'full' ) => ( { t: 'input', l, p: '', o: [], w } );

describe( 'getMiniRows', () => {
	it( 'pairs two half-width fields in a row', () => {
		const rows = getMiniRows( [
			field( 'First', 'half' ),
			field( 'Last', 'half' ),
			field( 'Message' ),
		] );
		expect( rows.map( ( row ) => row.map( ( f ) => f.l ) ) ).toEqual( [
			[ 'First', 'Last' ],
			[ 'Message' ],
		] );
	} );

	it( 'keeps a half-width field alone when the next one is full width', () => {
		const rows = getMiniRows( [
			field( 'Name', 'half' ),
			field( 'Message' ),
			field( 'Phone', 'half' ),
		] );
		expect( rows.map( ( row ) => row.length ) ).toEqual( [ 1, 1, 1 ] );
	} );

	it( 'draws at most four rows', () => {
		const rows = getMiniRows(
			Array.from( { length: 8 }, ( _, i ) => field( `F${ i }` ) )
		);
		expect( rows ).toHaveLength( 4 );
		expect( getMiniRows( [] ) ).toEqual( [] );
	} );
} );

global.IS_REACT_ACT_ENVIRONMENT = true;
// The plugin's build provides React to JSX; the tests do it here.
global.React = { createElement };

const renderToStaticMarkup = ( element ) => {
	const container = document.createElement( 'div' );
	const root = createRoot( container );
	act( () => root.render( element ) );
	const html = container.innerHTML;
	act( () => root.unmount() );
	return html;
};

describe( 'CardPicture', () => {
	const look = { primary: '#2563EB', onPrimary: '#FFFFFF' };

	it( 'shows the screenshot, lazily and as decoration', () => {
		const html = renderToStaticMarkup(
			createElement( CardPicture, {
				template: {
					title: 'Contact Us',
					thumbnail: 'https://example.com/contact-us.webp',
					card_fields: [],
				},
				look,
			} )
		);
		expect( html ).toContain( 'src="https://example.com/contact-us.webp"' );
		expect( html ).toContain( 'alt=""' );
		expect( html ).toContain( 'loading="lazy"' );
		// Fitted to the width from the top, never zoomed to fill the tile.
		expect( html ).toContain( 'top-0 w-full h-auto' );
		expect( html ).not.toContain( 'object-cover' );
	} );

	it( 'draws the form when the template has no screenshot', () => {
		const html = renderToStaticMarkup(
			createElement( CardPicture, {
				template: {
					title: 'Added',
					thumbnail: '',
					card_fields: [ field( 'Your name' ) ],
				},
				look,
			} )
		);
		expect( html ).not.toContain( '<img' );
		expect( html ).toContain( 'Your name' );
		expect( html ).toContain( 'aria-hidden="true"' );
	} );
} );

describe( 'getPreviewLabel', () => {
	it( 'names Premium templates as Premium', () => {
		expect(
			getPreviewLabel( { title: 'Quote', requires: 'business' } )
		).toBe( 'Preview the Quote template (Premium)' );
		expect( getPreviewLabel( { title: 'Contact', requires: 'free' } ) ).toBe(
			'Preview the Contact template'
		);
	} );
} );

describe( 'getUnlockLabel', () => {
	it( 'says where a locked card leads', () => {
		expect(
			getUnlockLabel( { title: 'Quote', lock_reason: 'tier' } )
		).toBe( 'See plans for the Quote template (Premium, opens in a new tab)' );
		expect(
			getUnlockLabel( { title: 'Quote', lock_reason: 'update' } )
		).toBe(
			'Update SureForms Pro to use the Quote template (opens in a new tab)'
		);
	} );
} );
