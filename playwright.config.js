// @ts-check
const { defineConfig, devices } = require( '@playwright/test' );

/**
 * Read environment variables from file.
 * https://github.com/motdotla/dotenv
 */
// require('dotenv').config();

/**
 * @see https://playwright.dev/docs/test-configuration
 */
module.exports = defineConfig( {
	testDir: './tests/play/specs',
	// Log in once before any worker starts; all workers reuse the saved cookies.
	globalSetup: './tests/play/global-setup.js',
	/* Fail the build on CI if you accidentally left test.only in the source code. */
	forbidOnly: !! process.env.CI,
	// Each test creates its own isolated form — safe to run in parallel.
	// CI: 2 workers (constrained by Docker resources on ubuntu-latest).
	// Local: 4 workers for faster feedback.
	fullyParallel: true,
	workers: process.env.CI ? 2 : 4,
	/* Retry on CI only */
	retries: process.env.CI ? 2 : 0,
	/* Maximum time one test can run for. */
	timeout: 170 * 1000,
	/* Reporter to use. See https://playwright.dev/docs/test-reporters */
	reporter: [ [ 'html', { open: 'never' } ], [ 'line' ] ],
	/* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
	expect: {
		/**
		 * Maximum time expect() should wait for the condition to be met.
		 * For example in `await expect(locator).toHaveText();`
		 */
		timeout: 10000,
	},
	// Suppress slow-test warnings — tests are intentionally long (WP env interactions).
	reportSlowTests: null,
	outputDir: 'tests/play/test-results/',
	use: {
		/* Maximum time each action such as `click()` can take. Defaults to 0 (no limit). */
		actionTimeout: 0,
		/* Base URL to use in actions like `await page.goto('/')`. */

		/* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
		baseURL: 'http://localhost:8888/',
		headless: true,
		ignoreHTTPSErrors: true,
		browserName: 'chromium',
		// Restore the shared auth state logged in during globalSetup.
		storageState: 'tests/play/storageState.json',
	},
	/* Configure projects for major browsers */
	projects: [
		{
			name: 'chromium',
			use: { ...devices[ 'Desktop Chrome' ] },
			grepInvert: /-chromium/,
		},
	],
} );
