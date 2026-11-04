[![Sous CI](https://github.com/fourkitchens/sous-drupal-project/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/fourkitchens/sous-drupal-project/actions/workflows/ci.yml)

# Sous Project

A Drupal starter with Gin administration, an Emulsify theme and Composer recipes.
Sous prepares the project; your site owns its code, dependencies and configuration.

## Features and Configurations

Choose the content model during installation; administration, the theme, Media, Config Split and a publishing workflow are always included.

| Choice | Content setup |
| --- | --- |
| `0` Admin only | Administration and theme without Page or Frontpage |
| `1` Paragraphs | Page and Frontpage with Layout Paragraphs components |
| `2` Layout Builder | Page and Frontpage with native layouts and text, media, Accordion, Tabs, Quote, Embed and Table blocks |

The package index advertises optional recipes such as Events, News Post, Profile, SEO and Dev Tools. Dev Tools adds Devel, Stage File Proxy and Masquerade locally. Paragraph Types belongs to the Paragraphs main course.
See [features and configurations](docs/features.md) for the site's building blocks.

# Installation

## Dependencies and Requirements

- [PHP 8.4](https://www.php.net/) and [Composer 2](https://getcomposer.org/)
- [DDEV](https://ddev.com/), Docker and Git
- [Node.js from `.nvmrc`](https://github.com/nvm-sh/nvm) on your computer

```sh
composer create-project fourkitchens/sous-drupal-project:^8.0 PROJECT_NAME
cd PROJECT_NAME
```

Answer the content, optional recipe, CI and dessert questions before installation starts. Dessert offers Emulsify UI Kit, a custom component library or None.
For an unattended install, set every answer:

```sh
SOUS_CONTENT=layout-builder SOUS_RECIPES=sous-events,sous-news-post \
  SOUS_CI=gitlab SOUS_LIBRARY=none \
  composer create-project fourkitchens/sous-drupal-project:^8.0 PROJECT_NAME
```

`SOUS_CONTENT` accepts `admin-only` (0), `paragraphs` (1) or `layout-builder` (2).
`SOUS_CI` accepts `circleci`, `github`, `gitlab` or `none` (0–3).
Set `SOUS_RECIPES=''` for no extras, or supply names separated by spaces or commas.
`SOUS_LIBRARY` accepts `none` (default), `ui-kit` or an Emulsify-compatible Git URL ending in `.git`.
`SOUS_LIBRARY_REF` optionally selects a branch or tag; custom repositories otherwise use their default branch.
The final order summary shows the local URL and a generated password for `sous_chef`; UID 1 stays blocked.

If a fresh install fails partway through, keep the partial directory for troubleshooting and fix the reported error. Run `composer create-project` again in a fresh directory with a different name so DDEV creates a separate database. To reuse the original name, remove the failed DDEV project and move the partial directory aside first. The installer starts from a fresh database and theme scaffold on each run.

# Recipe Dependency Management

[Sous packages](https://github.com/fourkitchens/sous-drupal-packages) publish independent Composer recipes.
Drupal's Composer plugin unpacks recipe dependencies when Composer requires a recipe. The installer applies the recipes once, then removes their source directories and the setup repository. The site owns the resulting dependencies and configuration. See [adding a recipe](docs/SETUP.md#add-a-recipe) for later additions.

# Working with Emulsify

Installation generates a project-named [Emulsify](https://www.emulsify.info/) theme, sets it as the frontend default and installs its npm dependencies. Selecting a component library also installs it and compiles the theme; None leaves the scaffold ready for later. See [Build Tools](docs/BUILD_TOOLS.md#theme).

# Additional Tooling

## Helper scripts

Run `npm run <command>` through the shell scripts in `devops/scripts/`.
Shared Drupal commands use DDEV locally and installed tools in CI or on hosts; Node tools run directly.
Setup, rebuild, database import and backup remain local DDEV commands.

| Command | Purpose |
| --- | --- |
| `npm run setup` | Prepare a fresh clone, build available theme sources and restore its database |
| `npm run rebuild` | Reset a branch's database and deploy configuration |
| `npm run confex` / `npm run confim` | Export configuration / run Drush deploy |
| `npm run import-data` / `npm run local-data-bak` | Import or back up the local database |

See [Local Setup](docs/SETUP.md), [Build Tools](docs/BUILD_TOOLS.md) and [Deployment](docs/DEPLOYMENT.md).

# Semantic Versioning

Conventional Commits drive releases from `main`. Generated sites use `v`-prefixed
GitHub or GitLab releases; Sous retains its unprefixed tags.
See [release setup](docs/DEPLOYMENT.md#releases).

# Contributing

Use [Conventional Commits and review guidance](docs/CONTRIBUTING.md).
Read the [maintainer guide](docs/maintainers/MAINTAINING.md) before changing Sous or its recipes.

[GPL-2.0-or-later](LICENSE); upstream packages retain their own licenses and notices.
