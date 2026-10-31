# How to Contribute

## Commit messages

Use [Conventional Commits](https://www.conventionalcommits.org/): `fix:` for patches,
`feat:` for features, and `!` or a `BREAKING CHANGE:` footer for incompatible changes.
Documentation and other nonbreaking maintenance commits do not publish a release.

## Git workflow

Create a focused feature branch from `main` and open a pull request before merging.
Keep lockfiles, custom source and the complete configuration export together.

## Pull requests

Describe the problem, resulting behavior, testing and deployment steps in the repository's review template.
Review editing, permissions, keyboard behavior and accessibility on affected pages.
Coordinate backups and rollback before database or content migrations.

## Code standards

Follow [Drupal coding standards](https://project.pages.drupalcode.org/coding_standards/).
Run `npm run lint`, `ddev composer code-sniff` and the affected theme build before review.
Editors and `ddev composer code-fix` use `phpcs.xml.dist` for PHP-bearing files.
See [Build Tools](BUILD_TOOLS.md#linting) for the commands and [Deployment](DEPLOYMENT.md#releases) for release setup.
