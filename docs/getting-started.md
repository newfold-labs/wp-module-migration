---
name: wp-module-migration
title: Getting started
description: Prerequisites, install, and run.
updated: 2026-06-22
---

# Getting started

Prerequisites: PHP 7.3+, Composer. The module vendors InstaWP migration utilities and requires wp-module-loader.

```bash
composer install
composer run test
composer run lint
composer run fix
```

See [integration.md](integration.md) for using in a host plugin.
