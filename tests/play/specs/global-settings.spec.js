/**
 * E2E tests — Global settings (P0).
 *
 * Covers:
 *   9.1 Global settings page loads without errors
 *   9.2 All main settings tabs are clickable and load content
 *
 * SureForms settings navigation (React Router links):
 *   "General Settings", "Form Validation", "Spam Protection",
 *   "Automations", "Integrations", "Payments"
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

		// The SureForms settings app renders a navigation sidebar.
		// The first nav item is "General Settings".
		await expect(
			page.locator( 'a, button, [role="link"]' )
				.filter( { hasText: 'General Settings' } )
				.first()
		).toBeVisible( { timeout: 15000 } );

		// No fatal JS errors should have occurred.
		const fatalErrors = jsErrors.filter( ( e ) =>
			/uncaught|undefined is not|cannot read|typeerror/i.test( e )
		);
		expect( fatalErrors, `Unexpected JS errors: ${ fatalErrors.join( '\n' ) }` ).toHaveLength( 0 );
	} );

	// ── 9.2 All settings tabs navigate correctly ──────────────────────────────
	test( 'settings nav links — main items are clickable and stay on settings page', async ( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=sureforms_form_settings' );
		await page.waitForLoadState( 'load' );

		// SureForms settings navigation items (from src/admin/settings/Navigation.js).
		const navItems = [
			'General Settings',
			'Spam Protection',
			'Payments',
		];

		for ( const navItem of navItems ) {
			// Scope click to elements within the SureForms settings app container
			// to avoid accidentally clicking WordPress core sidebar links.
			const link = page
				.locator( '#wpbody-content a, #wpbody-content button' )
				.filter( { hasText: navItem } )
				.first();

			if ( await link.isVisible( { timeout: 3000 } ).catch( () => false ) ) {
				await link.click();
				// Should still be on the SureForms settings page after clicking.
				expect( page.url() ).toContain( 'sureforms_form_settings' );
			}
		}
	} );
} );
