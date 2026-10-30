# Maintain and deploy this site

Build from committed lockfiles and the complete configuration export.
Set up hosting, secrets, uploaded files, backups and rollback for this site.
Use [Setup](SETUP.md) locally and [Development](DEVELOPMENT.md) for daily commands.

## Platform versions

The manifests set requirements; lockfiles record installed versions.
DDEV supplies the local runtime. Project creation also needs host PHP and Composer.

| Component | Version / source |
| --- | --- |
| Drupal / PHP | `^11.4.11` / `^8.4` in `composer.json`; DDEV PHP 8.4 |
| Composer / Drush | 2.x / `^13.8.0` |
| DDEV / MariaDB | `>=1.25.2 <2` / 10.11 in `.ddev/config.yaml` |
| Node.js / npm | 24.x in `.nvmrc` and DDEV / bundled npm |
| Sous recipes | `^2.0` in `composer.json` |
| Emulsify / Tools | `^7.3.0` / `^2.2.2` in the Sous Emulsify recipe |
| Emulsify Core / CLI / UI Kit | 4.5.0 / `^2.4.1` / 1.9.0 Drupal variant |

## Build and deploy

```sh
ddev start
ddev composer validate --no-check-publish
ddev composer build-assets
ddev composer check-platform-reqs
ddev npm run lint
ddev composer audit --abandoned=report
ddev npm audit
ddev npm --prefix "web/themes/custom/<theme>" audit
```

`build-assets` installs production PHP dependencies and the npm tools needed to
build the theme. Build optional Storybook with `ddev npm run theme-storybook`.
Resolve audit findings before release.

Ship `vendor/`, Drupal core, contributed extensions, libraries, recipes, custom
source, configuration, settings and deployment scripts. Include the theme's
`dist/` and generated `components/` despite their source Git ignores.
Exclude `node_modules`, local settings, caches and database backups.
Keep secrets and uploaded/private files on the host. Use `web/` as the document root.

Test against a database copy. Back up the destination database and files,
deliver the build, then run its Drush from the destination project root:

```sh
vendor/bin/drush deploy --yes --uri=https://YOUR_SITE
```

This applies database updates, configuration, caches and deployment hooks.
Check pages, editing, assets, logs, cron, queues and integrations.
Follow the site's rollback procedure if deployment fails.

## Update dependencies and configuration

Read release notes, requirements and patch changes. Update selected packages
on a branch with a local database copy; keep Drupal core packages aligned:

```sh
ddev composer update vendor/package --with-all-dependencies
ddev composer validate --no-check-publish
ddev composer check-platform-reqs
ddev composer audit
ddev drush updatedb --yes
ddev drush cache:rebuild
ddev npm run confex
git diff -- config
ddev npm run confim
```

Commit lockfiles and the complete `config/default` export. Confirm the sync path
with `ddev drush status --field=config`. Use settings overrides for host values.
Recipes run once and unpack; package updates do not replay configuration.
Adopt intended recipe changes explicitly and migrate content before removing fields.
Remove local path repositories and resolve published packages before committing locks.
Uninstall modules in Drupal before removing their Composer packages.
Update theme dependencies and checks as described in [Development](DEVELOPMENT.md).

Keep patch definitions in `patches/patches.json`; commit `patches.lock.json`.
After changes, run `ddev composer patches-relock` and `ddev composer patches-doctor`.
Use `ddev composer patches-repatch` locally to reinstall and verify patched packages.
After Entity Usage 2 to 5 updates, run `ddev drush entity-usage:recreate` and check references.
Preserve administrator accounts and roles; account assignments live in each database.

## Set up site CI

For GitHub Actions:

```sh
mkdir -p .github/workflows
cp assets/ci/github-actions/build.yml .github/workflows/build.yml
```

For CircleCI:

```sh
mkdir -p .circleci
cp assets/ci/circleci/config.yml .circleci/config.yml
```

The examples use DDEV to install locked dependencies, lint and build the theme.
Adapt theme commands for a site without Emulsify. Add artifact storage,
deployment credentials and release conditions for your hosting.

For Pantheon with authenticated Terminus, run:

```sh
bash devops/ci/drush-config-import SITE ENV
```

It invokes remote `drush deploy --yes`. `TERMINUS_SITE` and `TERMINUS_ENV` can
supply the arguments. Keep local `setup.sh` database restores in DDEV.
