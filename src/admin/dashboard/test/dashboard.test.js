/* eslint-env jest */
jest.mock(
	'@bsf/force-ui',
	() => {
		const Container = ( { children } ) => children;
		Container.Item = Container;
		return { Container };
	},
	{ virtual: true }
);
jest.mock( '@Utils/Helpers', () => ( { cn: () => '' } ), { virtual: true } );
jest.mock(
	'@Utils/features',
	() => jest.requireActual( '../../../utils/features' ),
	{ virtual: true }
);
jest.mock( '../FormsOverview', () => () => 'forms-overview' );
// jest.mock() is only hoisted as a top-level call, so one per module.
jest.mock( '../GetStarted', () => () => null );
jest.mock( '../ExtendTab', () => () => null );
jest.mock( '../ActionItems', () => () => null );
jest.mock( '../QuickAccessTab', () => () => null );
jest.mock( '../UpgradeToPro', () => () => null );
jest.mock( '../../components/Header', () => () => null );
jest.mock( '../../components/AdminNotice', () => () => null );

import { createElement, createRoot, Fragment } from '@wordpress/element';
// React's own act(): react-dom/test-utils' is deprecated in React 18.3.
// eslint-disable-next-line import/no-extraneous-dependencies
import { act } from 'react';
import Dashboard from '../Dashboard';

global.IS_REACT_ACT_ENVIRONMENT = true;
// The plugin's build provides React to JSX; the tests do it here.
global.React = { createElement, Fragment };

const render = () => {
	const container = document.createElement( 'div' );
	const root = createRoot( container );
	act( () => root.render( createElement( Dashboard ) ) );
	const html = container.innerHTML;
	act( () => root.unmount() );
	return html;
};

describe( 'Dashboard', () => {
	beforeEach( () => {
		window.srfm_admin = {};
	} );
	afterEach( () => {
		delete window.srfm_admin;
		delete window.srfmIsFeatureEnabled;
	} );

	it( 'shows the entries chart while Entries is on', () => {
		expect( render() ).toContain( 'forms-overview' );
	} );

	// The chart reads the entries-chart-data route, which is gone while Entries is off.
	it( 'leaves the entries chart out while Entries is off', () => {
		window.srfmIsFeatureEnabled = ( key ) => 'entries' !== key;
		expect( render() ).not.toContain( 'forms-overview' );
	} );
} );
