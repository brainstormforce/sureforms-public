# SureForms Release Version Bump

Automates the SureForms version bump release checklist end-to-end.

## Arguments

Parse from: $ARGUMENTS

Expected format: `<version> [pro-version] [branch]`

- `version` — **required**. Version number to release (e.g., `2.5.2`). If not provided, stop immediately and tell the user: `"Version number is required. Usage: /sureforms:version-bump <version> [pro-version] [branch]"`
- `pro-version` — optional. Version to set for `SRFM_PRO_RECOMMENDED_VER`. If not provided, ask: `"What should SRFM_PRO_RECOMMENDED_VER be set to? (default: <version>)"` and default to the core version if the user presses Enter or skips.
- `branch` — optional. Source branch to cut from — must be `dev` or `next-release`. Default: `next-release`.

## Working Directory

All commands and file paths are relative to the sureforms plugin root. Detect it as the directory containing `sureforms.php` — typically the current working directory or a `sureforms/` subdirectory of it.

---

## Steps

### Step 1 — Validate Arguments

- Extract `version`, `pro-version`, and `branch` from `$ARGUMENTS`.
- Abort with usage message if `version` is missing.
- If `branch` is provided but not `dev` or `next-release`, abort: `"Invalid branch. Must be 'dev' or 'next-release'."`
- If `pro-version` was not passed as an argument, prompt the user for it (default: same as `version`).

---

### Step 2 — Create Version Bump Branch

Run from the sureforms plugin root:

```bash
git fetch origin
git checkout <branch>
git pull origin <branch>
git checkout -b version-bump-<version>
```

Confirm the new branch was created successfully before continuing.

---

### Step 3 — Run Grunt Version Bump

```bash
npx grunt version-bump --ver=<version>
```

This runs `bumpup` (updates `package.json`) and `replace` (updates version strings in `readme.txt` and `sureforms.php`).

---

### Step 4 — Verify package.json and package-lock.json

- Read `package.json` → confirm `"version"` equals `<version>`.
- Read `package-lock.json` → confirm the top-level `"version"` and `packages[""].version` both equal `<version>`.
- If `package-lock.json` is out of sync, patch **only** the two version string values — do not alter any whitespace, indentation, line endings, or surrounding characters:
  - Top-level: replace only the value in `"version": "<old-version>"` → `"version": "<version>"`
  - Inside `"packages": { "": { ... } }`: replace only the value in `"version": "<old-version>"` → `"version": "<version>"`
  - The resulting diff must show **exactly 2 lines changed** — only the version string on each line. No other bytes in the file should change.
  - Do **not** run `npm install` — dependency conflicts can cause it to fail and it is not needed for this change.

---

### Step 5 — Verify readme.txt — Stable tag

- Read `readme.txt` and confirm `Stable tag: <version>`.
- If incorrect, edit the line to the correct value.

---

### Step 6 — Verify readme.txt — Tested up to

- Fetch the latest WordPress version by running:
  ```bash
  curl -s "https://api.wordpress.org/core/version-check/1.7/" | grep -o '"version":"[^"]*"' | head -1 | cut -d'"' -f4
  ```
- Store the result as `<wp-latest>`.
- Read `readme.txt` and check the current `Tested up to:` value.
- If it does **not** match `<wp-latest>`, update the line to `Tested up to: <wp-latest>`.
- Confirm the final value in the file after any edit.

---

### Step 7 — Verify sureforms.php — Plugin Header Version

- Read `sureforms.php` lines 1–15.
- Confirm `Version: <version>` in the plugin header comment.
- If incorrect, edit to the correct value.

---

### Step 8 — Verify SRFM_VER

- Confirm `define( 'SRFM_VER', '<version>' );` in `sureforms.php`.
- If incorrect, edit to the correct value.

---

### Step 9 — Verify SRFM_PRO_RECOMMENDED_VER

- Confirm `define( 'SRFM_PRO_RECOMMENDED_VER', '<pro-version>' );` in `sureforms.php`.
- If incorrect, edit to the correct value.

---

### Step 10 — Verify Changelog Entry in readme.txt

- Read the `== Changelog ==` section of `readme.txt`.
- **Check for a placeholder entry** matching `= x.x.x =` (case-insensitive):
  - If **found**: ask the user: `"What is the release date for <version>? (e.g. 19th February 2026)"`. Replace the placeholder header with `= <version> - <date> =`, matching the exact formatting of existing changelog entries (spaces around the `=`, date style matching what is already present).
- **Check for an entry** matching `= <version> -`:
  - If **missing** after the above: warn the user and **pause** — they must add a changelog entry for `<version>` before the process can continue. The expected format is:
    ```
    = <version> - DD Month YYYY =
    * New: ...
    * Improvement: ...
    * Fix: ...
    ```
  - If **found**: confirm the date is present and the format matches SureForms standards.
- **Polish each bullet line** of the `<version>` entry:
  - Fix any grammatical or spelling errors.
  - Rewrite passive or vague phrasing into clear, active, benefit-led language (e.g. "Fixed bug where X" → "Fix: Resolved an issue where X to ensure Y").
  - Keep the improvements **subtle and factual** — do not invent features or exaggerate. The prefix (`New:`, `Improvement:`, `Fix:`) must stay unchanged.
  - Show the user a before/after diff of any lines you changed and ask for confirmation before writing.
- Then sort the bullet lines of the `<version>` entry so they follow this group order:
  1. `* New:` lines
  2. `* Improvement:` lines
  3. `* Fix:` lines
  4. Any other prefixes come last
- Within each group, sort lines **alphabetically** (case-insensitive) by the text that follows the prefix.
- Write the re-ordered lines back to `readme.txt` if any reordering was needed, and report whether changes were made.

---

### Step 11 — Trim Changelog to 3 Entries

- Parse the `== Changelog ==` section of `readme.txt` to identify all version entries (lines matching `= X.X.X - ...=`).
- Keep only the **3 most recent** entries (the new `<version>` entry plus the 2 entries immediately before it), including all their bullet lines.
- Remove all older entries that follow the 3rd entry, up to but not including the next `==` section header.
- Write the trimmed content back to `readme.txt`.
- Report how many entries were removed (e.g., "Removed 4 old changelog entries").

---

### Step 12 — Generate README.md

```bash
npx grunt readme
```

---

### Step 13 — Generate POT File

```bash
npm run makepot
```

---

### Step 14 — Commit and Open PR

```bash
git add -A
git commit -m "Version Bump <version>"
git push origin version-bump-<version>
gh pr create \
  --repo brainstormforce/sureforms \
  --title "Version Bump <version>" \
  --base <branch> \
  --label "Release PR,skip-title-check"
```

---

### Step 15 — Final Report

Print a status summary for every step, then the PR URL:

```
Release Bump: <version>
─────────────────────────────────────────
✅ Branch created: version-bump-<version> (from <branch>)
✅ grunt version-bump: completed
✅ package.json: <version>
✅ package-lock.json: <version>
✅ readme.txt Stable tag: <version>
✅ readme.txt Tested up to: <wp-latest>
✅ sureforms.php Version: <version>
✅ SRFM_VER: <version>
✅ SRFM_PRO_RECOMMENDED_VER: <pro-version>
✅ Changelog entry polished, confirmed, and sorted (New → Improvement → Fix, alphabetical within groups)
✅ Changelog trimmed to 3 entries (N removed)
✅ README.md generated
✅ POT file generated
✅ PR opened: <pr-url>
```

If any step failed (❌) or needs attention (⚠️), list the required actions at the bottom.
