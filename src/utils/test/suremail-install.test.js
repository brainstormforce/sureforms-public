/* eslint-env jest */
/**
 * SureMail's install state goes stale on the page. After an install that
 * landed but failed to activate, installing again fails with "Destination
 * folder already exists", so the state is read again before each attempt.
 */
import { getSureMailPluginStatus, installSureMail } from '../suremail-install';

const statusWith = ( pluginStatus ) => ( {
	state: 'not_configured',
	suremail: { plugin_status: pluginStatus },
} );

describe( 'getSureMailPluginStatus', () => {
	it.each( [ 'Install', 'Installed', 'Activated' ] )(
		'uses the live state %s',
		( live ) => {
			expect(
				getSureMailPluginStatus( statusWith( live ), 'Install' )
			).toBe( live );
		}
	);

	it.each( [
		[ null ],
		[ {} ],
		[ statusWith( 'not_installed' ) ],
		[ statusWith( undefined ) ],
	] )( 'falls back when the response has no known state (%p)', ( status ) => {
		expect( getSureMailPluginStatus( status, 'Installed' ) ).toBe(
			'Installed'
		);
	} );
} );

describe( 'installSureMail', () => {
	const plugin = { slug: 'suremails', path: 'suremails/suremails.php' };

	it( 'does not install again when the page says not installed but SureMail is installed', async () => {
		const install = jest.fn().mockResolvedValue();
		const used = await installSureMail( {
			plugin: { ...plugin, status: 'Install' },
			readStatus: () => Promise.resolve( statusWith( 'Installed' ) ),
			install,
		} );

		expect( used ).toBe( 'Installed' );
		expect( install ).toHaveBeenCalledTimes( 1 );
		// 'Installed' sends only the activate request, never the install one.
		expect( install.mock.calls[ 0 ][ 0 ].status ).toBe( 'Installed' );
	} );

	it( 'installs when SureMail is really not there', async () => {
		const install = jest.fn().mockResolvedValue();
		await installSureMail( {
			plugin: { ...plugin, status: 'Installed' },
			readStatus: () => Promise.resolve( statusWith( 'Install' ) ),
			install,
		} );

		expect( install.mock.calls[ 0 ][ 0 ].status ).toBe( 'Install' );
	} );

	it( 'goes by the page-load state when the status cannot be read', async () => {
		const install = jest.fn().mockResolvedValue();
		await installSureMail( {
			plugin: { ...plugin, status: 'Install' },
			readStatus: () => Promise.reject( new Error( 'offline' ) ),
			install,
		} );

		expect( install.mock.calls[ 0 ][ 0 ].status ).toBe( 'Install' );
	} );

	it( 'passes the install error on', async () => {
		const install = jest
			.fn()
			.mockRejectedValue( new Error( 'Activation failed' ) );
		await expect(
			installSureMail( {
				plugin: { ...plugin, status: 'Install' },
				readStatus: () => Promise.resolve( statusWith( 'Installed' ) ),
				install,
			} )
		).rejects.toThrow( 'Activation failed' );
	} );
} );
