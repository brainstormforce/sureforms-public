/* eslint-env jest */
jest.mock( 'lucide-react', () => new Proxy( {}, { get: () => () => null } ) );
jest.mock(
	'@bsf/force-ui',
	() => {
		const { createElement: el } = require( '@wordpress/element' );
		const Pass = ( { children } ) => children ?? null;
		return {
			Button: ( { children } ) => el( 'button', null, children ),
			Container: Pass,
			Badge: Pass,
			Text: Pass,
			Tooltip: Pass,
		};
	},
	{ virtual: true }
);
// Renders only the Entries cell of the first row.
jest.mock(
	'@Admin/common/listing/components/Table',
	() => ( { columns, data } ) => {
		const column = columns.find( ( c ) => 'entries_count' === c.key );
		return column.render( data[ 0 ] );
	},
	{ virtual: true }
);
jest.mock( '@Utils/Helpers', () => ( { formatDateTime: () => '' } ), {
	virtual: true,
} );
jest.mock(
	'@Utils/features',
	() => jest.requireActual( '../../../../utils/features' ),
	{ virtual: true }
);
jest.mock( '../../utils', () => ( { exportForms: () => {} } ) );

import { createElement, createRoot } from '@wordpress/element';
// React's own act(): react-dom/test-utils' is deprecated in React 18.3.
// eslint-disable-next-line import/no-extraneous-dependencies
import { act } from 'react';
import FormsTable from '../FormsTable';

global.IS_REACT_ACT_ENVIRONMENT = true;
// The plugin's build provides React to JSX; the tests do it here.
global.React = { createElement };

const renderEntriesCell = () => {
	const container = document.createElement( 'div' );
	const root = createRoot( container );
	act( () =>
		root.render(
			createElement( FormsTable, {
				data: [ { id: 3, entries_count: 12 } ],
			} )
		)
	);
	const html = container.innerHTML;
	act( () => root.unmount() );
	return html;
};

describe( 'FormsTable entries count', () => {
	afterEach( () => {
		delete window.srfmIsFeatureEnabled;
	} );

	it( 'links to the form’s entries while Entries is on', () => {
		expect( renderEntriesCell() ).toBe( '<button>12</button>' );
	} );

	// The entries page is not registered while Entries is off.
	it( 'is plain text while Entries is off', () => {
		window.srfmIsFeatureEnabled = ( key ) => 'entries' !== key;
		const html = renderEntriesCell();
		expect( html ).toContain( '12' );
		expect( html ).not.toContain( '<button' );
	} );
} );
