/* eslint-env jest */
jest.mock( 'lucide-react', () => new Proxy( {}, { get: () => () => null } ) );
jest.mock(
	'@bsf/force-ui',
	() => ( {
		Button: ( { children } ) => children ?? null,
		Text: ( { children } ) => children,
	} ),
	{ virtual: true }
);
jest.mock(
	'@Utils/features',
	() => jest.requireActual( '../../../../utils/features' ),
	{ virtual: true }
);
jest.mock( '../utils', () => ( { formatOrderId: () => '#1' } ) );

import { createElement, createRoot } from '@wordpress/element';
// React's own act(): react-dom/test-utils' is deprecated in React 18.3.
// eslint-disable-next-line import/no-extraneous-dependencies
import { act } from 'react';
import PaymentHeader from '../paymentHeader';

global.IS_REACT_ACT_ENVIRONMENT = true;
// The plugin's build provides React to JSX; the tests do it here.
global.React = { createElement };

const render = () => {
	const container = document.createElement( 'div' );
	const root = createRoot( container );
	act( () =>
		root.render(
			createElement( PaymentHeader, {
				title: 'Order ID',
				paymentData: { entry_id: 7 },
			} )
		)
	);
	const html = container.innerHTML;
	act( () => root.unmount() );
	return html;
};

describe( 'PaymentHeader', () => {
	afterEach( () => {
		delete window.srfmIsFeatureEnabled;
	} );

	it( 'links to the form data while Entries is on', () => {
		expect( render() ).toContain( 'View Form Data' );
	} );

	// The entries page is not registered while Entries is off.
	it( 'leaves the form data link out while Entries is off', () => {
		window.srfmIsFeatureEnabled = ( key ) => 'entries' !== key;
		expect( render() ).not.toContain( 'View Form Data' );
	} );
} );
