# Build tools

Welcome to the world of build tools! In this section, we will explore various tools and scripts that can help streamline your development workflow and ensure consistency in your codebase.

1. [Theme](#theme)
2. [Fire](#fire)
3. [NPM Scripts](#npm-scripts)

## Theme

This project uses an [Emulsify](https://github.com/emulsify-ds/emulsify-drupal) theme named ` (`web/themes/custom/[theme_name]/`). For details on Emulsify usage, see that [project wiki](https://docs.emulsify.info/).

## Fire

This project uses the [FIRE](https://github.com/fourkitchens/fire) toolkit for local development.

### Install (or reinstall) your project locally

```bash
fire build
```

This will call many other FIRE commands in the ideal order to get you up and running (`build-php`, `build-js`, `build-theme`, `get-db`, `import-db`, etc.).  If any one of these commands fails, you can try to re-run it with more verbose output to get more details `fire <command> -v`.

### To see a list of all available commands

Just run `fire`

### Get more info about a specific command

`fire help <command>`

### Most common commands

Here we show the short-form alias.  Each of these also has a long-form full name.  Some of these commands accept additional arguments.

* `fire start`
* `fire stop`
* `fire build`
* `fire build-php`
* `fire build-theme`
* `fire watch-theme`
* `fire get-db`
* `fire import-db`
* `fire drush`
* `fire cex`
* `fire cim`
* `fire xdebug:enable`

## NPM Scripts

NPM Scripts are a powerful feature of Node.js that allow you to define and run custom scripts for your project. These scripts can be used to automate various tasks, such as building your project, running tests, or deploying to production.

In this section, we will explore some of the commonly used NPM Scripts for local development and Drupal-specific tasks. These scripts will help you streamline your development workflow and ensure consistency in your codebase.

Let's dive in and discover the different NPM Scripts available for your project!

### Local Build Commands

`npm run setup` - Run all required script for spinning up the local site.
`npm run rebuild` - Rebuild a fresh local instance of your site. Imports the canonical database backup and imports configuration into it.
`npm run get-db` - Download a copy of the canonical database backup into your local `./reference` directory.
`npm run import-data` - Import a copy of the canonical database backup into your local instance. This assumes the database backup is located in `./reference/db.sql.gz`.
`npm run local-data-bak` - Create a local database backup. Saves the backup to the `./reference` directory.

### Drupal specific build commands

`npm run confex` - Export active configuration to the config directory.
`npm run confim` - Import the configuration to the database.

### Linting

Check on coding standards for all custom code.

`npm run lint:js` - Lint javascript code for syntax errors.
`npm run lint:php` - Lint php code for syntax errors.
`npm run lint:styles` - Lint css code for syntax errors.

### Theme Tasks

`npm run theme-build` - Builds the emulsify based theme.
`npm run theme-watch` - Used for theme development.
