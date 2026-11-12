/* eslint-env jest */
import { isFeatureEnabled, withEnabledFeatures } from '../features';

describe( 'features', () => {
	afterEach( () => {
		delete window.srfmIsFeatureEnabled;
	} );

	it( 'reads every feature as on without the switches', () => {
		expect( isFeatureEnabled( 'entries' ) ).toBe( true );
	} );

	it( 'keeps an item only while every feature it names is on', () => {
		// Without Pro, `pdf` is not registered and reads as on; `entries` is off.
		window.srfmIsFeatureEnabled = ( key ) => 'entries' !== key;
		const items = [
			{ id: 'always' },
			{ id: 'payments', feature: 'payments' },
			{ id: 'entries', feature: 'entries' },
			{ id: 'pdf', feature: [ 'pdf', 'entries' ] },
			{ id: 'ai', feature: [ 'ai_builder' ] },
		];
		expect( withEnabledFeatures( items ).map( ( item ) => item.id ) ).toEqual(
			[ 'always', 'payments', 'ai' ]
		);
	} );
} );
