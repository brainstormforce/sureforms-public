/* eslint-env jest */
jest.mock( '@wordpress/api-fetch', () => jest.fn(), { virtual: true } );
jest.mock( 'lucide-react', () => ( { ArrowRight: () => null, X: () => null } ), {
	virtual: true,
} );

import { getTimeLeft } from '../PromoBar';

describe( 'getTimeLeft', () => {
	const end = Date.UTC( 2026, 11, 7, 8, 0, 0 );

	it( 'splits the time left into days, hours, minutes and seconds', () => {
		// 2 days, 3 hours, 4 minutes and 5 seconds, in milliseconds.
		const left = 183845000;
		expect( getTimeLeft( end, end - left ) ).toEqual( [ 2, 3, 4, 5 ] );
	} );

	it( 'rounds a part second down', () => {
		expect( getTimeLeft( end, end - 1500 ) ).toEqual( [ 0, 0, 0, 1 ] );
	} );

	it( 'never goes below zero after the end', () => {
		expect( getTimeLeft( end, end ) ).toEqual( [ 0, 0, 0, 0 ] );
		expect( getTimeLeft( end, end + 60000 ) ).toEqual( [ 0, 0, 0, 0 ] );
	} );
} );
