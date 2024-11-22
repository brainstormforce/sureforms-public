// config.spec.js
const { test } = require( '@playwright/test' );

// Export a function that logs in as an admin
async function loginAsAdmin( page ) {
	await page.goto( '/wp-login.php' );
	await page.fill( '#user_login', 'admin' );
	await page.fill( '#user_pass', 'password' );
	await page.click( '#wp-submit' );
	await page.goto( '/wp-admin' );
}
test.afterEach( async ( { page } ) => {
	// Close the page after the test completes
	await page.close();
} );

// Export the test object as well for easy use in other files
module.exports = {
	loginAsAdmin,
	test,
};
