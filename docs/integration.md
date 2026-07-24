---
name: wp-module-migration
title: Integration
description: How the module registers and integrates.
updated: 2026-06-22
---

# Integration

The module registers with the Newfold Module Loader via bootstrap.php. It integrates with the standalone InstaWP migration utility (`utils/iwp-migration-utils.php`) for migration URL generation and plugin bootstrap. The host plugin (e.g. onboarding) uses it to start migration flows. See [dependencies.md](dependencies.md).
