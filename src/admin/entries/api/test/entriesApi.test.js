/* eslint-env jest */
import apiFetch from '@wordpress/api-fetch';
import { fetchEntryDetail, fetchEntryLogs, toEntryId } from '../entriesApi';

jest.mock( '@wordpress/api-fetch', () => jest.fn( () => Promise.resolve( {} ) ) );

// Values a route param can hold that are not a plain positive integer.
const INVALID_IDS = [
	'x?rest_route=/wp/v2/users&_method=POST&roles=administrator',
	'1?rest_route=/wp/v2/users&_method=POST',
	'1&_method=DELETE',
	'1/../../wp/v2/users',
	'..',
	'1#x',
	' 1',
	'1.5',
	'1e3',
	'1'.repeat( 22 ),
	'9007199254740993',
	'0',
	'-1',
	'',
	null,
	undefined,
	NaN,
	1.5,
];

beforeAll( () => {
	global.srfm_admin = { global_settings_nonce: 'nonce' };
} );

beforeEach( () => {
	apiFetch.mockClear();
} );

describe( 'toEntryId', () => {
	it.each( [
		[ '42', 42 ],
		[ 42, 42 ],
		[ '1000', 1000 ],
		[ '9007199254740991', 9007199254740991 ],
	] )( 'accepts %p', ( input, expected ) => {
		expect( toEntryId( input ) ).toBe( expected );
	} );

	it.each( INVALID_IDS )( 'rejects %p', ( input ) => {
		expect( toEntryId( input ) ).toBeNull();
	} );
} );

describe( 'fetchEntryDetail', () => {
	it( 'requests the details route for a numeric id', async () => {
		await fetchEntryDetail( '42' );

		expect( apiFetch ).toHaveBeenCalledTimes( 1 );
		expect( apiFetch.mock.calls[ 0 ][ 0 ].path ).toBe(
			'/sureforms/v1/entry/42/details'
		);
	} );

	it.each( INVALID_IDS )(
		'sends no request for %p',
		async ( input ) => {
			await expect( fetchEntryDetail( input ) ).rejects.toThrow();
			expect( apiFetch ).not.toHaveBeenCalled();
		}
	);
} );

describe( 'fetchEntryLogs', () => {
	it( 'requests the logs route for a numeric id', async () => {
		await fetchEntryLogs( { id: '42', page: 2, per_page: 5 } );

		expect( apiFetch ).toHaveBeenCalledTimes( 1 );
		expect( apiFetch.mock.calls[ 0 ][ 0 ].path ).toBe(
			'/sureforms/v1/entry/42/logs?page=2&per_page=5'
		);
	} );

	it.each( INVALID_IDS )(
		'sends no request for %p',
		async ( input ) => {
			await expect( fetchEntryLogs( { id: input } ) ).rejects.toThrow();
			expect( apiFetch ).not.toHaveBeenCalled();
		}
	);
} );
