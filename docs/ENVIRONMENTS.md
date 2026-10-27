# Generated-site environment contract

Sous uses one provider-neutral runtime setting:

| `SOUS_ENVIRONMENT` | Runtime policy |
| --- | --- |
| `local` | Local settings may load; development services/debugging are available; native Drupal mail uses a collector |
| `staging` | Production-style caches/error display, no local settings file; native Drupal mail uses a collector |
| `production` | Production caches/error display, no local settings, no capture backend or file-proxy origin; ordinary complete base configuration |

An explicit value wins. If unset, actual DDEV (`IS_DDEV_PROJECT=true`) defaults
to local; elsewhere it defaults to production. Empty/unknown values fail before local
settings load. File existence, PHP CLI, domain names, hosting variables and
organization names never select an environment. Set the variable in the
project's deployment/runtime environment; no credentials or endpoints are
provided by Sous.

Set `staging` explicitly for dev/test/multidev environments that share the same
safety policy, and `production` for live. Project-owned settings can activate
additional native split definitions when those environments need distinct
module/configuration values. The safety policy names do not limit split count.

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

Local/staging use core's `test_mail_collector`; it records native Drupal
mail in state rather than sending it. Production retains its real stored mail
transport and never selects that collector through Sous defaults. Specific
per-module transport mappings and mail systems that bypass Drupal's native
mail manager require project review; the explicit runtime check verifies the
default collector, which does not override every integration. Production-mode testing inside DDEV checks application policy;
DDEV's own mail infrastructure is still local infrastructure. No production
mail is sent by the fixture.

Local/staging also override `automated_cron.settings:interval` to zero,
disabling Drupal's page-request cron trigger. This does not stop an external
job runner or every contributed scheduler. Those and project-specific outbound
integrations need project-owned runtime/state safeguards, repeated by the
[refresh hooks](SETUP.md#post-import-and-post-config-import-safeguards).
Production keeps its stored cron policy and never runs those development hooks.

No file proxy is installed or origin inferred. A project choosing local file
proxying supplies its own reviewed staging origin and development package;
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
ddev composer require fourkitchens/sous-config-split:^2.0
mkdir -p config/_splits config/local config/staging
ddev install-recipe fourkitchens/sous-config-split
```

Run these commands after the Sous 2.x package index and ZIPs are published;
candidate-source testing uses the explicit integration mode instead. Fresh
bootstrap offers this independent choice and creates the directories when
selected; its default is no. The Sous recipe requires the bounded stable
`drupal/config_split:^2.0.2` module line. Keep both packages in the application
lockfile, because imports need Config Split at runtime. The recipe supplies
inactive `sous_local` and `sous_staging` definitions plus the native `_splits`
definition split:

| Directory | Ownership |
| --- | --- |
| `config/default` | Complete production base, retaining `_splits` as the import bootstrap |
| `config/_splits` | Environment split definitions, written by the first normal export |
| `config/local` | Local modules/configuration values |
| `config/staging` | Staging modules/configuration values |

This restores the useful SFASU layout without copying its hosting assumptions.
The reusable YAML has one source in the Config Split package; generated sites own
their exported definitions. Runtime settings keep `_splits` active and select
only the applicable environment split. A native priority imports definitions
before their values and exports them afterward, including on fresh imports.
Production keeps both environment splits inactive. Module/theme/complete/partial
lists remain stored, reviewed project configuration. The [package guide](https://github.com/fourkitchens/sous-drupal-packages/tree/main/packages/sous-config-split)
explains adoption, adding dev/test/multidev definitions and preserving overrides.

For separate deployment-specific splits, add the definitions to
`_splits.complete_list` and select their statuses in project-owned settings.
For example, a project can set its own `PROJECT_DEPLOYMENT` variable and append
this after the Sous settings include, while separately selecting `staging` or
`production` as its runtime safety policy:

```php
$deployment = getenv('PROJECT_DEPLOYMENT') ?: '';
foreach (['dev', 'test', 'multidev', 'live'] as $split) {
  $config['config_split.config_split.' . $split]['status'] = $deployment === $split;
}
```

Define those four ordinary splits with stored inactive statuses and unique
directories. Use distinct IDs from `sous_local`/`sous_staging`, whose statuses
the scaffold owns. This example is a project decision, not an additional
automatic hosting detector or a limit to three deployment environments.

Configure the needed lists before enabling development modules, export in the
appropriate environment, and review both base and split files. A split can
control module enablement/configuration, but does not remove package code from
an artifact: use reviewed `require-dev` dependencies for development tools and
production `composer install --no-dev` as appropriate. Do not remove Config
Split itself while its configuration/import transformations remain in use.

Use native `config:export` and `deploy`. `_splits` uses Config Split's native
transforms; there is no unconditional partial import or custom loader. Sous
supplies no Config Filter requirement or broad Config Ignore policy and does
not set `config_exclude_modules`: core strips those
modules before split export and preserves already-active excluded modules on
import. Review those exclusions alongside split ownership. See the official [per-environment guidance](https://www.drupal.org/docs/contributed-modules/configuration-split/per-environment-configuration).

## Commands and execution context

Run from the project root; shell helpers also resolve that root when invoked
from a subdirectory. Host npm aliases default to DDEV. Inside actual DDEV the
same helpers always use native project tools, so they never invoke DDEV
recursively. A prepared CI/build environment explicitly selects native mode:

```sh
SOUS_RUNTIME=native SOUS_ENVIRONMENT=staging npm run confex
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

`confex` exports configuration and `confim` runs native Drush deploy. They do
not run the environment checker automatically. A nonproduction deployment also
runs the optional post-config-import hook, including after a failed deploy;
the deploy failure takes precedence over a hook failure. Development setup and
database restoration reject production before calling tools. Import validates
a non-empty SQL file and gzip integrity before database work, then rebuilds
caches, runs an optional post-import hook and sanitizes. Final login follows a
successful import and deployment. See the [restore guide](SETUP.md#restore-an-existing-generated-site).

## Explicit environment checks

Run these commands against a bootstrapped site after adopting `settings.sous.php`:

```sh
ddev npm run check:environment -- config
ddev npm run check:environment -- runtime
```

`config` is the default mode. It checks `config/default` for development modules,
verbose/all error display, capture mail backends and disabled CSS/JS aggregation.
The development-module list is `devel`, `devel_generate`, `webprofiler`,
`stage_file_proxy`, `reroute_email` and `devel_mail_log`, plus IDs in
`$settings['sous_development_modules']`.

`runtime` checks effective settings. Outside local mode, Twig debug must be off,
Twig cache on, the render cache must not be null, and rebuild access must be off.
Production must have no capture mail backend. Local and staging require the
default `test_mail_collector` backend and an automated-cron interval of `0`.

Each rule prints one `PASS` or `FAIL` line; any failure exits with status `1`.
A missing `sous_environment` setting fails with an adoption message. These checks
read the production base or current runtime; they do not transform/import
configuration or inspect Config Split folders and stored statuses.

## Existing-site adoption

Back up and use a disposable database copy. Composer preserves an existing
`settings.php`, local file and owned service overrides; downloading these source
changes does not rewrite them. Replace legacy unconditional local/DDEV includes
with the scaffold's ordered block, review any project database/environment
integration, and adopt the owned environment/services files explicitly. Remove
old unconditional partial-import scripts and broad ignore rules through reviewed
site configuration. Rename an existing `sous_nonproduction` definition and its
folder/settings explicitly to `sous_staging`; move its values to `config/staging`
and export definitions into `config/_splits`. Package updates do not perform
that migration. Keep environment split statuses inactive in stored exports;
the native `_splits` bootstrap stays active. Do not blindly overwrite a complete export or custom
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

The historical runs below predate the staging rename and restored definition
split. They establish the earlier baseline, not validation of this revision.
The current `devops/tests/config-splits.php` separately exercises native
definition export, fresh local/staging/production imports, and unsafe nested
folder rejection using memory storage and private files without a site/database.

The shared full integration checks local/staging/production export/import and
both Config Split states, alongside the four content choices. See the
[current matrix](INTEGRATION.md) for the exact candidate and published-index
gates. Focused tests cover
environment precedence/local-file isolation, copied core UI debug state,
project tools/context/arguments/exits, unsafe staged defaults, missing active
split folders and absent optional configuration.

The following historical environment-contract baseline predates the package
consolidation and database-refresh follow-up. It does not attest that this
release branch passed the current gate. That recorded baseline completed
**217 native commands**, exited zero,
and removed its owned containers, database and workspace. It used Drupal
11.4.11/PHP 8.4.20 and the tools listed in [Platform](PLATFORM.md), with optional
Config Split 2.0.2. Actual results:

| Check | Result |
| --- | --- |
| Local/staging/production, without Config Split | All three helper export/deploy round trips passed with split directories physically absent |
| The same environments with Config Split | All three passed; stable stored module lists, exclusive runtime statuses and split-only configuration verified |
| Copied local/staging database → production | Both removed Contact and its owned configuration through native deployment; copied development UI flags could not activate debug/null caches |
| Unsafe staged base defaults | Five deliberate module/mail/logging/aggregation/ignore changes rejected before deployment; active configuration unchanged |
| Split directory guards | Missing active directories rejected by export/import; incoming missing/public paths rejected before import, in local and staging |
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
| `config/{_splits,local,staging}/`, selected `fourkitchens/sous-config-split` package | Project-owned definitions/values directories plus independently distributed native recipe defaults |
| `devops/scripts/`, `.ddev/commands/web/install-recipe` | Shared project runtime, guarded export/deploy, explicit safe development workflows and consistent entry points |
| `devops/integration/{run.sh,validate-recipes.php}`, `devops/tests/` | Six-case native environment coverage, unsafe-default/path regressions, settings/services and command/Git checks |
| `README.md`, `PROJECT-README.md`, setup/platform/build/maintenance/deployment/integration guides and this guide | One environment contract with linked operating instructions and explicit existing-site adoption |
