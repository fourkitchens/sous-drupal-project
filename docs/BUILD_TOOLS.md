# Build Tools

Use Node.js from `.nvmrc`. Run npm scripts from the project root.
Shared Drupal commands use DDEV in a local DDEV checkout and installed tools in CI, on hosts or inside its container.
Setup, rebuild, import-data and local-data-bak remain local DDEV operations.

## Theme

Run `ddev drush config:get system.theme default` to find the active theme. The root `package.json` selects the build target through `config.theme`. In `web/themes/custom/<theme>`, read the theme's `README.md` and its `package.json` for frontend commands.

The installer enables the generated Emulsify theme and installs its npm dependencies. You can start with project assets or install a component library later.

### Author project assets

Add a Sass or JavaScript entrypoint under the theme's `src/` directory and run `npm run theme-build` from the project root. For example, in the generated theme, `src/base/site.scss` builds to `dist/global/base/site.css`.

To load the stylesheet in Drupal, list the compiled path in the theme's `.libraries.yml` and attach that library in the theme's `.info.yml` for all pages or in the relevant Twig template. Keep maintained source under `src/`. Builds write compiled assets to `dist/` and mirror Drupal components to `components/` when present.

### Install a component library later

Use the Emulsify CLI to install UI Kit or an external library. For UI Kit, use the upstream lint-fix branch until UI Kit 2.0 ships. [Emulsify CLI usage](https://www.emulsify.info/docs/supporting-projects/emulsify-cli/emulsify-cli-usage) · [UI Kit lint fix](https://github.com/emulsify-ds/emulsify-ui-kit/pull/189)

```sh
(
  cd "web/themes/custom/<theme>"
  npx --no-install emulsify system install --repository https://github.com/emulsify-ds/emulsify-ui-kit.git \
    --checkout codex/fix-drupal-theme-lint --all --yes
  npm install --include=dev
)
npm run theme-build
```

For an external library, use its Git URL and branch or tag in the command. The repository name must match its Emulsify system name. Follow the installed library's guidance for its source and asset integration.

An empty scaffold reports that it has no asset entrypoints. Once the theme has build inputs, build failures stop the command.

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
| `npm run theme-watch` | Watch theme assets with Vite and run Storybook |
| `npm run theme-storybook` | Build static Storybook at `web/storybook` |

Commit both npm manifests and lockfiles. Compare source, libraries and npm changes when updating the theme.
CI runs `composer ci` directly with PHP and Node; see [Deployment](DEPLOYMENT.md).
