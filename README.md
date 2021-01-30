[![Sous](https://circleci.com/gh/fourkitchens/sous-drupal-project.svg?style=svg)](https://app.circleci.com/github/fourkitchens/sous-drupal-project/pipelines)
<br/>
![Sous featuring Emulsify](https://github.com/fourkitchens/sous-drupal-distro/blob/master/themes/sous_admin/assets/images/Sous.png "Sous featuring Emulsify")

# Sous Project

This will provide you with a starting Drupal project that is managed with Composer. The install will include a small set of contrib modules, a starting custom module for specific for the build, and a custom starting theme generated from Emulsify.


## Composer Install

Use this command below and replace `PROJECT_NAME` with your chosen project name.

```
composer create-project fourkitchens/sous-drupal-project PROJECT_NAME --no-interaction

```

## Tweak & Install project

- Rename your project in `.lando.yml` file (line 1, line 10)
- Boot local environment and install `Lando start`
    - Follow URL once environment is booted and proceed with Drupal Install

- Create config directories and set path in settings.php
    - Recommendation is to create a config directory at the root level
    - Edit the `$settings['config_sync_directory']` line that was generated in settings.php

- Modify .gitignore
    - Remove the commented block at the EOF
    - Review ignored items you may need for your build and remove them


### Build project module

Create a new project module
Generate a custom module at `/web/modules/custom/PROJECT_NAME` using drupal console.
Follow the documentation for the generate:module command [here](https://hechoendrupal.gitbooks.io/drupal-console/en/commands/generate-module.html)


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
yarn local-data-bak
```

Create a local database backup. Saves the backup to the `./reference` directory.

**rebuild**

```
yarn rebuild
```

Rebuild a fresh local instance of your site. Imports the canonical database backup and imports configuration into it.

## Semantic Versioning

Setup
-----

  1. This repo has the following named/maintenance branches:
```
main
x.x
x.x.x
```
  2. These branches are protected on GitHub
  3. A personal access token was created for CircleCI.
  4. CircleCI was setup to run on this project and tag the releases
  5. Commit changes following the [Conventional commit guidelines](https://www.conventionalcommits.org/en/v1.0.0/)
  6. Push your change up and verify CircleCI passes and has run on your desired branch.

Troubleshooting
---------------

  1. Your branch must be a named stable release branch in order to get a tag.
  2. Prereleases are not supported with this package because they contain a dot.
