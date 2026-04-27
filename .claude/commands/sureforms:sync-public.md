---
allowed-tools: Bash(git:*), Bash(gh:*), Bash(rm:*), Bash(rmdir:*), Bash(mktemp:*), Bash(echo:*), Bash(cd:*), Bash(test:*)
description: Sync private master to the sureforms-public mirror, stripping internal-only paths in one commit
---

# SureForms — Sync to Public Mirror

Sync `brainstormforce/sureforms@master` (private, this repo) to `brainstormforce/sureforms-public@master` (public WordPress.org-facing mirror), stripping internal-only paths in a single follow-up commit on the sync branch. Replaces the previous manual flow (`git push mirror origin/master:sync/master` + `gh pr create`).

This skill is the **only sanctioned way** to sync the public mirror. Running raw `git push mirror …` skips the strip and re-leaks internal artifacts.

## Stripped paths (single source of truth)

Edit this list when the leak surface changes — there is no other place to update.

```
.claude
.scripts/git-hooks
internal-docs
CLAUDE.md
ARCHITECTURE.md
COMPREHENSIVE_ANALYSIS.md
PRODUCT_ANALYSIS.md
TECHNICAL_OVERVIEW.md
.github/workflows/push-to-deploy.yml
.github/workflows/push-asset-readme-update.yml
.github/workflows/release-tag-draft.yml
.github/workflows/update-translations.yml
.github/workflows/release-pr-template.yml
bin/build-zip.sh
bin/checkout-and-build
bin/i18n.sh
```

These paths must NOT appear on the public mirror. They legitimately exist on private `master` and stay there (internal release CI, AI tooling, internal team wiki).

## Preconditions

Working directory: any worktree of this repo.

Required remotes:
- `origin` → `https://github.com/brainstormforce/sureforms` (private)
- `mirror` → `https://github.com/brainstormforce/sureforms-public` (public)

Verify with `git remote -v`. Abort if either remote is missing.

## Instructions

Follow steps sequentially. If any step fails, jump to **Error Recovery**.

### Step 1: Record current state

```bash
ORIGINAL_BRANCH=$(git rev-parse --abbrev-ref HEAD)
HAD_STASH=false
if [ -n "$(git status --porcelain)" ]; then
  git stash push -m "sureforms-sync-public auto-stash"
  HAD_STASH=true
fi
```

### Step 2: Fetch both remotes

```bash
git fetch origin master
git fetch mirror master sync/master 2>/dev/null || git fetch mirror master
```

### Step 3: Detect no-op

```bash
PRIVATE_TIP=$(git rev-parse origin/master)
PUBLIC_TIP=$(git rev-parse mirror/master)
```

If `PRIVATE_TIP` == `PUBLIC_TIP`, report "Public mirror is already up to date with private master — nothing to sync." Run **Step 8 cleanup** and exit.

Capture the upstream commit range and count for reporting:

```bash
COMMIT_COUNT=$(git rev-list --count "$PUBLIC_TIP..$PRIVATE_TIP")
```

### Step 4: Stage cleaned tree on a temp worktree

Use a temp worktree so the developer's current working tree is never modified.

```bash
WORKTREE=$(mktemp -d)/srfm-sync-public
git worktree add "$WORKTREE" "$PRIVATE_TIP"
cd "$WORKTREE"
```

### Step 5: Strip internal paths idempotently

For each path in the **Stripped paths** list above, only act if it exists:

```bash
for p in \
  .claude \
  .scripts/git-hooks \
  internal-docs \
  CLAUDE.md \
  ARCHITECTURE.md \
  COMPREHENSIVE_ANALYSIS.md \
  PRODUCT_ANALYSIS.md \
  TECHNICAL_OVERVIEW.md \
  .github/workflows/push-to-deploy.yml \
  .github/workflows/push-asset-readme-update.yml \
  .github/workflows/release-tag-draft.yml \
  .github/workflows/update-translations.yml \
  .github/workflows/release-pr-template.yml \
  bin/build-zip.sh \
  bin/checkout-and-build \
  bin/i18n.sh \
; do
  if [ -e "$p" ]; then
    git rm -r --quiet "$p"
  fi
done

# Remove now-empty .scripts directory if applicable
if [ -d .scripts ] && [ -z "$(ls -A .scripts 2>/dev/null)" ]; then
  rmdir .scripts
fi
```

### Step 6: Commit the strip if anything changed

```bash
STRIPPED=false
if ! git diff --cached --quiet; then
  STRIPPED=true
  STRIPPED_PATHS=$(git diff --cached --name-only | tr '\n' ' ')
  git -c user.name="sureforms-sync" \
      -c user.email="noreply@brainstormforce.com" \
      commit -m "chore: strip internal-only paths from public mirror sync"
fi
```

If `STRIPPED=false`, the upstream commits did not touch any internal-only paths — that's fine, just push the unmodified upstream tip.

### Step 7: Push to `mirror sync/master`

```bash
git push --force-with-lease mirror HEAD:sync/master
```

`--force-with-lease` is used because the strip commit is regenerated on top of each new upstream tip; previous strip commits on `sync/master` are discarded. The `--force-with-lease` flag still refuses if someone else has pushed to `sync/master` in the meantime.

### Step 8: Open or update the sync PR

Check whether an open PR already exists from `sync/master` → `master` on `brainstormforce/sureforms-public`:

```bash
EXISTING_PR=$(gh pr list -R brainstormforce/sureforms-public \
  --base master --head sync/master --state open \
  --json number -q '.[0].number')
```

- **If `EXISTING_PR` is non-empty:** the existing PR auto-updates with the new commits (no action needed). Comment on it with a brief refresh note:

  ```bash
  gh pr comment "$EXISTING_PR" -R brainstormforce/sureforms-public \
    --body "Refreshed: synced \`$COMMIT_COUNT\` commit(s) from private master ($PUBLIC_TIP..$PRIVATE_TIP). Strip applied: $STRIPPED."
  ```

- **If `EXISTING_PR` is empty:** open a new PR.

  Title: `Sync master from upstream`

  Body: list the upstream commit range, the commit count, and the strip status. Paste the high-level summary of upstream commits (use `git log --oneline "$PUBLIC_TIP..$PRIVATE_TIP"`) into a "## Highlights" section. Note in the body that the strip commit on top of upstream content is the only public-only change.

  ```bash
  gh pr create -R brainstormforce/sureforms-public \
    --base master --head sync/master \
    --title "Sync master from upstream" \
    --body "$PR_BODY"
  ```

### Step 9: Tear down the temp worktree

```bash
cd -
git worktree remove --force "$WORKTREE"
```

### Step 10: Restore developer state

```bash
git checkout "$ORIGINAL_BRANCH"
if [ "$HAD_STASH" = "true" ]; then
  git stash pop
fi
```

## Output to user

Report at the end:

- Upstream range: `<old-mirror-sha>..<new-mirror-sha>` (`$COMMIT_COUNT` commits)
- Whether the strip commit was created (`$STRIPPED`); if true, list the stripped paths
- PR URL — new or existing

## Error Recovery

If any step fails:

1. **`cd` back to the original repo dir** if currently inside `$WORKTREE`.
2. **Remove temp worktree** if it was created: `git worktree remove --force "$WORKTREE"` (ignore errors).
3. **Restore branch and stash** as in Step 10.
4. Surface the failing command and its error to the user — do not retry blindly.

Common failure modes:

- **`git push --force-with-lease` rejected** — someone else pushed to `sync/master` since the last fetch. Re-run the skill from Step 2; their changes will be incorporated automatically.
- **`gh pr create` fails with "PR already exists"** — Step 8 detection missed a draft PR. Re-run Step 8 with `--state all` and reuse / reopen the existing PR.
- **No `mirror` remote** — add it and re-run: `git remote add mirror https://github.com/brainstormforce/sureforms-public`.
