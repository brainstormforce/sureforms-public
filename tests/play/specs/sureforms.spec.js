const { test, expect } = require( '@playwright/test' );
const { loginAsAdmin } = require( '../utils/loginAsAdmin' );

test.describe( 'Deactivate and activate the plugin', () => {
	test.beforeEach( async ( { page } ) => {
		// Use the imported login function
		await loginAsAdmin( page );
	} );

	test( 'deactivate the plugin', async ( { page } ) => {
		await page.goto( '/wp-admin/plugins.php' );

		// Deactivate plugin if it's already active
		const isPluginActive = await page.isVisible( '#deactivate-sureforms' );
		if ( isPluginActive ) {
			await page.click( '#deactivate-sureforms' );
			await page.waitForSelector( '#activate-sureforms', { timeout: 5000 } );
		}

		// Click the activate button and wait for navigation
		await Promise.all( [
			page.click( '#activate-sureforms', { timeout: 5000 } ),
			page.waitForNavigation( { waitUntil: 'networkidle' } ),
		] );

		// Check if the expected success message or element is visible after activation
		const isActive = await page.isVisible( `text="Get started with SureForms"` );
		expect( isActive ).toBeTruthy();
	} );
} );
