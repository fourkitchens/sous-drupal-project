# Release Sous

Publish compatible packages before the starter. The two repositories release
separately; a site installs only the packages it selects.

## Publish packages, then the starter

1. Test package changes with the starter's [smoke runner](INTEGRATION.md), using
   `--packages PATH` when needed, after committing the candidate starter changes.
2. Merge the packages changes to their `main` branch. Follow the package
   [release guide](https://github.com/fourkitchens/sous-drupal-packages/blob/main/docs/RELEASING.md)
   to publish a shared semantic version, individual package ZIPs and the static
   Composer index.
3. Verify the public index and install packages without a Composer cache. Run
   all starter smoke options against the published index, with no path override.
4. Merge the compatible starter changes to `main`. Its CI publishes a Git tag
   and GitHub release after source checks and every smoke job pass.

Keep published tags, ZIPs and index entries available for existing lockfiles.
Do not replace a tag or change the contents of a released ZIP.

## Choose the version with commits

| Commit | Release |
| --- | --- |
| `fix:` or `perf:` | Patch |
| `feat:` | Minor |
| A `!` after the type/scope, or a `BREAKING CHANGE:` footer | Major |
| `docs:`, `ci:`, `build:`, `refactor:`, `test:`, `style:`, `chore:` | None |

Preserve the release meaning in the squash commit. Include upgrade steps for
dependency majors, changed commands and changed configuration defaults.
The npm manifest version is tooling metadata; Git tags determine the release.
Tags have no `v` prefix. The publisher does not create an npm release, changelog
commit or version commit.

`release.config.js` defines release channels and the Conventional Commits preset.
`alpha` and `beta` produce prereleases. `next` and `next-major` use stable
version tags, which Composer can see. The CI publisher currently permits only
`main`; enable another channel in the workflow only as a deliberate release
policy change.

## Preview and check a release

On a configured release branch, with remote credentials available:

```sh
npm ci
npm run release:dry-run
```

The preview analyzes committed history. It does not simulate an uncommitted
change or a future squash merge. Only a push to canonical `main` can publish
through CI; pull requests and manual runs perform checks.

Remove test path repositories from application manifests and resolve published
packages before committing production locks. `composer update --lock` alone
does not turn a locked path package into a ZIP download.

Recipes are applied once during setup and then unpacked, so the site owns its
dependencies and configuration.

Use [product maintenance](MAINTENANCE.md) for package changes and
[site maintenance](../MAINTENANCE.md) for existing-site upgrade instructions.
