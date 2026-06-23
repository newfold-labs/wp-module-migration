---
name: wp-module-migration
title: Dependencies
description: Composer and npm dependencies.
updated: 2026-06-22
---

# Dependencies

**Runtime:** bundled `utils/iwp-migration-utils.php`, newfold-labs/wp-module-loader. **Dev:** johnpbloch/wordpress, lucatume/wp-browser, newfold-labs/wp-php-standards, phpunit/phpcov, wp-cli/i18n-command.

## Vendored InstaWP utility

`utils/iwp-migration-utils.php` is copied from [InstaWP iwp-migration-helper](https://github.com/InstaWP/iwp-migration-helper/blob/develop/migration-utils/iwp-migration-utils.php). Treat it as upstream code: avoid drive-by edits and only patch for documented security or compatibility reasons.

**Local patches (re-apply after upstream refresh):**

1. SSL verification enabled by default (`nfd_migration_iwp_sslverify` filter, default `true`).
2. Locale/slug sanitization in `instaMigrateRequest()` — preserve locale casing/underscores; use `sanitize_title()` for slugs so dashes are kept.
3. Outbound API user-agent uses `getInstaWPUserAgent( 'wp-module-migration' )`.
4. `installInstaWPConnect()` error copy references InstaWP Connect (not InstaMigrate).

**Refresh policy:** when InstaWP releases an update, replace the file from upstream `develop` (or the tag InstaWP specifies), re-apply the patches above, run `composer run lint` and `composer run test`, then QA both v3 and v4 migration paths.
