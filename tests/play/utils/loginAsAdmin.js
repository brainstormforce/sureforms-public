// config.spec.js
const { test, expect} = require( '@playwright/test' );
const { exec } = require('child_process');

// Export a function that logs in as an admin
async function loginAsAdmin( page ) {
	await page.goto( '/wp-login.php' );
	await page.fill( '#user_login', 'admin' );
	await page.fill( '#user_pass', 'password' );
	await page.click( '#wp-submit' );
	await page.goto( '/wp-admin' );
}
// Helper function to run shell commands
function runCommand(command) {
  return new Promise((resolve, reject) => {
    exec(command, (error, stdout, stderr) => {
      if (error) {
        reject(`Error: ${error.message}`);
      }
      if (stderr) {
        reject(`stderr: ${stderr}`);
      }
      resolve(stdout);
    });
  });
}

// Playwright test case
test.describe('WordPress Permalink Structure', () => {
  test('should change permalink structure to post name', async ({ page }) => {
    // Command to update permalink structure
    const updatePermalinkCommand = `wp option update permalink_structure '/%postname%/'`;
    const flushPermalinkCommand = `wp rewrite flush`;

    try {
      // Run WP-CLI command to update permalink structure
      await runCommand(updatePermalinkCommand);
      console.log('Permalink structure updated to /%postname%/');

      // Flush rewrite rules
      await runCommand(flushPermalinkCommand);
      console.log('Rewrite rules flushed');
    } catch (error) {
      console.error(`Failed to update permalink structure: ${error}`);
      throw new Error('Permalink update test failed');
    }

    // Add your Playwright assertions or further steps here if needed
  });
});

test.afterEach( async ( { page } ) => {
	// Close the page after the test completes
	await page.close();
} );

// Export the test object as well for easy use in other files
module.exports = {
	loginAsAdmin,
	test,
};
