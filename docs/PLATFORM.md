# Platform requirements

Use DDEV for local development. Creating a project requires Docker, DDEV, PHP
satisfying `^8.4` and Composer 2.x on your computer. Follow [Setup](SETUP.md).
DDEV supplies PHP, Composer, Node.js, npm, Git and MariaDB for project commands.

## Versions and their source

Use the project files below when changing versions. A generated site's lockfiles
record the exact package versions installed there.

| Component | Requirement | Source |
| --- | --- | --- |
| Drupal | `^11.4.11` | `composer.json` |
| PHP | 8.4 in DDEV; `^8.4` for Composer | `.ddev/config.yaml`, `composer.json` |
| Composer | 2.x | `.ddev/config.yaml` |
| Drush | `^13.8.0` | `composer.json` |
| DDEV | `>=1.25.2 <2` | `.ddev/config.yaml` |
| MariaDB | 10.11 | `.ddev/config.yaml` |
| Node.js | 24.x | `.nvmrc`; DDEV and npm engines use the same major |
| npm | Bundled with Node.js | Node.js installation |
| Sous packages | `^2.0` | `composer.json` and the selected packages' manifests |
| Emulsify / Emulsify Tools | `^7.3.0` / `^2.2.2` | Sous Emulsify package manifest |
| Emulsify CLI | `^2.4.1` | Root `package.json` and lockfile |
| Emulsify UI Kit | 1.9.0, Drupal variant | `devops/scripts/theme-setup.sh` |

Generated themes keep their own npm manifest and lockfile. See
[Build tools](BUILD_TOOLS.md) for theme dependencies, builds and overrides.
Use `ddev npm ci` when a lockfile exists. Keep root and theme lockfiles committed.

Run application platform checks in DDEV:

```sh
ddev composer check-platform-reqs
```

Use DDEV's npm for local commands; `.nvmrc` sets the Node major. Generated-site CI
installs current DDEV; `.ddev/config.yaml` limits the supported range. Choose
hosting, credentials and deployment tools for your site as described in
[Deployment](DEPLOYMENT.md).

The starter and Sous packages use GPL-2.0-or-later. Retain their license and
copyright notices, including notices supplied by dependencies.
