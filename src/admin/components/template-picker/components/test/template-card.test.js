/* eslint-env jest */
jest.mock( 'lucide-react', () => new Proxy( {}, { get: () => () => null } ) );
jest.mock( '@bsf/force-ui', () => ( {} ), { virtual: true } );
jest.mock( '@Utils/Helpers', () => ( { cn: ( ...classes ) => classes.join( ' ' ) } ), {
	virtual: true,
} );
jest.mock( '@Utils/form-templates', () => ( { trackTemplateAction: () => {} } ), {
	virtual: true,
} );

import { getMiniRows, getPreviewLabel, getUnlockLabel } from '../TemplateCard';

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
