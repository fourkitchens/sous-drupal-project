# PROJECT_NAME

Content management system built on Drupal. Record project URLs, contacts and access requirements here.

## At A Glance

| Item | Details |
| --- | --- |
| Local Environment | [DDEV](https://ddev.com/) |
| Drupal / PHP | Drupal 11.4 / PHP 8.4 |
| Node.js | Version in `.nvmrc`, installed on your computer |
| Theme | Emulsify; directory recorded in `package.json` → `config.theme` |
| Deployment | `composer build-assets`, then Drush deploy |
| CI | Repository configuration; see [Deployment](docs/DEPLOYMENT.md#set-up-site-ci) |
| Hosting | Record the platform and environment URLs here |

## Dependencies and Requirements

- [DDEV](https://ddev.com/), Docker and Git
- [Node.js from `.nvmrc`](https://github.com/nvm-sh/nvm)
- [PHP 8.4](https://www.php.net/) and [Composer 2](https://getcomposer.org/)

```sh
npm run setup -- reference/site-db.sql.gz
```

Use `npm run rebuild` after switching branches and `npm run theme-watch` for theme development.
Local setup and database resets use DDEV; shared Drupal commands also run in CI and on hosts.

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
