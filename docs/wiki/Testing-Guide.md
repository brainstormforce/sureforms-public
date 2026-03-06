# Testing Guide

SureForms uses three testing frameworks: PHPUnit for PHP unit tests, Playwright for end-to-end tests, and Jest for JavaScript unit tests.

## Testing Strategy

| Level | Framework | Location | Purpose |
|-------|-----------|----------|---------|
| Unit (PHP) | PHPUnit 9.x | `tests/unit/` | PHP class and function testing |
| Unit (JS) | Jest | Via `wp-scripts` | JavaScript utility testing |
| E2E | Playwright 1.48+ | `tests/play/` | Full browser-based workflow testing |

## PHPUnit (PHP Unit Tests)

### Setup

1. Install PHP dependencies:
   ```bash
   composer install
   ```

2. WordPress test environment must be available (via wp-env or local setup)

### Running Tests

```bash
# Run all PHP unit tests
vendor/bin/phpunit

# Run a specific test file
vendor/bin/phpunit tests/unit/test-specific.php

# Run tests with verbose output
vendor/bin/phpunit --verbose
```

### Test File Conventions

- Location: `tests/unit/`
- File prefix: `test-` (e.g., `test-helper.php`)
- Class naming: `Test_ClassName` extending `WP_UnitTestCase`
- Method naming: `test_` prefix (e.g., `test_form_submission()`)

### CI Configuration

PHPUnit runs in GitHub Actions (`.github/workflows/phpunit.yml`):
- PHP: 8.2
- WordPress: trunk
- MySQL: 5.7
- Triggered on pull requests

## Playwright (E2E Tests)

### Prerequisites

- Node.js 18.15.0 (Volta)
- Docker (for wp-env)

### Setup

```bash
# Install dependencies
npm install

# Start the WordPress environment
npm run play:up

# Build the plugin
npm run build

# Install Playwright browsers
npx playwright install
```

### Running Tests

```bash
# Run all E2E tests (headless)
npm run play:run

# Run tests in headed mode (visible browser)
npm run play:run:interactive

# Run a specific test file
npx playwright test tests/play/specific-test.spec.js
```

### Test File Conventions

- Location: `tests/play/`
- File suffix: `.spec.js`
- Uses Playwright Test runner with `expect` assertions
- Page Object Model pattern for reusable interactions

### CI Configuration

Playwright runs in GitHub Actions (`.github/workflows/playwright.yml`):
- Triggered when PR is labeled with `e2e`
- Node: 18.15
- Builds plugin and starts wp-env
- Generates HTML test report (30-day artifact retention)

## Jest (JavaScript Unit Tests)

### Running Tests

```bash
npm run test:unit
```

Uses `@wordpress/scripts` Jest configuration with WordPress-specific transforms and module resolution.

## Test Environment (wp-env)

The Docker-based test environment is configured in `.wp-env.json`:

```bash
# Start environment
npm run play:up

# Stop environment
npm run play:stop

# Clean all data
npm run env:clean
```

wp-env provides:
- WordPress installation with the plugin active
- MySQL database
- Consistent environment across developers
- Used by both Playwright E2E tests and local development

## Writing New Tests

### PHP Unit Test

```php
class Test_My_Feature extends WP_UnitTestCase {
    public function test_feature_works() {
        // Arrange
        $form_id = $this->factory->post->create([
            'post_type' => 'sureforms_form',
        ]);

        // Act
        $result = some_function($form_id);

        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

### Playwright E2E Test

```javascript
const { test, expect } = require('@playwright/test');

test('form submission works', async ({ page }) => {
    await page.goto('/form-page/');
    await page.fill('[name="email"]', 'test@example.com');
    await page.click('button[type="submit"]');
    await expect(page.locator('.success-message')).toBeVisible();
});
```

## Related Pages

- [Environment Configuration](Environment-Configuration) -- Development setup
- [Contributing Guide](Contributing-Guide) -- Test requirements for PRs
- [Deployment Guide](Deployment-Guide) -- CI/CD test pipeline
