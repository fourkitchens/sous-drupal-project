# Installation, restoration, and recipe development

Read [Platform](PLATFORM.md) for the one supported runtime/tooling reference and
repository relationships. DDEV, Docker, and Git are required locally. The
Composer bootstrap path additionally needs the reviewed host PHP and Composer;
host orchestration aliases require the same selected Node/npm versions.
NVM is one optional way to select them. DDEV supplies the application/runtime
and build tools. Terminus is needed only for an explicitly chosen Pantheon
workflow, not normal setup.

The [environment contract](ENVIRONMENTS.md) defines safe local/staging/
production settings and command context. Unset environments default to local
only inside actual DDEV, otherwise production. A local settings file never
selects the environment; existing sites adopt the ordered include explicitly.

## Published releases versus current coordinated source

The current fixes span seven source repositories and have not been published.
The existing public starter 7.0.1 and Content Types 1.0.3 do not contain this
implementation. Other owned recipes are currently untagged. Do not describe an
unqualified stable `composer create-project` as installing these repairs.

Until the coordinated releases exist, maintainers validate the checked-out
sources using [sibling development](#local-development-with-sibling-recipes).
Once a reviewed starter tag and its recipe dependencies exist, create a project
with an explicit real tag, replacing `REVIEWED_TAG` before running:

```sh
composer create-project fourkitchens/sous-drupal-project:REVIEWED_TAG my-site
```

This is the published-project command shape, not a claim that a future tag
exists or that Packagist delivery was tested. [Release maintenance](RELEASES.md)
defines the merge/tag order. Current necessary `dev-main` and existing beta
exceptions are explicit in the manifests; they are not new stable releases.

## Fresh installation and contributor clones

Work in a new generated-project directory or disposable copy. Fresh setup
installs Minimal Drupal and can replace that local database. Do not run the
bootstrap hooks in the canonical starter checkout you are maintaining: naming
changes files and successful setup removes its bootstrap helpers.

For a clone/copy of the reviewed starter source, run from its root:

```sh
composer install
composer run-script post-create-project-cmd
```

For a real remote clone, first check out the reviewed source ref containing the
coordinated changes. A clone of today's old public main does not acquire local
uncommitted repairs. The command sequence above is validated using a disposable
source fixture with checked-out companion recipes, not an invented public tag.

The hook names the project, starts DDEV, installs locked dependencies, installs
Minimal, applies Admin and Emulsify, creates/builds/enables the project theme,
and configures administrator access. Select one of the four choices in
[Platform](PLATFORM.md#installation-choices-and-scope). A Git clone is a valid
fresh-install target: `.git` is never used to choose database restoration, and
its existing branch is retained. A directory without Git is initialized on
`main` without changing global Git preferences.

Git and Composer archives exclude `devops/integration/` and every maintainer
test file, including hidden placeholders. Project naming also removes those
files for source-based installs and recreates an empty `devops/tests/` for the
application's own checks. It removes the maintainer-only `release:test` npm
alias; the source checkout retains that alias and its tests. Keep the original
starter checkout separate when maintaining Sous itself.

After naming, the equivalent explicit fresh command is:

```sh
npm run setup -- fresh
```

Choose the intended workflow explicitly. `npm run setup` without a mode prints
usage and does no installation work. Successful bootstrap exports complete
configuration to `config/default`, validates the generated Composer pair,
produces a login link, and only then removes its one-time setup files. Commit
Composer/npm manifests and lockfiles, the custom theme sources/patches, and the
complete configuration export. Downloaded dependencies/build output are ignored.

The first scaffold creates missing `settings.php` from the finished core
defaults, so the project's sync path and local include take effect before DDEV
uses its embedded fallback. Existing settings and local files/symlinks survive
Composer install/update/scaffolding. Review an older site's actual path with
`ddev drush status --field=config-sync`; path changes belong to that project.

A newly generated theme creates its first npm lockfile during setup. Later
installs use `npm ci`; routine builds/watch do not reinstall dependencies or
clear caches. [Build tools](BUILD_TOOLS.md) owns those commands.

## Restore an existing generated site

Clone the **site's own repository**, including its final lockfiles, custom
theme, and complete exported configuration. This is different from creating a
new starter. Supply an explicitly selected `.sql` or `.sql.gz` backup (and the
site's uploaded files if needed), then run from the project root on the host:

```sh
npm run setup -- restore "reference/database copies/reviewed.sql.gz"
```

DDEV starts; Composer/root/theme dependencies and theme assets build before
any database import. The workflow validates and imports the selected backup,
applies the post-import safeguards, then runs native Drush deployment and the
post-config-import safeguards. It sanitizes local accounts and prints a login
link only after both safeguard stages succeed. This replaces the local database.
Fresh setup and restoration reject production before replacing a database. The same helpers
use project tools directly when invoked inside DDEV. Prepared native CI must
explicitly select its runtime and environment as described in the
[environment guide](ENVIRONMENTS.md).

Backup paths may be absolute or relative to the project root, including spaces.
An explicit missing path fails; it never selects a different backup or source.
With no backup argument, restoration uses `reference/site-db.sql.gz` and invokes
the configured download hook only if that default is absent. There is no
automatic newest-backup or production-source selection. For an existing
prepared site, the narrower commands are:

```sh
npm run import-data -- "/absolute/path with spaces/reviewed.sql"
npm run rebuild -- "reference/database copies/reviewed.sql.gz"
```

`import-data` validates/imports and sanitizes after its post-import safeguards;
it does not import configuration or print a login link. `rebuild` performs the
complete dependency/build, import, configuration, sanitization and final-login
sequence. It invokes `import-data --defer-sanitize` internally so sanitization
waits for both post-import and post-config-import safeguards. A deferred copy
remains unsanitized until the complete workflow succeeds. Sanitization sets
local test passwords and email addresses only in this development copy.

### Backup validation and download hooks

Before destructive import, both runtime paths require a readable, nonempty
regular backup with a supported suffix. The `.sql`/`.sql.gz` suffix must match
its compression signature. Gzip input must pass its full integrity check and
produce nonempty SQL. Validation prepares private plain SQL before replacing
the database, including when DDEV performs the import. DDEV's own import hooks
are skipped so they cannot run Drupal commands or login before Sous safeguards.

`get-db` accepts an optional completed destination:

```sh
npm run get-db -- "reference/database copies/reviewed.sql.gz"
```

Its default destination is `reference/site-db.sql.gz`. The project-owned
[download hook](../devops/hooks/download-db.sh) is unconfigured by default and
fails explicitly. Configure an approved source in that hook; provider tools are
optional, and no remote site, production source, credentials or endpoint ships
with Sous. The hook runs in the caller's environment from the project root,
receives one absolute private payload path, and must write that exact file or
return nonzero. It does not choose or rename the completed destination.

For example, a project that already manages a local backup can use this body:

```sh
set -eu
: "${SOUS_DB_BACKUP_SOURCE:?Set an explicit project-managed backup path}"
cp -- "$SOUS_DB_BACKUP_SOURCE" "$1"
```

The variable is an example for that project-owned implementation, not a shipped
source or credential. Keep provider authentication outside tracked files and
command logs. A provider implementation must likewise report failed downloads
and write only to the supplied path.

The wrapper creates private temporary storage in the destination's directory
with a restrictive umask, validates the completed payload, sets mode `0600`,
then atomically renames it into place. A failed download or validation preserves
an existing completed backup and removes its partial payload. Imported/prepared
SQL temporary files are private and removed on completion or failure.

### Post-import and post-config-import safeguards

Keep these two small hooks in the generated project's source control:

- [post-import.sh](../devops/hooks/post-import.sh) runs after attempted database replacement.
- [post-config-import.sh](../devops/hooks/post-config-import.sh) runs after attempted native deployment/configuration import.

For an existing site, review these hook files and the mail/cron runtime policy
together when adopting the helpers. Composer preserves owned settings files;
it does not add new overrides to an existing `settings.sous.php` automatically.

They run only in local/staging through the selected project runtime, from
the project root. Make their project-specific shutdown operations idempotent,
verify the resulting state, and return nonzero if a required safeguard cannot
be established. Use project Drush/runtime helpers and retain any supplied target
selectors. The templates cannot infer which external synchronization or job
systems a generated site owns; fill them with those integrations' real kill
switches and checks. Do not copy client-specific module lists or disable
unrelated production capabilities.

The standard settings already capture native staging Drupal mail and
disable Drupal's automated cron trigger through runtime overrides, so ordinary
configuration import cannot reactivate those defaults. Custom transports,
schedulers and synchronization clients need their own settings/state policy.
Use runtime overrides for simple differences, as described in the
[environment contract](ENVIRONMENTS.md), rather than exporting local mail or
debug defaults. Native Drush deployment hooks run before the external
post-config-import hook: integrations that could execute during deployment
must remain quarantined by runtime settings throughout that sequence.

After an attempted import or deployment, Sous attempts cache cleanup, its
corresponding safety hook and the active environment check even when the
operation reports a partial failure. It preserves the original failure status;
cleanup failures are reported and cannot turn the command into success. If the
operation succeeded but a required safeguard fails, that safeguard's failure
stops dependent work. In the full workflow, sanitization is skipped after a
failed import, deployment or either safeguard stage; standalone import waits
for its post-import safeguards. Login generation is skipped after any required
stage fails. Safety cleanup attempts to stop integrations; it does not roll back
the database. Retain diagnostics
and restore a reviewed backup or correct the failed safeguard before using
the incomplete copy.

If a customized project has no `sous_chef` account, adapt its login step to its
own administrator before restoring. Recipes define roles, not a universal
account-management policy. User assignments live in the database. Keep a real
administrator account available and follow [maintenance](MAINTENANCE.md).

### Refresh verification

The refresh follow-up was checked on fresh, disposable Drupal 11.4.11/Drush
13.8 installations using the selected PHP 8.4 DDEV runtime:

| Check | Actual result |
| --- | --- |
| SQL/gzip imports | Host DDEV and in-container native imports passed with spaced paths; original read-only backups remained unchanged |
| Safeguards and configuration | Hooks disabled fixture-only job/sync state; a module deliberately reenabled that state during configuration import and the post-config hook disabled it again |
| Partial failures | Failed SQL import and native deployment retained their original exit codes while cache cleanup and failed safety hooks were still attempted |
| Sanitization/login | Failed safeguards prevented dependent work; the deferred import → configuration safeguards → sanitization → login sequence passed |
| Download completion | Private validated backup completed at mode `0600`; failed producer preserved the existing target and removed partial files |
| Invalid selections | Corrupt gzip, empty selected paths and an empty ZIP disguised as SQL were rejected before database replacement; a valid default was never substituted for an explicit empty path |
| Automated Cron absent | Fresh Minimal without the module or stored config passed the active guard with effective staging interval zero |
| Focused regressions | 34 command/refresh tests passed on the host and inside DDEV with no skips; 34 installer and seven settings tests passed on the host |
| Dependency checks | The minimal fixture's Composer manifest validation, platform checks and security audit all exited zero |

The existing shared integration entry point already runs these focused tests;
no extra provider or database matrix was added. Source hashes, native logs and
cleanup receipts are retained under `reference/database-refresh`. All three
owned fixture projects/databases were removed. No real provider download,
client data, production deployment or new asset build was tested in this
follow-up. Projects still configure and verify their own integration kill
switches and optional download source.

## Local development with sibling recipes

Use the shared product runner to test source changes together without touching
a developer database or depending on ignored installations:

```sh
bash devops/integration/run.sh --sources /path/to/Projects
```

The directory contains the seven Sous checkouts. The public Sous Paragraphs
companion is fetched when absent; when supplied locally, review its ref too.
The runner records actual source digests/revisions, uses temporary Composer
path repositories, invokes native unpacking, and removes development overrides
before proving a portable lockfile and clean installation. It does not commit
path repositories into production config.

To inspect a default Page site while editing a specific recipe source:

```sh
bash /path/to/sous-drupal-project/devops/integration/run.sh \
  --subject /path/to/sous-admin --sources /path/to/Projects \
  --option default --keep
```

`result.json` reports the unique disposable project and workspace. Enter its
`integration` directory and use `ddev describe` or `ddev drush user:login`.
Source snapshots are copies; edits to the original checkout are incorporated by
another run, not silently synced into an active site. Select/export only the
reusable changes back into the owning recipe. Never copy a complete site's
export over a recipe's partial defaults or remove nested plugin identifiers.

With `--keep`, clean up only that named project after confirming `ddev describe`
points to the owned fixture; from its directory run
`ddev delete --omit-snapshot --yes`, then remove that temporary workspace.
Without `--keep`, the runner performs ownership-checked cleanup automatically.
[Integration](INTEGRATION.md) covers PR refs, all options, diagnostics, and gates.

## Recipe application and unpacking

Recipes are applied once, not enabled as updatable extensions. In the starter's
supported layout, unpacked recipe roots live at `web/recipes/PACKAGE_NAME`.
The public host and the shared Linkit subrecipe use that layout too. A bare
consumer must configure Drupal/asset repositories, library/recipe installer
paths, native `drupal/core-recipe-unpack`, and root stability exceptions;
Composer does not inherit dependency-level repository declarations. Reuse the
starter's reviewed Composer setup rather than assuming one `require` configures
an arbitrary Composer project.

For example, from a configured Sous project using today's explicit development
exceptions:

```sh
ddev composer require fourkitchens/sous-admin:dev-main fourkitchens/sous-formats:dev-main drupal/login_destination:^2.0.0-beta10@beta
ddev install-recipe fourkitchens/sous-admin
```

Admin applies only the shared Linkit subrecipe from the Formats package. The
full editor recipe is a separate choice. Content Types/Paragraph Types require
root `dev-main` allowances for Formats and Media; Media also needs the declared
Dropzone/asset repository setup. Recipe README examples list their dependency
relationships. Replace development constraints with bounded stable lines only
when those coordinated releases actually exist.

The shared DDEV wrapper applies the recipe from `web/recipes` and rebuilds caches
only after successful application. An equivalent manual application from the
project root is:

```sh
ddev drush recipe /var/www/html/web/recipes/sous-admin
ddev drush cache:rebuild
```

The absolute example is the DDEV container path. Outside DDEV, use the actual
absolute recipe path with the project's Drush. Native Composer require/create
project events unpack recipe dependencies. If resolving source fixtures with
an explicit `composer update` instead, use the native command:

```sh
ddev composer drupal:recipe-unpack
```

Do not use the obsolete `composer unpack` command, and do not include the vendor
prefix in the directory name (`web/recipes/fourkitchens/sous-admin` is wrong).
An unpacked recipe can be removed from Composer requirements after its
underlying dependencies transfer to the application. Updating its package/code
does not update active site configuration. Use [existing-site maintenance](MAINTENANCE.md)
and explicit migrations instead of blanket recipe reapplication.

## Interrupted installation and recovery

A failure propagates and stops dependent work; it does not print success or
remove bootstrap helpers. Preserving files is not a promise that every already
applied recipe/action can be replayed. Admin's strict Gin baseline, theme
creation, and user creation can reject repeated work after a partial install.

1. Keep the first failing command and its stderr. Check DDEV/Docker health,
   available resources, Composer platform/constraints, and the exact source
   revisions. Review `composer audit`/`npm audit` findings rather than forcing
   dependency resolution or bypassing platform/security checks.
2. For an interrupted **new disposable** installation, preserve any useful logs
   or source edits, then return to a clean generated copy/database and rerun the
   reviewed fresh sequence. Do not apply fresh setup to a valued existing site.
3. Naming alone can be repeated without deleting an already generated README,
   but a whole partial installation should not be blindly retried. Do not
   manually delete bootstrap scripts to make a failed install appear complete.
4. If failure occurs after cleanup during the final success output, use the
   generated site's restore/maintenance workflow; the removed hooks are a
   deliberate completion boundary. Do not reintroduce them into that site.
5. A failed restore stops at the failing step. If import has begun, restore the
   supplied backup again only in the intended local database after fixing the
   cause. Keep an untouched backup and use the site's complete configuration.
6. With no existing theme lockfile after interrupted initial generation, restart
   from a clean theme/setup fixture. For a successfully locked project use
   `ddev npm ci --include=dev` and `ddev npm run theme-ci`; never fix failures by
   repeatedly clearing npm caches or globally installing another Emulsify CLI.

For config import errors, confirm the configured sync directory contains the
site's complete export and matching site UUID, not the retired template YAML or
another fresh site's export. Do not restore the nonexistent `_splits` import.
A preserved settings file may intentionally override the default sync path;
review it instead of overwriting it. Known tooling advisories and transient
Layout Builder warnings are recorded in the implementation reviews; rendering
and configuration checks must still pass.
