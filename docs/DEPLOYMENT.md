# Production build and deployment

A generated site owns its hosting, deployment pipeline, credentials, writable
files, backups, and rollback process. Sous supplies tested local build commands
and native Drupal/Drush maintenance, not a hosting account or automatic release
to production. Use the runtime versions in [Platform](PLATFORM.md) and the
project's committed lockfiles and complete configuration export.
The [environment contract](ENVIRONMENTS.md) defines production-safe settings,
optional Config Split, and explicit adoption for existing sites.

## Build a reviewed artifact

From the generated project's root in a disposable/local DDEV build environment:

```sh
ddev start
ddev composer validate --no-check-publish
ddev composer build-assets
ddev composer check-platform-reqs
ddev npm run lint:project
```

`build-assets` installs PHP packages from the lockfile with `--no-dev`, runs
root/theme locked npm installs with build dependencies included, then builds
production assets. Storybook is built separately only when the project wants
that preview and its design-system assets/checks are ready;
there is no second package installation hidden in ordinary build/watch tasks.
[Build tools](BUILD_TOOLS.md) owns the detailed command reference.

Run native `composer audit` and root/theme `npm audit` as separate gates in the
review environment. Known tooling findings must remain visible and reviewed;
an asset build is not proof of a clean audit. Shared Sous integration applies
its narrow policy and tests theme findings separately. Each application must
choose and enforce its own documented security policy before deployment.

A deployable artifact needs:

- Composer manifest/lock and installed production PHP dependencies under
  `vendor`, Drupal core, contributed modules/themes/libraries, and custom code.
- Selected Composer-managed `web/recipes` packages from the committed lock,
  including their nested recipes/migrations; they are restored from independent
  ZIPs and stay ignored in application Git.
- Generated theme metadata, templates/source assets, native built `components/`
  SDC mirrors, and compiled `dist/` assets. Current Emulsify global CSS is under
  `dist/global/`; the SVG sprite is under `dist/assets/`.
- The site's complete exported configuration and its reviewed environment
  settings/bootstrap integration, including any project-owned update/deploy hooks.

Do not omit `dist/` merely because source Git ignores it. Build and package it
in CI. Root/theme `node_modules`, development caches, database backups,
`.integration` fixtures, ignored reference files, and local settings are not
runtime artifact inputs. Publish `web/storybook` only when the project intends
to expose that separate static preview; otherwise exclude it from the deployed
artifact. Uploaded/private files and production secrets are environment-managed.
The public document root is `web`; private config/vendor paths must not be
served as public content.

On a prepared CI host without DDEV, select `SOUS_RUNTIME=native` and run the
same Composer/npm tasks with the reviewed PHP/extensions, Node/npm, paths and
lockfiles. Inside actual DDEV the helpers already select native project tools;
they do not invoke DDEV recursively. DDEV is not required on production.

## Apply code and the site's own configuration

1. Review the artifact/configuration diff and migration notes, test on a database
   copy, and back up production database/files before the authorized deployment.
   Confirm an administrator account and a rollback point are available.
2. Deliver the built artifact using the site's approved host/atomic-release
   process. Supply its database/environment settings and writable file paths,
   explicitly set `SOUS_ENVIRONMENT=production`, and disable server debug
   extensions. Confirm the runtime satisfies platform requirements.
3. In the selected site's project root, use the checked helper and project Drush:

   ```sh
   SOUS_RUNTIME=native SOUS_ENVIRONMENT=production \
     sh devops/scripts/confim.sh --uri=https://YOUR_SITE
   ```

   Replace the URI with the real selected target. The local-copy equivalent,
   used by the disposable environment matrix, is:

   ```sh
   SOUS_ENVIRONMENT=production npm run confim
   ```

   The helper checks the complete source, invokes native Drush deployment, then
   checks active production configuration. Native deployment runs database
   updates, imports the configured complete export, performs cache work and
   project deployment hooks. It does not
   download new packages, restore a database, or reapply recipes. See
   [maintenance](MAINTENANCE.md) for explicit configuration adoption/migrations.
   If an old database cannot bootstrap the safety check, apply its reviewed
   native database updates/cache rebuild on the test copy first. Keep the same
   artifact's helper/safety script available on the target. Execute the helper
   there through the project's authorized remote execution mechanism; guarded
   helpers reject `@alias` selectors rather than assuming Drush SSH forwards
   their environment and project scripts.
4. Check the site's important routes/content, administrator/editor access,
   rendered assets, logs, pending update status, and any project-specific
   integration/queue/cron behavior. Record the artifact and configuration revision.
5. If a step fails, stop dependent deployment work and retain diagnostics. Restore
   the prior compatible code **and database** when rollback requires reversing
   schema/data/configuration changes; switching code alone may be insufficient.
   Follow the site's tested rollback procedure rather than guessing an inverse
   recipe or deleting live configuration.

No remote production deployment or rollback is claimed by local disposable
verification. Existing-site package/database/configuration changes are reviewed
separately; updating a recipe's files does not update already applied defaults.
`npm run setup -- restore` and `npm run rebuild` replace a local database and
sanitize it; neither is a production deployment command.

## Optional Pantheon adapter

The retained `devops/ci/drush-config-import SITE ENV` entry point runs:

```sh
terminus -n drush SITE.ENV -- deploy --yes
```

Use it only for an intentionally configured Pantheon project with Terminus,
credentials, and explicit target authorization. It delegates to the remote
site's configured sync directory, not a local template export. No Pantheon site
name or FIRE/Lando pipeline is assumed. A non-Pantheon project uses its own
remote execution mechanism for the same native Drush step. The adapter's
arguments/error propagation are tested; remote Terminus execution is not.
This legacy adapter invokes native deployment directly and does not run the
local safety script remotely. Projects retaining it must arrange the reviewed
production policy and guard on the target in their own pipeline.

## Product releases versus application deployment

The packages repository publishes immutable recipe ZIPs and its static Composer
index after its reviewed gates. The starter publishes a compatible tag only
after full acceptance against the public index; a candidate package commit is
validation only. Neither operation deploys downstream applications. Generated sites need
their own application CI, artifact distribution, deployment permissions, and
release/rollback policy. See [coordinated releases](RELEASES.md) and
[maintenance ownership/cadence](OPERATIONS.md). Source recipe references used for
paired integration validation cannot publish a release.
