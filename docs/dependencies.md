---
name: wp-module-migration
title: Dependencies
description: Composer and npm dependencies.
updated: 2026-07-20
---

# Dependencies

**Runtime:** bundled `utils/iwp-migration-utils.php`, newfold-labs/wp-module-loader. **Dev:** johnpbloch/wordpress, lucatume/wp-browser, newfold-labs/wp-php-standards, phpunit/phpcov, wp-cli/i18n-command.

## Vendored InstaWP utility

`utils/iwp-migration-utils.php` is vendored from [InstaWP iwp-migration-helper](https://github.com/InstaWP/iwp-migration-helper/blob/develop/migration-utils/iwp-migration-utils.php). Treat it as upstream code: avoid drive-by edits and only patch for documented security or compatibility reasons.

**Pinned upstream baseline:** `migration-utils/iwp-migration-utils.php` from `develop` as of **2026-06-22** (see the file header in `utils/iwp-migration-utils.php`).

**Local patches (re-apply after upstream refresh):**

1. SSL verification enabled by default (`nfd_migration_iwp_sslverify` filter, default `true`).
2. Locale/slug sanitization in `instaMigrateRequest()` — preserve locale casing/underscores; use `sanitize_title()` for slugs so dashes are kept.
3. Outbound API user-agent uses `getInstaWPUserAgent( 'wp-module-migration' )`.
4. `installInstaWPConnect()` error copy references InstaWP Connect (not InstaMigrate).
5. Comment at the v3/v4 engine split in `instaMigrateRequest()` documenting that v4 does not persist `group_uuid`.

**Refresh policy:** when InstaWP releases an update, copy `migration-utils/iwp-migration-utils.php` from the tag or commit SHA InstaWP specifies (do not track floating `develop`), re-apply the patches above, update the pinned baseline date/SHA in this file and the utility header, then run `composer run lint` and `composer run test` and QA both v3 and v4 migration paths.
