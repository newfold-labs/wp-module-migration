<a href="https://newfold.com/" target="_blank">
    <img src="https://newfold.com/content/experience-fragments/newfold/site-header/master/_jcr_content/root/header/logo.coreimg.svg/1621395071423/newfold-digital.svg" alt="Newfold Logo" title="Newfold Digital" align="right" 
height="42" />
</a>

# WordPress Migration Module
[![Version Number](https://img.shields.io/github/v/release/newfold-labs/wp-module-migration?color=77dd77&labelColor=00000&style=for-the-badge)](https://github.com/newfold/wp-module-migration/releases)
[![License](https://img.shields.io/github/license/newfold-labs/wp-module-migration?labelColor=333333&color=666666&style=for-the-badge)](https://raw.githubusercontent.com/newfold-labs/wp-module-migration/master/LICENSE)

The migration module is used to initiate the migration process by installing the required plugins for migration.
<br><br>
[![React](https://img.shields.io/badge/Wordpress-21759B?style=for-the-badge&logo=wordpress&logoColor=white)]()
[![React](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)]()
[![React](https://shields.io/badge/react-black?logo=react&style=for-the-badge)]()
<br>

## Module Responsibilities

- It installs the `instawp-connect` plugin and connects the website with it.
- It triggers the events based on update option hook when instawp updates the `instawp_last_migration_details` value in database.

## Critical Paths
- Migration process is initiated when user hits the endpoint ( `/newfold-migration/v1/migrate/connect` ).
- It triggers the migration initiation process when `nfd_migrate_site` key gets updated in the database.

## How it works
- When user hits the endpoint or updates the value in db, it installs the plugin and connects the plugin with the website and returns a url.
- Once you're redirected to the url, you'll be taken to instawp screen where it'll ask you for source website that you wanted to migrate and migration starts here and completes in the instawp screens only.
- As soon as the migration gets completed, the current BH plugin which is present in destination site is being replaced with the latest BH plugin from hiive url. Instawp is updating the db  value `instawp_last_migration_details` with the respective status, which we're using to trigger the events ( `migration_completed`, `migration_failed` ) based on update hook of that value and doing the post migration logic here updating the db value to show migration steps in brand plugin dashboard.

## Installation

### 1. Add the Newfold Satis to your `composer.json`.

 ```bash
 composer config repositories.newfold composer https://newfold-labs.github.io/satis
 ```

### 2. Require the `newfold-labs/wp-module-migration` package.

 ```bash
 composer require newfold-labs/wp-module-migration
 ```

## Steps to create new release for Migration module

Versioning is **tag-based**. There is no version field to bump in `composer.json`, `package.json`, or `bootstrap.php`.

### Semver

- **Patch** (e.g. 1.7.2 → 1.7.3): bug fixes, copy changes, dependency reverts, and other low-risk updates.
- **Minor** (e.g. 1.7.x → 1.8.0): new libraries or changes to how consuming plugins integrate with the module.
- **Major** (e.g. 1.x → 2.0.0): breaking changes or large refactors.

### Changes to Migration Module

1. Merge approved PRs into `main`.

2. Go to [GitHub Releases](https://github.com/newfold-labs/wp-module-migration/releases).

3. Click **Draft a new release**.

4. Target branch: `main`.

5. Enter the new version as the tag (e.g. `1.7.3`) and create the tag.

6. Click **Generate release notes** to summarize merged PRs since the previous release.

7. Keep **Set as the latest release** checked.

8. Click **Publish release**.

9. Satis rebuilds automatically via the release workflow. The new version usually appears on [Satis](https://newfold-labs.github.io/satis/) within 5–10 minutes. Search for `migration` in the package filter.

10. Check workflow status [here](https://github.com/newfold-labs/wp-module-migration/actions).

11. For notable releases, add a brief entry to [docs/changelog.md](docs/changelog.md).

### Changes to BlueHost plugin repo

1. In `composer.json`, update the version constraint for `newfold-labs/wp-module-migration`.

2. Once Satis shows the new version, run:

   ```sh
   composer update newfold-labs/wp-module-migration -W
   ```

3. Create a branch (naming convention: `update/wp-module-migration-<version>`).

4. Open a pull request against the develop branch (via fork if you do not have direct publish access).

5. The release process is complete.

[More on NewFold WordPress Modules](https://github.com/newfold-labs/wp-module-loader)