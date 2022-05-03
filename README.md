[![Sous](https://circleci.com/gh/fourkitchens/sous-drupal-project.svg?style=svg)](https://app.circleci.com/github/fourkitchens/sous-drupal-project/pipelines)
[![semantic-release: angular](https://img.shields.io/badge/semantic--release-angular-e10079?logo=semantic-release)](https://github.com/semantic-release/semantic-release)
<br/>
<img style="max-width: 400px;" src="https://github.com/fourkitchens/sous-drupal-distro/blob/4.x-beta/themes/sous_admin/assets/images/sous.svg" alt="Sous featuring Emulsify">

# Sous Project

This will provide you with a starting Drupal project that is managed with Composer. The install will include a small set of contrib modules, a starting custom module for specific for the build, and a custom starting theme generated from Emulsify.

# Installation

## Requirements
Without these you will have difficulty installing this project.

1. [PHP ^7.4](http://www.php.net/)
2. [Node ^16.13 \(we recommend NVM\)](https://github.com/creationix/nvm)
3. [Composer 2.x](https://getcomposer.org/)
4. [Lando ^3.6](https://docs.lando.dev/basics/installation.html)

Use this command below and replace `PROJECT_NAME` with your chosen project name.

```
composer create-project fourkitchens/sous-drupal-project [PROJECT_NAME] --no-interaction

cd [PROJECT_NAME]

lando start

```

## Tweak & Install project

- Boot local environment and install `Lando start`

  - Follow URL once environment is booted and proceed with Drupal Install

- Create config directories and set path in settings.php

  - Recommendation is to create a config directory at the root level
  - Edit the `$settings['config_sync_directory']` line that was generated in settings.php

- Modify .gitignore
  - Remove the commented block at the EOF
  - Review ignored items you may need for your build and remove them

## Working with Emulsify
The [Emulsify](https://emulsify.info/) theme is installed as part of this project.

## Additional Tooling

This package provides some additional tooling to support the build.


### Helper scripts

To use the helper script provided you will need to have `npm` installed. Then just run `npm run <command>`. For example: `npm run import-data`. These commands are bash scripts located in the `./scripts/sous` directory and defined in `package.json`.

#### Configuration management scripts

**confex**

```
npm run confex
```

Export active configuration to the config directory.

**confim**

```
npm run confim
```

Import the configuration to the database.

**import-data**

```
npm run import-data
```

Import a copy of the canonical database backup into your local instance. This assumes the database backup is located in `./reference/db.sql.gz`.

**local-data-bak**

```
npm run local-data-bak
```

Create a local database backup. Saves the backup to the `./reference` directory.

**rebuild**

```
npm run rebuild
```

Rebuild a fresh local instance of your site. Imports the canonical database backup and imports configuration into it.

## Semantic Versioning

## Setup

1. This repo has the following named/maintenance branches:

```
master
x.x
x.x.x
```

2. These branches are protected on GitHub
3. A personal access token was created for CircleCI.
4. CircleCI was setup to run on this project and tag the releases
5. Commit changes following the [Conventional commit guidelines](https://www.conventionalcommits.org/en/v1.0.0/)
6. Push your change up and verify CircleCI passes and has run on your desired branch.

## Troubleshooting

1. Your branch must be a named stable release branch in order to get a tag.
2. Prereleases are not supported with this package because they contain a dot.

## Contributing

The composer command above can be adjusted to account for a new branch you're working on.

```
composer create-project fourkitchens/sous-drupal-project:dev-[branch-name] PROJECT_NAME --no-interaction

```

## Contributing without create-project or creating a project with a custom theme
1. Clone this repository
2. Checkout the 4.x-beta branch (if it hasn't been released yet)
3. `composer install` (you must have greater than PHP 7.4 installed locally)
4. `npm ci` (at the project root)
5. `npx emulsify init theme-name` (change theme-name to your theme name)
6. Change the name of the lando project if you haven't already
7. `lando start`
8. Go to the install page in your browser and install your Drupal site
9. `lando drush user:unblock superuser_1` (if you want to use drush uli)
10. Go forth and contribute!
