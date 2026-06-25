# Changelog

Recent changes to SureForms. For the full changelog, see [sureforms.com/whats-new](https://sureforms.com/whats-new/).

## 2.5.1 - 16th February 2026

- **New:** Added option to change currency position (e.g., $100, 100$, $ 100, 100 $)
- **Fix:** Fixed the payment block amount not updating correctly with conditional logic

## 2.5.0 - 2nd February 2026

- **Improvement:** Added translation support for the country list in the phone number field
- **Fix:** Fixed dropdown placeholder and GDPR label translation on the frontend

## 2.4.0 - 20th January 2026

- **New:** Added form scheduling restrictions with start and end date/time
- **New:** Added Previous/Next navigation for single entry page
- **Improvement:** Improved multi-choice block settings UI
- **Improvement:** Updated Syria flag in the Phone field
- **Fix:** Fixed HTML block content corruption when duplicating forms
- **Fix:** Fixed multi-choice options being translated in entries
- **Fix:** Fixed reCAPTCHA v3 validation triggering multiple times on the same page
- **Fix:** Fixed Spectra button styles being affected by SureForms CSS

## Version Bumping

When preparing a release, update the version in:

1. `sureforms.php` -- Plugin header `Version:` and `SRFM_VER` constant
2. `readme.txt` -- `Stable tag:` and changelog section
3. `package.json` -- `version` field

See [Deployment Guide](Deployment-Guide) for the full release process.

## Related Pages

- [Deployment Guide](Deployment-Guide) -- Release workflow and CI/CD
- [Contributing Guide](Contributing-Guide) -- Development workflow
