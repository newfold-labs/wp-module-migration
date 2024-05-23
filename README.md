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
- It triggers the events based on update option hook when insta updates the `instawp_last_migration_details` value.

## Critical Paths
- Migration process is initiated when user hits the endpoint ( `/newfold-migration/v1/migrate/connect` ).
- It triggers the migration initiation process when `nfd_migrate_site` key gets updated in the database.

## How it works
- When user hits the endpoint or updates the value in db, it installs the plugin and connects the plugin with the website and returns a url.
- Once you're redirected to the url, you'll be taken to instawp screen where it'll ask you for source website that you wanted to migrate and migration starts here and completes in the instawp screens only.
- As soon as the migration gets completed, the current BH plugin which is present in destination site is being replaced with the latest BH plugin from hiive url. Instawp is updating the db  value `instawp_last_migration_details` with the respective status, which we're using to trigger the events based on update hook of that value and doing the post migration logic here updating the db value to show migration steps in brand plugin dashboard.

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

If you're doing something that will not break anything or not adding any new dependency. But, only doing basic stuff like bug fixes, copy changes etc. Change which doesn't really impact the plugin in bigger way you can put them under the last number of versions. 

If you're doing something, like adding a new library, or changing something in the way the plugin can use our module then it's better to upgrade the minor version which is the 2nd digit of version. 

In rare scenarios, like UI redesign where the change is bigger or a major refactor, we update the 1st digit of version. 

#### Changes to Migration Module

1. Merge approved PRs into trunk branch

2. Do version update to new one in package.json and update the version in bootstrap.php 

3. Run, npm install (this will auto update version in package-lock.json) 

4. Commit above change in separate commit (commit message: Bump to new version value) 

5. Go to https://github.com/newfold-labs/wp-module-migration/releases 

6. Click on Draft a new release button.

7. By default, you'll always create a release from target: trunk branch.

8. Give V version that you want to release and click on create a new tag. 

9. Click Generate release notes button it will basically collect all the pull requests that came in from the previous release to now and then just create a summary. (It won't track any direct comments to the trunk. It will just only track pull request) 

10. Keep Set as the latest release checkbox `checked` as it is by default. 

11. Click Publish a release button. 

12. Go to https://newfold-labs.github.io/satis/ Satis is a PHP registry for our packages. 

13. On above URL in `package` filter, you can search for migration 

14. We have set up an integration within our workflow itself so once workflow completes, we trigger alert to Satis that newer version of migration module is released and rebuild Satis so that it can show newer version in packages (Repo: https://github.com/newfold-labs/satis/actions) 

15. The newer version will appear in 5 to 10 minutes of rebuilding. 

16. You can check the status of Statis build & Publish workflow here https://github.com/newfold-labs/wp-module-migration/actions 

17. On successful completion you can see latest package here https://github.com/newfold-labs/wp-module-migration/pkgs/npm/wp-module-migration 

#### Changes to BlueHost plugin repo

1. In composer.json file, update version of newfold-labs/wp-module-migration 

2. In package.json file, newfold-labs/wp-module-migration : version number 

3. Run command, 
```npm i --legacy-peer-deps```  

4. Package-lock.json should auto update. 

5. Once Satis starts showing updated version run below command for composer update, 
```$ composer update newfold-labs/wp-module-migration -W``` 

6. We need to create a branch (naming convention: update/wp-module-migration-version_number). 

7. Currently we don't have the permission to publish directly to the BlueHost plugin So, we need to create a fork basically of the repo then push it to that fork and then create a pull request against the develop branch. 

8. The new release process is thus completed. 
[More on NewFold WordPress Modules](https://github.com/newfold-labs/wp-module-loader)