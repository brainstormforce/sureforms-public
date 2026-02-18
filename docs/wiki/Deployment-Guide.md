# Deployment Guide

## Release Process Overview

SureForms follows a branch-based release workflow with automated CI/CD via GitHub Actions.

## Git Workflow

```
feature/branch  -->  next-release  -->  master  -->  tag/release
                     (development)     (production)
```

1. Feature branches are created from `next-release`
2. PRs target `next-release` for review and merge
3. `next-release` is merged to `master` for releases
4. Tags trigger release artifacts

## Build Steps

### Production Build

```bash
# Full production build (JS + SASS + minify)
npm run build

# Individual steps:
npm run build:script   # Build JS via wp-scripts
npm run build:sass     # Compile SASS via Grunt
```

The build process:
1. Webpack bundles JS/React source from `src/` to `assets/build/`
2. Grunt compiles SASS from `sass/` to `assets/css/`
3. Grunt minifies CSS to `assets/css/minified/`
4. Grunt minifies JS to `assets/js/minified/`
5. PostCSS applies Autoprefixer

### i18n

```bash
npm run makepot    # Generate .pot translation file
```

## CI/CD Pipelines

### GitHub Actions Workflows

| Workflow | Trigger | Purpose |
|----------|---------|---------|
| `phpunit.yml` | Pull requests | Run PHP unit tests |
| `playwright.yml` | PR labeled `e2e` | Run E2E browser tests |
| `code-analysis.yml` | Pull requests | PHPStan static analysis |
| `insights.yml` | Pull requests | PHP Insights code quality |
| `pr-title.yml` | Pull requests | Validate PR title format |
| `push-to-deploy.yml` | Push to master | Deploy to production |
| `push-asset-readme-update.yml` | Push to master | Update readme/assets |
| `release-tag-draft.yml` | Tag push | Create draft GitHub release |
| `release-pr-template.yml` | Release PRs | Apply release PR template |
| `claude-code-review.yml` | Pull requests | AI-powered code review |

### Test Pipeline

Pull requests automatically run:
1. PHPUnit tests (PHP 8.2, WordPress trunk)
2. PHPStan static analysis
3. PHP Insights code quality
4. PR title validation
5. E2E tests (when labeled `e2e`)

### Deployment Pipeline

On push to `master`:
1. `push-to-deploy.yml` triggers deployment
2. `push-asset-readme-update.yml` updates WordPress.org assets

### Release Pipeline

1. Merge `next-release` into `master`
2. Create a version tag (e.g., `v2.5.1`)
3. `release-tag-draft.yml` creates a draft GitHub release
4. Review and publish the release

## Pre-Deployment Checklist

- [ ] All tests pass (PHPUnit, Playwright, Jest)
- [ ] PHPStan analysis clean
- [ ] PHPCS standards pass
- [ ] Production build completes without errors
- [ ] Version bumped in `sureforms.php` and `readme.txt`
- [ ] `SRFM_VER` constant updated
- [ ] `SRFM_PRO_RECOMMENDED_VER` checked
- [ ] Changelog updated in `readme.txt`
- [ ] Translation .pot file regenerated
- [ ] No debug code or console.log statements
- [ ] Database migrations tested (if any)

## Version Bumping

Update version in these files:
1. `sureforms.php` -- Plugin header `Version:` and `SRFM_VER` constant
2. `readme.txt` -- `Stable tag:` and changelog section
3. `package.json` -- `version` field

## Related Pages

- [Testing Guide](Testing-Guide) -- Test setup and execution
- [Contributing Guide](Contributing-Guide) -- Development workflow
- [Environment Configuration](Environment-Configuration) -- Build tools
