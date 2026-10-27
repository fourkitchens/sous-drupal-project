[![Sous CI](https://github.com/fourkitchens/sous-drupal-project/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/fourkitchens/sous-drupal-project/actions/workflows/ci.yml)

# Sous Drupal starter

Sous creates a Drupal application with Gin administration, an Emulsify theme,
and Page/Frontpage defaults with optional Paragraphs or native Layout Builder,
plus an explicit admin-only setup.
This repository owns the application scaffold, installation/build helpers, and
product integration/release pipeline. The companion
[Sous Drupal Packages repository](https://github.com/fourkitchens/sous-drupal-packages)
owns the reusable recipes, package catalog and ZIP distributions; generated
sites own their complete configuration and
lockfiles. See [responsibilities and relationships](docs/MAINTENANCE.md#ownership).

Generated projects exclude `devops/integration/` and all `devops/tests/` files.
They retain an empty `devops/tests/` for application-owned checks. Run product
integration/tests from a Sous source checkout, not a generated application.

## Start here

- [Supported platform](docs/PLATFORM.md) — PHP, Drupal, Node/npm, Composer, DDEV, and Emulsify versions and requirements.
- [Setup and recovery](docs/SETUP.md) — fresh generation, contributor clones, restoration, and interrupted installation.
- [Features and installation choices](docs/FEATURES.md) — expected forms, fields, composition, and rendering.
- [Configuration audit](docs/CONFIGURATION_AUDIT.md) — ownership and restoration of the 129 legacy export files.
- [Product and application CI](docs/CI.md) — separate Sous Actions from opt-in CircleCI/Actions templates for generated sites.
- [Build tools](docs/BUILD_TOOLS.md) — dependency installation, production assets, Storybook, lint, and local helpers.
- [Environment contract](docs/ENVIRONMENTS.md) — local/staging/production settings, optional Config Split, and guarded configuration commands.
- [Configuration and existing-site maintenance](docs/MAINTENANCE.md) — project-owned exports, explicit migrations, and overrides.
- [Production deployment](docs/DEPLOYMENT.md) — building an artifact and updating an existing site.
- [Contributing to the two repositories](docs/CONTRIBUTING.md) — changing the product without using a developer database.
- [Integration checks](docs/INTEGRATION.md) — one disposable baseline and all four installation options.
- [Coordinated releases](docs/RELEASES.md) — dependency order, Conventional Commits, and publication gates.
- [Maintenance ownership and cadence](docs/OPERATIONS.md) — security, dependency/support reviews, patches, and downstream instructions.

This branch prepares the **Sous starter 8.0.0** release from `7.0.1`. Its
breaking contract requires Drupal `^11.4.11`, the shared Sous `^2.0` package
line, explicit fresh/restore setup, and project-owned configuration/environment
settings. See the [Sous 8 release contract](docs/RELEASES.md#sous-8-release-contract).
Packages are downloaded through a scoped static Composer index. The package
collection starts at `2.0.0`; merges to its `main` branch publish semantic
releases, package ZIPs and the index. Until that
first publication completes, candidate integration uses an explicit reviewed
package commit. An older published starter tag does not contain this
implementation. Follow
[setup](docs/SETUP.md) to select a published release or test the checked-out
sources; do not assume an unqualified create-project command retrieves local
changes. No release or deployment is performed by reading these instructions.

## Daily work

After setting up a generated site and installing its locked dependencies:

```sh
ddev npm run theme-build
ddev npm run theme-watch
```

The project-local Emulsify CLI is authoritative. There is no global CLI
prerequisite. Storybook, lint, and exact build outputs are documented once in
[Build tools](docs/BUILD_TOOLS.md). Site helpers default to DDEV from the host
and use project tools directly inside its container. Prepared native CI must
select that context explicitly; see the [environment contract](docs/ENVIRONMENTS.md).
The [database refresh guide](docs/SETUP.md#restore-an-existing-generated-site)
covers explicit backups, private downloads and project-owned integration safeguards.

Commit the generated Composer and npm lockfiles, custom theme source, and
complete `config/default` export. `composer install` and `npm ci` reproduce
those locks; future major upgrades need another reviewed dependency update.
Selected Sous recipes remain in Composer requirements/locks through the root's
exact native recipe-unpack exclusions. Their directories are downloaded
dependencies, so a clean clone restores them without committing recipe files.
Updating a recipe package does not replay its configuration on an existing
site. Use the [maintenance workflow](docs/MAINTENANCE.md).

## Work on Sous itself

CI runs source checks and four parallel smoke installs through the same
`devops/scripts/sous-setup.sh` and `setup.sh fresh` commands users run. With
Docker and DDEV available, test one option from this checkout:

```sh
bash devops/integration/run.sh --option default
bash devops/integration/run.sh --option paragraphs --config-split \
  --packages /path/to/sous-drupal-packages
```

Omit package overrides to use the published Composer index. `--packages PATH`
tests a local packages checkout; `--packages-ref SHA` fetches a reviewed packages
commit. Both use Composer path repositories only inside the disposable site.
See [Integration checks](docs/INTEGRATION.md) for the full interface, smoke checks,
logs and cleanup. The default option also checks backup/restore and dependency
audits. Source checks and all smoke legs must pass before publication from a
push to canonical `main`.

Edit `sous-drupal-packages/packages/<basename>` for reusable recipe defaults.
Installed copies under `web/recipes` can differ from those sources. Keep the
maintained starter checkout separate from generated client sites.

## License

The Sous scaffold is licensed under [GPL-2.0-or-later](LICENSE). Dependencies
retain their own license notices.
