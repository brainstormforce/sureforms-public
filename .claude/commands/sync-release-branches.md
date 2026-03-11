---
allowed-tools: Bash(git:*)
auto-approve: true
description: Sync master into dev and dev into next-release after a release
---

# Sync Release Branches

After a release, sync the three main branches: master → dev → next-release.

## Instructions

Follow these steps sequentially. If any step fails, jump to the Error Recovery section.

### Step 1: Record current state

1. Record the current branch:
```bash
git rev-parse --abbrev-ref HEAD
```
Store this as ORIGINAL_BRANCH.

2. Check for uncommitted changes:
```bash
git status --porcelain
```
If there is ANY output, stash the changes:
```bash
git stash
```
Store HAD_STASH=true. Otherwise HAD_STASH=false.

### Step 2: Fetch latest from remote

```bash
git fetch origin
```

### Step 3: Update master

```bash
git checkout master
git pull origin master
```

### Step 4: Merge master into dev

```bash
git checkout dev
git pull origin dev
git pull origin master
```

If there are merge conflicts, STOP. Tell the user: "Merge conflicts while merging master into dev. Please resolve them manually." List the conflicting files.

If merge succeeds:
```bash
git push origin dev
```

### Step 5: Merge dev into next-release

```bash
git checkout next-release
git pull origin next-release
git pull origin dev
```

If there are merge conflicts, STOP. Tell the user: "Merge conflicts while merging dev into next-release. Please resolve them manually." List the conflicting files.

If merge succeeds:
```bash
git push origin next-release
```

### Step 6: Return to original branch

```bash
git checkout ${ORIGINAL_BRANCH}
```

If HAD_STASH=true:
```bash
git stash pop
```

Report success: "Branch sync complete. master → dev → next-release are now in sync."

## Error Recovery

If any step fails after switching branches:
1. Switch back to original branch: `git checkout ${ORIGINAL_BRANCH}`
2. Restore stash if needed: `git stash pop` (only if HAD_STASH=true)
3. Report the error to the user.

NEVER force-push. If a merge has conflicts, stop and let the user resolve them.
