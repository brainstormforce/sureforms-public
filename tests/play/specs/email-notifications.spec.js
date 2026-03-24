/**
 * E2E tests — Email notifications (P0).
 *
 * These tests require a mail-capture service (e.g. MailHog) running alongside
 * wp-env so that sent emails can be intercepted and inspected.
 *
 * HOW TO ENABLE:
 *   1. Add MailHog to your wp-env Docker setup:
 *      - Set `WORDPRESS_SMTP_HOST=mailhog` in .wp-env.json env config
 *      - Install WP Mail SMTP or similar to point WordPress at MailHog
 *   2. Set env var:  MAILHOG_URL=http://localhost:8025
 *   3. Remove the `test.skip` calls below.
 *
 * Until MailHog is wired in, the tests are skipped to avoid false failures.
 */

const { test, expect } = require( '@playwright/test' );
const { loginAsAdmin } = require( '../utils/loginAsAdmin' );
const {
	createBlankForm,
	addFieldBlock,
	publishFormAndGetURL,
} = require( '../utils/formHelpers' );

const MAILHOG_URL = process.env.MAILHOG_URL;
const skipReason = 'Requires MailHog mail capture service. Set MAILHOG_URL env var to enable.';

/**
 * Fetch the most recent email from MailHog.
 *
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<{subject: string, body: string, to: string}>}
 */
async function getLatestEmail( page ) {
	const response = await page.request.get( `${ MAILHOG_URL }/api/v2/messages?limit=1` );
	const data = await response.json();
	const latest = data.items?.[ 0 ];
	if ( ! latest ) {
		throw new Error( 'No emails found in MailHog' );
	}
	return {
		subject: latest.Content?.Headers?.Subject?.[ 0 ] ?? '',
		body: latest.Content?.Body ?? '',
		to: latest.Content?.Headers?.To?.[ 0 ] ?? '',
	};
}

test.describe( 'Email notifications', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	// ── 6.1 Admin notification sent after submission ──────────────────────────
	test( 'admin notification email is sent after form submission', async ( { page } ) => {
		test.skip( ! MAILHOG_URL, skipReason );

		await createBlankForm( page );
		await addFieldBlock( page, 'input' );

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		await page.locator( 'input.srfm-input-input' ).first().fill( 'Email trigger test' );
		await page.locator( '#srfm-submit-btn' ).click();
		await expect( page.locator( '.srfm-success-box' ) ).toBeVisible( { timeout: 15000 } );

		// Give WordPress a moment to send the email.
		await page.waitForTimeout( 2000 );

		const email = await getLatestEmail( page );
		expect( email.subject ).toBeTruthy();
		expect( email.body ).toContain( 'Email trigger test' );
	} );

	// ── 6.2 Email contains submitted field values ─────────────────────────────
	test( 'notification email body contains submitted field values', async ( { page } ) => {
		test.skip( ! MAILHOG_URL, skipReason );

		await createBlankForm( page );
		await addFieldBlock( page, 'input' );
		await addFieldBlock( page, 'email' );

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		const uniqueText = `EmailTest-${ Date.now() }`;
		const submittedEmail = `verify-${ Date.now() }@example.com`;

		await page.locator( 'input.srfm-input-input' ).first().fill( uniqueText );
		await page.locator( 'input.srfm-input-email' ).first().fill( submittedEmail );
		await page.locator( '#srfm-submit-btn' ).click();
		await expect( page.locator( '.srfm-success-box' ) ).toBeVisible( { timeout: 15000 } );

		await page.waitForTimeout( 2000 );

		const email = await getLatestEmail( page );
		expect( email.body ).toContain( uniqueText );
		expect( email.body ).toContain( submittedEmail );
	} );
} );
