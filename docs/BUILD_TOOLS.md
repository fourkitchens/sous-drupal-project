# Build and check a theme

Run these commands from the project root. DDEV supplies the versions listed in
[Platform](PLATFORM.md). See [Setup](SETUP.md) for first installation or restoration.

## Install dependencies

Start DDEV and install the root and theme packages from their lockfiles:

```sh
ddev start
ddev npm ci --include=dev
ddev npm run theme-ci
```

Fresh setup creates the theme's first lockfile. Later installs use `npm ci`.
Commit the root and theme manifests and lockfiles with the theme source.
Builds require npm development dependencies, including when `NODE_ENV=production`.
The compiled browser assets do not contain those build tools.

## Build assets or Storybook

The theme lives at `web/themes/custom/sous-project`. Project naming updates this
path in the npm tasks and documentation.

| Command | Purpose |
| --- | --- |
| `ddev npm run theme-build` | Build production theme assets |
| `ddev npm run theme-watch` | Run the theme's development task |
| `ddev npm run theme-storybook` | Build a static Storybook at `web/storybook` |
| `ddev composer build-assets` | Install PHP and root/theme npm dependencies, then build production assets |

The build and watch scripts remain available after setup. Their npm aliases
call `devops/scripts/theme-build.sh` and `devops/scripts/theme-watch.sh`.
CI calls the same build script directly:

```sh
sh devops/scripts/theme-build.sh
```

The script calls `ddev npm` from the host and the container's npm inside DDEV.
CI uses DDEV too.

The individual theme tasks use installed dependencies. `build-assets` also runs
Composer installation with `--no-dev`; Storybook is a separate optional build.

Emulsify writes compiled assets to the theme's `dist/` directory and generates
a `components/` mirror from `src/components/`. Edit and commit the source under
`src/`, not the generated mirror. Source Git ignores both outputs; include them
in deployments. Local Storybook output is available at `/storybook/`. See
[Deployment](DEPLOYMENT.md) for build contents and site CI examples.

## Use the Emulsify CLI

The project installs its CLI locally. Run theme commands from the theme directory:

```sh
cd web/themes/custom/sous-project
ddev emulsify component list
```

For component and system commands, see the
[Emulsify CLI documentation](https://www.emulsify.info/docs/supporting-projects/emulsify-cli/emulsify-cli-usage).
When deliberately fetching a changed branch of a design system, clear the CLI
cache first with `ddev emulsify cache clear`.

First setup installs the selected UI Kit, fixes its starter source for the
current lint tools, and creates the theme lockfile. It keeps the Storybook
packages on one compatible version through scoped npm overrides. It also sets
simple-git and Pa11y overrides. Keep these with the lockfile until updated
dependencies pass install, lint and builds. Use the routine tasks above after setup;
`theme-setup.sh` is for first initialization and rejects an initialized theme.

## Check and format source

| Command | Checks |
| --- | --- |
| `ddev npm run lint` | PHP/JavaScript checks and the generated theme's checks |
| `ddev npm run lint:php` | PHP syntax in custom code and project scripts |
| `ddev npm run lint:js` | Root and custom-module JavaScript |
| `ddev npm run lint:theme` | Theme JavaScript and SCSS |
| `ddev npm run prettier` | Theme formatting |

PHP lint uses `php -l`. Generated assets and downloaded dependencies are excluded
from source checks. To fix theme lint or formatting, run its tasks directly:

```sh
ddev npm --prefix web/themes/custom/sous-project run lint-fix
ddev npm --prefix web/themes/custom/sous-project run prettier-fix
```

Review the changed source before committing. [Contributing](CONTRIBUTING.md)
covers project code standards and [Maintenance](MAINTENANCE.md) covers dependency
and Composer patch updates.

## Upgrade an existing theme

Updating Emulsify Drupal or Emulsify Tools does not rewrite an existing child
theme. Compare its templates, libraries, component metadata, and npm configuration
with the new upstream version, then make the needed changes in the child theme.
Install its dependencies and run lint, the production build, and Storybook if the
project uses it. Do not rerun first-time theme initialization over custom work.

For an older child theme with favicon configuration incompatible with Emulsify
Tools 2, use its repair command after updating the packages:

```sh
ddev drush emulsify_tools:repair-favicon-config sous-project
ddev npm run confex
```

Review and commit the exported configuration. Check the rendered theme and its
asset URLs after deploying the update.
