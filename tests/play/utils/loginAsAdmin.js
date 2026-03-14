const { expect } = require( '@playwright/test' );

const WP_ADMIN_USER = process.env.WP_ADMIN_USER || 'admin';
const WP_ADMIN_PASS = process.env.WP_ADMIN_PASS || 'password';

/**
 * Log in to WordPress as admin and wait until the dashboard is ready.
 *
 * @param {import('@playwright/test').Page} page
 */
async function loginAsAdmin( page ) {
	await page.goto( '/wp-login.php' );
	await page.fill( '#user_login', WP_ADMIN_USER );
	await page.fill( '#user_pass', WP_ADMIN_PASS );

	// Wait for navigation triggered by the login form submit.
	await Promise.all( [
		page.waitForURL( '**/wp-admin/**', { waitUntil: 'domcontentloaded' } ),
		page.click( '#wp-submit' ),
	] );

	// Confirm we actually landed in the dashboard (catches wrong-password failures).
	await expect(
		page.locator( '#wpadminbar' ),
		'Login failed — check WP_ADMIN_USER / WP_ADMIN_PASS'
	).toBeVisible( { timeout: 15000 } );
}

module.exports = { loginAsAdmin };
