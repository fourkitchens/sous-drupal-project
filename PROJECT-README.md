# Drupal application

Use this repository to develop and deploy the site. Record project URLs, team
contacts and access requirements here. Commit custom code, theme source, complete
configuration exports and Composer/npm lockfiles.

## Get started

Follow [setup and database restoration](docs/SETUP.md). DDEV provides PHP,
Composer, Node and npm. See [platform requirements](docs/PLATFORM.md) before
changing runtime versions.

```sh
ddev npm run theme-build
ddev npm run theme-watch
```

[Build tools](docs/BUILD_TOOLS.md) covers dependency installation, theme tasks,
Storybook and lint. Site-specific tests belong in `devops/tests/`.

## Work on the site

- [Contribution workflow](docs/CONTRIBUTING.md)
- [Features and optional packages](docs/FEATURES.md)
- [Local settings and Config Split](docs/SETUP.md#local-and-host-settings)
- [Dependency updates and configuration](docs/MAINTENANCE.md)
- [Deployment and site CI](docs/DEPLOYMENT.md)

Keep the site's complete `config/default` export, including its UUIDs. Package
updates do not apply new recipe configuration automatically. Review and export
site changes before deploying them. Store credentials in environment settings,
not configuration exports.

Choose this site's CI template using the deployment guide. `.gitattributes`
export-ignore rules omit Sous publishing workflows and maintainer guides from
release archives.

## License

The scaffold uses [GPL-2.0-or-later](LICENSE). Dependencies retain their own notices.
