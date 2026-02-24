# SureForms Internal Documentation

**Version:** 2.5.0
**Last Updated:** February 2026
**Target Audience:** Developers, AI Agents, Technical Team

---

## Welcome

This is the internal documentation for **SureForms Free** and **SureForms Pro** - WordPress form builder plugins used by 300,000+ websites.

**SureForms Free:** AI-powered form builder with payments (Stripe), native WordPress blocks, 16+ fields
**SureForms Pro:** Advanced features - conversational forms, multi-step, conditional logic, PayPal, 24+ integrations

---

## Quick Start

### For New Developers (1 Hour)

1. **Install locally:**
   ```bash
   cd /path/to/wordpress/wp-content/plugins
   git clone https://github.com/brainstormforce/sureforms.git
   git clone https://github.com/brainstormforce/sureforms-pro.git
   cd sureforms && npm install && npm run build
   cd ../sureforms-pro && npm install && npm run build
   ```

2. **Activate plugins:**
   - SureForms Free (required)
   - SureForms Pro (optional, requires Free)

3. **Create first form:**
   - Dashboard → SureForms → Add New
   - Try AI form builder or blank form
   - Add fields from block inserter
   - Publish and test submission

4. **Read these docs:**
   - [Architecture](architecture.md) - System design
   - [Codebase Map](codebase-map.md) - Where is what
   - [APIs](apis.md) - REST, AJAX, hooks

### For AI Agents

Start with [ai-agent-guide.md](ai-agent-guide.md) for codebase conventions, patterns, and agent-specific guidance.

---

## Key Files & Directories

| Path | Purpose |
|------|---------|
| `sureforms/inc/` | Core PHP logic |
| `sureforms/inc/blocks/` | Gutenberg block PHP |
| `sureforms/src/` | React components (admin UI) |
| `sureforms/inc/payments/` | Stripe payment processing |
| `sureforms/inc/ai-form-builder/` | AI form generation |
| `sureforms-pro/inc/business/` | Pro features (PayPal, registration, PDF) |
| `sureforms-pro/inc/pro/native-integrations/` | 24+ service integrations |

---

## Build System

**SureForms Free:**
```bash
npm run build          # Full build (webpack + sass + grunt)
npm run start          # Dev mode (watch)
npm run lint-js:fix    # Fix JS linting
npm run makepot        # Generate translation files
```

**SureForms Pro:**
```bash
npm run build          # Full build
npm run package        # Create distributable zip
```

Both use:
- **@wordpress/scripts** (webpack 5, Babel, ESLint)
- **Grunt** (minification, compression)
- **Sass** for CSS compilation

---

## Database Tables

Custom tables (via `inc/database/`):

| Table | Purpose |
|-------|---------|
| `wp_sureforms_entries` | Form submissions |
| `wp_sureforms_payments` | Payment transactions (Stripe/PayPal) |
| `wp_sureforms_integrations` | Native integration credentials (encrypted) |
| `wp_sureforms_save_resume` | Draft form state (Pro) |

---

## Testing

**Unit Tests:**
```bash
composer test              # PHPUnit
npm run test:unit          # Jest (JS)
```

**E2E Tests:**
```bash
npm run play:up            # Start wp-env
npm run play:run           # Run Playwright tests
```

**Code Quality:**
```bash
composer lint              # PHP_CodeSniffer
composer phpstan           # Static analysis
npm run lint-js            # ESLint
```

---

## Release Process

**Free (WordPress.org):**
1. Update version in `sureforms.php` and `package.json`
2. Run `npm run build && grunt release`
3. Files excluded via `.distignore`
4. SVN commit to WordPress.org repo

**Pro (Custom Distribution):**
1. Update version in `sureforms-pro.php`
2. Run `npm run build && npm run package`
3. Generates `sureforms-pro.zip`
4. Deploy to licensing server

---

## Support & Resources

- **GitHub (Free):** https://github.com/brainstormforce/sureforms
- **GitHub (Pro):** https://github.com/brainstormforce/sureforms-pro
- **Documentation:** https://sureforms.com/docs/
- **Bug Bounty:** https://brainstormforce.com/bug-bounty-program/
- **Support:** support@brainstormforce.com

---

## Next Steps

- **Product Vision:** [product-vision.md](product-vision.md) - Understand goals and personas
- **Architecture:** [architecture.md](architecture.md) - System design and data flow
- **Onboarding:** [onboarding.md](onboarding.md) - 1-hour, 1-day, 1-week learning paths

---

**Maintained by:** SureForms Engineering Team
**Contact:** engineering@brainstormforce.com
