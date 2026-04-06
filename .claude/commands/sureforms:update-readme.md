---
description: Audit and update the WordPress.org readme.txt — analyze new features, research competitors, draft changes, and create a PR
---

# SureForms readme.txt Update

Comprehensive audit and update of the `readme.txt` (and `README.md`) for the WordPress.org listing.

## Arguments

Parse from: $ARGUMENTS

Expected format: `[version] [--full]`

- `version` — optional. Baseline version to analyze changes since (e.g., `2.5.0`). If not provided, auto-detect from the most recent `= X.X.X` changelog header in `readme.txt`.
- `--full` — optional flag. Perform a full rewrite of all sections instead of an incremental update.

If no arguments are given, auto-detect the baseline and proceed with incremental mode.

## Working Directory

All commands and file paths are relative to the sureforms plugin root — the directory containing `sureforms.php`.

---

## Phase 1: Analyze What's New

### Step 1.1 — Determine Baseline Version

- If `version` argument was provided, use that as the baseline.
- Otherwise, read the `== Changelog ==` section of `readme.txt`, extract the most recent version header (`= X.X.X - ... =`), and use that version as the baseline.
- Resolve the git tag: `v<version>` (e.g., `v2.5.0`). Verify it exists with `git tag -l "v<version>"`.
- If the tag does not exist, list available tags (`git tag --sort=-version:refname | head -10`) and ask the user to pick one.

### Step 1.2 — Gather Changes Since Baseline

Launch **3 Explore subagents in parallel** (single message, 3 Agent tool calls):

**Agent 1 — Git History Analysis:**
- Run `git log v<baseline>..HEAD --oneline` to get all commits since the baseline.
- Categorize commits: new features, improvements, bug fixes, refactors.
- Focus on commits touching: `inc/blocks/`, `inc/fields/`, `inc/payments/`, `inc/ai-form-builder/`, `modules/`, `src/blocks/`, `inc/page-builders/`.
- Report: list of significant user-facing changes with commit hashes and affected areas.

**Agent 2 — Free Codebase Feature Scan:**
- List all blocks in `inc/blocks/` — compare against the readme's "Input Fields" and feature bullets.
- Check `inc/payments/` for payment providers.
- Check `inc/ai-form-builder/` for AI feature updates.
- Check `modules/` for any new modules.
- Check `inc/global-settings/` for new settings sections.
- Check `inc/rest-api.php` for new REST endpoints.
- Check `inc/page-builders/` for page builder compatibility additions.
- Report: features present in code but missing from readme.

**Agent 3 — Pro Codebase Feature Scan:**
- Check `../sureforms-pro/inc/pro/native-integrations/integrations/` — list ALL integration JSON files and compare against the readme's integration list.
- Check `../sureforms-pro/inc/blocks/` for pro-only blocks.
- Check `../sureforms-pro/inc/pro/` subdirectories for pro features (conversational-form, save-resume, signature, zapier, etc.).
- Check `../sureforms-pro/inc/extensions/` for conditional logic, field-restrictions, etc.
- If `../sureforms-pro/` does not exist, skip this agent and warn: "Pro plugin not found. Only free features will be analyzed."
- Report: pro features/integrations present in code but missing from readme.

### Step 1.3 — Present Gap Analysis

Merge all agent results and present a table:

```
┌─────────────────────────────────────┬────────┬──────────────────────────────┐
│ Area                                │ Status │ Details                      │
├─────────────────────────────────────┼────────┼──────────────────────────────┤
│ Free field types match readme       │ ✅/❌  │ Code: N, Readme: M           │
│ Pro blocks mentioned                │ ✅/❌  │ Missing: ...                 │
│ Integration list up to date         │ ✅/❌  │ Missing: ...                 │
│ Pro features all listed             │ ✅/❌  │ Missing: ...                 │
│ Payment providers accurate          │ ✅/❌  │                              │
│ Page builder compat listed          │ ✅/❌  │                              │
│ "Tested up to" WP version           │ ✅/❌  │ Readme: X.X, Current: Y.Y   │
│ New features since v<baseline>      │ ℹ️     │ N features found             │
└─────────────────────────────────────┴────────┴──────────────────────────────┘
```

Tell the user: "Phase 1 complete. Found **N gaps** and **M new features** to incorporate. Proceeding to competitor research."

---

## Phase 2: Competitor & Market Research

### Step 2.1 — Research Competitor READMEs

Use **WebSearch** to run these searches in parallel:

1. `"WPForms" site:wordpress.org/plugins description features`
2. `"Gravity Forms" site:wordpress.org/plugins description features`
3. `"Fluent Forms" site:wordpress.org/plugins fluentform features`
4. `"Formidable Forms" site:wordpress.org/plugins description features`
5. `"Contact Form 7" site:wordpress.org/plugins description`
6. `"WordPress.org readme.txt best practices 2025 2026"`

For each competitor, use **WebFetch** on the top result URL if needed to extract:
- How they structure feature lists
- Marketing language and benefit-focused copy
- How they distinguish free vs pro features
- FAQ structure and common questions
- Trust signals (install counts, star ratings, testimonials)

### Step 2.2 — Present Research Summary

Show a comparison table:

```
Feature Highlighted by Competitors    │ SureForms Has It? │ In Readme?
──────────────────────────────────────┼───────────────────┼──────────
Conditional Logic                     │ ✅ (Pro)          │ ✅/❌
File Upload                           │ ✅ (Pro)          │ ✅/❌
Drag & Drop Builder                   │ ✅ (Free)         │ ✅/❌
...                                   │                   │
```

Also note:
- SEO-relevant keywords competitors use that SureForms is missing.
- Structural patterns that work well (comparison tables, benefit-focused headers).
- Any WordPress.org formatting trends.

Tell the user: "Phase 2 complete. Ready to draft the update. Shall I proceed?"

**⏸️ WAIT for user confirmation before proceeding to Phase 3.**

---

## Phase 3: Draft the Update

### Step 3.1 — Determine Scope

- If `--full` flag was passed: draft ALL sections from scratch.
- Otherwise: draft only sections that need changes based on Phase 1 and Phase 2 findings.

### Step 3.2 — Draft Each Section

For each section that needs updating, present in this format:

```
━━━ SECTION: [Section Name] ━━━

── CURRENT ──
[Current text from readme.txt, abbreviated if very long]

── PROPOSED ──
[New text]

── CHANGES ──
• Added: [what was added]
• Removed: [what was removed, if any]
• Reworded: [what was reworded and why]
```

Sections to evaluate (in order):

1. **Plugin header** — Stable tag, Tested up to, Tags (optimize for WordPress.org search)
2. **Short description** — Must be under 150 characters. Make it compelling and keyword-rich.
3. **Long description** — Feature bullets, AI section, payment section, field types list
4. **Premium features section** — Integration list, pro-only features, pro field types
5. **FAQ** — Add questions for new features, update outdated answers
6. **3rd Party Services** — Add any new external services used
7. **Compatibility lists** — Themes and plugins

### Step 3.3 — WordPress.org Compliance Check

Verify the draft against WordPress.org readme standards:
- ✅ Short description is under 150 characters
- ✅ No disallowed HTML in description
- ✅ `== Changelog ==` section exists and is properly formatted
- ✅ `== Frequently Asked Questions ==` uses correct `= Question =` format
- ✅ No broken markdown that WordPress.org would render incorrectly
- ✅ Stable tag matches the version in the main plugin file

### Step 3.4 — Present Full Draft

Present all drafted sections together with a summary:

```
📋 Readme Update Summary
─────────────────────────────────────
Sections modified:    N
Lines added:          +X
Lines removed:        -Y
New features listed:  Z
New integrations:     W
New FAQ entries:      Q
WP.org compliance:    ✅ All checks pass
```

Tell the user: "Please review the draft above. You can:
1. **Approve** — proceed to apply changes and create PR
2. **Request edits** — tell me what to change
3. **Abort** — cancel the update"

**⏸️ WAIT for explicit user approval. Do NOT proceed without it.**

---

## Phase 4: User Review & Iteration

This phase is a loop:

1. If user **requests edits** → apply them to the draft and re-present the affected sections. Ask again for approval.
2. If user **approves** → proceed to Phase 5.
3. If user **aborts** → stop and report "Readme update cancelled. No changes were made."

---

## Phase 5: Apply & Create PR

### Step 5.1 — Create Branch

```bash
git fetch origin
git checkout dev
git pull origin dev
git checkout -b chore/update-readme
```

If `chore/update-readme` already exists, append a date suffix: `chore/update-readme-YYYYMMDD`.

### Step 5.2 — Apply Changes

Edit `readme.txt` with the approved draft changes. Apply section by section — do NOT rewrite sections that weren't modified.

### Step 5.3 — Regenerate README.md

```bash
npx grunt readme
```

Verify `README.md` was updated. If `grunt readme` fails, check that `npm install` has been run and `grunt-wp-readme-to-markdown` is in `devDependencies`.

### Step 5.4 — Verify

Read the updated `readme.txt` and confirm:
- Stable tag matches current plugin version
- Tested up to is correct
- Short description is under 150 characters
- All sections are properly formatted
- No stray draft markers or debug text

### Step 5.5 — Commit and Push

```bash
git add readme.txt README.md
git commit -m "chore: update readme.txt for WordPress.org listing

Update plugin description, feature lists, integrations, FAQ,
and compatibility information to reflect current capabilities.

Co-Authored-By: Claude Opus 4.6 (1M context) <noreply@anthropic.com>"
git push -u origin chore/update-readme
```

### Step 5.6 — Create PR

```bash
gh pr create \
  --title "chore: Update readme.txt for WordPress.org" \
  --base dev \
  --body "$(cat <<'EOF'
## Summary
- Updated readme.txt to reflect current plugin capabilities
- Regenerated README.md via `grunt readme`

## What Changed
[List each section that was modified and what changed]

## Research Notes
- Competitor READMEs reviewed: WPForms, Gravity Forms, Fluent Forms, Formidable, CF7
- Gaps identified and addressed: [count]

## Test Plan
- [ ] Preview on WordPress.org readme validator
- [ ] Verify all links in the readme are working
- [ ] Confirm feature claims match actual plugin capabilities
- [ ] Verify short description is under 150 characters

🤖 Generated with [Claude Code](https://claude.com/claude-code)
EOF
)"
```

### Step 5.7 — Final Report

```
✅ Readme Update Complete
─────────────────────────────────────────
✅ Phase 1: Gap analysis complete
✅ Phase 2: Competitor research complete
✅ Phase 3: Draft created
✅ Phase 4: User review passed
✅ Phase 5: Changes applied
✅ Branch: chore/update-readme (from dev)
✅ readme.txt updated
✅ README.md regenerated
✅ PR: <pr-url>

Sections updated:
  • [List each modified section]
```

---

## Rules

- **NEVER modify readme.txt without user approval.** Always present the full draft first.
- **NEVER invent features.** Only document what actually exists in the codebase.
- **NEVER modify changelog entries.** The changelog is managed by `/sureforms:version-bump`.
- **ALWAYS branch from `dev`**, never from `master` or `next-release`.
- **ALWAYS run `npx grunt readme`** after modifying `readme.txt` to regenerate `README.md`.
- **ALWAYS verify feature claims** by checking the actual codebase, not just commit messages.
- **Keep the 150-character limit** for the WordPress.org short description.
- **Maintain the existing voice and tone** — professional, benefit-focused, not overly salesy.

## Error Recovery

| Symptom | Fix |
|---------|-----|
| `grunt readme` fails | Run `npm install` first; verify `grunt-wp-readme-to-markdown` is in `devDependencies` |
| Branch already exists | Append date suffix: `chore/update-readme-YYYYMMDD` |
| Pro plugin not found at `../sureforms-pro/` | Skip pro analysis, warn user, document only free features |
| Git tag not found | List available tags and ask user to pick one |
| WebSearch unavailable | Skip Phase 2, proceed with codebase analysis only, note the limitation |
