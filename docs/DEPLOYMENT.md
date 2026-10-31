# Deployment

Build from committed lockfiles and the complete configuration export.
Use [Local Setup](SETUP.md) and [Build Tools](BUILD_TOOLS.md) for development.

## Platform versions

| Component | Source |
| --- | --- |
| Drupal / PHP | `composer.json`; DDEV PHP 8.4 |
| Composer / Drush | Composer 2; Drush 13 |
| DDEV / database | `.ddev/config.yaml` |
| Node.js / npm | `.nvmrc` on the computer, CI or host; bundled npm |
| Emulsify dependencies | Theme manifests and lockfile |

## Build and deploy

CI and hosting use PHP and Node directly. Prepare the production build and review release audits:

```sh
composer build-assets
composer check-platform-reqs
composer audit --abandoned=report
npm audit
npm --prefix "web/themes/custom/<theme>" audit
```

Ship `vendor/`, Drupal core, extensions, libraries, custom source, configuration and shared settings.
Include the theme's `dist/` and generated `components/`; omit `node_modules`, local settings, caches and backups.
Keep secrets and uploaded/private files on the host; serve the `web/` document root.
Back up the destination, deliver the build, then apply database updates and configuration:

```sh
vendor/bin/drush deploy -y --uri=https://YOUR_SITE
# From CI with a configured remote alias:
vendor/bin/drush @mysite.prod deploy -y
```

The shared configuration script supports the same deployment from CI, hosting or a laptop:

```sh
npm run confim -- --uri=https://YOUR_SITE
npm run confim -- @mysite.prod
```

It uses DDEV in a local DDEV checkout and installed Drush on hosts and CI runners without DDEV.
Add deployment steps in `hook_deploy_NAME()`; verify pages, editing, assets, queues and logs afterward.
Acquia aliases come from `acli remote:aliases:download`; Pantheon aliases come from `terminus aliases`.
Configure SSH keys and alias discovery for Drush, or define other hosts in `drush/sites/*.site.yml`.

## Update dependencies and configuration

Review release notes on a branch and test with a database copy:

```sh
ddev composer update vendor/package --with-all-dependencies
ddev composer validate --no-check-publish
ddev composer check-platform-reqs
ddev composer audit
npm run confim
npm run confex
git diff -- config
```

Commit lockfiles and the complete export. Recipes apply once and unpack; updates do not replay their configuration.
Migrate stored content before changing fields, and uninstall modules before removing Composer requirements.
Keep patch definitions in `patches/patches.json` and commit `patches.lock.json`.
Run `ddev composer patches-relock` and `ddev composer patches-doctor` after patch changes.
Use `ddev composer patches-repatch` to reinstall and verify them locally.
For Entity Usage 2 → 5, run `ddev drush entity-usage:recreate` and verify references.

## Set up site CI

The repository's CI configuration runs `composer ci`: install dependencies, build the theme, lint and check PHP standards.
Change these steps in `composer.json`; CI runs without DDEV.

| Provider | Configuration |
| --- | --- |
| GitHub Actions | `.github/workflows/build.yml` |
| GitLab CI/CD | `.gitlab-ci.yml` |
| CircleCI | `.circleci/config.yml` |

When changing providers, configure its file and update `release.config.js` for the chosen publisher.
Keep action versions pinned. CircleCI should build pull requests and pushes to the default `main` branch.
The commented deployment job can build assets, deliver them to the host and run Drush deploy with an alias.
Make release depend on deployment when enabled so tags mark what is live.

## Releases

Conventional Commits merged to `main` publish `v`-prefixed tags and notes on the repository's Releases page.
GitHub needs no setup. GitLab needs a project access token with `api` and `write_repository` scopes
in a protected, masked `GL_TOKEN` CI/CD variable; CircleCI needs `GITHUB_TOKEN` in project settings.
Preview with the appropriate token available locally:

```sh
npm run release:dry-run
```
