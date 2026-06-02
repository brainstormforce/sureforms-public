---
allowed-tools: Bash(git:*), Bash(gh:*), Bash(rm:*), Bash(rmdir:*), Bash(mktemp:*), Bash(echo:*), Bash(cd:*), Bash(test:*)
description: Sync private master to the sureforms-public mirror, stripping internal-only paths in one commit
---

# SureForms — Sync to Public Mirror

Sync `brainstormforce/sureforms@master` (private, this repo) to `brainstormforce/sureforms-public@master` (public WordPress.org-facing mirror), stripping internal-only paths. The sync branch is bulk re-signed (verified signatures) and capped with a merge commit against the mirror so the public PR diff stays clean.

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
- public-mirror remote → `https://github.com/brainstormforce/sureforms-public` (public). May be named `mirror` **or** `public` — detect with `git remote -v` and use whichever points at `sureforms-public`. Examples below use `$MIRROR`.

Verify with `git remote -v`. Abort if neither a `mirror` nor `public` remote points at `sureforms-public`.

### ⚠️ `sureforms-public` ruleset constraints (READ FIRST)

The public repo enforces a branch ruleset (since 2026-06-01) that the previous version of this skill violated. This flow is built around them:

1. **Every commit needs a *verified* signature.** Signing is not enough — GitHub must mark it **Verified**, which requires BOTH the signing key registered on the pushing account as a **Signing Key** (an *Authentication* key does NOT count) AND the **committer email** verified on that account. **The skill uses the identity of whoever runs it** — your local `git config user.name` / `user.email` / `user.signingkey`. Before running, confirm: your `user.signingkey` points at an SSH public-key file (`*.pub`, since `gpg.format=ssh`) that is registered as a **Signing Key** on *your* GitHub account, and your `user.email` is a **verified** email on that same account. Hardware-backed keys (e.g. Secretive) verify fine but prompt for a tap **per commit**, so they cannot bulk-sign ~150 commits — use an on-disk passphrase-less key for the bulk re-sign.
2. **`sync/master` is force-push protected.** Push each sync to a **fresh** branch `sync/master-<YYYYMMDD>` (`-v2`, `-v3`… if re-pushing the same day — reusing a branch makes the ruleset re-evaluate the mirror's own unsigned ancestors and reject).
3. **Claude's auto-mode guardrail blocks the agent from pushing to the public mirror.** The agent prepares everything and hands the user the exact `git push` to run themselves. `gh pr` / `gh api` ARE agent-allowed.

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

### Step 2: Detect the mirror remote + signing identity, then fetch

Detect the public-mirror remote (named `mirror` or `public`) once, and capture the running user's signing identity. These vars are used throughout:

```bash
MIRROR=$(git remote -v | awk '/sureforms-public(\.git)?[[:space:]]/{print $1; exit}')
[ -n "$MIRROR" ] || { echo "No remote points at sureforms-public — add one and retry"; exit 1; }

# Identity of whoever runs the skill (must satisfy the ruleset — see Preconditions)
SIGN_NAME=$(git config user.name)
SIGN_EMAIL=$(git config user.email)
SIGN_KEY=$(git config user.signingkey)   # expected: path to an SSH *.pub registered as a Signing Key
[ -n "$SIGN_NAME" ] && [ -n "$SIGN_EMAIL" ] && [ -n "$SIGN_KEY" ] || { echo "git user.name/user.email/user.signingkey must all be set"; exit 1; }

git fetch origin master
git fetch "$MIRROR" master sync/master 2>/dev/null || git fetch "$MIRROR" master
```

### Step 3: Detect no-op

```bash
PRIVATE_TIP=$(git rev-parse origin/master)
PUBLIC_TIP=$(git rev-parse "$MIRROR/master")
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

### Step 6: Commit the strip, then bulk re-sign ALL commits

First commit the strip (committer is the running user — must be the verified email, see ruleset constraints):

```bash
STRIPPED=false
if ! git diff --cached --quiet; then
  STRIPPED=true
  STRIPPED_PATHS=$(git diff --cached --name-only | tr '\n' ' ')
  git commit -m "chore: strip internal-only paths from public mirror sync"
fi
```

Then re-sign **every** commit new to the mirror so they all carry verified signatures. Use `filter-branch` (it reuses each commit's tree + parents and only adds a signature → zero conflicts; `rebase --rebase-merges` re-merges and hits conflicts):

> **Note:** this rewrites the **committer** of all re-signed commits to `$SIGN_EMAIL` (the original **author** of each commit is preserved). That committer/attribution change on the mirror is intentional — it's what lets the running user's registered key produce verified signatures.

```bash
BASE=$(git merge-base "$MIRROR/master" "$PRIVATE_TIP")
FILTER_BRANCH_SQUELCH_WARNING=1 git \
  -c gpg.format=ssh -c user.signingkey="$SIGN_KEY" \
  filter-branch -f \
    --env-filter "export GIT_COMMITTER_NAME=\"$SIGN_NAME\"; export GIT_COMMITTER_EMAIL=\"$SIGN_EMAIL\"" \
    --commit-filter "git -c gpg.format=ssh -c user.signingkey=\"$SIGN_KEY\" commit-tree -S \"\$@\"" \
    -- "$BASE..HEAD"
```

### Step 7: Cap with a merge commit on the mirror, push to a fresh branch

Cap the branch with a merge commit whose **first parent is `$MIRROR/master`**. This makes GitHub diff the PR against `$MIRROR/master` (which has no internal files), so the public PR shows **only real upstream changes — no internal-file deletions**.

```bash
SIGNED_TIP=$(git rev-parse HEAD)
MERGE=$(git -c gpg.format=ssh -c user.signingkey="$SIGN_KEY" \
  -c user.name="$SIGN_NAME" -c user.email="$SIGN_EMAIL" \
  commit-tree -S "$(git rev-parse HEAD^{tree})" -p "$MIRROR/master" -p "$SIGNED_TIP" \
  -m "Sync master from upstream")
BRANCH="sync/master-$(date +%Y%m%d)"
git branch -f "$BRANCH" "$MERGE"
```

Then **the user runs the push** (the agent's push to the public mirror is blocked by Claude's guardrail). Push to a **fresh** branch — never `sync/master` (force-protected), and never reuse an existing branch (its prior value makes the ruleset re-evaluate the mirror's unsigned ancestors):

```bash
git push "$MIRROR" "$BRANCH:refs/heads/$BRANCH"
```

If the push is rejected for "verified signatures", isolate before re-signing ~150 commits: push a single test commit signed the same way to a throwaway branch and confirm it's accepted (and `gh api .../commits/<sha> -q .commit.verification.verified` is `true`).

### Step 8: Open or update the sync PR

Check whether an open PR already exists for the current sync branch → `master` on `brainstormforce/sureforms-public`:

```bash
EXISTING_PR=$(gh pr list -R brainstormforce/sureforms-public \
  --base master --head "$BRANCH" --state open \
  --json number -q '.[0].number')
```

- **If `EXISTING_PR` is non-empty:** comment a refresh note:

  ```bash
  gh pr comment "$EXISTING_PR" -R brainstormforce/sureforms-public \
    --body "Refreshed: synced \`$COMMIT_COUNT\` commit(s) from private master ($PUBLIC_TIP..$PRIVATE_TIP). Strip applied: $STRIPPED."
  ```

- **If `EXISTING_PR` is empty:** open a new PR.

  Title: `Sync master from upstream`

  Body: upstream commit range, count, strip status, and a "## Highlights" section (`git log --oneline "$PUBLIC_TIP..$PRIVATE_TIP"`). Note that the diff is computed against `public/master` (merge-cap) so no internal files appear.

  ```bash
  gh pr create -R brainstormforce/sureforms-public \
    --base master --head "$BRANCH" \
    --title "Sync master from upstream" \
    --body "$PR_BODY"
  ```

  Close/delete any superseded sync branch + PR:

  ```bash
  gh pr close <OLD_PR> -R brainstormforce/sureforms-public
  gh api -X DELETE repos/brainstormforce/sureforms-public/git/refs/heads/<OLD_BRANCH>
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

- **Push rejected, "Commits must have verified signatures"** — a commit is signed by an unregistered key or committed under an unverified email. Confirm your `git config user.signingkey` (the `*.pub`) is registered as a **Signing Key** (not Authentication) on your GitHub account and your `git config user.email` is a verified email there. Test with a single throwaway commit before re-signing ~150.
- **Push rejected, "Cannot force-push to this branch"** — you targeted `sync/master`; use a fresh `sync/master-<date>` branch instead.
- **Push rejected naming a few commits already on `master`** — you reused/updated an existing branch; push to a brand-new branch name so the ruleset only evaluates the new commits.
- **Agent push "denied by auto mode classifier"** — expected; the user must run the `git push` to the mirror themselves.
- **`gh pr create` fails with "PR already exists"** — Step 8 detection missed a draft PR. Re-run Step 8 with `--state all` and reuse / reopen the existing PR.
- **No mirror remote** — add it and re-run: `git remote add public https://github.com/brainstormforce/sureforms-public`.
