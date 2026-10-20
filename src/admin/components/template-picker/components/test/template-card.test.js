/* eslint-env jest */
jest.mock( 'lucide-react', () => new Proxy( {}, { get: () => () => null } ) );
jest.mock( '@bsf/force-ui', () => ( {} ), { virtual: true } );
jest.mock( '@Utils/Helpers', () => ( { cn: ( ...classes ) => classes.join( ' ' ) } ), {
	virtual: true,
} );
jest.mock( '@Utils/form-templates', () => ( { trackTemplateAction: () => {} } ), {
	virtual: true,
} );

import { getMiniRows } from '../TemplateCard';

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
