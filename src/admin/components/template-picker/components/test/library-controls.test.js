/* eslint-env jest */
jest.mock( 'lucide-react', () => new Proxy( {}, { get: () => () => null } ) );
jest.mock( '@bsf/force-ui', () => ( {} ), { virtual: true } );
jest.mock( '@Utils/Helpers', () => ( { cn: () => '' } ), { virtual: true } );
jest.mock( '@Utils/form-start', () => ( { getScratchUrl: () => '' } ), {
	virtual: true,
} );
jest.mock(
	'@Utils/features',
	() => jest.requireActual( '../../../../../utils/features' ),
	{ virtual: true }
);

import { canGenerateWithAi } from '../LibraryControls';

describe( 'canGenerateWithAi', () => {
	afterEach( () => {
		delete window.srfmIsFeatureEnabled;
		delete window.srfm_admin;
	} );

	it( 'is true with generations left and the AI form builder on', () => {
		window.srfm_admin = { srfm_ai_usage_details: { remaining: 3 } };
		expect( canGenerateWithAi() ).toBe( true );
	} );

	it( 'is false while the AI form builder is switched off', () => {
		window.srfm_admin = { srfm_ai_usage_details: { remaining: 3 } };
		window.srfmIsFeatureEnabled = ( key ) => 'ai_builder' !== key;
		expect( canGenerateWithAi() ).toBe( false );
	} );

	it( 'is false with no generations left', () => {
		window.srfm_admin = { srfm_ai_usage_details: { remaining: 0 } };
		expect( canGenerateWithAi() ).toBe( false );
	} );
} );
