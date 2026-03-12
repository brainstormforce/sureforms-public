# Investigate Issue

Investigate a reported issue using parallel subagents for speed.

## Input

$ARGUMENTS — GitHub issue URL, Jira ticket ID, or a description of the issue.

If no arguments provided, stop and ask: "Please provide an issue URL, Jira ticket ID, or describe the issue."

## Step 1 — Read the Issue

- If a GitHub URL: use `gh issue view` to read the issue
- If a Jira ticket: use the Jira MCP tools to read the ticket
- If a description: use the provided text directly

Extract: title, description, steps to reproduce, expected vs actual behavior, screenshots, labels.

## Step 2 — Classify

Determine the type:
- **Bug** — something is broken, unexpected behavior, error, regression
- **Feature** — new functionality requested
- **Improvement** — enhancement to existing functionality

Tell the user: "This looks like a **[type]**. Proceeding with [investigation/planning]."

## Step 3 — Investigate with Subagents

Use subagents to parallelize the investigation:

### For Bugs:
Launch these subagents in parallel:
1. **Code Search Agent** — Search the codebase for files related to the bug (grep for keywords, function names, error messages, related hooks/filters)
2. **Test Coverage Agent** — Check if there are existing tests covering this area, identify gaps
3. **Impact Analysis Agent** — Trace all usages of the affected code to understand blast radius

Once all agents report back, synthesize findings:
- Present the probable root cause with file path and line number
- Show which other areas might be affected
- Suggest a fix with a clear explanation
- Ask the user: "Should I apply this fix?"
- If yes — apply the fix, run linters, and suggest running relevant tests

### For Features:
1. Enter plan mode
2. Launch subagents in parallel:
   - **Pattern Discovery Agent** — Find similar existing features in the codebase to follow the same patterns
   - **Impact Analysis Agent** — Identify all files that will need changes
3. Use findings to outline implementation steps
4. Present the plan for approval

### For Improvements:
1. Launch subagents in parallel:
   - **Code Search Agent** — Read the relevant existing code
   - **Impact Analysis Agent** — Trace dependencies to understand what might break
2. Suggest the minimal changes needed
3. Ask the user: "Should I implement these changes?"
4. If yes — apply changes and run linters

## Rules
- Always use subagents for investigation to maximize speed
- Show your reasoning — which files you checked and why
- Be precise with file paths and line numbers
- For bugs, prioritize finding the root cause over applying a quick patch
- Never make changes without asking first
- After any code change, run the verification checklist from CLAUDE.md
