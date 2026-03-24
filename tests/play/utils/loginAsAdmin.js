const { expect } = require( '@playwright/test' );

const WP_ADMIN_USER = process.env.WP_ADMIN_USER || 'admin';
const WP_ADMIN_PASS = process.env.WP_ADMIN_PASS || 'password';

/**
 * Ensure the page is authenticated as admin.
 *
 * storageState (set in playwright.config.js) restores auth cookies before
 * each test, so this is normally a no-op navigation.  If cookies are stale
 * or absent the function falls back to a full form-based login.
 *
 * @param {import('@playwright/test').Page} page
 */
async function loginAsAdmin( page ) {
	// Navigate to admin only if we're not already there.
	if ( ! page.url().includes( '/wp-admin' ) ) {
		await page.goto( '/wp-admin/' );
		await page.waitForLoadState( 'load' );
	}

	// If WordPress redirected us to the login page the stored session has
	// expired — perform a fresh form-based login as a safety net.
	if ( page.url().includes( 'wp-login.php' ) ) {
		await page.fill( '#user_login', WP_ADMIN_USER );
		await page.fill( '#user_pass', WP_ADMIN_PASS );
		await page.click( '#wp-submit' );
		await page.waitForLoadState( 'domcontentloaded' );

		// Handle WordPress interstitial screens that can appear after login
		// (e.g. "Database Update Required", "Confirm admin email").
		if ( ! page.url().includes( '/wp-admin' ) ) {
			await page.goto( '/wp-admin/' );
			await page.waitForLoadState( 'domcontentloaded' );
		}
	}

	await expect(
		page.locator( '#wpadminbar' ),
		'Login failed — check WP_ADMIN_USER / WP_ADMIN_PASS'
	).toBeVisible( { timeout: 15000 } );
}

module.exports = { loginAsAdmin };
