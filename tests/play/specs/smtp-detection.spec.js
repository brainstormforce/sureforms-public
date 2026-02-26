/**
 * SMTP Detection Feature Tests
 * 
 * Test suite for the SMTP detection functionality introduced in the smtp-detection branch.
 * This tests the is_any_smtp_plugin_active() function and the admin notice behavior.
 * 
 * @since 1.9.1
 */

const { test, expect } = require('@playwright/test');
const { loginAsAdmin } = require('../utils/loginAsAdmin');

// Test configuration - using relative URLs like other tests
const ADMIN_URL = '/wp-admin/';
const PLUGINS_URL = '/wp-admin/plugins.php';
const SUREFORMS_URL = '/wp-admin/admin.php?page=sureforms_menu';
const DASHBOARD_URL = '/wp-admin/index.php';

// Known SMTP plugins to test
const SMTP_PLUGINS = [
	{ slug: 'wp-mail-smtp', name: 'WP Mail SMTP' },
	{ slug: 'post-smtp', name: 'Post SMTP' },
	{ slug: 'easy-wp-smtp', name: 'Easy WP SMTP' },
	{ slug: 'wp-smtp', name: 'WP-SMTP' },
	{ slug: 'fluent-smtp', name: 'FluentSMTP' },
	{ slug: 'smtp-mailer', name: 'SMTP Mailer' },
	{ slug: 'suremails', name: 'SureMails' }
];

test.describe('SMTP Detection Feature', () => {
	test.beforeEach(async ({ page }) => {
		await loginAsAdmin(page);
	});

	// Helper function to activate a plugin
	async function activatePlugin(page, pluginSlug) {
		await page.goto(PLUGINS_URL);
		const pluginRow = page.locator(`tr[data-slug="${pluginSlug}"]`);
		
		if (await pluginRow.count() === 0) {
			console.log(`Plugin ${pluginSlug} not found`);
			return false;
		}
		
		const activateLink = pluginRow.locator('a:has-text("Activate")');
		if (await activateLink.count() > 0) {
			await activateLink.click();
			await page.waitForLoadState('networkidle');
			return true;
		}
		
		return false; // Already active
	}

	// Helper function to deactivate a plugin
	async function deactivatePlugin(page, pluginSlug) {
		await page.goto(PLUGINS_URL);
		const pluginRow = page.locator(`tr[data-slug="${pluginSlug}"]`);
		
		if (await pluginRow.count() === 0) {
			return false;
		}
		
		const deactivateLink = pluginRow.locator('a:has-text("Deactivate")');
		if (await deactivateLink.count() > 0) {
			await deactivateLink.click();
			await page.waitForLoadState('networkidle');
			return true;
		}
		
		return false;
	}

	// Helper function to check if SMTP warning notice is visible
	async function isSmtpWarningVisible(page) {
		const warning = page.locator('.notice.srfm-smtp-warning');
		return await warning.isVisible();
	}

	// Helper function to dismiss SMTP warning
	async function dismissSmtpWarning(page) {
		const dismissLink = page.locator('.srfm-smtp-warning a:has-text("Dismiss")');
		if (await dismissLink.isVisible()) {
			await dismissLink.click();
			await page.waitForTimeout(1000);
		}
	}

	// Setup before all tests  
	test.beforeAll(async ({ browser }) => {
		const context = await browser.newContext();
		const page = await context.newPage();
		await loginAsAdmin(page);
		
		// Deactivate all SMTP plugins to start with clean state
		for (const plugin of SMTP_PLUGINS) {
			await deactivatePlugin(page, plugin.slug);
		}
		
		await context.close();
	});

	test('Should show SMTP warning when no SMTP plugin is active', async ({ page }) => {
		
		// Navigate to SureForms dashboard
		await page.goto(SUREFORMS_URL);
		
		// Check if warning is visible
		const warningVisible = await isSmtpWarningVisible(page);
		expect(warningVisible).toBeTruthy();
		
		// Verify warning message content
		const warningText = await page.locator('.srfm-smtp-warning').textContent();
		expect(warningText).toContain('We were not able to detect any SMTP plugin activated');
		expect(warningText).toContain('SureMail');
	});

	test('Should NOT show SMTP warning when SureMails is active', async ({ page }) => {
		
		// Activate SureMails if available
		const activated = await activatePlugin(page, 'suremails');
		
		if (activated) {
			// Navigate to SureForms dashboard
			await page.goto(SUREFORMS_URL);
			
			// Check that warning is NOT visible
			const warningVisible = await isSmtpWarningVisible(page);
			expect(warningVisible).toBeFalsy();
			
			// Cleanup - deactivate the plugin
			await deactivatePlugin(page, 'suremails');
		} else {
			console.log('SureMails plugin not available for testing');
			test.skip();
		}
	});

	test('Should NOT show SMTP warning when WP Mail SMTP is active', async ({ page }) => {
		
		// Activate WP Mail SMTP if available
		const activated = await activatePlugin(page, 'wp-mail-smtp');
		
		if (activated) {
			// Navigate to SureForms dashboard
			await page.goto(SUREFORMS_URL);
			
			// Check that warning is NOT visible
			const warningVisible = await isSmtpWarningVisible(page);
			expect(warningVisible).toBeFalsy();
			
			// Cleanup - deactivate the plugin
			await deactivatePlugin(page, 'wp-mail-smtp');
		} else {
			console.log('WP Mail SMTP plugin not available for testing');
			test.skip();
		}
	});

	test('Should detect multiple SMTP plugins correctly', async ({ page }) => {
		
		// Track which plugins we've activated
		const activatedPlugins = [];
		
		// Try to activate at least 2 SMTP plugins
		for (const plugin of SMTP_PLUGINS) {
			if (await activatePlugin(page, plugin.slug)) {
				activatedPlugins.push(plugin.slug);
				if (activatedPlugins.length >= 2) break;
			}
		}
		
		if (activatedPlugins.length >= 2) {
			// Navigate to SureForms dashboard
			await page.goto(SUREFORMS_URL);
			
			// Check that warning is NOT visible (multiple SMTP plugins active)
			const warningVisible = await isSmtpWarningVisible(page);
			expect(warningVisible).toBeFalsy();
			
			// Cleanup - deactivate all activated plugins
			for (const pluginSlug of activatedPlugins) {
				await deactivatePlugin(page, pluginSlug);
			}
		} else {
			console.log('Not enough SMTP plugins available for multi-plugin test');
			test.skip();
		}
	});

	test('Warning should be dismissible and remember dismissal', async ({ page }) => {
		
		// Ensure no SMTP plugins are active
		for (const plugin of SMTP_PLUGINS) {
			await deactivatePlugin(page, plugin.slug);
		}
		
		// Navigate to SureForms dashboard
		await page.goto(SUREFORMS_URL);
		
		// Check if warning is visible
		let warningVisible = await isSmtpWarningVisible(page);
		expect(warningVisible).toBeTruthy();
		
		// Dismiss the warning
		await dismissSmtpWarning(page);
		
		// Check that warning is no longer visible
		warningVisible = await isSmtpWarningVisible(page);
		expect(warningVisible).toBeFalsy();
		
		// Refresh the page and verify warning stays dismissed
		await page.reload();
		warningVisible = await isSmtpWarningVisible(page);
		expect(warningVisible).toBeFalsy();
		
		// Navigate to WordPress dashboard and check there too
		await page.goto(DASHBOARD_URL);
		warningVisible = await isSmtpWarningVisible(page);
		expect(warningVisible).toBeFalsy();
	});

	test('Should show correct SureMail link based on plugin status', async ({ page }) => {
		
		// Ensure no SMTP plugins are active
		for (const plugin of SMTP_PLUGINS) {
			await deactivatePlugin(page, plugin.slug);
		}
		
		// Navigate to SureForms dashboard
		await page.goto(SUREFORMS_URL);
		
		// Check the SureMail link in the warning
		const suremailLink = page.locator('.srfm-smtp-warning a[href*="suremail"]');
		const linkHref = await suremailLink.getAttribute('href');
		
		// Check if SureMails is installed
		await page.goto(PLUGINS_URL);
		const suremailsRow = page.locator('tr[data-slug="suremails"]');
		const isInstalled = await suremailsRow.count() > 0;
		
		if (isInstalled) {
			// If installed, link should be to activate
			expect(linkHref).toContain('action=activate');
		} else {
			// If not installed, link should be to plugin search
			expect(linkHref).toContain('plugin-install.php');
			expect(linkHref).toContain('suremail');
		}
	});

	test('Warning should appear on dashboard and SureForms pages only', async ({ page }) => {
		
		// Ensure no SMTP plugins are active
		for (const plugin of SMTP_PLUGINS) {
			await deactivatePlugin(page, plugin.slug);
		}
		
		// Test pages where warning SHOULD appear
		const pagesWithWarning = [
			DASHBOARD_URL,
			SUREFORMS_URL,
			ADMIN_URL + 'admin.php?page=sureforms_form_settings',
			ADMIN_URL + 'admin.php?page=sureforms_entries'
		];
		
		for (const pageUrl of pagesWithWarning) {
			await page.goto(pageUrl);
			const warningVisible = await isSmtpWarningVisible(page);
			expect(warningVisible).toBeTruthy();
			
			// Dismiss to avoid interference with next test
			await dismissSmtpWarning(page);
		}
		
		// Test pages where warning should NOT appear
		const pagesWithoutWarning = [
			ADMIN_URL + 'options-general.php',
			ADMIN_URL + 'users.php',
			ADMIN_URL + 'tools.php'
		];
		
		for (const pageUrl of pagesWithoutWarning) {
			await page.goto(pageUrl);
			const warningVisible = await isSmtpWarningVisible(page);
			expect(warningVisible).toBeFalsy();
		}
	});

	test('Multisite support - should detect network-activated SMTP plugins', async ({ page }) => {
		// This test requires multisite setup
		// Skip if not in multisite environment
		await page.goto(ADMIN_URL);
		
		// Check if this is a multisite installation
		const networkAdminLink = page.locator('a[href*="network/admin.php"]');
		const isMultisite = await networkAdminLink.count() > 0;
		
		if (!isMultisite) {
			console.log('Not a multisite installation, skipping multisite test');
			test.skip();
			return;
		}
		
		// Navigate to network admin
		await networkAdminLink.click();
		await page.waitForLoadState('networkidle');
		
		// Go to network plugins page
		await page.goto(ADMIN_URL + 'network/plugins.php');
		
		// Try to network activate an SMTP plugin
		const networkActivateLink = page.locator('a:has-text("Network Activate")').first();
		if (await networkActivateLink.count() > 0) {
			await networkActivateLink.click();
			await page.waitForLoadState('networkidle');
			
			// Go back to regular admin and check if warning is hidden
			await page.goto(SUREFORMS_URL);
			const warningVisible = await isSmtpWarningVisible(page);
			expect(warningVisible).toBeFalsy();
		}
	});

	test('Performance test - SMTP detection should not slow down page load', async ({ page }) => {
		
		// Measure page load time with SMTP detection
		const startTime = Date.now();
		await page.goto(SUREFORMS_URL);
		const loadTime = Date.now() - startTime;
		
		// Page should load within reasonable time (5 seconds)
		expect(loadTime).toBeLessThan(5000);
		
		console.log(`Page load time with SMTP detection: ${loadTime}ms`);
	});
});

// Additional test for wp-smtp specific issue mentioned by user
test.describe('WP-SMTP Plugin Specific Tests', () => {
	test.beforeEach(async ({ page }) => {
		await loginAsAdmin(page);
	});
	// Helper function to activate a plugin
	async function activatePlugin(page, pluginSlug) {
		await page.goto('/wp-admin/plugins.php');
		const pluginRow = page.locator(`tr[data-slug="${pluginSlug}"]`);
		
		if (await pluginRow.count() === 0) {
			return false;
		}
		
		const activateLink = pluginRow.locator('a:has-text("Activate")');
		if (await activateLink.count() > 0) {
			await activateLink.click();
			await page.waitForLoadState('networkidle');
			return true;
		}
		
		return false;
	}

	// Helper function to deactivate a plugin
	async function deactivatePlugin(page, pluginSlug) {
		await page.goto('/wp-admin/plugins.php');
		const pluginRow = page.locator(`tr[data-slug="${pluginSlug}"]`);
		
		if (await pluginRow.count() === 0) {
			return false;
		}
		
		const deactivateLink = pluginRow.locator('a:has-text("Deactivate")');
		if (await deactivateLink.count() > 0) {
			await deactivateLink.click();
			await page.waitForLoadState('networkidle');
			return true;
		}
		
		return false;
	}

	// Helper function to check if SMTP warning notice is visible
	async function isSmtpWarningVisible(page) {
		const warning = page.locator('.notice.srfm-smtp-warning');
		return await warning.isVisible();
	}

	test('Should correctly detect wp-smtp plugin', async ({ page }) => {
		
		// Check if wp-smtp is installed
		await page.goto('/wp-admin/plugins.php');
		const wpSmtpRow = page.locator('tr[data-slug="wp-smtp"]');
		
		if (await wpSmtpRow.count() === 0) {
			console.log('wp-smtp plugin not installed, skipping test');
			test.skip();
			return;
		}
		
		// Deactivate all SMTP plugins first
		const smtpPlugins = ['wp-mail-smtp', 'post-smtp', 'easy-wp-smtp', 'wp-smtp', 'fluent-smtp', 'smtp-mailer', 'suremails'];
		for (const pluginSlug of smtpPlugins) {
			await deactivatePlugin(page, pluginSlug);
		}
		
		// Activate wp-smtp specifically
		await activatePlugin(page, 'wp-smtp');
		
		// Navigate to SureForms and verify no warning
		await page.goto('/wp-admin/admin.php?page=sureforms_menu');
		const warningVisible = await isSmtpWarningVisible(page);
		expect(warningVisible).toBeFalsy();
		
		// Cleanup
		await deactivatePlugin(page, 'wp-smtp');
	});
});