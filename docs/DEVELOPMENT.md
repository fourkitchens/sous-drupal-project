# Develop this site

Use Node.js from `.nvmrc` on your computer and run npm scripts from the project
root. They call DDEV for PHP, Drush and the database; Node tools run directly.
Follow [Setup](SETUP.md) for installation.
The theme name is `package.json`'s `config.theme`, in `web/themes/custom/<theme>`.

## Install and build

Install the committed root and theme lockfiles:

```sh
npm ci
npm run theme-ci
npm run theme-build
```

Keep development dependencies installed for builds. Commit both npm manifests
and lockfiles. First setup normalizes the pinned UI Kit 1.9.0 for the current
Emulsify Core 4.5.0 tools and keeps its compatibility overrides in the theme.

| Command | Result |
| --- | --- |
| `npm run setup` | Install Node dependencies, build assets and restore the local site |
| `npm run rebuild` | Refresh PHP dependencies, restore the database and deploy configuration |
| `npm run theme-ci` | Install the theme's locked npm dependencies |
| `npm run theme-build` | Production theme assets |
| `npm run theme-watch` | Theme development task |
| `npm run theme-storybook` | Static Storybook at `web/storybook` |

Edit `src/`. Emulsify generates `dist/` and a `components/` mirror; include both
in deployments. Local Storybook is served at `/storybook/`.
Run the installed Emulsify CLI from the theme directory:

```sh
cd "web/themes/custom/<theme>"
npx emulsify component list
```

See the [CLI command reference](https://www.emulsify.info/docs/supporting-projects/emulsify-cli/emulsify-cli-usage).
Clear its cache with `npx emulsify cache clear` before fetching a changed system branch.

## Change configuration

Commit the complete export in `config/default`, including UUIDs.
Keep environment values and secrets in the [site settings](SETUP.md#local-settings-and-config-split).
Export UI changes, review the diff, then test importing them on your local copy:

```sh
npm run confex
git diff -- config
npm run confim
```

`confim` runs local Drush deployment. Add deployment steps in
`hook_deploy_NAME()`, which Drush deploy runs.
Keep reusable recipes in the [Sous packages repository](https://github.com/fourkitchens/sous-drupal-packages).
See [Deployment](DEPLOYMENT.md) for dependency and patch updates.

## Check and review

```sh
npm run lint
ddev composer code-sniff
ddev composer code-fix
npm run prettier
npm run theme-build
```

CI runs `composer ci` with PHP 8.4 and Node.js from `.nvmrc`: validate Composer,
install PHP development dependencies, install and build the front end, lint and
check coding standards. CI and hosting builds use Composer scripts directly;
they do not run the local npm setup or database scripts.
Lint checks custom PHP syntax, JavaScript and the installed theme's JS/SCSS.
`code-sniff`, `code-fix` and editors share `phpcs.xml.dist` for the Drupal and
DrupalPractice standards; review changes after `code-fix`.
Use [Drupal coding standards](https://project.pages.drupalcode.org/coding_standards/).
Review source changes after autofixing:

```sh
npm run lint:js -- --fix
npm --prefix "web/themes/custom/<theme>" run lint-fix
npm --prefix "web/themes/custom/<theme>" run prettier-fix
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
npm run confex
```
