[![Sous CI](https://github.com/fourkitchens/sous-drupal-project/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/fourkitchens/sous-drupal-project/actions/workflows/ci.yml)

# Sous Drupal starter

Create a Drupal site with Gin administration, an Emulsify theme and Page and
Frontpage content types. Choose Layout Builder, Paragraphs or a basic Body field,
or install administration tools without the content types. Config Split is a
separate option.

This repository provides the application template and setup commands.
[Sous Drupal Packages](https://github.com/fourkitchens/sous-drupal-packages)
provides the recipes. Each site keeps its own configuration, custom code,
lockfiles and deployment process.

## Create and develop a site

Start with [setup](docs/SETUP.md), then use these guides:

- [Features and installation choices](docs/FEATURES.md)
- [Platform requirements](docs/PLATFORM.md)
- [Build tools and theme commands](docs/BUILD_TOOLS.md)
- [Local settings and Config Split](docs/SETUP.md#local-and-host-settings)
- [Site updates and configuration](docs/MAINTENANCE.md)
- [Site contribution workflow](docs/CONTRIBUTING.md)
- [Deployment and site CI](docs/DEPLOYMENT.md)

After setup, build or watch theme assets with:

```sh
ddev npm run theme-build
ddev npm run theme-watch
```

## Work on Sous itself

Use a source checkout to change the starter or recipes. Keep it separate from
client sites. Commit candidate changes, then run a smoke install with host PHP,
Composer, Docker and DDEV available:

```sh
bash devops/integration/run.sh --option default
bash devops/integration/run.sh --option paragraphs --config-split \
  --packages /path/to/sous-drupal-packages
```

- [Source changes and package responsibilities](docs/maintainers/MAINTENANCE.md)
- [Source checks and installation smoke tests](docs/maintainers/INTEGRATION.md)
- [GitHub Actions configuration](docs/maintainers/CI.md)
- [Package and starter releases](docs/maintainers/RELEASES.md)
- [Dependency reviews and support](docs/maintainers/OPERATIONS.md)

`.gitattributes` export-ignore rules omit maintainer guides, product workflows,
integration scripts and upstream tests from release archives.

## License

The scaffold uses [GPL-2.0-or-later](LICENSE). Dependencies retain their own notices.
