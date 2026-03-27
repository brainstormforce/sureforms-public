/**
 * Playwright global setup — log in once and save auth cookies.
 *
 * Runs once before any test worker starts. Saves the authenticated browser
 * storage state to a JSON file that every worker restores via storageState in
 * playwright.config.js, eliminating the need for each worker to log in via
 * the form (which causes WordPress nonce collisions under parallel execution).
 */

const { chromium } = require( '@playwright/test' );

const WP_ADMIN_USER = process.env.WP_ADMIN_USER || 'admin';
const WP_ADMIN_PASS = process.env.WP_ADMIN_PASS || 'password';
const BASE_URL = 'http://localhost:8888';

module.exports = async function globalSetup() {
	const browser = await chromium.launch();
	const context = await browser.newContext( {
		baseURL: BASE_URL + '/',
		ignoreHTTPSErrors: true,
	} );
	const page = await context.newPage();

	await page.goto( BASE_URL + '/wp-login.php' );
	await page.fill( '#user_login', WP_ADMIN_USER );
	await page.fill( '#user_pass', WP_ADMIN_PASS );

	await page.click( '#wp-submit' );
	await page.waitForLoadState( 'domcontentloaded' );

	// Handle WordPress interstitial screens that can appear after login
	// (e.g. "Database Update Required", "Confirm admin email").
	if ( ! page.url().includes( '/wp-admin' ) ) {
		await page.goto( BASE_URL + '/wp-admin/' );
		await page.waitForLoadState( 'domcontentloaded' );
	}

	// Save cookies + localStorage so every worker starts authenticated.
	await context.storageState( { path: 'tests/play/storageState.json' } );
	await browser.close();
};
