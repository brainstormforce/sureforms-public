/**
 * E2E tests — Stripe payments (P0).
 *
 * These tests require Stripe test API keys.  They are automatically skipped
 * when the keys are not present so the CI suite still passes without them.
 *
 * HOW TO ENABLE:
 *   1. Add Stripe test keys as GitHub Actions secrets:
 *        STRIPE_TEST_PUBLIC_KEY   — pk_test_...
 *        STRIPE_TEST_SECRET_KEY   — sk_test_...
 *   2. Expose them as env vars in the workflow's "Run Playwright" step.
 *   3. Store them in global settings before the test run (see initial-setup.sh
 *      or add a wp-cli command to the setup script).
 *
 * Stripe test cards used:
 *   4242 4242 4242 4242 — Visa, always succeeds
 *   4000 0000 0000 0002 — Card declined
 */

const { test, expect } = require( '@playwright/test' );
const { loginAsAdmin } = require( '../utils/loginAsAdmin' );
const {
	createBlankForm,
	addFieldBlock,
	publishFormAndGetURL,
} = require( '../utils/formHelpers' );

const STRIPE_PUBLIC_KEY = process.env.STRIPE_TEST_PUBLIC_KEY;
const skipReason = 'Requires Stripe test keys. Set STRIPE_TEST_PUBLIC_KEY env var to enable.';

/**
 * Fill the Stripe card iframe with the given card details.
 *
 * Stripe embeds its card input inside an iframe; Playwright can access it
 * via frameLocator.
 *
 * @param {import('@playwright/test').Page} page
 * @param {object} card
 * @param {string} card.number
 * @param {string} card.expiry  MM/YY
 * @param {string} card.cvc
 */
async function fillStripeCard( page, { number = '4242424242424242', expiry = '12/34', cvc = '123' } = {} ) {
	const stripeFrame = page.frameLocator( 'iframe[name*="stripe"], iframe[src*="stripe"]' ).first();

	await stripeFrame.locator( 'input[name="cardnumber"], input[placeholder*="card" i]' )
		.fill( number );
	await stripeFrame.locator( 'input[name="exp-date"], input[placeholder*="MM" i]' )
		.fill( expiry );
	await stripeFrame.locator( 'input[name="cvc"], input[placeholder*="CVC" i]' )
		.fill( cvc );
}

test.describe( 'Stripe payments', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	// ── 8.1 Payment field renders ─────────────────────────────────────────────
	test( 'payment field renders the Stripe card widget', async ( { page } ) => {
		test.skip( ! STRIPE_PUBLIC_KEY, skipReason );

		await createBlankForm( page );
		await addFieldBlock( page, 'payment' );

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		// Stripe renders its widget inside an iframe.
		await expect(
			page.locator( 'iframe[name*="stripe"], iframe[src*="stripe"], .srfm-payment-block' ).first()
		).toBeVisible( { timeout: 15000 } );
	} );

	// ── 8.2 Valid test card — payment succeeds ────────────────────────────────
	test( 'valid Stripe test card — payment succeeds and success message shown', async ( { page } ) => {
		test.skip( ! STRIPE_PUBLIC_KEY, skipReason );

		await createBlankForm( page );
		await addFieldBlock( page, 'payment' );

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		await fillStripeCard( page, { number: '4242424242424242', expiry: '12/34', cvc: '123' } );
		await page.locator( '#srfm-submit-btn' ).click();

		await expect( page.locator( '.srfm-success-box' ) ).toBeVisible( { timeout: 30000 } );
		await expect( page.locator( '.srfm-success-box' ) ).toContainText( /Thank you/i );
	} );

	// ── 8.3 Declined test card — error shown ─────────────────────────────────
	test( 'declined Stripe test card — error message shown', async ( { page } ) => {
		test.skip( ! STRIPE_PUBLIC_KEY, skipReason );

		await createBlankForm( page );
		await addFieldBlock( page, 'payment' );

		const formURL = await publishFormAndGetURL( page );
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		// Card 4000000000000002 is always declined by Stripe test mode.
		await fillStripeCard( page, { number: '4000000000000002', expiry: '12/34', cvc: '123' } );
		await page.locator( '#srfm-submit-btn' ).click();

		await expect(
			page.locator( '.srfm-error-message, .srfm-payment-error, #srfm-error-message' ).first()
		).toBeVisible( { timeout: 20000 } );
		await expect( page.locator( '.srfm-success-box' ) ).not.toBeVisible();
	} );

	// ── 8.6 Payment entry created in admin after success ─────────────────────
	test( 'successful payment creates an entry in the admin entries list', async ( { page } ) => {
		test.skip( ! STRIPE_PUBLIC_KEY, skipReason );

		await createBlankForm( page );
		await addFieldBlock( page, 'payment' );

		const formURL = await publishFormAndGetURL( page );
		const editorURL = page.url();
		const formIdMatch = editorURL.match( /[?&]post=(\d+)/ );
		const formId = formIdMatch ? formIdMatch[ 1 ] : null;

		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		await fillStripeCard( page, { number: '4242424242424242', expiry: '12/34', cvc: '123' } );
		await page.locator( '#srfm-submit-btn' ).click();
		await expect( page.locator( '.srfm-success-box' ) ).toBeVisible( { timeout: 30000 } );

		// Navigate to entries and verify a new entry was created.
		const entriesURL = formId
			? `/wp-admin/admin.php?page=sureforms_entries#/?form=${ formId }`
			: '/wp-admin/admin.php?page=sureforms_entries';
		await page.goto( entriesURL );
		await page.waitForLoadState( 'load' );
		await page.waitForTimeout( 2500 );

		// At least one entry row should exist.
		await expect(
			page.locator( 'tr, [role="row"]' ).nth( 1 )
		).toBeVisible( { timeout: 10000 } );
	} );
} );
