# Generated Drupal application

A Drupal application generated from Sous. This repository owns its custom code,
Emulsify theme, complete configuration export, package lockfiles, hosting, and
deployment policy. Record project-specific URLs, owners, and access requirements
here after generation.

`devops/tests/` starts empty for this application's own checks. Sous's product
integration runner and maintainer test files are excluded during generation;
run upstream product checks from a separate Sous source checkout.

## At a glance

|   |   |
| --- | --- |
| Local environment | [DDEV](https://ddev.readthedocs.io/en/stable/) — see `.ddev/config.yaml` |
| Supported runtime | [Platform and Emulsify versions](docs/PLATFORM.md) |
| Build tools | [Project-local npm and Emulsify tooling](docs/BUILD_TOOLS.md) |
| Configuration | Complete project-owned `config/default` export |
| Sous packages | Selected independent `^2.0` recipes from the scoped Composer index; see [maintenance](docs/MAINTENANCE.md) |
| Environment policy | [Local/staging/production and optional Config Split](docs/ENVIRONMENTS.md) |
| Hosting and deployment | Project-owned; [deployment guide](docs/DEPLOYMENT.md) |
| CI | Opt-in [CircleCI or GitHub Actions application templates](docs/CI.md) |

## Develop and restore

Follow [local setup and recovery](docs/SETUP.md) for prerequisites and the
existing-site restore workflow. Use DDEV's PHP/Composer/Node tools. The host
needs the reviewed Node/npm version only for aliases that orchestrate DDEV;
calling the documented shell helper directly avoids that host dependency.
Inside DDEV those helpers use project tools directly. Prepared native CI must
select its runtime explicitly; see the [environment contract](docs/ENVIRONMENTS.md).
Terminus is needed only if this project chooses the optional Pantheon helper.

[Build tools](docs/BUILD_TOOLS.md) documents installing from locks, theme
build/watch, Storybook, and linting. Use the [configuration maintenance guide](docs/MAINTENANCE.md)
for package/database updates and project-specific overrides. Recipe defaults
are not an update subscription: newer recipe files do not automatically change
configuration already applied here.

Commit the Composer and root/theme npm manifests and lockfiles, custom theme
source, and complete configuration export with site UUIDs. Review configuration
changes before deploying them. Accounts, content, and role assignments live in
the database; environment-specific values belong in this project's settings
integration or ignored local settings. Local files load only in local mode;
production is the default outside DDEV unless explicitly selected otherwise.

Keep the selected Sous requirements and lock entries. Native recipe-unpack
exclusions retain their Composer ownership, so a clean `composer install`
restores `web/recipes` from individual ZIPs. Recipe directories stay ignored;
package updates do not replay configuration. Layout Builder, Paragraphs and
Config Split remain explicit installation choices.

## Project operations

- [Production build and deployment](docs/DEPLOYMENT.md)
- [Maintenance responsibilities and review cadence](docs/OPERATIONS.md)
- [Contribution and code standards](docs/CONTRIBUTING.md)
- [Sous features](docs/FEATURES.md)
- [Upstream integration checks](docs/INTEGRATION.md)
- [Upstream coordinated releases](docs/RELEASES.md)

Sous product workflows are removed during generation. Choose and verify this
application's [CI template](docs/CI.md) before enabling releases. Review release
branches and permissions; its private npm release tooling uses this project's
Git origin, commit history, and tags. Application deployment is not implied by
an upstream Sous release.

## License

The Sous scaffold is licensed under [GPL-2.0-or-later](LICENSE). Dependencies
retain their own license notices.
