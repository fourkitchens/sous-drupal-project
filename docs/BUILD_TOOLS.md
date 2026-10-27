# Build tools

The project uses DDEV, Composer, and the tasks in `package.json`. The reviewed
runtime and Emulsify versions are listed in [Supported platform](PLATFORM.md).
See [Local setup](SETUP.md) for generation, contributor clones, restoration,
and recovery. [Frontend tooling](FRONTEND_TOOLING.md) records the dependency
review and earlier validation; this guide is the current command reference.

## Install locked tools

From the project root, after starting DDEV:

```bash
ddev npm ci --include=dev
ddev npm run theme-ci
```

The second command requires an existing generated theme and reproduces its
lockfile. For an ungenerated starter, stop after root `npm ci` and use only
starter checks until theme generation. Fresh bootstrap handles the theme's
first `npm install --include=dev` while creating that lockfile. Commit both root
and theme manifests/lockfiles and theme source.
Routine build, watch, lint, and Storybook tasks use those installed dependencies.
Build/tool installations explicitly include development dependencies, even
under `NODE_ENV=production`, because the CLI, compiler, and linters are
build tools. This policy does not add Node packages to the browser output.

## Theme tasks

The generated theme lives at `web/themes/custom/sous-project`. Naming setup
updates the npm task paths for the generated project.

| Command | Behavior |
| --- | --- |
| `ddev npm run theme-build` | Run the theme's native production asset build |
| `ddev npm run theme-watch` | Run its native development/watch task |
| `ddev npm run theme-storybook` | Run the native Storybook build directly into `web/storybook`, also compiling production assets |
| `ddev composer build-theme` | Call the asset build using the installed tools |
| `ddev composer build-storybook` | Call the native Storybook build using the installed tools |
| `ddev composer build-assets` | Install locked PHP dependencies, run root/theme `npm ci` once each, then build production assets |

`build-assets` is the complete dependency-and-build pipeline. It runs Composer
installation with `--no-dev` and invokes the native production build. Storybook
is a separate optional preview task. Individual build/watch commands do not
install dependencies.

Production assets are generated under the theme's `dist/` directory. Emulsify
Core's global CSS uses `dist/global/` and its SVG sprite uses `dist/assets/`.
Storybook is separate static output under `web/storybook`, served locally at
`/storybook/`. Include `dist/` in the production artifact even though it is
ignored in source Git. Include Storybook only if the application intentionally
publishes that preview. See [Production deployment](DEPLOYMENT.md) for artifact
contents and the database/configuration update sequence.

The selected Core build also mirrors component output into the generated
theme's `components/`; its project-root Git ignore rule follows the generated
theme name. Component source under `src/components/` stays tracked. The rule
does not ignore another custom theme's own `components/` source directory.

## Emulsify CLI

`ddev emulsify` uses the root project's locked CLI. Run it from the theme
directory when working with that theme's project configuration:

```bash
cd web/themes/custom/sous-project
ddev emulsify component list
```

The selected Emulsify Core also uses a theme-local SDK dependency on the same CLI version.
The reviewed Git security override is retained without a local CLI patch.
The theme retains its own build/lint configuration and can install and
build independently from its committed files. See the
[Emulsify CLI documentation](https://www.emulsify.info/docs/supporting-projects/emulsify-cli/emulsify-cli-usage)
for component and system commands. No global Emulsify installation is required,
and routine DDEV startup does not install it.

First theme setup installs the pinned UI Kit and normalizes its stock source
with the selected formatting/lint tools. It also corrects the upstream example
Storybook icon references and excludes compiled output from source checks.
Review and commit the generated source and manifest with the theme lockfile.
After initialization, use the routine build/watch/lint tasks rather than rerun
initial setup over a customized theme. Those tasks never rewrite project source.
Drupal component metadata and library wiring remain project-owned.

The generated manifest retains a scoped Storybook `10.6.4` package-set override
under Emulsify Core. It keeps addon/adapter peers aligned while a newer addon
requires a Storybook version missing from the registry. Keep the reviewed pin
and lock together; remove it only after a coherent upstream set resolves and
passes the native preview/build checks. Setup does not bypass peer validation.

CLI 2.4.1 with the secure Git dependency supports cold-cache generation from
pinned releases. Its moving-branch refresh lookup remains an upstream
compatibility limitation. Use the native `ddev emulsify cache clear` before
deliberately fetching a changed moving reference. No local CLI patch or unsafe
Git allowance is installed.

The acceptance gate checks JavaScript, SCSS, formatting, production assets and
Storybook separately after initialization. Deliberate invalid authored files
must still fail, while compiled JavaScript and generated component mirrors
stay outside source lint. The initialization fixes do not replace those checks.
Projects own later design-system assets, style conventions and library wiring.

## Lint and formatting

The source starter defaults to `lint:starter`, which works without a generated
theme or `web/` tree. Naming setup rewires generated applications' `lint` alias
to `lint:project`, which requires their theme checks to succeed too.

| Command | Scope |
| --- | --- |
| `ddev npm run lint` | The appropriate starter or generated-project checks |
| `ddev npm run lint:starter` | Native PHP syntax and root/custom-module ESLint 10 checks |
| `ddev npm run lint:js` | Starter/custom-module JavaScript with appropriate Node/Drupal globals |
| `ddev npm run lint:php` | Custom PHP syntax, including Drupal file extensions and paths with spaces |
| `ddev npm run lint:project` | Starter checks followed by theme checks |
| `ddev npm run lint:theme` | The theme's own JavaScript and SCSS checks |
| `ddev npm run lint:styles` | The theme's native Stylelint check |
| `ddev npm run prettier` | The theme's formatting check |

Downloaded dependencies, minified output, and generated SDC mirrors are excluded.
PHP lint uses `php -l`; it checks syntax rather than Drupal coding standards.
For theme fixes, use the theme's `lint-fix` or `prettier-fix` task. See
[Contributing](CONTRIBUTING.md#code-standards-and-linting)
for focused regression commands.

## Site and configuration helpers

These aliases default to DDEV from the host. They also work inside actual DDEV,
where they use native project tools and never call DDEV recursively. Use the
reviewed host Node/npm versions for host npm aliases, or invoke the shell
scripts directly. Explicit prepared native CI, environment selection, target
arguments, and safety checks are defined in the
[environment contract](ENVIRONMENTS.md).

| Command | Behavior |
| --- | --- |
| `npm run setup -- fresh` | Install a new/disposable site; successful bootstrap removes its one-time setup files |
| `npm run setup -- restore [BACKUP]` | Start DDEV, install/build the theme, then restore the selected backup through rebuild |
| `npm run rebuild -- [BACKUP]` | Install root dependencies, import/sanitize the backup, deploy configuration, then generate final login |
| `npm run get-db -- [DESTINATION]` | Download through the explicitly configured project hook; validate and atomically complete a private backup |
| `npm run import-data -- [BACKUP]` | Validate `.sql`/`.sql.gz`, restore, run safeguards, then sanitize; no configuration import or login |
| `npm run local-data-bak` | Save a local database backup under `reference` |
| `npm run confex -- [DRUSH_ARGS]` | Check and export complete project-owned configuration, including optional native split transforms |
| `npm run confim -- [DRUSH_ARGS]` | Check source, run native deployment, attempt post-config safeguards and check active state, preserving failures |

Fresh installation and database restoration can replace the local database.
Choose the intended workflow explicitly and use disposable installations for
destructive validation. Setup/restoration reject production before destructive
work. Build/watch commands install no packages; theme paths are configured in
`package.json` and renamed during project naming.
Backup paths are root-relative or absolute and may contain spaces. The
[restore guide](SETUP.md#restore-an-existing-generated-site) owns validation,
atomic downloads, hook implementation and partial-failure recovery. There is
no default production source; no-argument imports use `reference/site-db.sql.gz`.

See [configuration ownership and maintenance](MAINTENANCE.md) before adopting
new recipe defaults in an existing site. The template sync directory is not a
reusable source of defaults; each generated site commits its complete export.
Security findings, review cadence, and escalation belong to the
[maintenance owners](OPERATIONS.md); an asset build completing does not mean
the dependency audit is clean.
