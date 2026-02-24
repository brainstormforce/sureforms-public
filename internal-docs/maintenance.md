# Documentation Maintenance Guide

**Version:** 2.5.0

---

## Purpose

This guide explains how to keep SureForms internal documentation accurate and useful.

**Goal:** Documentation should always reflect current reality.

**Principle:** Update docs when code changes, not as afterthought.

---

## When to Update Documentation

### Always Update

**1. New Feature Added**
- Update: `architecture.md`, `apis.md`, `product-vision.md`
- Add: Code examples to `ai-agent-guide.md`
- Update: Onboarding guide if workflow changes

**2. API Changes**
- Update: `apis.md` (new endpoints, hooks, parameters)
- Update: Code examples in all docs referencing changed API
- Mark deprecated APIs in `apis.md`

**3. Database Schema Changes**
- Update: `architecture.md` (database section)
- Update: `codebase-map.md` (new files)
- Update: Migration guide if schema change requires migration

**4. Security Fix**
- Update: `ai-agent-guide.md` (add to common pitfalls)
- Update: `troubleshooting.md` if fix addresses known issue
- Add example of vulnerable pattern to avoid

**5. Breaking Changes**
- Update: `README.md` (migration guide)
- Update: `onboarding.md` (new workflows)
- Update: `faq.md` (address upgrade questions)

---

### Update if Needed

**6. Bug Fixes**
- Update: `troubleshooting.md` if common issue
- Add: To FAQ if frequently asked

**7. Performance Improvements**
- Update: `architecture.md` if architecture changed
- Update: Benchmarks in `README.md`

**8. UI/UX Changes**
- Update: `ui-and-copy.md` (new patterns, copy)
- Update: `onboarding.md` (user journeys)

---

### Don't Update

**9. Code Refactoring (No Behavior Change)**
- No doc update unless internal architecture significantly changed

**10. Minor Copy Changes**
- No doc update unless it changes UX patterns

**11. Version Bumps**
- Update version number at top of each doc
- That's it (unless other changes)

---

## How to Update Documentation

### Step 1: Identify Affected Docs

**Use this decision tree:**

```
Did you change...

┌─ Database schema?
│  └─> Update: architecture.md, codebase-map.md
│
┌─ REST API?
│  └─> Update: apis.md, ai-agent-guide.md (examples)
│
┌─ AJAX handlers?
│  └─> Update: apis.md
│
┌─ Hooks/filters?
│  └─> Update: apis.md, ai-agent-guide.md (patterns)
│
┌─ User-facing UI?
│  └─> Update: ui-and-copy.md, onboarding.md
│
┌─ Security patterns?
│  └─> Update: ai-agent-guide.md, coding-standards.md
│
┌─ Build process?
│  └─> Update: README.md, onboarding.md
│
└─ New file/folder?
   └─> Update: codebase-map.md
```

---

### Step 2: Read Existing Doc

**Before editing:**
1. Read entire document
2. Identify outdated sections
3. Check for consistency with your changes

**Don't:**
- Blindly add new section without reading context
- Create duplicate information
- Use different terminology than existing docs

---

### Step 3: Make Minimal Changes

**Best practices:**

**Do:**
- Update only what changed
- Keep existing structure
- Match existing tone and style
- Add code examples if helpful

**Don't:**
- Rewrite entire document (unless necessary)
- Change unrelated sections
- Reorganize without discussion
- Remove information (mark deprecated instead)

---

### Step 4: Verify Examples Still Work

**All code examples must be tested:**

**PHP examples:**
```bash
# Create test file
cat > /tmp/test-example.php << 'EOF'
<?php
// Paste example code here
EOF

# Check syntax
php -l /tmp/test-example.php
```

**JavaScript examples:**
```bash
# Check syntax (if using ESLint)
npx eslint /tmp/test-example.js
```

**Shell commands:**
```bash
# Test in safe environment
# Don't run destructive commands!
```

**WordPress CLI:**
```bash
# Test on development site
wp eval "$(cat /tmp/test-snippet.php)"
```

---

### Step 5: Update Cross-References

**Check if other docs reference changed section:**

```bash
# Search for references
cd internal-docs/
grep -r "architecture.md#database" .
grep -r "\[Database Schema\]" .
```

**Update links if:**
- Section renamed
- Section moved
- File renamed

---

### Step 6: Update Version Number

**At top of each modified doc:**

```markdown
**Version:** 2.5.1  # ← Change this
```

**When to increment version:**
- **Major (3.0.0):** Complete rewrite, major changes
- **Minor (2.6.0):** New sections, significant updates
- **Patch (2.5.1):** Small fixes, clarifications

**Match plugin version:**
- Docs version should match plugin version
- Example: Plugin 2.5.0 → Docs 2.5.0

---

## Documentation Standards

### File Organization

**Keep this structure:**

```
internal-docs/
├── README.md               # Entry point, always read first
├── architecture.md         # High-level system design
├── codebase-map.md         # File structure reference
├── apis.md                 # Technical API reference
├── coding-standards.md     # Code conventions
├── ai-agent-guide.md       # AI-specific guidance
├── onboarding.md           # Learning paths
├── product-vision.md       # Product context
├── ui-and-copy.md          # UX patterns
├── troubleshooting.md      # Problem solutions
├── faq.md                  # Common questions
├── glossary.md             # Term definitions
└── maintenance.md          # This file
```

**Never:**
- Add files outside this structure
- Create subdirectories
- Rename existing files (without team approval)

---

### Writing Style

**Tone:**
- Clear, concise, direct
- Friendly but professional
- No marketing fluff

**Format:**
- Short paragraphs (3-4 sentences max)
- Bullet points for lists
- Code blocks for examples
- Headers for navigation

**Code Examples:**

**✅ Good:**
```php
// Specific, realistic example
$entries = Entries::get_all([
  'where' => [
    ['key' => 'form_id', 'value' => 123, 'compare' => '=']
  ],
  'limit' => 20
]);
```

**❌ Bad:**
```php
// Vague, generic
$data = SomeClass::get_stuff($args);
```

---

### Cross-Referencing

**Internal links:**
```markdown
See [Architecture Guide](architecture.md) for details.
See [Database Schema](architecture.md#database-schema) for table structure.
```

**External links:**
```markdown
Read [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/).
```

**Code references:**
```markdown
See `inc/form-submit.php:88-119` for nonce verification.
```

---

### Code Block Guidelines

**Always specify language:**

````markdown
```php
// PHP code here
```

```javascript
// JavaScript here
```

```bash
# Shell commands here
```

```sql
-- SQL queries here
```
````

**Include comments:**
```php
// Good: Explain what code does
$entries = Entries::get_all([
  'where' => [
    ['key' => 'status', 'value' => 'published', 'compare' => '=']
  ]
]);
```

**Show output if helpful:**
```bash
wp plugin list --status=active
# Output:
# sureforms        active
# sureforms-pro    active
```

---

## Deprecation Process

**When removing features:**

### Step 1: Mark as Deprecated

**In code:**
```php
/**
 * Old function.
 *
 * @deprecated 2.5.0 Use new_function() instead.
 */
function old_function() {
  _deprecated_function(__FUNCTION__, '2.5.0', 'new_function');
  return new_function();
}
```

**In docs:**
```markdown
## ~~Old Feature~~ (Deprecated)

**Deprecated in:** 2.5.0
**Removed in:** 3.0.0
**Replacement:** [New Feature](#new-feature)

This feature is deprecated and will be removed in version 3.0.0.
Use [New Feature](#new-feature) instead.

~~Old documentation here...~~
```

---

### Step 2: Update Migration Guide

**In README.md:**

```markdown
## Upgrading from 2.4.x to 2.5.0

### Breaking Changes

**Old Feature Deprecated:**
- **What changed:** `old_function()` is now deprecated
- **Action required:** Replace with `new_function()`
- **Code example:**
  ```php
  // Old (deprecated)
  old_function($data);

  // New (recommended)
  new_function($data);
  ```
```

---

### Step 3: Remove After Major Version

**On next major version (3.0.0):**
1. Remove deprecated code
2. Remove ~~strikethrough~~ docs
3. Update changelog

---

## Changelog Management

**Keep CHANGELOG.md updated:**

### Format

```markdown
# Changelog

## [2.5.1] - 2026-02-15

### Added
- New REST endpoint: `/sureforms/v1/forms/{id}/duplicate`
- Support for custom date formats in email notifications

### Changed
- Improved performance of entry queries (20% faster)
- Updated Stripe API to v2024-01-01

### Deprecated
- `old_function()` - Use `new_function()` instead

### Fixed
- Bug where conditional logic didn't work with checkboxes
- Memory leak in AI form generation

### Security
- Fixed XSS vulnerability in admin settings (CVE-2026-0001)

## [2.5.0] - 2026-02-01

...
```

**Categories:**
- **Added:** New features
- **Changed:** Changes to existing features
- **Deprecated:** Features marked for removal
- **Removed:** Features removed
- **Fixed:** Bug fixes
- **Security:** Security fixes

---

## Documentation Review Checklist

**Before committing doc changes:**

### Content
- [ ] Information is accurate (tested code examples)
- [ ] No outdated information (double-checked)
- [ ] Cross-references updated (links work)
- [ ] Version number updated
- [ ] Terminology consistent with other docs

### Style
- [ ] Clear and concise (no fluff)
- [ ] Code examples formatted correctly
- [ ] Spelling and grammar correct
- [ ] Tone matches existing docs

### Technical
- [ ] All code examples tested
- [ ] All commands verified (on dev environment)
- [ ] All links work (no 404s)
- [ ] File paths correct (checked in codebase)

### Accessibility
- [ ] Headings in logical order (H1 → H2 → H3)
- [ ] Code blocks have language specified
- [ ] Alt text for images (if any)
- [ ] Tables have headers

---

## Automation & Tools

### Linting Documentation

**Check Markdown syntax:**

```bash
# Install markdownlint
npm install -g markdownlint-cli

# Lint all docs
cd internal-docs/
markdownlint *.md
```

**Common issues caught:**
- Inconsistent heading levels
- Trailing whitespace
- Missing blank lines around code blocks

---

### Link Checking

**Verify all links work:**

```bash
# Install markdown-link-check
npm install -g markdown-link-check

# Check links
markdown-link-check internal-docs/*.md
```

**Fix broken links:**
- Update to correct URL
- Remove if resource no longer exists
- Use Internet Archive if critical resource

---

### Spell Checking

**Use spell checker:**

```bash
# Install aspell
brew install aspell  # macOS
apt install aspell   # Linux

# Check spelling
aspell check internal-docs/README.md
```

**Custom dictionary:**

Create `.aspell.en.pws`:
```
personal_ws-1.1 en 50
SureForms
WordPress
Gutenberg
Mailchimp
PayPal
```

---

## Documentation Templates

### New Feature Documentation Template

**When adding new feature:**

```markdown
## [Feature Name]

**Since:** [version]
**Type:** [Free/Pro]

### Overview

[2-3 sentence description of what feature does and why it exists]

### Use Cases

- [Use case 1]
- [Use case 2]
- [Use case 3]

### How to Use

**Step 1: [Action]**

[Description]

```php
// Code example
```

**Step 2: [Action]**

[Description]

### Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `option_name` | string | 'default' | What this does |

### API Reference

**REST Endpoint:**
```
POST /sureforms/v1/feature
```

**Parameters:**
- `param1` (string, required) - Description
- `param2` (integer, optional) - Description

**Response:**
```json
{
  "success": true,
  "data": { ... }
}
```

### Examples

**Example 1: [Use case]**
```php
// Code here
```

**Example 2: [Use case]**
```php
// Code here
```

### Troubleshooting

**Issue:** [Common problem]
**Solution:** [How to fix]

### Related

- [Related feature 1](link)
- [Related feature 2](link)
```

---

### Bug Fix Documentation Template

**When fixing bug:**

**In troubleshooting.md:**

```markdown
### [Issue Description]

**Symptom:** [What user sees]

**Diagnosis:**

[How to identify the issue]

```bash
# Commands to diagnose
```

**Fixes:**

**Option 1: [Fix description]**
```php
// Code or command
```

**Option 2: [Alternative fix]**
```php
// Alternative code
```

**Prevents:** [What this prevents]

**See also:** [Related issues]
```

---

## Review & Approval Process

### Before Merging Docs

**Self-review:**
1. Read your changes out loud
2. Test all code examples
3. Run linters
4. Check cross-references

**Peer review:**
1. Request review from team member
2. Address feedback
3. Update based on comments

**Final checks:**
1. Rebase on main branch
2. Verify no conflicts
3. One last proofread

---

### Documentation Pull Request Template

**PR description:**

```markdown
## Documentation Update

**Plugin Version:** 2.5.1
**Docs Changed:** [List files]

### Changes Made

- [ ] Added documentation for [feature]
- [ ] Updated [section] in [file]
- [ ] Fixed typos in [file]

### Verification

- [x] All code examples tested
- [x] All links checked
- [x] Spelling checked
- [x] Cross-references updated
- [x] Version numbers updated

### Related

- Related PR: #123
- Related Issue: #456
```

---

## Documentation Metrics

**Track documentation quality:**

### Metrics to Monitor

**Coverage:**
- % of features documented
- % of APIs documented
- % of code examples tested

**Freshness:**
- Days since last update
- Number of outdated sections
- Deprecated content count

**Quality:**
- Broken link count
- Spelling error count
- User-reported doc issues

**Engagement:**
- Doc views (if analytics enabled)
- Time spent reading
- Bounce rate (if high, docs unclear)

---

## Long-Term Maintenance

### Quarterly Review

**Every 3 months:**

1. **Audit all docs:**
   - Read each document start to finish
   - Test all code examples
   - Verify all links
   - Update outdated info

2. **User feedback:**
   - Review GitHub issues tagged "documentation"
   - Survey users about doc clarity
   - Identify gaps in coverage

3. **Reorganize if needed:**
   - Move sections to better fit structure
   - Split large docs if too long
   - Merge duplicate information

---

### Annual Review

**Every year:**

1. **Major cleanup:**
   - Remove deprecated content
   - Archive old versions
   - Rewrite outdated sections

2. **Structure evaluation:**
   - Does structure still make sense?
   - Should we add/remove files?
   - Are cross-references clear?

3. **Voice & tone:**
   - Is tone consistent?
   - Is it still beginner-friendly?
   - Does it match product evolution?

---

## Questions?

**For documentation questions:**
- Create GitHub issue: `[Docs] Your question`
- Tag: `documentation`
- Assign: Documentation maintainer

**For urgent doc bugs:**
- Ping in Slack: `#sureforms-docs`
- Include: File name, line number, issue description

---

## Conclusion

**Documentation is code.**

Treat it with same care:
- Test before committing
- Review changes
- Keep it DRY (Don't Repeat Yourself)
- Refactor when needed

**Good documentation:**
- Saves support time
- Accelerates onboarding
- Prevents bugs
- Shows we care

**Thank you for maintaining SureForms docs!** 📚

---

**Version:** 2.5.0
**Last Updated:** 2026-02-12
**Maintainer:** SureForms Team
