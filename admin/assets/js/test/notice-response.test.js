/* eslint-env jest */
/**
 * The close cross on the classic action-item notices, and how a closed card
 * leaves the notice carousel.
 */

const listeners = [];
const observers = [];

function card( id ) {
	return (
		'<div class="notice srfm-action-item-notice notice-error srfm-action-item-closable">' +
		`<p>Card ${ id }</p>` +
		`<a href="#close-${ id }" class="notice-dismiss srfm-action-item-close" data-srfm-dismiss-item="${ id }">x</a>` +
		'</div>'
	);
}

/**
 * Render the cards and load the script fresh, as a page load would.
 *
 * @param {string[]} ids Card ids.
 */
function load( ids ) {
	document.body.innerHTML = `<div id="host">${ ids
		.map( card )
		.join( '' ) }</div>`;
	jest.isolateModules( () => require( '../notice-response.js' ) );
}

const cardText = ( id ) =>
	Array.from( document.querySelectorAll( '.srfm-action-item-notice' ) ).find(
		( el ) => el.textContent.includes( `Card ${ id }` )
	);
const close = ( id ) =>
	cardText( id ).querySelector( '.srfm-action-item-close' ).click();
const visible = () =>
	Array.from( document.querySelectorAll( '.srfm-action-item-notice' ) )
		.filter( ( el ) => ! el.hidden )
		.map( ( el ) => el.querySelector( 'p' ).textContent );
const counter = () =>
	document.querySelector( '.srfm-action-item-carousel-nav span' ).textContent;
const nextButton = () =>
	document.querySelectorAll( '.srfm-action-item-carousel-nav button' )[ 1 ];

beforeEach( () => {
	global.srfmNoticeResponse = {
		ajaxurl: '/wp-admin/admin-ajax.php',
		nonce: 'n',
		dismiss: 'd',
		carousel: {},
	};
	global.fetch = jest.fn( () =>
		Promise.resolve( {
			status: 200,
			json: () => Promise.resolve( { success: true } ),
		} )
	);

	// Each load adds document listeners and an observer; drop them after the
	// test so one page's script does not act on the next.
	const add = document.addEventListener.bind( document );
	jest.spyOn( document, 'addEventListener' ).mockImplementation(
		( ...args ) => {
			listeners.push( args );
			add( ...args );
		}
	);
	const Real = window.MutationObserver;
	window.MutationObserver = class extends Real {
		constructor( cb ) {
			super( cb );
			observers.push( this );
		}
	};
	window.MutationObserver.Real = Real;
} );

afterEach( () => {
	listeners
		.splice( 0 )
		.forEach( ( args ) => document.removeEventListener( ...args ) );
	observers.splice( 0 ).forEach( ( o ) => o.disconnect() );
	window.MutationObserver = window.MutationObserver.Real;
	jest.restoreAllMocks();
	delete global.srfmNoticeResponse;
} );

describe( 'notice-response close cross', () => {
	it( 'records the close and shows the card that followed', () => {
		load( [ 'a', 'b', 'c' ] );

		expect( counter() ).toBe( '1 of 3' );
		nextButton().click();
		expect( visible() ).toEqual( [ 'Card b' ] );

		close( 'b' );

		expect( global.fetch ).toHaveBeenCalledTimes( 1 );
		const body = global.fetch.mock.calls[ 0 ][ 1 ].body;
		expect( body.get( 'action' ) ).toBe( 'srfm_dismiss_action_item' );
		expect( body.get( 'item_id' ) ).toBe( 'b' );

		expect( cardText( 'b' ) ).toBeUndefined();
		expect( visible() ).toEqual( [ 'Card c' ] );
		expect( counter() ).toBe( '2 of 2' );
		expect( document.activeElement ).toBe( nextButton() );
	} );

	it( 'steps back when the last card is closed', () => {
		load( [ 'a', 'b', 'c' ] );

		nextButton().click();
		nextButton().click();
		close( 'c' );

		expect( visible() ).toEqual( [ 'Card b' ] );
		expect( counter() ).toBe( '2 of 2' );
	} );

	it( 'removes the carousel and keeps focus when one card is left', () => {
		load( [ 'a', 'b' ] );

		close( 'a' );

		expect(
			document.querySelector( '.srfm-action-item-carousel' )
		).toBeNull();
		expect( visible() ).toEqual( [ 'Card b' ] );
		expect( document.activeElement ).toBe( cardText( 'b' ) );
	} );

	it( 'does not let a double click close the next card', () => {
		const now = jest.spyOn( Date, 'now' ).mockReturnValue( 1000 );
		load( [ 'a', 'b', 'c' ] );

		close( 'a' );
		close( 'b' );

		expect( global.fetch ).toHaveBeenCalledTimes( 1 );
		expect( visible() ).toEqual( [ 'Card b' ] );

		// A deliberate second close still works.
		now.mockReturnValue( 2000 );
		close( 'b' );

		expect( global.fetch ).toHaveBeenCalledTimes( 2 );
		expect( visible() ).toEqual( [ 'Card c' ] );
	} );

	it( 'removes a lone card with no carousel', () => {
		load( [ 'a' ] );

		close( 'a' );

		expect( global.fetch ).toHaveBeenCalledTimes( 1 );
		expect( cardText( 'a' ) ).toBeUndefined();
	} );
} );
