/* eslint-env jest */
/**
 * getView() decides which screen the onboarding email step shows. A failure
 * must never fall through to the "done" screen, which is how an owner was
 * told "Continue" with no way to fix a failing site.
 */
import {
	getView,
	getDoneAction,
	getProvisionFailure,
	isSiteRefused,
	isRateLimited,
	isLikelyLocalHost,
	getDefaultOption,
	canCreateAccount,
	getSureContactSignInUrl,
} from '../pages/email-delivery-view';

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
		// Without an action to match first, only the state check sends it here.
		[ status( 'limited', 'none' ), 'limited' ],
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

describe( 'getProvisionFailure', () => {
	it( 'sends an existing account to sign in, never connects it', () => {
		expect(
			getProvisionFailure( {
				success: false,
				reason: 'email_already_registered',
			} )
		).toBe( 'already_registered' );
	} );

	it( 'hands over to SureMail when the route is missing', () => {
		expect( getProvisionFailure( { code: 'rest_no_route' } ) ).toBe(
			'unavailable'
		);
	} );

	it.each( [ [ {} ], [ null ], [ { reason: 'site_unreachable' } ] ] )(
		'treats %o as a failure',
		( error ) => {
			expect( getProvisionFailure( error ) ).toBe( 'failed' );
		}
	);
} );

describe( 'isSiteRefused', () => {
	it.each( [
		[ { reason: 'site_unreachable' }, true ],
		[ { reason: 'site_not_verified' }, true ],
		[
			{
				message:
					'site_url: The site url must not point to an internal or private network address.',
			},
			true,
		],
		[ { reason: 'email_already_registered' }, false ],
		[ null, false ],
	] )( '%o → %s', ( error, refused ) => {
		expect( isSiteRefused( error ) ).toBe( refused );
	} );
} );

describe( 'isLikelyLocalHost', () => {
	it.each( [
		'localhost',
		'my-site.test',
		'shop.local',
		'wp.localhost',
		'127.0.0.1',
		'10.0.0.12',
		'192.168.1.20',
		'172.16.0.1',
		'172.31.255.255',
		'169.254.10.10',
		'[::1]',
		'',
		'0.0.0.0',
		'100.64.1.2',
		'100.127.0.1',
		'fd12:3456::1',
		'fe80::1',
		'::ffff:127.0.0.1',
	] )( 'treats %p as local', ( host ) => {
		expect( isLikelyLocalHost( host ) ).toBe( true );
	} );

	it.each( [
		'example.com',
		'accessible-aristide-0ygqd.zipwp.link',
		'172.32.0.1',
		'8.8.8.8',
		'test.example.org',
		'localhost.example.com',
		'100.128.0.1',
		'2606:4700::6810:84e5',
		'::ffff:8.8.8.8',
	] )( 'treats %p as public', ( host ) => {
		expect( isLikelyLocalHost( host ) ).toBe( false );
	} );
} );

describe( 'getDefaultOption', () => {
	it( 'opens SureContact on a public site', () => {
		expect( getDefaultOption( null, false ) ).toBe( 'surecontact' );
	} );

	it( 'opens another provider on a local site', () => {
		expect( getDefaultOption( null, true ) ).toBe( 'other_smtp' );
	} );

	it( 'opens another provider when an active SureMail cannot connect SureContact', () => {
		expect(
			getDefaultOption(
				{
					suremail: {
						plugin_status: 'Activated',
						supports_surecontact: false,
					},
				},
				false
			)
		).toBe( 'other_smtp' );
	} );

	// Found on the QA site: an inactive SureMail reports false because its
	// version cannot be read, which opened Other SMTP on a public site.
	it( 'ignores the support flag while SureMail is not active', () => {
		expect(
			getDefaultOption(
				{
					suremail: {
						plugin_status: 'Installed',
						supports_surecontact: false,
					},
				},
				false
			)
		).toBe( 'surecontact' );
	} );

	it( 'treats an unknown SureMail version as able', () => {
		expect( getDefaultOption( { suremail: {} }, false ) ).toBe(
			'surecontact'
		);
	} );
} );

describe( 'canCreateAccount', () => {
	const ok = { consented: true, option: 'surecontact', email: 'a@b.co' };

	it( 'allows it with consent, SureContact open and an address', () => {
		expect( canCreateAccount( ok ) ).toBe( true );
	} );

	// Vansh's review: tick, click, then untick or switch while SureMail installs.
	it( 'refuses once consent is withdrawn', () => {
		expect( canCreateAccount( { ...ok, consented: false } ) ).toBe( false );
	} );

	it( 'refuses once another option is open', () => {
		expect( canCreateAccount( { ...ok, option: 'other_smtp' } ) ).toBe(
			false
		);
	} );

	it( 'refuses without an address', () => {
		expect( canCreateAccount( { ...ok, email: '' } ) ).toBe( false );
	} );
} );

describe( 'getSureContactSignInUrl', () => {
	it( 'opens SureMail on its SureContact screen', () => {
		expect(
			getSureContactSignInUrl( {
				action: {
					url: 'https://x.test/wp-admin/options-general.php?page=suremail#/onboarding/connection',
				},
			} )
		).toBe(
			'https://x.test/wp-admin/options-general.php?page=suremail#/onboarding/connect'
		);
	} );

	it( 'falls back to the setup link and adds the screen', () => {
		expect(
			getSureContactSignInUrl( {
				links: { setup: 'https://x.test/wp-admin/admin.php?page=suremail' },
			} )
		).toBe( 'https://x.test/wp-admin/admin.php?page=suremail#/onboarding/connect' );
	} );

	it( 'returns nothing without a SureMail link', () => {
		expect( getSureContactSignInUrl( null ) ).toBe( '' );
	} );
} );

describe( 'isRateLimited', () => {
	it( 'spots an HTTP 429 from SureContact', () => {
		expect( isRateLimited( { data: { status: 429 } } ) ).toBe( true );
	} );

	it( 'spots a "too many requests" message or code', () => {
		expect(
			isRateLimited( {
				message: 'Too many provisioning requests. Please wait a few minutes and try again.',
			} )
		).toBe( true );
		expect( isRateLimited( { code: 'rate_limited' } ) ).toBe( true );
	} );

	it( 'leaves other failures alone', () => {
		expect(
			isRateLimited( { data: { status: 500 }, message: 'Server error' } )
		).toBe( false );
		expect( isRateLimited( { reason: 'email_already_registered' } ) ).toBe(
			false
		);
		expect( isRateLimited( null ) ).toBe( false );
	} );
} );
