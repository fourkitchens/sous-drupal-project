# Sous responsibilities and supported platform

This is the shared platform reference for the starter and all six owned recipe
repositories. Versions below describe the selected, tested implementation;
they are not a promise that an old published recipe contains current source
changes. See [release coordination](RELEASES.md) before choosing a published tag.

## Runtime and frontend versions

| Component | Selected baseline | Authority |
| --- | --- | --- |
| Drupal core | 11.4.11; starter constraint `^11.4.11` | `composer.json`; generated site's lockfile |
| PHP | 8.4 in DDEV; Composer platform 8.4.0 and requirement `^8.4` | `.ddev/config.yaml`, `composer.json` |
| Composer | 2.10.3 | `.ddev/config.yaml` |
| Drush | 13.8.0 / `^13.8.0` | `composer.json`; generated lockfile |
| DDEV | `>=1.25.2 <2`; shared CI installs 1.25.4 | `.ddev/config.yaml`, `.github/workflows/integration.yml` |
| Database | MariaDB 10.11 | `.ddev/config.yaml` |
| Node.js | 24.22.1 | Root and generated theme `.nvmrc`, DDEV config/image, npm engines |
| npm | 11.21.1 | `packageManager`, DDEV image, release workflow |
| Drupal Emulsify theme | 7.2.1 / `^7.2.1` | Sous Emulsify Composer manifest |
| Drupal Emulsify Tools | 2.2.2 / `^2.2.2` | Sous Emulsify Composer manifest |
| Project Emulsify CLI | 2.4.1 / `^2.4.1`, locked, without a local CLI patch | Root npm manifest/lockfile and `ddev emulsify` |
| Generated-theme Emulsify Core | 4.5.0 | Generated theme tooling/lockfile; see frontend review |
| Emulsify UI Kit | Tag 1.9.0, Drupal variant | `devops/scripts/theme-setup.sh` |

The native integration baseline runs Drupal 11.4.11 on PHP 8.4.20. Wider upstream
package constraints do not establish additional tested Sous combinations. Host
PHP satisfying `^8.4` and Composer are needed for the Composer bootstrap path;
that path can use a different PHP patch/minor from the DDEV application runtime.
Host npm aliases require the reviewed Node/npm selection too. Host PHP 8.5.12
is used only for bootstrap/metadata validation in the documented disposable
verification; Drupal executes in DDEV's PHP 8.4 environment.

DDEV builds the selected Node/npm and native frontend libraries into its image.
Normal starts reuse it; they do not globally install Emulsify or reinstall npm
dependencies. `ddev emulsify` selects the root's locked CLI. The theme's Emulsify
Core SDK also depends on that CLI package; it does not change the authoritative
project command. Use `npm ci` wherever a lockfile exists. The first generated
theme setup creates its lock once, then ordinary builds do not install packages.
Build outputs, lint tools, security overrides, and patch rationale are described
in [Build tools](BUILD_TOOLS.md) and the [frontend implementation review](FRONTEND_TOOLING.md).

## Repositories and dependencies

| Repository | Responsibility and relationships |
| --- | --- |
| `sous-drupal-project` | Consumer template: dependency/scaffold/runtime setup, theme generation, four choices, complete initial site export, shared integration, and release coordination |
| `sous-admin` | Gin/Navigation, administration defaults and Superuser role. Requires the Formats **package** and applies its shared Linkit subrecipe; does not apply the full editor recipe, Page model, or Sous Media |
| `sous-formats` | Editor/filter formats and canonical shared Linkit defaults. Independent of other Sous packages; its subrecipe installs required core Node/Media and Linkit |
| `sous-media` | Media bundles/fields/displays and native module imports; independent of Admin and the other Sous recipes |
| `sous-content-types` | Usable Page fields/forms/rendering; applies Formats and Media. Supports the Paragraphs and native Layout Builder compositions |
| `sous-paragraph-types` | Four reusable Paragraph types and icons; applies Formats and Media. Does not require Page or Admin by itself |
| `sous-emulsify` | Theme dependencies and frontend theme selection; independent of Admin and supplies no authorization role |

“Independent” means no prerequisite Sous recipe beyond those listed. A consuming
Composer project still needs the required module/library repositories,
installer paths, stability exceptions, and native unpacking support. The starter
provides these; a bare Composer project does not inherit dependency-level
repository declarations automatically. See [recipe application](SETUP.md#recipe-application-and-unpacking).

The public `sous-paragraphs` companion adds the Page's `field_content` host and
composes Content Types with Paragraph Types. It is fetched/tested as source but
is outside these seven repositories' ownership. Native Layout Builder is owned
by the starter's `web/recipes/sous-layout-builder`; the incompatible public Gin
Layout Builder package is not selected. All includes use the documented
`web/recipes` layout; slash-containing native recipe includes are Drupal-root
relative.

## Installation choices and scope

| Choice | Expected result |
| --- | --- |
| `0` Default | Gin + generated Emulsify theme; Page and Frontpage Body/SEO/metadata/teasers, title-based aliases, media and native management Views |
| `1` Layout Builder | Both starter types plus native core Layout Builder, visible Body blocks, Basic inline blocks and per-node overrides |
| `2` Paragraphs | Both starter types with Body and Image, Text, Text with Media and Video components through the bundled extension of the public host |
| `3` Admin only | Gin/Navigation/authorization and generated Emulsify theme, without optional Page or content-composition recipes |

The starter's bundled recipes supply Frontpage; they do not seed homepage/error
content, enforce a single Frontpage node, or configure Entity Browser/Role
Delegation. [Features](FEATURES.md) describes the supported compositions and
[the configuration audit](CONFIGURATION_AUDIT.md) records ownership/restoration.

## Local tooling and hosting

DDEV is the supported local environment. Docker and Git are prerequisites;
Bash 3.2+, jq, curl, realpath and shasum run the shared harness. NVM can select
host Node, not an application dependency. Helpers default to DDEV on the host
and use project tools directly inside actual DDEV, without recursive calls.
Prepared native CI explicitly selects `SOUS_RUNTIME=native`; `CI=true` alone
does not select it. The [environment contract](ENVIRONMENTS.md) defines routing,
safe runtime defaults, and optional Config Split support.

Hosting, CI credentials, domains, environment secrets, and production deployment
belong to each generated site. No Lando, FIRE, or Pantheon account is required.
A manual Pantheon/Terminus helper remains available for projects that choose
that provider; it is not the default deployment system. See [deployment](DEPLOYMENT.md).

## License and ongoing reviews

The maintainer confirmed **GPL-2.0-or-later** for all seven Sous repositories.
Each now includes uppercase `LICENSE` with the full GPL v2 text and an explicit
“or later” notice; Composer/npm/root-lock metadata uses the same SPDX expression.
The five pre-existing Four Kitchens copyright notices are retained. Earlier
tags/history and third-party dependency notices were not rewritten.

Review responsibility and cadence are defined once in [Operations](OPERATIONS.md):
security advisories, latest stable/major versions, Drupal support windows,
patches, integration/release gates, and downstream upgrade instructions.
