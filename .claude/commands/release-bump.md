# SureForms Release Version Bump

Automates the SureForms version bump release checklist end-to-end.

## Arguments

Parse from: $ARGUMENTS

Expected format: `<version> [pro-version] [branch]`

- `version` — **required**. Version number to release (e.g., `2.5.2`). If not provided, stop immediately and tell the user: `"Version number is required. Usage: /release-bump <version> [pro-version] [branch]"`
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
- If `package-lock.json` is out of sync, run: `npm install --package-lock-only` to regenerate it.

---

### Step 5 — Verify readme.txt — Stable tag

- Read `readme.txt` and confirm `Stable tag: <version>`.
- If incorrect, edit the line to the correct value.

---

### Step 6 — Verify readme.txt — Tested up to

- Read `readme.txt` and confirm `Tested up to:` is set to the latest WordPress version.
- If it looks outdated (older than the known current WP release), warn the user and ask them to confirm the correct WordPress version before updating.

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

### Step 10 — Replace @since x.x.x Placeholders

- Search all `.php` files in the plugin for occurrences of `@since x.x.x`.
- Replace every occurrence with `@since <version>`.
- Report the count and list of files updated.

---

### Step 11 — Verify Changelog Entry in readme.txt

- Read the `== Changelog ==` section of `readme.txt`.
- Check for an entry matching `= <version> -`.
- If **missing**: warn the user and **pause** — they must add a changelog entry for `<version>` before the process can continue. The expected format is:
  ```
  = <version> - DD Month YYYY =
  * New: ...
  * Fix: ...
  ```
- If **found**: confirm the date is present and the format matches SureForms standards.

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
  --label "Release PR,skip-title-check" \
  --body "$(cat <<'EOF'
### Release Checklist:
- [x] Run to update version number  : \`grunt version-bump --ver=<version>\`
- [x] Verify the version number in \`package.json\` and \`package-lock.json\`
- [x] Verify \`Stable tag\` is \`<version>\` in readme.txt
- [x] Verify \`Tested upto\` is set to latest tested version of WordPress
- [x] Update version in \`sureforms.php\` in plugin description
- [x] Verify constant \`SRFM_VER\` in \`sureforms.php\` with latest version of SureForms
- [x] Verify constant \`SRFM_PRO_RECOMMENDED_VER\` in \`sureforms.php\` with compatible version of SureForms Pro
- [x] Verify changelog \`date\` and \`content\` as per SureForms standards
- [x] Generate README.md : \`grunt readme\`
- [x] Generate POT file : \`npm run makepot\`
EOF
)"
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
✅ readme.txt Tested up to: <wp-version>
✅ sureforms.php Version: <version>
✅ SRFM_VER: <version>
✅ SRFM_PRO_RECOMMENDED_VER: <pro-version>
✅ @since x.x.x: N file(s) updated
✅ Changelog entry found
✅ README.md generated
✅ POT file generated
✅ PR opened: <pr-url>
```

If any step failed (❌) or needs attention (⚠️), list the required actions at the bottom.
