# Set up a site

Install PHP 8.4, Composer 2, Git, Docker, DDEV and Node.js from `.nvmrc`
on your computer. Run npm scripts there; they call DDEV for PHP, Drush and
database operations and run Node tools directly.

## Create a site

```sh
composer create-project fourkitchens/sous-drupal-project:^8.0 my-site
cd my-site
```

Composer's post-create hook runs `sous-build.sh` once. It names the project from
its directory, stores the theme name in `package.json`'s `config.theme`, asks for
a content composition, optional recipes and a CI provider, then starts DDEV
and installs. After the content choice, it reads the published Sous 2.x index
and lists recipes not already included by the base setup or chosen composition.
The menu updates as recipes are published; no question appears if none remain.
Choose numbered entries or short names such as `sous-config-split`, separated by
spaces or commas:

```text
Add recipes (numbers or names, separated by spaces; Enter for none):
```

Enter or end of input skips additional recipes. The CI question follows:

```text
CI: [0] CircleCI [1] GitHub Actions [2] GitLab CI/CD [3] None:
```

For unattended installation, set all three answers:

```sh
SOUS_CONTENT=layout-builder SOUS_RECIPES=sous-config-split SOUS_CI=gitlab \
  composer create-project fourkitchens/sous-drupal-project:^8.0 my-site
```

`SOUS_CONTENT` accepts `default`, `layout-builder`, `paragraphs` or `admin-only`
(and menu numbers `0`–`3`). `SOUS_CI` accepts `circleci` (`0`), `github` (`1`),
`gitlab` (`2`) or `none` (`3`). Noninteractive installation requires explicit
content and CI choices. Set `SOUS_RECIPES` to short names separated by spaces or
commas, or `SOUS_RECIPES=''` for none; setting it skips both the menu and index
fetch. These names are not checked against the index, so unpublished path
packages work and Composer rejects unknown names. Use `SOUS_CONTENT` for the
content compositions; the base recipes are already included.

The installer creates `sous_chef` with the `superuser` role, blocks `superuser_1`,
builds the theme, applies the composition and optional recipes in the chosen
order, exports configuration and prints a login link. Config Split is selected
with `SOUS_RECIPES=sous-config-split` or from the recipe menu.

Commit manifests, lockfiles, `patches.lock.json`, custom code/theme and the complete
`config/default` export, including `core.extension.yml`, `system.site.yml` and UUIDs.
Commit `web/sites/default/settings.php` too. Keep credentials in local or host
settings.
Recipes are applied once during setup and then unpacked, so the site owns its
dependencies and configuration. Package updates do not reapply configuration;
adopt intended changes explicitly, export them and review the diff.

## Restore or refresh locally

Clone the site's repository and obtain its database backup and uploaded files.
For a fresh clone, run from the project root:

```sh
npm run setup -- "reference/site-db.sql.gz"
```

Setup installs root and theme npm dependencies, builds the theme, then runs
`rebuild`. After switching branches, use the quicker reset:

```sh
npm run rebuild -- "reference/site-db.sql.gz"
```

Rebuild starts DDEV, installs PHP dependencies, replaces and sanitizes the
database, deploys configuration and prints a login link. It leaves Node
dependencies and theme assets alone; run `npm run theme-ci` or
`npm run theme-build` when those change.
Both commands accept `.sql` or `.sql.gz`; omit the argument to use
`reference/site-db.sql.gz`. If the backup is missing, `get-db.sh` runs. Edit
`devops/scripts/get-db.sh` with the site's download command; `ddev pull` also works.

To import and sanitize only, or save a timestamped backup under `reference/`:

```sh
npm run import-data -- "reference/site-db.sql.gz"
npm run local-data-bak
```

Sanitization sets user passwords to `admin` and emails to `user-%uid@example.com`.
Add site-specific post-import steps directly in `devops/scripts/import-data.sh`.
Deployment steps belong in `hook_deploy_NAME()`, which Drush deploy runs.
Put local-only values in `settings.local.php`.

## Add a recipe

Review the [package guide](https://github.com/fourkitchens/sous-drupal-packages),
then download, apply and export:

```sh
ddev composer require fourkitchens/sous-content-types:^2.0
npm run install-recipe -- fourkitchens/sous-content-types
npm run confex
```

The helper applies an installed recipe under `web/recipes/`; it does not download
packages. Review and commit the changed manifest, lockfile and complete export.
See [Development](DEVELOPMENT.md) for checks and [Deployment](DEPLOYMENT.md) for updates.

## Local settings and Config Split

`settings.php` loads DDEV settings before local overrides:

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

DDEV supplies database credentials through `settings.ddev.php`.
Local overrides load only in DDEV; a stray `settings.local.php` cannot apply
development settings on a host. Keep it untracked. Export production defaults
with aggregation on and errors hidden; supply credentials and services through
the host.

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
