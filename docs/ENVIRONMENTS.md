# Generated-site environment contract

Sous uses one provider-neutral runtime setting:

| `SOUS_ENVIRONMENT` | Runtime policy |
| --- | --- |
| `local` | Local settings may load; development services/debugging are available; native Drupal mail uses a collector |
| `nonproduction` | Production-style caches/error display, no local settings file; native Drupal mail uses a collector |
| `production` | Production caches/error display, no local settings, no capture backend or file-proxy origin; ordinary complete base configuration |

An explicit value wins. If unset, actual DDEV (`IS_DDEV_PROJECT=true`) defaults
to local; elsewhere it defaults to production. Empty/unknown values fail before local
settings load. File existence, PHP CLI, domain names, hosting variables and
organization names never select an environment. Set the variable in the
project's deployment/runtime environment; no credentials or endpoints are
provided by Sous.

## Settings, services, and production safety

The first Composer scaffold supplies `settings.sous.php` and
`sites/sous.production.services.yml` without overwriting existing project files.
The scaffolded main settings file includes DDEV's connection settings **before**
the environment policy. Its direct include prevents DDEV appending a second
include after the policy. `settings.local.php` is included only in local mode,
and simple values remain runtime `$config` overrides rather than exported
configuration. Existing local files and their bytes remain project-owned.

Outside local mode, aggregation is enabled, PHP/Drupal error display is hidden,
Twig debugging/cacheability headers are disabled, caching remains enabled and
the rebuild endpoint is disabled. Core stores its development UI switches in
key/value storage rather than config; nonlocal services route only that
collection to core's memory factory. A copied local database cannot reactivate
those switches or disable rendered-output caches, and stored values are not
rewritten. Projects with additional `factory.keyvalue` mappings must retain
them in their owned service file. Production server owners must independently
disable debug extensions such as Xdebug; PHP settings cannot reliably unload an
extension enabled by server configuration.

Local/nonproduction use core's `test_mail_collector`; it records native Drupal
mail in state rather than sending it. Production retains its real stored mail
transport and never selects that collector through Sous defaults. Specific
per-module transport mappings and mail systems that bypass Drupal's native
mail manager require project review; the helper rejects uncaptured native
nonproduction mappings rather than assuming the default overrides every
integration. Production-mode testing inside DDEV checks application policy;
DDEV's own mail infrastructure is still local infrastructure. No production
mail is sent by the fixture.

Local/nonproduction also override `automated_cron.settings:interval` to zero,
disabling Drupal's page-request cron trigger. This does not stop an external
job runner or every contributed scheduler. Those and project-specific outbound
integrations need project-owned runtime/state safeguards, repeated by the
[refresh hooks](SETUP.md#post-import-and-post-config-import-safeguards).
Production keeps its stored cron policy and never runs those development hooks.

No file proxy is installed or origin inferred. A project choosing local file
proxying supplies its own reviewed nonproduction origin and development package;
production policy clears the origin and the safety check rejects the module in
production configuration. Keep production credentials, data, integration
settings and file origins out of examples and source defaults.

## Optional module/configuration differences

Without Config Split, ordinary export/deploy works and has no split-directory
dependency. Keep the complete base suitable for production; do not enable
development-only modules and export them into it. Use runtime overrides for
logging, aggregation, debug or mail differences.

When stored module/configuration differences are needed, explicitly opt in:

```sh
ddev composer require drupal/config_split:^2.0.2
ddev install-recipe fourkitchens/sous-config-split
```

Config Split **2.0.2** is the latest stable verified from official Composer
metadata and [release notes](https://www.drupal.org/project/config_split/releases/2.0.2).
Its application lockfile is reproducible. It remains a production dependency
when selected, because imports need the module; it is not a new Sous package or
a default dependency. The bundled native recipe supplies two inactive entities,
`sous_local` and `sous_nonproduction`, and tracked directories
`config/splits/local` and `config/splits/nonproduction`, outside the public root.
Production uses the complete base with both inactive; there is no production
meta-split. Only status is selected at runtime. Module/theme/complete/partial
lists are ordinary stored, reviewed project configuration, identical across
environments. The [bundle README](../web/recipes/sous-config-split/README.md)
describes adoption and preserving existing definitions.

Configure the needed lists before enabling development modules, export in the
appropriate environment, and review both base and split files. A split can
control module enablement/configuration, but does not remove package code from
an artifact: use reviewed `require-dev` dependencies for development tools and
production `composer install --no-dev` as appropriate. Do not remove Config
Split itself while its configuration/import transformations remain in use.

Use native `config:export` and `deploy`. There is no unconditional `_splits`
partial import, recursive meta-split, Config Filter requirement, or broad Config
Ignore policy. Sous does not set `config_exclude_modules`: core strips those
modules before split export and preserves already-active excluded modules on
import. The helper rejects overlap with split-owned modules and development
exclusions in production. See the official [per-environment guidance](https://www.drupal.org/docs/contributed-modules/configuration-split/per-environment-configuration).

## Commands and execution context

Run from the project root; shell helpers also resolve that root when invoked
from a subdirectory. Host npm aliases default to DDEV. Inside actual DDEV the
same helpers always use native project tools, so they never invoke DDEV
recursively. A prepared CI/build environment explicitly selects native mode:

```sh
SOUS_RUNTIME=native SOUS_ENVIRONMENT=nonproduction npm run confex
SOUS_RUNTIME=native SOUS_ENVIRONMENT=production npm run confim
```

Native means PHP, Composer, Node/npm, project `vendor/bin/drush`, locked project
CLI/dependencies, database/settings and the target site are already prepared.
It starts no containers, installs no tools implicitly, and never falls back to
global Drush or a global Emulsify CLI. `CI=true` alone does not change routing.
Host DDEV helpers forward the explicit environment and safety mode only; they
do not forward the entire host environment or credentials.

Arguments and child exit codes are retained. Global Drush target selectors are
also passed to safety checks so the checked and imported sites match. Guarded
helpers support prepared local project targets; they reject `@alias` selectors.
For a remote server, execute the helper on that server with explicit native
runtime/environment. Drush SSH does not automatically forward the guard's
environment or provide its project scripts. Theme helpers
accept their optional project-contained directory followed by tool arguments.
The canonical recipe implementation is the existing install-recipe script;
the DDEV command delegates to it. Pure build commands install no dependencies.

`confex` validates the native transformed export before writing the complete
base. `confim` checks the complete source/native production transform, runs
native deploy, and checks the resulting active state. Guards reject known
development modules, capture transports, proxy settings, debug/aggregation
defaults, active stored Sous split statuses and broad Config Ignore rules in
the production base. After attempted deployment it also attempts cache cleanup
and the nonproduction post-config-import hook, including partial failures;
the original failure status wins over cleanup errors. See the
[refresh safeguards](SETUP.md#post-import-and-post-config-import-safeguards).
Add project-specific development module IDs to tracked
`$settings['sous_development_modules']`; this is not an exhaustive classification
of every third-party module.

Active split directories must exist before export/import; inactive optional
directories are not imported unconditionally. Import validates incoming as well
as active split definitions, with the same runtime overrides as Config Split's
native importer; staged folder changes cannot create missing/public split
directories implicitly. The guard uses the selected Config Split 2.x manager
API, which must be rechecked during that package's major upgrade. Fresh setup and database
restore/sanitization are development workflows and reject production before
destructive work. Restore validates readable/nonempty SQL and compression
signatures/integrity before replacement. It attempts cache cleanup, its
post-import hook and the active check even after partial failure; sanitization
waits for import/safeguards to succeed. Final login waits for deployment and
its repeated safeguards too. Downloads require an explicitly configured project
hook and complete atomically with restrictive permissions. The
[restore guide](SETUP.md#restore-an-existing-generated-site) is authoritative
for paths, commands, hook implementation and recovery.

## Existing-site adoption

Back up and use a disposable database copy. Composer preserves an existing
`settings.php`, local file and owned service overrides; downloading these source
changes does not rewrite them. Replace legacy unconditional local/DDEV includes
with the scaffold's ordered block, review any project database/environment
integration, and adopt the owned environment/services files explicitly. Remove
old provider/meta-split activation and broad ignore rules through reviewed site
configuration. Keep module/config lists stored, and keep exported split statuses
inactive. Do not blindly overwrite a project's complete export or custom
key/value mappings. If an older database cannot bootstrap a preflight, perform
its reviewed native database updates/cache rebuild first, then run the checked
configuration workflow on the copy; no old-major upgrade is implied by this
baseline test.

[Maintenance](MAINTENANCE.md) owns package/database/configuration upgrade policy;
[Setup](SETUP.md) owns fresh/restoration/recovery commands, and
[Deployment](DEPLOYMENT.md) owns production artifacts and target authorization.
Manually bypassing the helpers or enabling arbitrary custom development modules
is not made safe by a settings override. Production acceptance remains a
project-owned check of the complete export, runtime, extensions and artifacts.

## Verification

The shared full integration adds one Page baseline with local/nonproduction/
production export/import cases, with and without the optional split, rather
than multiplying all four content choices. Explicit single-option debug runs
retain their selected site and skip this additional matrix. Focused tests cover
environment precedence/local-file isolation, copied core UI debug state,
project tools/context/arguments/exits, unsafe staged defaults, missing active
split folders and absent optional configuration.

The recorded environment-contract baseline, before the database-refresh
follow-up, completed **217 native commands**, exited zero,
and removed its owned containers, database and workspace. It used Drupal
11.4.11/PHP 8.4.20 and the tools listed in [Platform](PLATFORM.md), with optional
Config Split 2.0.2. Actual results:

| Check | Result |
| --- | --- |
| Local/nonproduction/production, without Config Split | All three helper export/deploy round trips passed with split directories physically absent |
| The same environments with Config Split | All three passed; stable stored module lists, exclusive runtime statuses and split-only configuration verified |
| Copied local/nonproduction database → production | Both removed Contact and its owned configuration through native deployment; copied development UI flags could not activate debug/null caches |
| Unsafe staged base defaults | Five deliberate module/mail/logging/aggregation/ignore changes rejected before deployment; active configuration unchanged |
| Split directory guards | Missing active directories rejected by export/import; incoming missing/public paths rejected before import, in local and nonproduction |
| Context and durability | 34 installer/Git/scaffold regressions, 19 command regressions and seven settings tests passed inside DDEV; missing guard mode rejected |
| Existing product options | Default, Paragraphs, native Layout Builder and admin-only passed, including actual HTTPS Page authoring |
| Frontend | Theme generation, production assets, Storybook, lint and explicit prepared-native build passed |
| Source validation | All seven Composer manifests, recipe YAML/metadata/dependencies and platform requirements passed |

The subsequent [refresh verification](SETUP.md#refresh-verification) separately
records the new archive/download and post-import safety-hook checks; the
217-command record is not a claim that those later changes were in that run.

An additional stopped-DDEV fixture ran the actual `setup.sh fresh` entry point:
it auto-started, entered the actual bootstrap child and stopped at an
intentionally missing Composer manifest. Its expected failure and cleanup
passed; that probe establishes the startup boundary, not a complete successful
site installation.

Raw audits remain visible: Composer reported zero security advisories and one
abandoned installer (`oomphinc/composer-installers-extender`); each source npm
toolchain reported the existing `ip-address` moderate and `undici` high findings.
The explicit integration policy accepted only those reviewed findings.
The generated-theme audit reported zero vulnerabilities. These tooling findings
are separate from the generated browser assets. Layout Builder still emitted
its two previously recorded transient field-block discovery warnings; fresh
discovery, saved layouts and rendering checks passed.

Evidence is retained under `reference/client-reference-improvements/`: the full
run's `result.json`, command logs, source snapshot/digest, hash-recorded reviewed
source overlays and final runtime/source matches, supplemental latest-source
tests, startup probe and cleanup receipts. Earlier failed extraction/test runs
are retained separately. No developer/client database was used. This validates
the selected baseline and copied databases; it does not establish an old-major
Drupal upgrade, remote production deployment or arbitrary project-specific
mail/integration behavior.

## Source changes

Only `sous-drupal-project` changed for this contract. Existing changes in all
seven repositories, including staged entries, were preserved on dedicated
`codex/client-reference-improvements` branches. Reference clients were read only.

| Files | Purpose |
| --- | --- |
| `composer.json`, `.gitignore`, `assets/scaffold/files/` | Once-only environment settings/services, ordered DDEV include, local isolation and trackable source |
| `config/splits/`, `web/recipes/sous-config-split/` | Optional inactive native split definitions, directories, adoption guide and defaults probe |
| `devops/scripts/`, `.ddev/commands/web/install-recipe` | Shared project runtime, guarded export/deploy, explicit safe development workflows and consistent entry points |
| `devops/integration/{run.py,validate-recipes.php}`, `devops/tests/` | Six-case native environment coverage, unsafe-default/path regressions, settings/services and command/Git checks |
| `README.md`, `PROJECT-README.md`, setup/platform/build/maintenance/deployment/integration guides and this guide | One environment contract with linked operating instructions and explicit existing-site adoption |
