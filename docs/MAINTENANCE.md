# Maintain an existing site

Update a branch and a local copy of the existing site's database first. Back up
the database and files before changing dependencies or configuration. Review
pending active configuration changes before starting.

## Keep the site's configuration

Commit the complete export in `config/default`, including `core.extension.yml`,
`system.site.yml` and UUIDs. Verify the actual sync path on an older site:

```sh
ddev drush status --field=config
```

Set the intended tracked directory in
`settings.php` before exporting. Keep existing settings and local includes;
Composer initializes missing settings files but does not rewrite existing ones.
Follow [Environments](ENVIRONMENTS.md) to adopt `settings.sous.php` and optional
Config Split definitions.

Export changes made through Drupal, then review the diff:

```sh
ddev npm run confex
git diff -- config
```

Use settings overrides for environment values and credentials. They change the
configuration Drupal reads without becoming stored defaults in the export.
Keep `config/default` suitable for production; use the explicit environment
checks in [Environments](ENVIRONMENTS.md). Never replace the complete export
with a recipe's partial `config` directory or a new starter's empty directory.

## Update dependencies

1. Read release notes, platform requirements, advisories and patch changes for
   the packages being updated. Change constraints deliberately for a new major
   version and keep Drupal core packages aligned.
2. Resolve the selected packages and inspect the manifest and lockfile changes.
   Replace `vendor/package` below with the package names:

   ```sh
   ddev composer update vendor/package --with-all-dependencies
   ddev composer validate --no-check-publish
   ddev composer check-platform-reqs
   ddev composer audit
   ```

3. Apply database updates on the local copy:

   ```sh
   ddev drush updatedb --yes
   ddev drush cache:rebuild
   ```

4. Adopt desired recipe changes explicitly, then export configuration. Compare
   fields, displays, permissions and settings with the site's customizations.
   Use the UI, a small migration recipe, or project update/deploy hooks as needed.
5. Test content, editing, access, integrations and assets. Run
   `ddev npm run confim` against the local copy to check import and deployment
   hooks. Review and commit the complete configuration diff and lockfiles.
6. Build from committed locks and follow [Deployment](DEPLOYMENT.md). Use
   [Build tools](BUILD_TOOLS.md) for root/theme installs and builds. `rebuild`
   restores a local database backup; it is not a production update command.

`composer install` and `npm ci` reproduce lockfiles. They do not request future
major releases. Do not ignore platform requirements or disable security checks
to make an update resolve. Uninstall modules through Drupal before removing
their Composer packages.

### Update recipe packages

Downloading a recipe does not replay it on an existing site. Reapplying a full
recipe can reject or overwrite customized configuration. Keep the site's
customizations and apply only the intended changes. Fields renamed by a new
recipe also need an explicit content migration before old fields are removed.

Retain the scoped Composer index and its package list in `composer.json`, and
keep recipes under `web/recipes`. Remove local path repositories before
committing an application lockfile; resolve published packages again because
`composer update --lock` alone does not replace path distributions. See
[Features](FEATURES.md) for package responsibilities.

### Upgrade Entity Usage 2 to 5

Complete the supported 2.x database updates first. After upgrading to 5.x, run
the database updates and cache rebuild above, then rebuild derived usage data:

```sh
ddev drush entity-usage:recreate
```

Check node, media, paragraph and revision references. Convert custom
`EntityUsageTrack` plugins to PHP attributes before using 5.x.

### Maintain Composer patches

Keep local patch definitions in `patches/patches.json` and commit
`patches.lock.json` with `composer.lock`. After editing definitions, run:

```sh
ddev composer patches-relock
ddev composer patches-doctor
```

Use `ddev composer patches-repatch` on a local copy to reinstall patched
packages and verify application. It deletes and reinstalls those dependencies.
Remove a patch only after checking that the installed upstream release contains
the fix and a clean locked install succeeds.

## Maintain administrator access

Keep existing roles and account assignments when updating packages. Verify an
active administrator other than UID 1 before changing access. The `superuser`
role grants all permissions; changing that policy is a site decision. Assign
trusted accounts deliberately through Drupal or:

```sh
ddev drush user:role:add superuser ACCOUNT_NAME
```

Account assignments live in the database and must be managed for each
environment. Export role definition changes with the site's configuration.
