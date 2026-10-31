# Local Setup

## Requirements

Install DDEV, Docker, Git, PHP 8.4, Composer 2 and Node.js from `.nvmrc` on your computer.
Run npm scripts from the project root; PHP, Drush and database steps use DDEV.

## Project setup script

Clone the repository, obtain its database backup and uploaded files, then run:

```sh
npm run setup -- reference/site-db.sql.gz
```

Setup installs Node dependencies, builds the theme, then restores and deploys the local site.

## Restore or refresh locally

```sh
npm run rebuild -- reference/site-db.sql.gz
npm run import-data -- reference/site-db.sql.gz
npm run local-data-bak
```

Rebuild leaves Node dependencies and assets alone; import-data only imports and sanitizes.
The default backup is `reference/site-db.sql.gz`; `.sql` also works.
Edit `devops/scripts/get-db.sh` to download missing backups, or configure `ddev pull`.
Sanitization sets passwords to `admin` and emails to `user-%uid@example.com`.
Add local adjustments in `import-data.sh`; deployment steps belong in `hook_deploy_NAME()`.

## Add a recipe

```sh
ddev composer require vendor/recipe
npm run install-recipe -- vendor/recipe
npm run confex
```

Recipes apply once and unpack; review and commit their dependencies and complete configuration export.

## Local settings and Config Split

Local overrides load only in DDEV; keep `settings.local.php` untracked.

```php
if (getenv('IS_DDEV_PROJECT') === 'true') {
  if (file_exists($app_root . '/' . $site_path . '/settings.ddev.php')) {
    include $app_root . '/' . $site_path . '/settings.ddev.php';
  }
  if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
    include $app_root . '/' . $site_path . '/settings.local.php';
  }
}
```

When Config Split is installed, enable local in local settings with
`$config['config_split.config_split.local']['status'] = TRUE;` and staging in host settings with
`$config['config_split.config_split.staging']['status'] = TRUE;`. Keep both inactive in exports.
Use `ddev drush uli --name=admin` for a local login after setup.
