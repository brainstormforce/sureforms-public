# Security Auditor Memory — SureForms Plugin

## Confirmed False Positives

### Google Maps API Key in `data-api-key` attribute
- File: `inc/fields/address-markup.php:91`
- The Google Maps JavaScript API is architecturally required to expose its key client-side (Google's own documented requirement). Placing it in a `data-*` attribute vs. a hardcoded `<script src>` URL is functionally equivalent exposure.
- The editor-side localization (`inc/gutenberg-hooks.php:217`) deliberately exposes only a boolean (key present/absent), not the key itself — showing intentional hygiene.
- Key restriction (HTTP referrer allowlisting in Google Cloud Console) is the correct mitigation and is outside the plugin's control.
- DO NOT re-flag this as a vulnerability. It is an inherent design requirement, not a code defect.

## Project-Specific Patterns

- Google Maps settings stored in `srfm_google_maps_settings` WP option; key field is `srfm_google_maps_api_key`.
- Address autocomplete enabled per-block via `enableAutocomplete` block attribute.
- Frontend JS (`assets/js/unminified/blocks/address-autocomplete.js`) reads `data-api-key` from the DOM to lazily load the Maps API — this is by design.
- `inc/gutenberg-hooks.php` passes only `(bool)` of key presence to editor JS, not the key value itself.

## Vulnerability Patterns to Watch

- Any other credential type (Stripe secret keys, SMTP passwords, OAuth client secrets) exposed in HTML attributes would be a TRUE_POSITIVE — those are NOT required to be client-side.
- Watch for REST API routes that return full API key values to unauthenticated callers.
