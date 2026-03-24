---
name: wp-module-migration
title: Integration
description: How the module registers and integrates.
updated: 2025-03-18
---

# Integration

The module registers with the Newfold Module Loader via bootstrap.php. It integrates with InstaWP connect-helpers for migration. The host plugin (e.g. onboarding) uses it to start migration flows. See [dependencies.md](dependencies.md).
