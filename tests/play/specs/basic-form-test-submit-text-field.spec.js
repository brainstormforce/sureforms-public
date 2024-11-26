import { test, expect } from '@playwright/test';
const { loginAsAdmin } = require( '../utils/loginAsAdmin' );

test.beforeEach( async ( { page } ) => {
	// Use the imported login function
	await loginAsAdmin( page );
} );

test( 'create a form with field to check submit', async ( { page } ) => {
	await page.goto( '/wp-admin/admin.php?page=sureforms_menu' );
	await page.getByRole( 'button', { name: 'Create New Form →' } ).first().click();
	await page.getByRole( 'link', { name: 'Build From Scratch' } ).click();
	await page.locator( '#draggable-box__srfm--input div' ).first().click();
	//await page.getByRole('option', { name: 'Text Field', exact: true }).click();
	await page.getByText( 'Text FieldSubmit' ).click();
	await page.getByRole( 'button', { name: 'Publish', exact: true } ).click();
	await page.getByLabel( 'Editor publish' ).getByRole( 'button', { name: 'Publish', exact: true } ).click();
	await page.getByLabel( 'Editor publish' ).getByRole( 'link', { name: 'View Form' } ).click();
	await page.getByRole( 'button', { name: 'Submit' } ).click();
	await expect( page.getByRole( 'heading', { name: 'Thank you' } ) ).toBeVisible();
} );
