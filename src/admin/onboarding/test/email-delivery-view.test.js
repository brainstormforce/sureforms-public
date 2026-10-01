/* eslint-env jest */
/**
 * getView() decides which screen the onboarding email step shows. A failure
 * must never fall through to the "done" screen, which is how an owner was
 * told "Continue" with no way to fix a failing site.
 */
import { getView, getDoneAction } from '../pages/email-delivery-view';

const status = ( state, action = 'none' ) => ( {
	state,
	action: { type: action },
} );

describe( 'getView', () => {
	it.each( [
		[ status( 'not_configured', 'install_activate' ), 'install' ],
		[ status( 'not_configured', 'activate' ), 'install' ],
		[ status( 'attention', 'install_activate' ), 'install' ],
		[ status( 'suremail_no_connection', 'open_suremail' ), 'connect' ],
		[ status( 'attention', 'open_suremail' ), 'connect' ],
		[ status( 'limited', 'open_suremail' ), 'limited' ],
		[ status( 'attention', 'view_logs' ), 'attention' ],
		[ status( 'attention', 'verify_surecontact' ), 'attention' ],
		[ status( 'attention', 'none' ), 'attention' ],
		[ status( 'ready' ), 'done' ],
		[ status( 'other_mailer' ), 'done' ],
	] )( 'maps %o to %s', ( input, view ) => {
		expect( getView( input ) ).toBe( view );
	} );

	it( 'offers no install when promotions are hidden (no action)', () => {
		expect( getView( status( 'not_configured', 'none' ) ) ).toBe( 'done' );
	} );

	it( 'treats a missing status as done', () => {
		expect( getView( null ) ).toBe( 'done' );
	} );
} );

describe( 'getDoneAction', () => {
	it.each( [
		[ 'ready', 'continue_ready' ],
		[ 'other_mailer', 'continue_other_mailer' ],
		[ 'not_configured', 'continue_not_set_up' ],
	] )( 'records %s as %s', ( state, action ) => {
		expect( getDoneAction( status( state ) ) ).toBe( action );
	} );
} );
