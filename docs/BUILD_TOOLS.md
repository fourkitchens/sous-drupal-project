# Build Tools

Use Node.js from `.nvmrc`. Run npm scripts from the project root.
Shared Drupal commands use DDEV in a local DDEV checkout and installed tools in CI, on hosts or inside its container.
Setup, rebuild, import-data and local-data-bak remain local DDEV operations.

## Theme

The Emulsify theme is at `web/themes/custom/<theme>`; `package.json` records its name in `config.theme`.
Initial installation enables the theme and installs its npm dependencies, without a component library or asset build.
When ready, choose a library through the [Emulsify CLI](https://www.emulsify.info/docs/supporting-projects/emulsify-cli/emulsify-cli-usage); this example installs UI Kit manually:

```sh
(
  cd "web/themes/custom/<theme>"
  npx emulsify system install --repository https://github.com/emulsify-ds/emulsify-ui-kit.git \
    --checkout 1.9.0 --variant drupal --all
  npm install
)
npm run theme-build
npm run theme-watch
```

Edit `src/`; builds generate `dist/` and a `components/` mirror for deployment.
An empty scaffold reports no build inputs; once sources exist, build failures stop the command.

## NPM Scripts

### Local Build Commands

| Command | Purpose |
| --- | --- |
| `npm run setup` | Install root/theme dependencies, build available sources, then rebuild a fresh clone |
| `npm run rebuild` | Start DDEV, install PHP dependencies, import a backup, deploy and print a login |
| `npm run get-db` | Run the project's database download command |
| `npm run import-data` | Import and sanitize a local database |
| `npm run local-data-bak` | Save a timestamped backup under `reference/` |

### Drupal specific build commands

```sh
npm run confex
git diff -- config
npm run confim
```

Commit the complete `config/default` export, including UUIDs; use [settings overrides](SETUP.md#local-settings-and-config-split) for environment values.
`confim` runs Drush deploy, including `hook_deploy_NAME()` steps.
Both configuration commands accept a leading Drush alias, such as `npm run confim -- @mysite.prod`.

### Linting

```sh
npm run lint
ddev composer code-sniff
ddev composer code-fix
npm run prettier
```

PHP coding standards use `phpcs.xml.dist`; review autofixes and follow [contribution guidelines](CONTRIBUTING.md).

### Theme Tasks

| Command | Purpose |
| --- | --- |
| `npm run theme-ci` | Install locked theme dependencies |
| `npm run theme-build` | Build production assets when source inputs exist |
| `npm run theme-watch` | Develop the theme after adding a component library |
| `npm run theme-storybook` | Build static Storybook at `web/storybook` |

Commit both npm manifests and lockfiles. Compare source, libraries and npm changes when updating the theme.
For Emulsify Tools 2 favicon migration, run `ddev drush emulsify_tools:repair-favicon-config "<theme>"` and export configuration.
CI runs `composer ci` directly with PHP and Node; see [Deployment](DEPLOYMENT.md).
