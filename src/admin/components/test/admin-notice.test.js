/* eslint-env jest */
jest.mock(
	'@bsf/force-ui',
	() => ( {
		Alert: ( { title, content } ) => (
			<div>
				{ title }
				{ content }
			</div>
		),
		Button: ( { children, icon, ...props } ) => (
			<button type="button" { ...props }>
				{ icon }
				{ children }
			</button>
		),
	} ),
	{ virtual: true }
);
jest.mock( '@wordpress/api-fetch', () => jest.fn() );
// lucide-react ships ESM, which the unit-test transform does not compile.
jest.mock(
	'lucide-react',
	() => {
		const Icon = () => null;
		return {
			TriangleAlert: Icon,
			Info: Icon,
			CheckCircle: Icon,
			CircleAlert: Icon,
			X: Icon,
		};
	},
	{ virtual: true }
);

import { createElement, createRoot, Fragment } from '@wordpress/element';
// React's own act(): react-dom/test-utils' is deprecated in React 18.3.
// eslint-disable-next-line import/no-extraneous-dependencies
import { act } from 'react';
import AdminNotice from '../AdminNotice';

global.IS_REACT_ACT_ENVIRONMENT = true;
// The plugin's build provides React to JSX; the tests do it here.
global.React = { createElement, Fragment };

const render = ( notices ) => {
	window.srfm_admin = {
		ajax_url: '/wp-admin/admin-ajax.php',
		notice_response_nonce: 'nonce-123',
		notices,
	};
	const container = document.createElement( 'div' );
	const root = createRoot( container );
	act( () =>
		root.render( createElement( AdminNotice, { currentPage: 'sureforms_forms' } ) )
	);
	return container;
};

const dismissButton = ( container ) =>
	container.querySelector( 'button[aria-label="Dismiss notice"]' );

describe( 'AdminNotice', () => {
	afterEach( () => {
		delete window.srfm_admin;
		delete global.fetch;
	} );

	it( 'offers no close button on a notice that is not dismissible', () => {
		const container = render( [
			{ id: 'fault', message: 'Broken', dismissible: false },
		] );

		expect( container.textContent ).toContain( 'Broken' );
		expect( dismissButton( container ) ).toBeNull();
	} );

	it( 'hides a dismissible notice when its close button is clicked', () => {
		global.fetch = jest.fn( () => Promise.resolve() );
		const container = render( [ { id: 'tip', message: 'A tip' } ] );

		act( () => dismissButton( container ).click() );

		expect( container.textContent ).not.toContain( 'A tip' );
		// No dismiss_response, so the close stays in the browser.
		expect( global.fetch ).not.toHaveBeenCalled();
	} );

	it( 'reports the pressed button to srfm_notice_response before following it', () => {
		global.fetch = jest.fn( () => Promise.resolve() );
		const assign = jest.fn();
		const open = jest.spyOn( window, 'open' ).mockImplementation( assign );

		const container = render( [
			{
				id: 'notification_error',
				message: 'Email failed',
				dismissible: false,
				actions: [
					{
						label: 'Help Me Fix',
						url: 'https://example.com/guide',
						target: '_blank',
						response: 'help_me_fix',
					},
				],
			},
		] );

		const button = Array.from( container.querySelectorAll( 'button' ) ).find(
			( node ) => node.textContent === 'Help Me Fix'
		);
		act( () => button.click() );

		expect( global.fetch ).toHaveBeenCalledTimes( 1 );
		const [ url, options ] = global.fetch.mock.calls[ 0 ];
		const body = new URLSearchParams( options.body );
		expect( url ).toBe( '/wp-admin/admin-ajax.php' );
		// The page may navigate away straight after, so the request must survive it.
		expect( options.keepalive ).toBe( true );
		expect( body.get( 'action' ) ).toBe( 'srfm_notice_response' );
		expect( body.get( 'nonce' ) ).toBe( 'nonce-123' );
		expect( body.get( 'notice_id' ) ).toBe( 'notification_error' );
		expect( body.get( 'button' ) ).toBe( 'help_me_fix' );
		expect( open ).toHaveBeenCalledWith(
			'https://example.com/guide',
			'_blank',
			'noopener,noreferrer'
		);

		open.mockRestore();
	} );

	it( 'sends nothing for an action that names no response', () => {
		global.fetch = jest.fn( () => Promise.resolve() );
		const open = jest.spyOn( window, 'open' ).mockImplementation( () => {} );

		const container = render( [
			{
				id: 'srfm-database-repair-failed',
				message: 'Failed',
				actions: [
					{
						label: 'Contact support',
						url: 'https://example.com/contact',
						target: '_blank',
					},
				],
			},
		] );

		const button = Array.from( container.querySelectorAll( 'button' ) ).find(
			( node ) => node.textContent === 'Contact support'
		);
		act( () => button.click() );

		expect( global.fetch ).not.toHaveBeenCalled();
		expect( open ).toHaveBeenCalled();

		open.mockRestore();
	} );

	it( 'records the close on the server when the notice names a dismiss response', () => {
		global.fetch = jest.fn( () => Promise.resolve() );

		const container = render( [
			{
				id: 'notification_error',
				message: 'Email failed',
				dismiss_response: 'dismissed',
			},
		] );

		act( () => dismissButton( container ).click() );

		expect( container.textContent ).not.toContain( 'Email failed' );
		expect( global.fetch ).toHaveBeenCalledTimes( 1 );
		const body = new URLSearchParams( global.fetch.mock.calls[ 0 ][ 1 ].body );
		expect( body.get( 'notice_id' ) ).toBe( 'notification_error' );
		expect( body.get( 'button' ) ).toBe( 'dismissed' );
	} );
} );
