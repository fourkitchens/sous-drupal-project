# PROJECT_NAME agent instructions

PROJECT_NAME is a Drupal site built from Four Kitchens' Sous starter.

- Run `ddev start`, `ddev composer install`, and `npm ci` from the project root. Use `ddev drush` and `ddev composer` for local PHP commands. See [Local Setup](docs/SETUP.md).
- `npm run setup`, `npm run rebuild`, and `npm run import-data` replace the local database. Read the [restore instructions](docs/SETUP.md#restore-or-refresh-locally) before running them. `npm run confim` runs Drush deploy: database updates, configuration import and deployment hooks, in that order.
- The custom theme is `web/themes/custom/PROJECT_THEME`. Run `npm run theme-build` and `npm run theme-watch` from the project root, or run the theme's scripts from `web/themes/custom/PROJECT_THEME`. Read the [custom theme's AGENTS.md](web/themes/custom/PROJECT_THEME/AGENTS.md) and the [theme build guide](docs/BUILD_TOOLS.md#theme) before frontend work.
- Export shared configuration to `config/default` with `npm run confex`. Follow the [configuration workflow](docs/BUILD_TOOLS.md#drupal-specific-build-commands) and the [recipe workflow](docs/SETUP.md#add-a-recipe).
- Manage `vendor/`, `web/core`, and contrib extensions through Composer. Follow the [dependency and patch workflow](docs/DEPLOYMENT.md#update-dependencies-and-configuration). Change scaffolded defaults through the mappings in [composer.json](composer.json) and their inputs in [assets/scaffold](assets/scaffold/).
- Run `composer ci` for the full build and checks. See [focused checks](docs/BUILD_TOOLS.md#linting) and the [contribution guide](docs/CONTRIBUTING.md) before opening a pull request.
- Workflow facts: Read [the workflow facts](docs/agents/workflow-facts.md) before debugging, implementing, reviewing, or deploying a change.
