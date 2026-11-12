/* eslint-env jest */
import { indexAfterDelete, updateSelectedIndicesAfterReorder } from '../util';

describe( 'indexAfterDelete', () => {
	it( 'drops the deleted index', () => {
		expect( indexAfterDelete( 2, 2 ) ).toBeNull();
	} );

	it( 'shifts later indices down by one', () => {
		expect( indexAfterDelete( 2, 0 ) ).toBe( 1 );
	} );

	it( 'keeps earlier indices', () => {
		expect( indexAfterDelete( 1, 3 ) ).toBe( 1 );
	} );

	it( 'keeps null', () => {
		expect( indexAfterDelete( null, 0 ) ).toBeNull();
	} );
} );

describe( 'open description follows its option on reorder', () => {
	it( 'moves with the dragged option', () => {
		// C (2) dragged to the top stays open on C.
		expect( updateSelectedIndicesAfterReorder( [ 2 ], 2, 0 )[ 0 ] ).toBe(
			0
		);
	} );

	it( 'shifts when another option crosses it', () => {
		// B (1) open, A (0) dragged below it: B is now index 0.
		expect( updateSelectedIndicesAfterReorder( [ 1 ], 0, 2 )[ 0 ] ).toBe(
			0
		);
	} );
} );
