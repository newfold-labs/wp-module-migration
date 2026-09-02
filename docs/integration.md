---
name: wp-module-migration
title: Integration
description: How the module registers and integrates.
updated: 2026-09-02
---

# Integration

The module registers with the Newfold Module Loader via `bootstrap.php`. It integrates with the standalone InstaWP migration utility (`utils/iwp-migration-utils.php`) for migration URL generation and plugin bootstrap. The host plugin (e.g. onboarding) uses it to start migration flows. See [dependencies.md](dependencies.md).

## Migration completion (v3 and v4)

v3 and v4 use the same destination-side flow in `InstaWpOptionsUpdatesListener`. InstaMigrate updates `instawp_last_migration_details` when migration status changes. The listener handles both `pre_update_option_instawp_last_migration_details` and `added_option`, because WordPress uses the add path when the option does not exist after a v4 migration replaces the installed plugin.

When the option changes and `nfd_migration_status_sent` is false:

1. Read `migrate_group_uuid` and `status` from the option (not from the API response).
2. Enrich via `UtilityService::get_migration_enrichment()` when the API returns data:
   - v4: `GET https://app.instawp.io/api/v2/migrate-v4/{migrate_group_uuid}`
   - v3 fallback: `GET https://app.instawp.io/api/v2/migrates-v3/status/{migrate_group_uuid}`
3. For terminal statuses (`completed`, `failed`, `aborted`): update Push/LastStep tracking, schedule post-migration crons when `source_site_url` is present, and send Hiive events.
4. `migration_completed` and `migration_successful` are sent from the `nfd_migration_page_speed_destination` cron after page-speed tracking. `migration_failed` and `migration_aborted` are sent immediately on the option hook.

API requests use a Bearer token from the brand migration proxy worker (`GET {NFD_MIGRATION_PROXY_WORKER}/token?brand=...`).

Starting a new migration clears `nfd_migration_status_sent` and stale post-migration cron jobs from prior runs.

## Redirect URL filters

Integrators can adjust migration redirect handling:

| Filter | Purpose |
|--------|---------|
| `nfd_migration_redirect_url` | Final migration redirect URL before returning to the client. |
| `nfd_migration_instawp_v3_redirect_hosts` | Hostnames (default `app.instawp.io`) that trigger v3 brand-proxy URL rebuild with `g_id` and `locale`. |
| `nfd_migration_instawp_redirect_hosts` | Hostnames (default `migrate.instawp.io`, `app.instawp.io`) eligible for brand-proxy host swap on v4 URLs. |
| `nfd_migration_iwp_sslverify` | SSL verification for outbound InstaWP HTTP calls (default `true`). |
