# SureForms — Form Migration (Overview)

> Looking for the full technical plan? See `FORM_MIGRATION_PLAN.md` in this folder.

## What this feature does

Lets users move their existing forms into SureForms from the other popular form-builder plugins, in one click — no copy-pasting, no rebuilding.

## Which plugins we'll support

| Source plugin | Approx. user base | Status |
|---|---|---|
| Contact Form 7 | 10M+ sites | **Available in v1** |
| WPForms | 6M+ sites | Coming next |
| Gravity Forms | ~650K sites | Coming next |
| Ninja Forms | 600K+ sites | Later release |
| Caldera Forms | ~50K sites (unmaintained plugin) | Later release |

Combined audience: roughly **17 million sites** that could move to SureForms.

## What an import gives you

When a user clicks "Import", we bring across:

1. **The form's fields** — text, email, phone, dropdown, checkbox, radio, file URL, number, textarea, consent checkboxes, etc. They show up as native SureForms blocks, ready to edit in the Gutenberg editor.
2. **The form's title** — same as the original.
3. **A working admin notification email** — recipient is the site admin, subject mentions the form name, body uses `{all_data}` so submissions go straight to your inbox.
4. **A success message** — a clean "Thank you" confirmation shown after submission.

The imported form works on day one. Users can polish it in SureForms' editor.

## What doesn't transfer yet (and why)

| Thing | Why not |
|---|---|
| File-upload fields | SureForms doesn't have a file-upload block yet — coming separately. |
| Quiz / signature / rating fields | No SureForms equivalent yet. |
| The exact email-body text from CF7 | CF7 uses a different placeholder syntax (`[your-name]` vs SureForms' smart tags). Trying to translate it gets unreliable, so we ship a clean default instead. WPForms and Gravity emails will come through fully when those are added. |
| Multi-step / page breaks | Waiting on a SureForms multi-step block. |

When a field can't be migrated, the import screen lists it under **"Some fields were skipped"** so the user knows what to add by hand.

## How users will find it

A new **Migration** tab inside **SureForms → Settings**, at the bottom of the sidebar. The workflow is four short steps:

1. **Choose a source** — tiles show each detected plugin and how many forms it has.
2. **Pick which forms** — checkbox list; "Select all" if you want everything.
3. **Preview** — see what the migrated form will look like before anything is saved.
4. **Confirm & import** — done. The result screen has direct "Edit in SureForms" links.

If a user re-runs the import for the same source form, we **update** the existing SureForms version instead of creating a duplicate.

## What's already built

- The Migration tab is live in **SureForms → Settings → Migration**
- Contact Form 7 imports work end to end (fields + notification + confirmation)
- "Preview before importing" works — nothing gets saved until the user confirms
- Skipped fields are surfaced clearly to the user

## What's next

1. **WPForms importer** — including its full email-notification and confirmation rules
2. **Gravity Forms importer** — including notifications, scheduling, entry limits
3. **Ninja Forms + Caldera Forms importers**
4. **Entry migration** — bringing across historical form submissions (capped at 1,000 per form to keep imports fast)
5. **Filling in field gaps** — once SureForms ships file-upload, multi-step, and signature blocks, the migrator will start carrying those over too

## Why we chose this approach

We modelled the migrator on **Fluent Forms**' approach, which has been running in production on 700K+ sites for years. Same flow (source → forms → preview → import), same safety net (re-imports update rather than duplicate), and the same honest stance on edge cases like CF7 email templates.

## Where to find things (for engineers)

- Full technical plan with code structure, REST endpoints, field-mapping tables: `FORM_MIGRATION_PLAN.md`
- PHP code: `inc/migrator/`
- Admin tab UI: `src/admin/settings/migration/`
