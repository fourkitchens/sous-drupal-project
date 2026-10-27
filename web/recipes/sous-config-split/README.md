# Optional Config Split defaults

Use this bundled native recipe when modules or stored configuration must differ
between environments. Simple differences such as logging, debugging and mail
capture use [runtime settings](../../../docs/ENVIRONMENTS.md). This recipe adds
no development module, mail module, file proxy, or Config Ignore rule.

## Explicit adoption

From the generated project root:

```sh
ddev composer require drupal/config_split:^2.0.2
ddev install-recipe fourkitchens/sous-config-split
ddev npm run confex
```

The recipe is already bundled; the second command does not download a package
named `fourkitchens/sous-config-split`. Config Split remains a runtime dependency
on every environment importing the site's export. The reviewed 2.0.2 baseline
contains the fix for [SA-CONTRIB-2025-017](https://www.drupal.org/project/config_split/releases/2.0.2).

## Definitions and values

The recipe's `config/` files are the authoritative reusable defaults. Once
applied, the generated project owns its definitions and exported values:

| Location | Purpose |
|---|---|
| `config/default` | Complete production base, including the `_splits` bootstrap definition |
| `config/_splits` | Environment split definitions, written by the first native export |
| `config/local` | Values/modules owned by `sous_local` |
| `config/staging` | Values/modules owned by `sous_staging` |

This restores the useful SFASU pattern: an always-active `_splits` split routes
environment definitions into one directory. Its own small definition stays in
the complete base, so a fresh import can discover it before any active split
exists. A native Config Split priority imports definitions before environment
values and exports them afterward. Do not move the bootstrap into itself or
remove that priority without testing fresh configuration imports.

The source directories start empty; the recipe supplies definitions at install
time and the first export writes them into `config/_splits`. No partial import
of an absent folder, duplicate YAML source, or custom loader is required.
Keep the directories and exported definitions under project version control.

`sous_local` and `sous_staging` start with empty module/theme/complete/partial
lists and stored `status: false`. Project settings select only the applicable
split. Production uses the complete base with both inactive:

| Deployment environment | Explicit `SOUS_ENVIRONMENT` |
|---|---|
| DDEV/local workstation | `local` (DDEV default) |
| dev, test, staging or multidev | `staging` |
| live/production | `production` (default outside DDEV) |

Hosting config sets the variable; Sous does not infer safety from a provider or
hostname. Projects needing separate dev/test/multidev differences add their own
definitions to `_splits.complete_list`, choose separate directories, and select
their statuses in project-owned settings. The three runtime safety policies do
not limit the number of native Config Split definitions a project can use.

## Project-owned changes

Configure module/config lists through Config Split's UI or reviewed project
configuration and export in the relevant environment. Normal `config:export`
and `deploy` use native transformations; no separate `--partial` import or
hand-written import ordering is needed. Keep every active folder outside `web`
and present before import/export.

Do not combine split-owned modules with `$settings['config_exclude_modules']`:
core removes excluded modules before they can reach a split, while import can
preserve already active excluded modules. Exclusions are not an uninstall
policy. Sous supplies no such list.

The recipe preserves existing site-owned definitions. Package updates do not
rewrite already applied configuration. For an existing installation, review
the old definitions, move split values to the selected directories, rename the
staging definition and matching settings explicitly, and export/deploy through
the project's normal [configuration migration process](../../../docs/MAINTENANCE.md).
Do not merely rename a folder while active definitions still point to it.

## Checks

On a fresh disposable fixture immediately after recipe application:

```sh
ddev exec env SOUS_DISPOSABLE_TEST=1 vendor/bin/drush php:script web/recipes/sous-config-split/tests/verify-defaults.php
```

Maintainers can exercise native definition export and fresh environment import
transforms without bootstrapping a site or connecting to a database:

```sh
ddev exec php devops/tests/config-splits.php
```

The latter needs installed Drupal and Config Split dependencies. It uses only
memory storage and private temporary files. Full module/configuration round
trips remain part of the disposable integration suite.
