/* eslint-env jest */
jest.mock( '@wordpress/api-fetch', () => jest.fn( () => Promise.resolve( {} ) ) );

import apiFetch from '@wordpress/api-fetch';
import {
	getAddNewFormScreen,
	getStartChoice,
	scopeStartChoiceToVisit,
	setStartChoice,
	trackStartStep,
} from '../form-start';

const params = ( query ) => new URLSearchParams( query );

beforeEach( () => {
	window.sessionStorage.clear();
	global.srfm_admin = {
		site_url: 'http://example.test',
		form_start: { enabled: true, tracking: true },
	};
	apiFetch.mockClear();
} );

describe( 'getAddNewFormScreen', () => {
	it( 'opens the start screen unless the link asks for a screen', () => {
		expect( getAddNewFormScreen( params( '?page=add-new-form' ) ) ).toBe(
			'start'
		);
		expect(
			getAddNewFormScreen( params( '?page=add-new-form&source=learn' ) )
		).toBe( 'start' );
		expect(
			getAddNewFormScreen( params( '?page=add-new-form&view=templates' ) )
		).toBe( 'templates' );
		expect(
			getAddNewFormScreen( params( '?page=add-new-form&form_type=payment' ) )
		).toBe( 'ai' );
	} );

	it( 'opens the AI screen when the start screen is filtered off', () => {
		srfm_admin.form_start.enabled = false;
		expect( getAddNewFormScreen( params( '?page=add-new-form' ) ) ).toBe(
			'ai'
		);
	} );
} );

describe( 'scopeStartChoiceToVisit', () => {
	it( 'drops a choice left from an earlier visit when another screen opens', () => {
		setStartChoice( 'template' );
		scopeStartChoiceToVisit(
			params( '?page=add-new-form&form_type=payment' ),
			'navigate'
		);
		expect( getStartChoice() ).toBe( '' );
	} );

	it( 'keeps the choice for the AI sign-in coming back', () => {
		setStartChoice( 'ai' );
		scopeStartChoiceToVisit(
			params( '?page=add-new-form&access_key=abc' ),
			'navigate'
		);
		expect( getStartChoice() ).toBe( 'ai' );
	} );

	it( 'keeps the choice on a reload or Back / Forward', () => {
		setStartChoice( 'ai' );
		scopeStartChoiceToVisit( params( '?page=add-new-form&view=ai' ), 'reload' );
		scopeStartChoiceToVisit(
			params( '?page=add-new-form&view=ai' ),
			'back_forward'
		);
		expect( getStartChoice() ).toBe( 'ai' );
	} );

	it( 'leaves the choice alone when the start screen opens', () => {
		setStartChoice( 'template' );
		scopeStartChoiceToVisit( params( '?page=add-new-form' ), 'navigate' );
		expect( getStartChoice() ).toBe( 'template' );
	} );
} );

describe( 'trackStartStep', () => {
	it( 'returns a promise that settles once the count is sent', async () => {
		window.history.pushState( {}, '', '/wp-admin/admin.php?page=add-new-form' );
		await trackStartStep( 'choose', { choice: 'scratch', position: 'link' } );
		expect( apiFetch ).toHaveBeenCalledWith(
			expect.objectContaining( {
				path: 'sureforms/v1/form-start/track',
				data: { choice: 'scratch', position: 'link', action: 'choose' },
			} )
		);
	} );

	it( 'skips path steps when no choice is in play, and counts nothing outside Add New Form', async () => {
		window.history.pushState( {}, '', '/wp-admin/admin.php?page=add-new-form' );
		await trackStartStep( 'ai_generate' );
		window.history.pushState( {}, '', '/wp-admin/post.php?post=1' );
		await trackStartStep( 'view' );
		expect( apiFetch ).not.toHaveBeenCalled();
	} );
} );
