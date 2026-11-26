/* eslint-env jest */
jest.mock( '@wordpress/api-fetch', () => jest.fn(), { virtual: true } );
// lucide-react ships ESM, which the unit-test transform does not compile.
jest.mock( 'lucide-react', () => ( { ArrowRight: () => null, X: () => null } ), {
	virtual: true,
} );

import { createElement, createRoot, Fragment } from '@wordpress/element';
// React's own act(): react-dom/test-utils' is deprecated in React 18.3.
// eslint-disable-next-line import/no-extraneous-dependencies
import { act } from 'react';
import apiFetch from '@wordpress/api-fetch';
import PromoBar, {
	SPEED,
	getMarqueeLayout,
	getTimeLeft,
	getTimerLabel,
} from '../PromoBar';

global.IS_REACT_ACT_ENVIRONMENT = true;
// The plugin's build provides React to JSX; the tests do it here.
global.React = { createElement, Fragment };

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

describe( 'getTimerLabel', () => {
	it( 'uses the singular for one of each unit', () => {
		expect( getTimerLabel( 'Sale ends in', 1, 1, 1 ) ).toBe(
			'Sale ends in 1 day, 1 hour, 1 minute'
		);
	} );

	it( 'uses the plural for other counts', () => {
		expect( getTimerLabel( 'Sale ends in', 2, 0, 5 ) ).toBe(
			'Sale ends in 2 days, 0 hours, 5 minutes'
		);
	} );
} );

describe( 'getMarqueeLayout', () => {
	it( 'waits until both widths are measured', () => {
		expect( getMarqueeLayout( 0, 1400 ) ).toBeNull();
		expect( getMarqueeLayout( 700, 0 ) ).toBeNull();
	} );

	it( 'repeats the copy until one run covers the visible area', () => {
		expect( getMarqueeLayout( 700, 1400 ).repeat ).toBe( 2 );
		expect( getMarqueeLayout( 550, 1400 ).repeat ).toBe( 3 );
		expect( getMarqueeLayout( 550, 5000 ).repeat ).toBe( 10 );
		expect( getMarqueeLayout( 2000, 300 ).repeat ).toBe( 1 );
	} );

	it( 'keeps the same speed whatever the copy length', () => {
		for ( const copy of [ 550, 700, 913 ] ) {
			const { repeat, duration } = getMarqueeLayout( copy, 1400 );
			expect( ( repeat * copy ) / duration ).toBeCloseTo( SPEED );
		}
	} );
} );

describe( 'PromoBar', () => {
	const now = Date.UTC( 2026, 10, 26, 12, 0, 0 );
	const promotion = {
		id: 'sureforms-bfcm-2026',
		phase: 'black-friday',
		ends_at: Math.floor( now / 1000 ) + 5,
		timer_label: 'Sale ends in',
		items: [ [ { text: 'Black Friday Sale', style: 'bold' } ] ],
		cta: { label: 'View deals', url: 'https://sureforms.com/pricing/' },
	};

	let container;
	let root;

	const render = ( element ) => {
		act( () => {
			root.render( element );
		} );
	};

	const posted = ( action ) =>
		apiFetch.mock.calls.filter(
			( [ options ] ) =>
				options.path === `/sureforms/v1/promotion/${ action }`
		).length;

	beforeEach( () => {
		jest.useFakeTimers();
		jest.setSystemTime( now );
		apiFetch.mockReset();
		apiFetch.mockResolvedValue( {} );
		container = document.createElement( 'div' );
		document.body.appendChild( container );
		root = createRoot( container );
	} );

	afterEach( () => {
		act( () => root.unmount() );
		container.remove();
		jest.useRealTimers();
	} );

	it( 'counts itself as shown once it is on screen', () => {
		render( createElement( PromoBar, { promotion } ) );

		expect( container.querySelector( 'section' ) ).not.toBeNull();
		expect( posted( 'shown' ) ).toBe( 1 );
		expect( apiFetch ).toHaveBeenCalledWith(
			expect.objectContaining( {
				method: 'POST',
				data: { id: promotion.id },
			} )
		);

		render( createElement( PromoBar, { promotion } ) );
		expect( posted( 'shown' ) ).toBe( 1 );
	} );

	it( 'counts nothing when there is no bar to show', () => {
		render( createElement( PromoBar, { promotion: null } ) );
		render(
			createElement( PromoBar, {
				promotion: { ...promotion, ends_at: now / 1000 },
			} )
		);

		expect( container.querySelector( 'section' ) ).toBeNull();
		expect( posted( 'shown' ) ).toBe( 0 );
	} );

	it( 'keeps the clock in left-to-right order on RTL pages', () => {
		render( createElement( PromoBar, { promotion } ) );

		const timer = container.querySelector( '[role="timer"]' );
		expect( timer.getAttribute( 'dir' ) ).toBe( 'ltr' );
		expect( timer.getAttribute( 'aria-label' ) ).toBe(
			'Sale ends in 0 days, 0 hours, 0 minutes'
		);
	} );

	it( 'slides the other way on RTL pages and never animates with reduced motion', () => {
		render( createElement( PromoBar, { promotion } ) );

		const track = container.querySelector( '[class*="srfm-marquee"]' );
		expect( track.className ).toContain(
			'motion-safe:animate-srfm-marquee'
		);
		expect( track.className ).toContain(
			'motion-safe:rtl:animate-srfm-marquee-rtl'
		);
		expect( track.className ).not.toMatch( /(^|\s)animate-srfm-marquee/ );
	} );

	it( 'hides the moment the countdown reaches zero', () => {
		const onHide = jest.fn();
		render( createElement( PromoBar, { promotion, onHide } ) );

		act( () => {
			jest.advanceTimersByTime( 4000 );
		} );
		expect( container.querySelector( 'section' ) ).not.toBeNull();

		act( () => {
			jest.advanceTimersByTime( 1000 );
		} );
		expect( container.querySelector( 'section' ) ).toBeNull();
		expect( onHide ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'moves focus to the first visible header control on dismiss', () => {
		const onHide = jest.fn();
		render(
			createElement(
				'div',
				null,
				createElement( PromoBar, { promotion, onHide } ),
				createElement(
					'nav',
					null,
					createElement( 'button', { id: 'hidden-menu' } ),
					createElement( 'a', { id: 'dashboard', href: '#d' } )
				)
			)
		);
		// jsdom lays nothing out; mark only the link as visible.
		container.querySelector( '#dashboard' ).getClientRects = () => [ {} ];

		act( () => {
			container
				.querySelector( 'button[aria-label="Dismiss promotion"]' )
				.click();
		} );
		act( () => {
			jest.runOnlyPendingTimers();
		} );

		expect( container.querySelector( 'section' ) ).toBeNull();
		expect( onHide ).toHaveBeenCalledTimes( 1 );
		expect( posted( 'dismiss' ) ).toBe( 1 );
		expect( document.activeElement?.id ).toBe( 'dashboard' );
	} );
} );
