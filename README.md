[![Sous CI](https://github.com/fourkitchens/sous-drupal-project/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/fourkitchens/sous-drupal-project/actions/workflows/ci.yml)

# Sous Drupal starter

Sous creates a Drupal site with Gin administration, Navigation and an Emulsify
theme. This Composer template supplies DDEV, settings and setup commands;
[Sous packages](https://github.com/fourkitchens/sous-drupal-packages) supply recipes.
Your site owns its configuration, custom code, dependencies and deployment.

## Installation options

| Choice | Content setup |
| --- | --- |
| `0` Default | Page and Frontpage with Body, metadata, SEO, teasers and title-based aliases; Page also has tags |
| `1` Layout Builder | Page and Frontpage with field blocks, Basic inline blocks and per-node layout overrides |
| `2` Paragraphs | Page and Frontpage with Body and Image, Text, Text with Media and Video components |
| `3` Admin only | Administration and theme without Page or Frontpage |

Layout Builder and Paragraphs are separate compositions; installation downloads
only your selection. Optional recipes, including Config Split, can be added from
the setup menu or with `SOUS_RECIPES`.
CI is a separate choice: CircleCI, GitHub Actions, GitLab CI/CD or None.

## Quickstart

Install PHP 8.4, Composer 2, Git, Docker, DDEV and Node.js from `.nvmrc`
on your computer, then run:

```sh
composer create-project fourkitchens/sous-drupal-project:^8.0 my-site
cd my-site
```

Composer prompts for the options above, installs Drupal and prints a login link.
For an existing site clone, use `npm run setup`; after switching branches, use
`npm run rebuild`. Run `npm run theme-watch` while developing the theme.

- [Set up, restore or add recipes](docs/SETUP.md)
- [Develop, build and check the theme](docs/DEVELOPMENT.md)
- [Deploy the site](docs/DEPLOYMENT.md)

To change Sous itself, use the [maintainer guide](docs/maintainers/MAINTAINING.md).

[GPL-2.0-or-later](LICENSE); dependencies retain their own notices.
