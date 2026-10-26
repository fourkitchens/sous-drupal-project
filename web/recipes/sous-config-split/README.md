# Optional Config Split defaults

Use this bundled native recipe only when module enablement or stored
configuration must differ between environments. The
[environment contract](../../../docs/ENVIRONMENTS.md) owns runtime policy and
command/adoption instructions. Logging, debugging, mail
capture and similar simple runtime values use the starter's environment
overrides instead. This recipe is not an additional Composer package and does
not install development tools, a mail module, file proxy, or Config Ignore.

## Explicit adoption

The consuming project's administrator chooses this feature. From its root:

```sh
ddev composer require drupal/config_split:^2.0.2
ddev install-recipe fourkitchens/sous-config-split
```

The second command addresses the already bundled recipe by its basename; it
does not download a package named `fourkitchens/sous-config-split`. An equivalent
native path is `/var/www/html/web/recipes/sous-config-split` in DDEV. Config Split
is an optional application dependency, not a default or `require-dev` package:
its module must remain available on environments importing the site's export.

Version 2.0.2 is the newest stable release verified against official Composer
metadata for this implementation. It supports the selected Drupal 11 baseline
and fixes [SA-CONTRIB-2025-017](https://www.drupal.org/project/config_split/releases/2.0.2).
Older 2.0.0/2.0.1 releases do not contain that fix. A project lockfile records
the resolved version; future upgrades require another reviewed update.

The recipe supplies `sous_local` and `sous_nonproduction` definitions, inactive
in stored/exported configuration. The starter selects the corresponding status
through its validated `SOUS_ENVIRONMENT` contract. Production uses the ordinary
complete base with both splits inactive; it has no extra production split.
The real project directories are `config/splits/local` and
`config/splits/nonproduction`, outside the public `web` root and outside the
complete `config/default` sync directory. Keep these directories present before
activation; do not rely on the module silently creating a missing path.

## Project-owned module separation

Definitions start with empty module/theme/complete/partial lists. Choose only
the environment differences the project actually needs, configure the lists
through the module's UI or reviewed project config, then export in that
environment. Native `config:export` and `deploy` incorporate the active split;
no separate `--partial` import or hardcoded import ordering is necessary.

Do not combine a module owned by a split with Drupal's
`$settings['config_exclude_modules']`. Core removes excluded modules and their
dependent configuration at export priority 500, before Config Split's default
priority 0, so that data cannot reach the split. On import core deliberately
preserves already active excluded modules; exclusions are not a production
uninstall or safety policy. Sous ships no such list. The project owns any
existing exclusions and must review overlap before enabling this optional
feature.

The recipe preserves an existing split definition, including custom lists and
paths. Updating a package does not update applied configuration. Review active
definitions, retained directories and runtime status overrides before adopting
or modifying the feature; use the project's normal configuration migration and
deployment process. See [configuration ownership](../../../docs/MAINTENANCE.md)
and [setup](../../../docs/SETUP.md) in the starter source checkout.

## Disposable defaults check

Immediately after applying the recipe to a fresh disposable fixture:

```sh
ddev exec env SOUS_DISPOSABLE_TEST=1 vendor/bin/drush php:script web/recipes/sous-config-split/tests/verify-defaults.php
```

This checks the created definitions and their native typed-config schema. Full
environment export/import and production-safety checks belong to the shared
integration suite. Do not run the fresh-default comparison after replacing the
lists with project-specific choices.
