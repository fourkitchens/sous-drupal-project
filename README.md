[![Sous CI](https://github.com/fourkitchens/sous-drupal-project/actions/workflows/semantic-release.yml/badge.svg?branch=main)](https://github.com/fourkitchens/sous-drupal-project/actions/workflows/semantic-release.yml)

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

This release branch targets the shared `^2.0` package line through a scoped
static Composer index. The package collection starts at `2.0.0`; merges to its
`main` branch publish semantic releases, package ZIPs and the index. Until that
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

The shared check tests the starter with the companion packages repository in
its own disposable site. See [Integration checks](docs/INTEGRATION.md) for the
published-index gate and the explicit candidate-commit mode. For local edits,
the source parent contains `sous-drupal-project` and `sous-drupal-packages`:

```sh
bash devops/integration/run.sh --packages /path/to/sous-drupal-packages
```

Edit `sous-drupal-packages/packages/<basename>` for reusable recipe defaults.
Installed copies under `web/recipes` can differ from those sources. Candidate
repositories are validation inputs and do not belong in a production lockfile.

Documentation paths and filename case can be checked without installing PHP or
Node dependencies:

```sh
bash devops/tests/documentation.sh --sources /path/to/checked-out-sous-projects
```

[Contributing](docs/CONTRIBUTING.md) separates source maintainer regressions from
generated-site checks. [Dependency](docs/DEPENDENCY_UPGRADE.md),
[frontend](docs/FRONTEND_TOOLING.md), and [Paragraph Types](docs/PARAGRAPH_TYPES.md)
reviews retain earlier validation and migration evidence; current operating
instructions are the guides linked above.

## License

The Sous scaffold is licensed under [GPL-2.0-or-later](LICENSE). Dependencies
retain their own license notices.
