# Local Setup

## Requirements

Install DDEV, Docker, Git, PHP 8.4, Composer 2 and Node.js from `.nvmrc` on your computer.
Run project commands from the checkout root.

## Fresh Composer installation

`composer create-project` starts DDEV, installs dependencies and creates the local Drupal site. Open the local URL from the installer summary. Run `ddev start` from the project root if the site is stopped.

## Project setup script

For a clone of an existing site, obtain its database backup and uploaded files, then run:

```sh
npm run setup -- reference/site-db.sql.gz
```

Setup installs Node dependencies, builds available theme sources, replaces the local database with the backup, sanitizes copied accounts and deploys configuration.

## Restore or refresh locally

```sh
npm run rebuild -- reference/site-db.sql.gz
npm run import-data -- reference/site-db.sql.gz
npm run local-data-bak
```

`rebuild` and `import-data` both replace the local database from the backup and sanitize copied accounts. `rebuild` also installs Composer dependencies and deploys configuration.
The default backup is `reference/site-db.sql.gz`; `.sql` also works.
The restore runs `devops/scripts/get-db.sh` only when you omit the backup path and the default file is missing. Edit that script to download the default backup, or configure `ddev pull`.
The backup must be readable and nonempty. Gzip files are checked before the local database is replaced.
Sanitization sets passwords to `admin` and emails to `user-%uid@example.com`.
Add local adjustments in `import-data.sh`; deployment steps belong in `hook_deploy_NAME()`.

## Shared Drupal commands

From a laptop with DDEV installed and `.ddev/config.yaml` present, the shared PHP launcher uses that checkout's DDEV web container. Inside the container, it runs the installed tools directly.

```sh
ddev start
sh devops/scripts/confex.sh
sh devops/scripts/confim.sh --uri=https://mysite.ddev.site
# Run the export from inside the web container.
ddev exec --raw -- sh devops/scripts/confex.sh
```

The `npm run confex` and `npm run confim` shortcuts call the same scripts. See [Deployment](DEPLOYMENT.md#build-and-deploy) for native execution in CI and on hosts.

## Add a recipe

```sh
ddev composer require vendor/recipe
sh devops/scripts/install-recipe.sh vendor/recipe
sh devops/scripts/confex.sh
```

Recipes apply once and unpack; review and commit their dependencies and complete configuration export.

## Local settings and Config Split

The starter loads local overrides in DDEV. Keep `settings.local.php` untracked.

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

Config Split keeps local tools out of production; `config/local` and `config/staging` stay inactive in exports.
Local settings enable the local split in DDEV; activate staging before importing configuration with `$config['config_split.config_split.staging']['status'] = TRUE;` in host settings.
Every generated site includes Stage File Proxy in the local and staging Config Splits. To fetch missing files locally, set the site's source URL as the origin in `settings.local.php`.

```php
$config['stage_file_proxy.settings']['origin'] = 'https://production.example.com';
```

Use `ddev drush uli --name=sous_chef` for a local login after setup.

## Adapt an existing site to Lando

Add a project-owned `.lando.yml` with the `drupal11` recipe, PHP 8.4, `web` as the webroot and a compatible database such as `mariadb:10.11`. Run `lando start` and `lando composer install`. [Lando Drupal configuration](https://docs.lando.dev/plugins/drupal/config.html)

Keep the DDEV settings includes scoped to DDEV. In `settings.php`, include the untracked `settings.local.php` for the Lando service as well, using a Lando-only guard such as `getenv('LANDO_INFO')`. In that local file, use the internal database connection details from `lando info` and set `$config['config_split.config_split.local']['status'] = TRUE;`. [Lando service environment](https://docs.lando.dev/config/env.html)

Use `lando db-import reference/site-db.sql.gz` and `lando db-export` for Lando databases. After importing a copy, sanitize accounts with `lando drush sql:sanitize -y`. The starter installer and bundled local database scripts use DDEV. [Lando Drupal tooling](https://docs.lando.dev/plugins/drupal/tooling.html)

For Lando 3, open a shell in the PHP service and run the shared command from the mounted project root. [Lando shell command](https://docs.lando.dev/cli/ssh.html)

```sh
lando ssh --service appserver
SOUS_PHP_RUNTIME=native sh devops/scripts/confim.sh --uri=https://mysite.lndo.site
```
