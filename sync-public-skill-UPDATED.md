# Updated `/sureforms:sync-public` skill (apply manually)

> **Why this file exists:** Claude's auto-mode guardrail blocks the agent from editing files under `.claude/`, so it cannot update the skill directly. Copy the body below over `.claude/commands/sureforms:sync-public.md` (keep the existing YAML frontmatter), then delete this file.
>
> **Background:** On 2026-06-01 the `brainstormforce/sureforms-public` repo enabled a branch ruleset that the previous skill violated: (1) **all commits must have *verified* signatures**, and (2) **`sync/master` is force-push protected**. The flow below is rewritten around those constraints and was validated end-to-end (PR #87).

---

## Stripped paths (single source of truth)

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

## Preconditions

- `origin` → private `sureforms`.
- Public-mirror remote may be named **`mirror`** or **`public`** — detect with `git remote -v` and use whichever points at `sureforms-public`. Examples use `$MIRROR`.

### ⚠️ Ruleset constraints (read first)

1. **Verified signatures on every commit.** Signing is not enough — GitHub must mark it **Verified**, which needs BOTH:
   - the signing key registered on the pushing account as a **Signing Key** (an *Authentication* key does NOT count — separate GitHub entries), and
   - the **committer email** is a **verified** email on that account.
   - Validated combo on this machine: on-disk key **`~/.ssh/id_ed25519`** (registered as a Signing Key on `vanshk141999`) + committer **`kvansh297@gmail.com`**. The Secretive hardware key also verifies but prompts Touch-ID **per commit**, so it cannot bulk-sign ~150 commits — use `id_ed25519`.
2. **`sync/master` is force-push protected** → push each sync to a **fresh** branch `sync/master-<YYYYMMDD>` (`-v2`, `-v3`… if re-pushing same day).
3. **Claude's guardrail blocks the agent from pushing to the public mirror.** The agent prepares everything and hands the user the exact `git push` to run themselves. `gh pr` / `gh api` are agent-allowed.

## Instructions

### Step 1–4: record state, fetch, no-op check, temp worktree
Unchanged from the original skill — stash if dirty, `git fetch origin master` + `git fetch $MIRROR master`, compare `origin/master` vs `$MIRROR/master`, then:
```bash
PRIVATE_TIP=$(git rev-parse origin/master)
BASE=$(git merge-base "$MIRROR/master" "$PRIVATE_TIP")   # needed for the re-sign range
WORKTREE=$(mktemp -d)/srfm-sync-public
git worktree add "$WORKTREE" "$PRIVATE_TIP"
cd "$WORKTREE"
```

### Step 5: strip internal paths
Unchanged — `git rm -r --quiet` each path in the stripped list that exists; `rmdir .scripts` if empty; commit the strip:
```bash
git -c user.name="Vansh Kapoor" -c user.email="kvansh297@gmail.com" \
    commit -m "chore: strip internal-only paths from public mirror sync"
```

### Step 6: bulk re-sign ALL commits (verified signatures)
`filter-branch` reuses each commit's tree + parents and only adds a signature — zero merge conflicts (unlike `rebase --rebase-merges`).
```bash
FILTER_BRANCH_SQUELCH_WARNING=1 git \
  -c gpg.format=ssh -c user.signingkey="$HOME/.ssh/id_ed25519.pub" \
  filter-branch -f \
    --env-filter 'export GIT_COMMITTER_NAME="Vansh Kapoor"; export GIT_COMMITTER_EMAIL="kvansh297@gmail.com"' \
    --commit-filter 'git -c gpg.format=ssh -c user.signingkey="$HOME/.ssh/id_ed25519.pub" commit-tree -S "$@"' \
    -- "$BASE..HEAD"
```

### Step 7: cap with a merge commit so the public PR diff is CLEAN
Capping with a merge commit whose **first parent is `$MIRROR/master`** makes GitHub diff against `$MIRROR/master` (which has no internal files), so the PR shows **only real upstream changes — no internal-file deletions**.
```bash
SIGNED_TIP=$(git rev-parse HEAD)
MERGE=$(git -c gpg.format=ssh -c user.signingkey="$HOME/.ssh/id_ed25519.pub" \
  -c user.name="Vansh Kapoor" -c user.email="kvansh297@gmail.com" \
  commit-tree -S "$(git rev-parse HEAD^{tree})" -p "$MIRROR/master" -p "$SIGNED_TIP" \
  -m "Sync master from upstream")
BRANCH="sync/master-$(date +%Y%m%d)"
git branch -f "$BRANCH" "$MERGE"
```
Push to a **fresh** branch (NOT an existing one — updating an existing branch makes the ruleset re-evaluate `$MIRROR/master`'s own unsigned ancestors and reject). **The USER runs this** (agent is blocked):
```bash
git push $MIRROR <BRANCH>:refs/heads/<BRANCH>
```

### Step 8: open the PR (agent-allowed)
```bash
gh pr create -R brainstormforce/sureforms-public --base master --head "$BRANCH" \
  --title "Sync master from upstream" --body "...range, highlights, note that diff is vs public/master..."
```
Close/delete any superseded sync branch + PR:
```bash
gh pr close <OLD_PR> -R brainstormforce/sureforms-public
gh api -X DELETE repos/brainstormforce/sureforms-public/git/refs/heads/<OLD_BRANCH>
```

### Step 9–10: tear down worktree, restore developer state
Unchanged.

## Error Recovery
- **Push rejected, "Commits must have verified signatures"** → a commit is signed by an unregistered key or committed under an unverified email. Confirm `id_ed25519` is a **Signing Key** on the account and committer is `kvansh297@gmail.com`. Push a single test commit to a throwaway branch to isolate before re-signing ~150.
- **Push rejected, "Cannot force-push"** → you targeted `sync/master`; use a fresh `sync/master-<date>` branch.
- **2–N violations naming commits already on `master`** → you updated an existing branch instead of a fresh one; push to a new branch name.
- **Agent "denied by auto mode classifier" on push** → expected; the user must run the push.
