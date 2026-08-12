---
name: wp-module-migration
title: Integration
description: How the module registers and integrates.
updated: 2026-08-12
---

# Integration

The module registers with the Newfold Module Loader via `bootstrap.php`. It integrates with the standalone InstaWP migration utility (`utils/iwp-migration-utils.php`) for migration URL generation and plugin bootstrap. The host plugin (e.g. onboarding) uses it to start migration flows. See [dependencies.md](dependencies.md).

## Migration completion (v3 and v4)

InstaMigrate updates the WordPress option `instawp_last_migration_details` as migration status changes. The module listens on `pre_update_option_instawp_last_migration_details` in `InstaWpOptionsUpdatesListener`.

When the option changes and completion events have not been sent yet (`nfd_migration_status_sent` is false):

1. Read `migrate_group_uuid` and `status` from the option.
2. Enrich migration details via `UtilityService::get_migration_enrichment()`:
   - v4: `GET https://app.instawp.io/api/v2/migrate-v4/{migrate_group_uuid}`
   - v3 fallback: `GET https://app.instawp.io/api/v2/migrates-v3/status/{migrate_group_uuid}`
3. `MigrationCompletionService` handles terminal statuses (`completed`, `failed`, `aborted`), schedules post-migration crons (source hosting info, page speed), and sends Hiive events (`migration_completed`, `migration_successful`, `migration_failed`, `migration_aborted`).

API requests use a Bearer token from the brand migration proxy worker (`GET {NFD_MIGRATION_PROXY_WORKER}/token?brand=...`).

Starting a new migration clears `nfd_migration_status_sent` and any stale post-migration cron jobs from prior runs.
