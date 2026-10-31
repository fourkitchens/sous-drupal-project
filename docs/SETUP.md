# Set up a site

Install PHP 8.4, Composer 2, Git, Docker, DDEV and Node.js from `.nvmrc`.
Run npm scripts on your computer; they call DDEV for PHP, Drush and the database.

## Create a site

```sh
composer create-project fourkitchens/sous-drupal-project:^8.0 my-site
cd my-site
```

Choose a content composition, optional recipes and CI before installation starts.
The recipe menu offers package-defined extras; enter numbers or names separated
by spaces or commas, or Enter for none. CI choices are CircleCI, GitHub Actions,
GitLab CI/CD or None.

Set all answers for an unattended installation:

```sh
SOUS_CONTENT=layout-builder SOUS_RECIPES=sous-config-split SOUS_CI=gitlab \
  composer create-project fourkitchens/sous-drupal-project:^8.0 my-site
```

`SOUS_CONTENT` accepts `default`, `layout-builder`, `paragraphs` or `admin-only`
(numbers 0–3); use it for compositions. `SOUS_CI` accepts `circleci`, `github`,
`gitlab` or `none` (numbers 0–3). Set `SOUS_RECIPES=''` to skip extras and the index.
Commit manifests, lockfiles, custom source, `config/default` and
`web/sites/default/settings.php`; keep credentials in local or host settings.

## Restore or refresh locally

Run from the project root with a database backup and uploaded files:

| Command | Purpose |
| --- | --- |
| `npm run setup -- reference/site-db.sql.gz` | Fresh clone: install Node dependencies, build assets, then rebuild |
| `npm run rebuild -- reference/site-db.sql.gz` | Branch reset: start DDEV, install PHP dependencies, import, deploy and print a login |
| `npm run import-data -- reference/site-db.sql.gz` | Import and sanitize the database only |
| `npm run local-data-bak` | Save a timestamped backup under `reference/` |

Omit the backup argument to use `reference/site-db.sql.gz`; `.sql` also works.
Edit `devops/scripts/get-db.sh` to fetch missing backups, or use `ddev pull`.
Sanitization sets passwords to `admin` and emails to `user-%uid@example.com`.
Add post-import steps in `devops/scripts/import-data.sh`; deployment steps belong
in `hook_deploy_NAME()`. Rebuild leaves Node dependencies and theme assets alone.

## Add a recipe

Recipes apply once and then unpack; package updates do not replay configuration.
Download, apply and export later additions:

```sh
ddev composer require fourkitchens/sous-config-split:^2.0
npm run install-recipe -- fourkitchens/sous-config-split
npm run confex
```

See [Development](DEVELOPMENT.md) for checks and [Deployment](DEPLOYMENT.md) for updates.

## Local settings and Config Split

Local overrides load only in DDEV. Keep `settings.local.php` untracked;
production settings should enable aggregation and hide errors.

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

Config Split supplies inactive `local` and `staging` definitions. Enable local in
`settings.local.php` with `$config['config_split.config_split.local']['status'] = TRUE;`
and staging in host settings with `$config['config_split.config_split.staging']['status'] = TRUE;`.
Keep both splits inactive in exported configuration.
