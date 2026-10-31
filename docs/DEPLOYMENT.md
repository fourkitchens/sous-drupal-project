# Maintain and deploy this site

Build from committed lockfiles and the complete configuration export.
Set up hosting, secrets, uploaded files, backups and rollback for this site.
Use [Setup](SETUP.md) locally and [Development](DEVELOPMENT.md) for daily commands.

## Platform versions

The manifests set requirements; lockfiles record installed versions.
DDEV supplies local PHP and the database. Engineers install Node.js from
`.nvmrc` on their computer; project creation also needs host PHP and Composer.

| Component | Version / source |
| --- | --- |
| Drupal / PHP | `^11.4.11` / `^8.4` in `composer.json`; DDEV PHP 8.4 |
| Composer / Drush | 2.x / `^13.8.0` |
| DDEV / MariaDB | `>=1.25.2 <2` / 10.11 in `.ddev/config.yaml` |
| Node.js / npm | 24.x from `.nvmrc` on the computer, CI or host / bundled npm |
| Sous recipes | `^2.0` in `composer.json` |
| Emulsify / Tools | `^7.3.0` / `^2.2.2` in the Sous Emulsify recipe |
| Emulsify Core / CLI / UI Kit | 4.5.0 / `^2.4.1` / 1.9.0 Drupal variant |

## Build and deploy

CI and hosting use PHP 8.4 and Node.js from `.nvmrc` directly; they never run
the local setup or database scripts. CI runs `composer ci`. Prepare a production
build with:

```sh
composer build-assets
composer check-platform-reqs
composer audit --abandoned=report
npm audit
npm --prefix "web/themes/custom/<theme>" audit
```

`build-assets` installs production PHP dependencies and the npm tools needed to
build the theme. Build optional Storybook with `npm run theme-storybook`.
Audits are release checks; resolve findings before release.

Ship `vendor/`, Drupal core, contributed extensions, libraries, custom
source, configuration, settings and deployment scripts. Include the theme's
`dist/` and generated `components/` despite their source Git ignores.
Exclude `node_modules`, local settings, caches and database backups.
Keep secrets and uploaded/private files on the host. Use `web/` as the document root.

Test against a database copy. Back up the destination database and files,
deliver the build, then apply configuration from the destination project root:

```sh
vendor/bin/drush deploy -y --uri=https://YOUR_SITE
```

From CI, pass a leading Drush alias instead:

```sh
vendor/bin/drush @mysite.prod deploy -y
```

For Acquia, authenticate Acquia CLI and download aliases with
[`acli remote:aliases:download`](https://docs.acquia.com/acquia-cloud-platform/add-ons/acquia-cli/commands/remote%3Aaliases%3Adownload).
For Pantheon, authenticate Terminus, run
[`terminus aliases`](https://docs.pantheon.io/guides/drush/drush-aliases), and
[add an SSH key](https://docs.pantheon.io/ssh-keys) for remote access.
Make downloaded aliases available to Drush; global alias paths must be
configured explicitly. For other hosts,
define [`drush/sites/*.site.yml`](https://www.drush.org/13.x/site-aliases/)
with the remote host, SSH user, Drupal root and URI.

Drush deploy runs database updates, configuration, caches and deployment hooks.
Add site-specific deployment steps in `hook_deploy_NAME()`.
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
npm run confex
git diff -- config
npm run confim
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

The base configurations provide PHP 8.4 and Node.js 24 and run `composer ci`
to install locked dependencies, build the theme, lint and check coding standards.
The steps live in `composer.json`; change them there so every provider and local
checks run the same commands. CI runs without DDEV.

| Provider | Folder in the Sous release | Installed file |
| --- | --- | --- |
| GitHub Actions | `assets/ci/github/` | `.github/workflows/build.yml` |
| GitLab CI/CD | `assets/ci/gitlab/` | `.gitlab-ci.yml` |
| CircleCI | `assets/ci/circleci/` | `.circleci/config.yml` |

Setup installs the provider selected by `SOUS_CI`, or no configuration for `none`,
then removes `assets/ci`. To switch providers later, check out the Sous release
tag the site was created from in a separate starter checkout. Copy that provider
folder's contents, including hidden files, into the site's project root:

```sh
cp -R /path/to/sous-release/assets/ci/github/. .
```

Use `circleci` or `gitlab` instead of `github` for the other providers, and remove
the previous provider's CI file or directory. GitLab sites move the pull request
template to `.gitlab/merge_request_templates/Default.md` and remove `.github`.
GitHub sites keep `.github/dependabot.yml` so Dependabot updates action pins;
restore it and the pull request template from the same release when leaving GitLab.
Remove the old `.gitlab/merge_request_templates` directory after restoring it.
Also restore that release's root `release.config.js` when leaving GitLab so
releases publish to GitHub again.

For CircleCI, make `main` the default branch and configure pull-request events
and default-branch pushes in
[Project Settings > Triggers](https://circleci.com/docs/guides/orchestrate/github-trigger-event-options/).
GitHub OAuth projects can enable **Only build pull requests**, which also builds
the default branch. Manual pipeline runs use the same workflow.

**Releases.** Conventional Commits merged to `main` publish a versioned tag such
as `v1.2.3` and release notes on the repository's GitHub or GitLab Releases page.
GitHub needs no setup. For GitLab, save a project access token with `api` and
`write_repository` scopes as a protected, masked CI/CD variable named `GL_TOKEN`.
CircleCI needs `GITHUB_TOKEN` in its project settings. Preview a release locally:

```sh
npm run release:dry-run
```

Each template includes a commented deployment job or stage after its checks;
nothing deploys by default. To enable it, restrict deployment to `main`, run
`composer build-assets`, deliver the build to your host, then apply configuration
with `vendor/bin/drush @your-site.env deploy -y`. Configure credentials and the
Drush alias as described in [Build and deploy](#build-and-deploy).
Make the release job depend on deployment so its tags mark what is live.

CircleCI sites deploying to Pantheon or Acquia can adopt
[Four Kitchens' POTS](https://github.com/fourkitchens/pots) instead for its
hosting-specific deployment workflows.
Engineers use `npm run setup` and `npm run rebuild` for local database restores.
