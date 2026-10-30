# Build and deploy a site

Build from the site's committed lockfiles and complete configuration export.
Configure its hosting, secrets, database, uploaded files, backups, and rollback
procedure separately. Sous supplies build commands and CI examples; publishing
a Sous package does not deploy a site.

## Build the site

From the project root:

```sh
ddev start
ddev composer validate --no-check-publish
ddev composer build-assets
ddev composer check-platform-reqs
ddev npm run lint
```

`build-assets` installs production PHP dependencies, installs the root and theme
npm dependencies needed for building, and compiles the theme. Build Storybook
separately with `ddev npm run theme-storybook` if the site publishes that preview.
[Build tools](BUILD_TOOLS.md) lists the individual tasks.

Run dependency audits as part of the project's release checks:

```sh
ddev composer audit --abandoned=report
ddev npm audit
ddev npm --prefix "web/themes/custom/<theme>" audit
```

Resolve findings or document how the project handles them before deployment.

Package these files for the server:

- Production PHP dependencies in `vendor/`, Drupal core, contributed extensions,
  libraries, and the Composer-managed recipes in `web/recipes/`.
- Custom modules and theme source, the theme's generated `components/` mirror,
  and its compiled `dist/` directory.
- The complete configuration export, environment settings and services files,
  and project update/deployment scripts.

Include generated theme output even though source Git ignores it. Exclude
`node_modules`, local settings, caches, database backups, and local reference
files. Include `web/storybook` only when publishing it is intended. Keep secrets
and uploaded/private files in the hosting environment. Set the public document
root to `web/`; configuration and `vendor/` belong outside it.

## Deploy code and configuration

1. Test the build and configuration changes against a database copy. Back up the
   destination database and files before deploying.
2. Deliver the build through the site's deployment pipeline. Supply its database
   settings and writable paths. Use the versions in [Platform](PLATFORM.md) and
   disable server debugging extensions.
3. From the destination's project root, deploy its configuration and database
   updates using the project's Drush:

   ```sh
   vendor/bin/drush deploy --yes --uri=https://YOUR_SITE
   ```

   Replace the URI with the destination URL. This runs `drush deploy --yes`,
   including database updates, configuration import, cache work, and deployment
   hooks.
4. Check important pages, administrator access, rendered assets, logs, cron,
   queues, and project integrations.

The server needs PHP and the installed project dependencies. Build assets with
DDEV before delivering them. Run deployment through the project's Drush directly.
Apply recipe changes explicitly.

If deployment fails, stop dependent steps and follow the site's rollback
procedure. A schema or data change can require restoring the database together
with compatible code. Local `setup.sh` and `import-data.sh` replace and sanitize
a database; use them for development, not production deployment.

## Choose site CI

Copy one of the generated-site examples into the site's repository.
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

Both examples start DDEV, validate Composer metadata, install locked PHP and npm
dependencies, run lint, and build production theme assets. They do not deploy
or publish a release. Add the project's checks, artifact storage, deployment
credentials, and release conditions. Replace theme tasks if the site has no
Emulsify theme. `.gitattributes` export-ignore rules omit the starter's own
GitHub workflows from release archives; these examples are the site's starting
point.

## Deploy with Pantheon

For a project using Pantheon and an authenticated Terminus installation, the
optional adapter accepts a site and environment:

```sh
bash devops/ci/drush-config-import SITE ENV
```

It runs `terminus -n drush SITE.ENV -- deploy --yes`. The same values can be set
with `TERMINUS_SITE` and `TERMINUS_ENV`. Supply host settings through the site's
deployment pipeline.
