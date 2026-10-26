[![Sous CI](https://github.com/fourkitchens/sous-drupal-project/actions/workflows/semantic-release.yml/badge.svg?branch=main)](https://github.com/fourkitchens/sous-drupal-project/actions/workflows/semantic-release.yml)

# Sous Drupal starter

Sous creates a Drupal application with Gin administration, an Emulsify theme,
and a choice of Page, Paragraphs, native Layout Builder, or admin-only setup.
This repository owns the application scaffold, installation/build helpers, and
shared product integration/release pipeline. The sibling recipes own reusable
configuration defaults; generated sites own their complete configuration and
lockfiles. See [responsibilities and relationships](docs/MAINTENANCE.md#ownership).

Generated projects exclude `devops/integration/` and all `devops/tests/` files.
They retain an empty `devops/tests/` for application-owned checks. Run product
integration/tests from a Sous source checkout, not a generated application.

## Start here

- [Supported platform](docs/PLATFORM.md) — PHP, Drupal, Node/npm, Composer, DDEV, and Emulsify versions and requirements.
- [Setup and recovery](docs/SETUP.md) — fresh generation, contributor clones, restoration, and interrupted installation.
- [Features and installation choices](docs/FEATURES.md) — expected forms, fields, composition, and rendering.
- [Build tools](docs/BUILD_TOOLS.md) — dependency installation, production assets, Storybook, lint, and local helpers.
- [Environment contract](docs/ENVIRONMENTS.md) — local/nonproduction/production settings, optional Config Split, and guarded configuration commands.
- [Configuration and existing-site maintenance](docs/MAINTENANCE.md) — project-owned exports, explicit migrations, and overrides.
- [Production deployment](docs/DEPLOYMENT.md) — building an artifact and updating an existing site.
- [Contributing and sibling sources](docs/CONTRIBUTING.md) — changing the product without using a developer database.
- [Integration checks](docs/INTEGRATION.md) — one disposable baseline and all four installation options.
- [Coordinated releases](docs/RELEASES.md) — dependency order, Conventional Commits, and publication gates.
- [Maintenance ownership and cadence](docs/OPERATIONS.md) — security, dependency/support reviews, patches, and downstream instructions.

Current source changes require coordinated recipe/starter releases. An older
published starter tag does not contain this working implementation. Follow
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
Updating a recipe package does not replay its configuration on an existing
site. Use the [maintenance workflow](docs/MAINTENANCE.md).

## Work on Sous itself

The shared check snapshots the starter and sibling recipe sources, resolves
those exact changes together, then creates and removes its own disposable site:

```sh
bash devops/integration/run.sh --sources /path/to/checked-out-sous-projects
```

Use a parent containing the recipe checkouts described in
[Integration checks](docs/INTEGRATION.md). The installed copies under
`web/recipes` can differ from those sibling sources. Local Composer path
repositories used by integration are temporary and do not belong in production
configuration.

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
