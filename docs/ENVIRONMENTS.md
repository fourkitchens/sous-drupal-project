# Configure environments

Set `SOUS_ENVIRONMENT` in the site's runtime environment:

| Value | Settings |
| --- | --- |
| `local` | Load local settings, enable development services, capture mail, disable automated cron |
| `staging` | Use production caches and error display, capture mail, disable automated cron |
| `production` | Use production caches, error display, and the site's configured mail transport |

An explicit value wins. Inside DDEV, an unset value defaults to `local`;
elsewhere it defaults to `production`. Empty or unknown values fail. A local
settings file does not choose the environment.

Use `staging` for dev, test, or multidev environments that need the same policy.
Select additional Config Split definitions when their modules or configuration
need to differ. Configure production mail, scheduled cron, file storage, and
secrets through the project and its hosting environment.

## Load settings and services

Composer scaffolds these files without replacing existing copies:

- `web/sites/default/settings.sous.php`
- `web/sites/development.services.yml`
- `web/sites/sous.production.services.yml`

The generated `settings.php` loads DDEV's database settings before
`settings.sous.php`. The latter loads `settings.local.php` only for `local`.
Keep this order when adopting the files in an existing project:

```php
$settings['config_sync_directory'] = '../config/default';
if (getenv('IS_DDEV_PROJECT') === 'true' && is_readable($app_root . '/' . $site_path . '/settings.ddev.php')) {
  require $app_root . '/' . $site_path . '/settings.ddev.php';
}
require $app_root . '/' . $site_path . '/settings.sous.php';
```

Replace earlier unconditional local/DDEV includes instead of loading them again
later. Keep the site's database integration and service customizations. Test on
a database copy before changing an existing site's settings.

Outside local, the Sous service settings disable Twig debugging and keep Twig
and render caching enabled. They also prevent copied development UI switches
from changing these defaults, disable debug cacheability headers and rebuild
access, and hide PHP error output. Preserve other `factory.keyvalue` mappings
when customizing the services file. Disable server debugging extensions such
as Xdebug separately.

Local and staging use Drupal's `test_mail_collector` as the default mail backend.
It stores mail in Drupal state instead of sending it. Check per-module mail
mappings and integrations that send mail outside Drupal's mail manager.
Production uses the site's stored mail configuration. Configure a file-proxy
origin explicitly if the project uses one; Sous supplies none and clears it in
production.

## Add Config Split

Config Split is optional during fresh installation and can be added later.
See [Features](FEATURES.md) for the package and [Setup](SETUP.md#add-a-recipe) for
applying a recipe. The package uses these directories:

| Directory | Contents |
| --- | --- |
| `config/default` | Complete production base, including the `_splits` definition |
| `config/_splits` | Environment split definitions |
| `config/local` | Local modules and configuration |
| `config/staging` | Staging modules and configuration |

The settings keep `_splits` active, enable `sous_local` only for local, and enable
`sous_staging` only for staging. Production leaves both environment splits off.
The `_splits` priority imports definitions before their values and exports them
afterward. Keep environment split statuses inactive in the stored definitions.

For more environments, create definitions with distinct IDs and folders, add
them to `_splits.complete_list`, and set their statuses in the project's settings.
For example, after the Sous include:

```php
$deployment = getenv('PROJECT_DEPLOYMENT') ?: '';
foreach (['dev', 'test', 'multidev', 'live'] as $split) {
  $config['config_split.config_split.' . $split]['status'] = $deployment === $split;
}
```

Set `SOUS_ENVIRONMENT` separately to choose local, staging, or production settings.
Configure split lists before enabling development modules, then export and
review both the base and split files. Splits change configuration; production
`composer install --no-dev` controls whether development dependencies are present.

## Export and import configuration

From a DDEV project:

```sh
ddev npm run confex
ddev npm run confim
```

`confex` runs `drush config:export --yes`. `confim` runs `drush deploy --yes`.
Pass additional Drush arguments after `--`. Outside production, deployment also
attempts the optional `devops/hooks/post-config-import.sh`, even if deployment
failed. A deployment failure takes precedence over a hook failure.

Shell helpers use DDEV from the host and the container's project tools inside
DDEV. They default to `local`. The project must provide `vendor/bin/drush`;
the helpers do not use global Drush.

Set environment variables in DDEV; host variables are not forwarded
automatically into the container. Database refresh and setup commands refuse an
explicit `production` environment before calling tools. See [Setup](SETUP.md)
for those commands. On production servers, run the project's Drush directly as
shown in [Deployment](DEPLOYMENT.md#deploy-code-and-configuration).

## Check configuration and runtime settings

Run both checks against a bootstrapped site:

```sh
ddev npm run check:environment -- config
ddev npm run check:environment -- runtime
```

`config` is the default mode. It reads `config/default` and checks for development
modules, verbose error display, capture mail backends, and disabled CSS/JS
aggregation. Its development-module list includes `devel`, `devel_generate`,
`webprofiler`, `stage_file_proxy`, `reroute_email`, and `devel_mail_log`. Add other
project module IDs with `$settings['sous_development_modules']`.

`runtime` checks effective settings. Outside local, Twig debug must be off,
Twig cache on, render caching enabled, and rebuild access off. Production must
have no capture mail backend. Local and staging must use the default mail
collector and an automated-cron interval of `0`.

Each rule prints `PASS` or `FAIL`; any failure exits with status `1`. A missing
`sous_environment` setting fails with instructions to adopt `settings.sous.php`.
These commands do not import configuration or run automatically during export
or deployment. Use them explicitly in the project's checks and deployment
pipeline; see [Deployment](DEPLOYMENT.md).
