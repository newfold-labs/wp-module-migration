---
name: wp-module-migration
title: Development
description: Lint, test, workflow, and releases.
updated: 2026-05-25
---

# Development

## Lint and test

PHP: `composer run lint`, `composer run fix`. Tests: `composer run test`.

When changing dependencies, update [dependencies.md](dependencies.md).

## Releases

The module version is **not** stored in `composer.json`, `package.json`, or `bootstrap.php`. Releases are tagged on `main` and published through GitHub Releases. Satis picks up the new tag automatically via the `satis-update` workflow.

### Semver

- **Patch** (e.g. 1.7.2 → 1.7.3): bug fixes, copy changes, dependency reverts, low-risk updates.
- **Minor** (e.g. 1.7.x → 1.8.0): new dependencies, API or integration changes that affect consumers.
- **Major** (e.g. 1.x → 2.0.0): breaking changes or large refactors.

### Release checklist

1. Merge approved PRs into `main`.
2. Go to [GitHub Releases](https://github.com/newfold-labs/wp-module-migration/releases) and draft a new release from `main`.
3. Create a new tag for the chosen version (e.g. `1.7.3`).
4. Generate release notes and publish.
5. Wait for Satis to rebuild (typically 5–10 minutes). Check [Actions](https://github.com/newfold-labs/wp-module-migration/actions).
6. Update consuming brand plugins: bump `newfold-labs/wp-module-migration` in `composer.json`, then run `composer update newfold-labs/wp-module-migration -W`.
7. For notable releases, add a brief entry to [changelog.md](changelog.md).

See the root [README.md](../README.md) for the full release and brand-plugin update steps.
