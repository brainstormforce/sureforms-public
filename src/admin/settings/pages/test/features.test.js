/* eslint-env jest */
jest.mock( 'lucide-react', () => new Proxy( {}, { get: () => () => null } ) );
jest.mock(
	'@bsf/force-ui',
	() => {
		const { createElement: h } = jest.requireActual( '@wordpress/element' );
		return {
			Button: ( { children, onClick } ) =>
				h( 'button', { onClick }, children ),
			Switch: ( { value, onChange, 'aria-label': label } ) =>
				h( 'input', {
					type: 'checkbox',
					'aria-label': label,
					checked: !! value,
					onChange: () => onChange( ! value ),
				} ),
			Tabs: Object.assign( () => null, { Group: () => null, Tab: () => null } ),
			toast: { success: () => {}, error: () => {} },
		};
	},
	{ virtual: true }
);
jest.mock( '@wordpress/api-fetch', () => jest.fn() );

// Captures the header Save button's adapter so the test can press Save.
const adapter = {};
jest.mock( '../../tab-save-adapter', () => ( {
	// `_tab` is unused: the page registers one tab, so the options are all we need.
	useGlobalSettingsTabSaveAdapter: ( _tab, options ) =>
		Object.assign( adapter, options ),
} ) );

import { createElement, createRoot } from '@wordpress/element';
// eslint-disable-next-line import/no-extraneous-dependencies
import { act } from 'react';
import apiFetch from '@wordpress/api-fetch';
import FeaturesPage from '../Features';

global.IS_REACT_ACT_ENVIRONMENT = true;
global.React = { createElement };

const row = ( label, extra = {} ) => ( {
	label,
	description: `${ label } description.`,
	group: 'core',
	depends_on: [],
	switch: true,
	enabled: true,
	blocked_by: [],
	...extra,
} );

let container;
let root;

beforeEach( () => {
	window.srfmFeatures = {
		entries: row( 'Entries' ),
		payments: row( 'Payments' ),
		quiz: row( 'Quizzes', { group: 'pro', depends_on: [ 'entries' ] } ),
	};
	container = document.createElement( 'div' );
	document.body.appendChild( container );
	root = createRoot( container );
	act( () => root.render( createElement( FeaturesPage ) ) );
} );

afterEach( () => {
	act( () => root.unmount() );
	container.remove();
	delete window.srfmFeatures;
	apiFetch.mockReset();
} );

const toggle = ( label ) =>
	act( () =>
		container.querySelector( `input[aria-label="${ label }"]` ).click()
	);

describe( 'FeaturesPage', () => {
	it( 'says which features turn off with Entries', () => {
		expect(
			container.querySelector( '#srfm-feature-entries-description' )
				.textContent
		).toBe(
			'Entries description. Turning this off also turns off Quizzes.'
		);
	} );

	it( 'lists the reason and the status of each form in use, and Cancel restores the saved switches', async () => {
		apiFetch.mockResolvedValue( {
			needs_confirmation: true,
			in_use: {
				payments: [
					{
						id: 3,
						title: 'Donate',
						edit_url: '',
						status: 'draft',
						reason: 'has a payment field',
					},
				],
			},
		} );

		toggle( 'Payments' );
		expect( adapter.isDirty ).toBe( true );
		await act( async () => adapter.onSave() );

		expect( container.textContent ).toContain(
			'Donate (Payments: has a payment field, draft)'
		);

		const cancel = [ ...container.querySelectorAll( 'button' ) ].find(
			( button ) => 'Cancel' === button.textContent
		);
		act( () => cancel.click() );

		expect(
			container.querySelector( 'input[aria-label="Payments"]' ).checked
		).toBe( true );
		expect( adapter.isDirty ).toBe( false );
	} );
} );
