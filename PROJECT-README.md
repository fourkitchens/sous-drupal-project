# PROJECT_NAME

Content management system built on Drupal. Record project URLs, contacts and access requirements here.

## At A Glance

| Item | Details |
| --- | --- |
| Local Environment | [DDEV](https://ddev.com/) |
| Drupal / PHP | Drupal 11.4 / PHP 8.4 |
| Node.js | Version in `.nvmrc`, installed on your computer |
| Theme | Emulsify theme; directory recorded in `package.json` → `config.theme` |
| Included | Media, Config Split and a publishing workflow with scheduling |
| Deployment | `composer build-assets`, then Drush deploy |
| CI | Repository configuration; see [Deployment](docs/DEPLOYMENT.md#set-up-site-ci) |
| Hosting | Record the platform and environment URLs here |

## Dependencies and Requirements

- [DDEV](https://ddev.com/), Docker and Git
- [Node.js from `.nvmrc`](https://github.com/nvm-sh/nvm)
- [PHP 8.4](https://www.php.net/) and [Composer 2](https://getcomposer.org/)

## Local development

After `composer create-project` completes, open the local URL shown in the installer summary. Run `ddev start` from the project root if the site is stopped.

For a clone of an existing site or a database refresh, see [Local Setup](docs/SETUP.md). For theme development, see [the theme build commands](docs/BUILD_TOOLS.md#theme).

## Documentation

- [Local Setup](docs/SETUP.md)
- [Contribution Guidelines](docs/CONTRIBUTING.md)
- [Build Tools](docs/BUILD_TOOLS.md)
- [Deployment and Releases](docs/DEPLOYMENT.md)
- [Features and Configurations](docs/features.md)
- [Emulsify](https://www.emulsify.info/docs)

Release notes live on this repository's Releases page.

## Known Issues

Record project-specific limitations and their issue links here.

[GPL-2.0-or-later](LICENSE); upstream packages retain their own licenses and notices.
