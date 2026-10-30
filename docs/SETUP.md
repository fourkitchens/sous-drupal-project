# Set up a site

Install PHP 8.4, Composer 2, Docker and DDEV on your computer.
DDEV supplies Node.js, npm, Git and the site's PHP/database tools.

## Create a site

```sh
composer create-project fourkitchens/sous-drupal-project:^8.0 my-site
cd my-site
```

Composer's post-create hook runs `sous-build.sh` once. It names the project from
its directory, stores the theme name in `package.json`'s `config.theme`, asks for
a content composition and optional Config Split, then starts DDEV and installs.
For unattended installation, set both answers:

```sh
SOUS_CONTENT=layout-builder SOUS_CONFIG_SPLIT=yes \
  composer create-project fourkitchens/sous-drupal-project:^8.0 my-site
```

`SOUS_CONTENT` accepts `default`, `layout-builder`, `paragraphs` or `admin-only`
(and menu numbers `0`–`3`). `SOUS_CONFIG_SPLIT` accepts `yes` or `no`, defaulting
to no. Noninteractive installation requires an explicit content choice.
The installer creates `sous_chef` with the `superuser` role, blocks `superuser_1`,
builds the theme, exports configuration and prints a login link.

Commit manifests, lockfiles, `patches.lock.json`, custom code/theme and the complete
`config/default` export, including `core.extension.yml`, `system.site.yml` and UUIDs.
Setup removes the exact `settings.php` entry from `.gitignore`; commit
`web/sites/default/settings.php` too. Keep credentials in local or host settings.
Recipes are applied once during setup and then unpacked, so the site owns its
dependencies and configuration. Package updates do not reapply configuration;
adopt intended changes explicitly, export them and review the diff.

## Restore or refresh locally

Clone the site's repository and obtain its database backup and uploaded files.
Run from the project root whenever the local site needs restoring:

```sh
sh devops/scripts/setup.sh "reference/site-db.sql.gz"
```

This starts DDEV, installs dependencies, builds the theme, replaces and sanitizes
the database, deploys configuration and prints a login link after success.
It accepts `.sql` or `.sql.gz`; omit the argument to use `reference/site-db.sql.gz`.
If that default is missing, `devops/hooks/download-db.sh` must write a backup to `$1`.

To import and sanitize only, or save a timestamped backup under `reference/`:

```sh
sh devops/scripts/import-data.sh "reference/site-db.sql.gz"
sh devops/scripts/local-data-bak.sh
```

Sanitization sets user passwords to `admin` and emails to `user-%uid@example.com`.
Optional `post-import.sh` and `post-config-import.sh` hooks in `devops/hooks/`
run after import and deployment. Use `runtime_drush` for project commands there.

## Add a recipe

Review the [package guide](https://github.com/fourkitchens/sous-drupal-packages),
then download, apply and export:

```sh
ddev composer require fourkitchens/sous-content-types:^2.0
ddev npm run install-recipe -- fourkitchens/sous-content-types
ddev npm run confex
```

The helper applies an installed recipe under `web/recipes/`; it does not download
packages. Review and commit the changed manifest, lockfile and complete export.
See [Development](DEVELOPMENT.md) for checks and [Deployment](DEPLOYMENT.md) for updates.

## Local settings and Config Split

`settings.php` uses Drupal's standard local include:

```php
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}
```

DDEV supplies database credentials through `settings.ddev.php`.
Keep `settings.local.php` untracked. Export production defaults with aggregation
on and errors hidden; supply production credentials and services through the host.

The optional Config Split recipe adds inactive `local` and `staging` definitions.
Enable local in `settings.local.php`:

```php
$config['config_split.config_split.local']['status'] = TRUE;
```

Enable staging in that host's settings:

```php
$config['config_split.config_split.staging']['status'] = TRUE;
```

Keep both splits inactive in exported configuration.
