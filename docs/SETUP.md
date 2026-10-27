# Set up a site

Use DDEV, Docker, and Git for local development. Creating a project with
Composer also requires PHP and Composer on your computer. See
[Platform](PLATFORM.md) for supported versions and [Features](FEATURES.md) for
the installation choices.

## Create a new site

Create the project in a new directory:

```sh
composer create-project fourkitchens/sous-drupal-project:^8.0 my-site
cd my-site
```

The Composer hook names the project from its directory and asks which content
composition to install and whether to add Config Split. It starts DDEV after
both questions. Choose the content option explicitly;
Config Split defaults to no.

For an unattended install, set both answers before running Composer:

```sh
SOUS_CONTENT=layout-builder SOUS_CONFIG_SPLIT=yes \
  composer create-project fourkitchens/sous-drupal-project:^8.0 my-site
```

`SOUS_CONTENT` accepts `default`, `layout-builder`, `paragraphs`, or `admin-only`
(and the corresponding menu numbers `0`–`3`). `SOUS_CONFIG_SPLIT` accepts `yes`
or `no`. Invalid values stop the bootstrap before Drupal or theme installation.
When input is unavailable, an unset content choice stops setup; an unset Config
Split choice uses no.

Setup installs Drupal, applies the selected recipes, builds the custom theme,
exports configuration to `config/default`, and prints a login link. It creates
the `sous_chef` administrator account and blocks the initial `superuser_1`
account. Successful setup removes its one-time installer files.

Commit the resulting Composer and npm manifests and lockfiles, `patches.lock.json`,
the custom theme source, and the complete configuration export. Dependencies,
compiled assets, local settings, and database backups stay out of source Git.
[Deployment](DEPLOYMENT.md) explains which generated files belong in a build.

## Restore an existing site

Clone the site's repository, including its lockfiles, custom theme, and complete
configuration export. Get a database backup and any uploaded files the site
needs. From the project root, run:

```sh
sh devops/scripts/setup.sh restore "reference/site-db.sql.gz"
```

This starts DDEV, installs and builds the theme, installs root dependencies,
imports and sanitizes the database, and runs Drush deployment. It prints a
`sous_chef` login link only after import and deployment succeed. Restoration
replaces the local database and refuses the production environment.

After a completed bootstrap, omitting the mode also selects restore:

```sh
sh devops/scripts/setup.sh
```

Before bootstrap finishes, choose `fresh` or `restore` explicitly. A Git clone
alone does not select restore. Do not run fresh installation in a site that
contains data you need to keep.

## Refresh a local database

For an already configured site, refresh dependencies and the database without
rebuilding the theme:

```sh
sh devops/scripts/rebuild.sh "reference/site-db.sql.gz"
```

To import and sanitize only, without deploying configuration or printing a
login link:

```sh
sh devops/scripts/import-data.sh "reference/site-db.sql.gz"
```

Backup paths can be absolute or relative to the project root and may contain
spaces. Both commands use `reference/site-db.sql.gz` when no path is supplied.
If that default is missing, import first calls `get-db.sh`. An explicitly
supplied missing path fails instead.

Only non-empty `.sql` and `.sql.gz` files are accepted. Gzip files must pass
`gzip -t` before any database command. After a successful import, the helper
rebuilds caches, runs the optional post-import hook, and sanitizes user passwords
to `admin` and email addresses to `user-%uid@example.com`.

Configure `devops/hooks/download-db.sh` to write a backup to the path in `$1`.
Then download a fresh default backup with:

```sh
sh devops/scripts/get-db.sh
```

An optional destination can follow the command. Downloads go to a `.part` file;
the destination is replaced only after the hook succeeds and validation passes.
Sous supplies no database source or credentials. Keep backups outside Git and
follow the project's rules for handling production data.

Optional `devops/hooks/post-import.sh` and `post-config-import.sh` run after
import and deployment respectively. Use `runtime_drush` in these scripts for
project commands. A failed import still attempts its hook; a failed deployment
still attempts its hook. The original failure remains the command's exit status.
Hooks do not run in production. See [Environments](ENVIRONMENTS.md) for routing
and environment selection.

Save a local backup before changing data:

```sh
sh devops/scripts/local-data-bak.sh
```

It writes a timestamped gzip dump under `reference/`.

## Add a recipe

Install the package, then apply it to the site. For example:

```sh
ddev composer require fourkitchens/sous-content-types:^2.0
ddev npm run install-recipe -- fourkitchens/sous-content-types
ddev npm run confex
```

The helper applies an already installed recipe under `web/recipes/`; it does
not download packages. Review the complete export and commit the site's changed
manifest, lockfile, and configuration. [Features](FEATURES.md) lists the packages;
[Maintenance](MAINTENANCE.md) covers later package and configuration updates.

## Recover from an interrupted setup

Read the first failing command before retrying. Fix its dependency, database,
or configuration error first. If the one-time installer still exists, rerun
`sh devops/scripts/setup.sh fresh` only when replacing that database is intended.
Keep the original starter checkout separate from generated sites: naming edits
files, and successful installation removes the bootstrap helpers. Use a new
project directory when you need to start over without preserving partial work.
