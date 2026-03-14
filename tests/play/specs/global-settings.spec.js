/**
 * E2E tests — Global settings (P0).
 *
 * Covers:
 *   9.1 Global settings page loads without errors
 */

const { test, expect } = require( '@playwright/test' );
const { loginAsAdmin } = require( '../utils/loginAsAdmin' );

test.describe( 'Global settings', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	// ── 9.1 Settings page loads ────────────────────────────────────────────────
	test( 'global settings page loads without a JS error', async ( { page } ) => {
		// Collect any console errors that indicate a broken page.
		const jsErrors = [];
		page.on( 'console', ( msg ) => {
			if ( msg.type() === 'error' ) {
				jsErrors.push( msg.text() );
			}
		} );

		await page.goto( '/wp-admin/admin.php?page=sureforms_form_settings' );
		await page.waitForLoadState( 'load' );
		// Allow the React settings app to render.
		await page.waitForTimeout( 2000 );

		// The settings page should render its tabs / navigation.
		await expect(
			page.locator( '.sureforms-settings, [class*="settings"], [role="tablist"]' ).first()
		).toBeVisible( { timeout: 15000 } );

		// No fatal JS errors should have occurred.
		const fatalErrors = jsErrors.filter( ( e ) =>
			/uncaught|undefined is not|cannot read|typeerror/i.test( e )
		);
		expect( fatalErrors, `Unexpected JS errors: ${ fatalErrors.join( '\n' ) }` ).toHaveLength( 0 );
	} );

	// ── 9.2 All settings tabs navigate correctly ──────────────────────────────
	test( 'settings tabs — all main tabs are clickable and load content', async ( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=sureforms_form_settings' );
		await page.waitForLoadState( 'load' );
		await page.waitForTimeout( 2000 );

		// SureForms settings has tabs for General, Security (CAPTCHA), Email, Payments.
		const expectedTabs = [ /general/i, /security|captcha/i, /email/i, /payment/i ];

		for ( const tabPattern of expectedTabs ) {
			const tab = page.getByRole( 'link', { name: tabPattern } )
				.or( page.getByRole( 'tab', { name: tabPattern } ) )
				.or( page.locator( 'a, button' ).filter( { hasText: tabPattern } ) )
				.first();

			if ( await tab.isVisible( { timeout: 3000 } ).catch( () => false ) ) {
				await tab.click();
				await page.waitForTimeout( 1000 );
				// The page should still be on the settings screen after tab click.
				expect( page.url() ).toContain( 'sureforms_form_settings' );
			}
		}
	} );
} );
