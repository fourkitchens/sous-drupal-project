# Set up a site

Use DDEV for local development. Install Docker, DDEV, PHP and Composer on your
computer. DDEV supplies Node.js, npm and Git for setup. See
[Platform](PLATFORM.md) for supported versions and [Features](FEATURES.md) for
the installation choices.

## Create a new site

Create the project in a new directory:

```sh
composer create-project fourkitchens/sous-drupal-project:^8.0 my-site
cd my-site
```

Composer's post-create hook runs the one-time `sous-build.sh` installer. It names
the project from its directory, records the theme name in `package.json` under
`config.theme`, and asks which content composition to install and whether to add
Config Split. It starts DDEV after both questions. Choose the content option
explicitly; Config Split defaults to no.

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

The post-create hook installs Drupal, applies the selected recipes, builds the
custom theme, exports configuration to `config/default`, and prints a login
link. It creates the `sous_chef` administrator account and blocks the initial
`superuser_1` account.

Commit the resulting Composer and npm manifests and lockfiles, `patches.lock.json`,
the custom theme source, and the complete configuration export. Dependencies,
compiled assets, local settings, and database backups stay out of source Git.
[Deployment](DEPLOYMENT.md) explains which generated files belong in a build.

## Local and host settings

`settings.php` uses Drupal's standard local include:

```php
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}
```

DDEV supplies database credentials through `settings.ddev.php`; keep
`settings.local.php` untracked. Export production defaults with aggregation on
and errors hidden; supply credentials and services through the host's settings.

With Config Split, enable local in `settings.local.php`:
`$config['config_split.config_split.local']['status'] = TRUE;`
For staging, put `$config['config_split.config_split.staging']['status'] = TRUE;` in the staging host's settings.
Keep both splits inactive in exported configuration.

## Restore an existing site

Clone the site's repository, including its lockfiles, custom theme, and complete
configuration export. Get a database backup and any uploaded files the site
needs. From the project root, run:

```sh
sh devops/scripts/setup.sh "reference/site-db.sql.gz"
```

This starts DDEV, installs dependencies, builds the theme, imports and sanitizes
the database, and runs Drush deployment. It prints a `sous_chef` login link only
after import and deployment succeed. It replaces the local DDEV database and
can be run whenever the site needs restoring. Omit the backup argument to use
`reference/site-db.sql.gz`.

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
Local helpers use DDEV from the host or the container's project tools inside
DDEV.

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

Read the first failing command and fix its dependency, database or configuration
error before creating the project again in a new directory. Installation runs
only through Composer's post-create hook; `setup.sh` restores an existing site.
