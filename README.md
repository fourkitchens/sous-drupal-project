# Sous Project

This will provide you with a starting Drupal project that is managed with Composer. The install will include a small set of contrib modules, a starting custom module for specific for the build, and a custom starting theme generated from Emulsify.

## Install

Use this command below and replace `PROJECT_NAME` with your chosen project name.

```
composer create-project fourkitchens/sous-drupal-project PROJECT_NAME --no-interaction

```

## Additional Tooling

This package provides some additional tooling to support the build.

### Helper scripts

To use the helper script provided you will need to have `yarn` or `npm` installed. Then just run `yarn <command>` or `npm run <command>`. For example: `yarn import-data`. These commands are bash scripts located in the `./scripts/sous` directory and defined in `package.json`.

#### Configuration management scripts

**confex**

```
yarn confex
```

Export active configuration to the config directory.

**confim**

```
yarn confim
```

Import the configuration to the database.

**import-data**

```
yarn import-data
```

Import a copy of the canonical database backup into your local instance. This assumes the database backup is located in `./reference/db.sql.gz`.

**local-data-bak**

```
yarn local-data-back
```

Create a local database backup. Saves the backup to the `./reference` directory.

**confex**

```
yarn rebuild
```

Rebuild a fresh local instance of your site. Imports the canonical database backup and imports configuration into it.
