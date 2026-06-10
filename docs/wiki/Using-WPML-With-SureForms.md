# Using WPML With SureForms

SureForms ships with native compatibility for **WPML**, the leading WordPress multilingual plugin. With WPML active, you can offer your forms in any number of languages without duplicating the form itself — one form serves every language, every submission is tagged with the visitor's language, and translators work from a single place.

This guide walks you through everything you need to know as a site owner, content editor, or translator.

---

## What you can do with WPML + SureForms

Once both plugins are active and configured, SureForms automatically:

- Translates **field labels, placeholders, help text, error messages** and **dropdown / multi-choice options** based on the visitor's language.
- Translates the **submit button** label.
- Translates the **success message** shown after a submission.
- Translates the **confirmation redirect content** (when "show same page" confirmation is used).
- Translates the **form-restriction notice** (e.g., "This form is closed").
- Records the **submission language** alongside every entry, so you can see at a glance which language a lead came from.

You keep one form. You collect entries from every language into the same list. Reports, integrations, and exports stay unified.

---

## Before you start

You will need:

| Item | Notes |
|---|---|
| **WordPress 6.4 or higher** | SureForms minimum requirement. |
| **WPML Multilingual CMS** (4.5+) | Paid plugin from [wpml.org](https://wpml.org). |
| **WPML String Translation** add-on | Required to translate form labels, options, and messages. Bundled with most WPML licences. |
| **SureForms 2.5.1 or higher** | Native WPML support shipped in this release. |

> **Polylang and other multilingual plugins:** SureForms is architected with a generic provider interface so other plugins can be supported in the future. WPML is the only multilingual plugin currently supported out of the box.

---

## Step 1 — Install and configure WPML

1. Install and activate the following plugins from your WordPress dashboard:
   - **WPML Multilingual CMS** (`sitepress-multilingual-cms`)
   - **WPML String Translation**

2. Open **WPML → Languages** and choose at least two languages (for example, English as the default, plus German and French).

3. Open **WPML → Settings → Translation Options** and confirm that **Posts** are set to *"Translatable — use translation"*.

That's the only WPML setup that is required. SureForms takes care of the rest automatically.

---

## Step 2 — Create your form (just once)

Create a SureForms form exactly the way you always have:

1. Go to **SureForms → Add New Form**.
2. Pick a template or start blank.
3. Add fields (text, email, dropdown, multi-choice, etc.) and configure their labels, placeholders, help text, options, and error messages in your default language.
4. Configure the submit button text, success message, and any other form settings.
5. Save the form.

> **Important:** Do **not** try to duplicate the form for each language. SureForms intentionally registers the form post type as *non-translatable* in WPML. There is one canonical form post; WPML translates the strings on it. This keeps entries unified and avoids the maintenance nightmare of keeping multiple form copies in sync.

When you save the form, SureForms automatically registers every translatable string with WPML under the `sureforms` text domain.

---

## Step 3 — Translate your form strings

1. Go to **WPML → String Translation**.
2. In the **"Select strings within domain"** dropdown at the top, choose `sureforms`.
3. You will see entries for every translatable piece of the form, grouped by form ID for easy scanning. For example, a form with ID `42` will show strings such as:

   - `form_42_submit_button`
   - `form_42_confirmation_0_message`
   - `form_42_notification_0_subject`
   - `form_42_restriction_message`
   - `form_42_block_{block-id}_label`
   - `form_42_block_{block-id}_placeholder`
   - `form_42_block_{block-id}_helpText`
   - `form_42_block_{block-id}_errorMsg`
   - `form_42_block_{block-id}_option_0_label` *(for dropdown / multi-choice options)*

4. Click **"translations"** next to any string, enter the translated text for each language, and save.

You can also use WPML's built-in **Translation Editor** workflow or send strings to a translation service from this screen — the standard WPML String Translation UI applies.

> **Tip:** The per-form naming scheme (`form_{id}_...`) keeps every form's strings neatly grouped, so translators don't have to hunt across the entire site for a particular form's labels.

---

## Step 4 — Display your form on translated pages

SureForms forms are added to pages exactly the way they always have been — via the SureForms block or the `[sureforms id="..."]` shortcode.

For the translated version of a form to render correctly, the **host page must also be translated in WPML**:

1. Create the original page and embed your form on it.
2. In WPML, translate that page into each language. The form block / shortcode is carried over automatically.
3. Visit the translated URL (e.g., `/de/contact/`) — the form will render with all the labels, placeholders, options, and the submit button in German.

If you instead use SureForms' **instant form** URL (`/form/{id}/`), append the language code to switch languages:

```
https://example.com/form/42/?lang=de
https://example.com/form/42/?lang=fr
```

---

## Step 5 — Verify everything is translating

Open the translated form in the browser and confirm that the following are all in the target language:

- ✅ Field labels
- ✅ Field placeholders
- ✅ Help text
- ✅ Inline error messages (e.g., "This field is required")
- ✅ Dropdown / multi-choice / checkbox option labels
- ✅ Submit button text
- ✅ Success / confirmation message after submission
- ✅ Form restriction notice (if the form is closed or capped)

If anything appears in the original language:

1. Check that the host page is translated in WPML.
2. Check that the specific string has a translation in **WPML → String Translation** (filter by domain `sureforms`).
3. Re-save the form once — this regenerates the string registry.

---

## Step 6 — Collect entries and review them by language

Submissions made on the German version of the page are stored against the **same form** as English submissions, with the submission language recorded on the entry row.

1. Go to **SureForms → Entries**.
2. You will see a new **Language** column between *Date & Time* and *Actions*.
3. Each entry displays an uppercase language code: `EN`, `DE`, `FR`, etc.
4. Click the **Language** column header to sort entries by language.

Older entries (and entries submitted on sites where WPML is not active) display `—`.

> **Tip:** All your usual entry features — search, view details, delete, export — work as normal. Language is just an extra column for filtering and reporting.

---

## Step 7 — Form restriction messages

If you have configured form restrictions (a closing date or maximum entry count), the **restriction notice** shown to visitors is automatically translated:

1. Open the form and enable restrictions (e.g., set a close date in the past, or set a max-entry limit).
2. Customise the restriction message in the form settings.
3. Translate `form_{id}_restriction_message` in **WPML → String Translation**.
4. Trigger the restriction (visit the form in a translated URL once it's closed) — visitors see the translated restriction notice.

---

## What about email notifications?

> **Note:** In the current release, email notifications (the message sent to your admin or to the visitor after a successful submission) are sent in the site's default language. Multilingual email notifications — where each email is composed in the visitor's submission language — are planned for a future SureForms release.

The submission language is already recorded on the entry, so any custom integration that hooks into the SureForms submission flow can use it.

---

## Graceful fallback when WPML is not active

You can safely install and uninstall WPML at any time:

- **WPML active**: forms translate based on the visitor's language. Entries are tagged.
- **WPML deactivated**: forms render in their default language as if WPML had never been installed. New entries record an empty language string. No errors, no broken pages.

The plugin is designed so that the multilingual layer is completely silent when WPML is not active.

---

## Troubleshooting

**Translated strings don't appear in WPML String Translation**

- Confirm that *WPML String Translation* is active (not just WPML core).
- Re-save your form once. The string registry runs on form save.
- Filter by domain `sureforms` in the WPML String Translation UI — strings from other plugins live in different domains.

**Translated labels still render in the original language on `/de/...`**

- Make sure the host page is translated in WPML and you are visiting the translated URL.
- Open WPML → String Translation, filter by `sureforms`, and confirm the specific strings actually have translations saved.
- Check that the page language switcher confirms you are on the German (or other target) version of the page.

**The success message is in English even though I submitted in German**

- Confirm `form_{id}_confirmation_0_message` has a German translation in WPML String Translation.
- Confirm the form is being submitted from a German page (`/de/...`), not from a query-string variant in some WPML modes.

**Entries show `—` in the Language column**

- Older entries submitted before WPML support shipped will not have a language stored.
- Entries created from the admin (e.g., test submissions, or imports) may not carry a language code.
- New front-end submissions on translated pages with WPML active will always carry a language code.

**An entry's language code is wrong**

- The visitor's language is determined by WPML based on the URL of the page they submitted from. If your WPML configuration uses query-string mode (`?lang=de`), make sure the parameter is on the page URL when the form is submitted.

---

## Frequently asked questions

**Do I need to duplicate the form for each language?**

No. There is one canonical form post regardless of how many languages you support. WPML translates the strings on it.

**Will entries from different languages mix in the same list?**

Yes — by design. All entries from a given form, regardless of the visitor's language, belong to that one form. The Language column lets you filter and sort.

**Can I export entries with the language?**

The submission language is stored alongside each entry and is available to any integration that reads SureForms entries. A future SureForms release will include the language column in the standard CSV export header.

**What languages are supported?**

Any language WPML supports. SureForms records the standard WPML language code (`en`, `de`, `hi`, `zh-cn`, `pt-br`, etc.) — up to 20 characters, which covers the full WPML and BCP-47 locale-code range.

**Does this work with Polylang or other multilingual plugins?**

SureForms is built on a generic multilingual provider interface, so support for Polylang and other plugins is straightforward to add. WPML is the only multilingual plugin currently supported in the box.

---

## Need help?

If you run into anything not covered here, contact the SureForms support team at [sureforms.com/support](https://sureforms.com/support) with:

1. Your SureForms version (Plugins → Installed Plugins → SureForms).
2. Your WPML version, including whether *WPML String Translation* is active.
3. Your form ID.
4. The specific string or behaviour that isn't translating.
5. A screenshot if possible.

We're happy to help.
