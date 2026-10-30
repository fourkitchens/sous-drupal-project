# Develop this site

Use DDEV from the project root. Follow [Setup](SETUP.md) for installation.
The theme name is `package.json`'s `config.theme`, in `web/themes/custom/<theme>`.

## Install and build

Install the committed root and theme lockfiles:

```sh
ddev start
ddev npm ci --include=dev
ddev npm run theme-ci
ddev npm run theme-build
```

Keep development dependencies installed for builds. Commit both npm manifests
and lockfiles. First setup normalizes the pinned UI Kit 1.9.0 for the current
Emulsify Core 4.5.0 tools and keeps its compatibility overrides in the theme.
Use routine build tasks after setup; `theme-setup.sh` initializes a theme once.

| Command | Result |
| --- | --- |
| `ddev npm run theme-build` | Production theme assets |
| `ddev npm run theme-watch` | Theme development task |
| `ddev npm run theme-storybook` | Static Storybook at `web/storybook` |
| `ddev composer build-assets` | Install PHP/root/theme dependencies and build assets |

Edit `src/`. Emulsify generates `dist/` and a `components/` mirror; include both
in deployments. Local Storybook is served at `/storybook/`.
The build/watch helpers accept an explicit theme directory when called directly:

```sh
sh devops/scripts/theme-build.sh "web/themes/custom/<theme>"
```

Run the installed Emulsify CLI from the theme directory:

```sh
cd "web/themes/custom/<theme>"
ddev emulsify component list
```

See the [CLI command reference](https://www.emulsify.info/docs/supporting-projects/emulsify-cli/emulsify-cli-usage).
Clear its cache with `ddev emulsify cache clear` before fetching a changed system branch.

## Change configuration

Commit the complete export in `config/default`, including UUIDs.
Keep environment values and secrets in the [site settings](SETUP.md#local-settings-and-config-split).
Export UI changes, review the diff, then test importing them on your local copy:

```sh
ddev npm run confex
git diff -- config
ddev npm run confim
```

`confim` runs Drush deployment and the project's post-import hook.
Keep reusable recipes in the [Sous packages repository](https://github.com/fourkitchens/sous-drupal-packages).
See [Deployment](DEPLOYMENT.md) for dependency and patch updates.

## Check and review

```sh
ddev npm run lint
ddev npm run prettier
ddev npm run theme-build
```

Lint checks custom PHP syntax, JavaScript and the installed theme's JS/SCSS.
Use [Drupal coding standards](https://project.pages.drupalcode.org/coding_standards/).
Review source changes after autofixing:

```sh
ddev npm run lint:js -- --fix
ddev npm --prefix "web/themes/custom/<theme>" run lint-fix
ddev npm --prefix "web/themes/custom/<theme>" run prettier-fix
```

Check affected pages, editing, permissions, keyboard use and accessibility.
Use Conventional Commits. Include the problem, behavior, checks and deployment
steps in the pull request. Commit custom source, configuration and lockfiles.

## Update an existing theme

Compare the child theme with upstream template, library and npm changes.
Update its source, install dependencies, then run lint, builds and Storybook.
For Emulsify Tools 2 favicon migration, repair and export:

```sh
ddev drush emulsify_tools:repair-favicon-config "<theme>"
ddev npm run confex
```
