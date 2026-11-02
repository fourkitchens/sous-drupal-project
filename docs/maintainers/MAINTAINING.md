# Maintain Sous

Read [AGENTS.md](../../AGENTS.md) before changing this starter.
Site guides use the published layout: [Local Setup](../SETUP.md), [Build Tools](../BUILD_TOOLS.md),
[Contributing](../CONTRIBUTING.md), [Deployment](../DEPLOYMENT.md) and [Features](../features.md).
Shared guides and the generated README must remain project-generic.

## Change the right source

Edit the template, installer, settings scaffold, CI templates and site guides here.
Reusable recipes live in [sous-drupal-packages](https://github.com/fourkitchens/sous-drupal-packages).
The starter's `patches/` mirrors `sous-drupal-packages/patches`; update both in the same release.
Generated sites own custom source, dependencies, complete configuration and deployment.
Keep `config/default` empty except `.gitkeep`; recipe config omits site UUIDs.
Admin owns roles and Formats owns Linkit defaults; preserve existing project customization.

## Verify changes

Use Node from `.nvmrc`, PHP 8.4, Composer, Docker, DDEV, Git, Bash, jq, curl,
ShellCheck 0.11.0 (`pipx install shellcheck-py==0.11.0.1`) and actionlint.

```sh
npm ci
npm run lint
find devops -name '*.sh' -print0 | xargs -0 shellcheck -x -P SCRIPTDIR
composer validate --no-check-publish
actionlint .github/workflows/ci.yml
actionlint assets/ci/github/.github/workflows/build.yml
```

Commit before the local admin-only smoke; it exports `HEAD` and runs the real Composer installer:

```sh
bash devops/integration/run.sh --ci github --artifacts /tmp/sous-admin
```

Append `--packages /path/to/sous-drupal-packages` for unpublished recipes.
It copies `PATH/packages/*` at version `2.0.0` and mounts that path read-only in DDEV.
Omit the override to verify published ZIPs before release.
Use `--library ui-kit` or `--library URL`, optionally with `--library-ref REF`, to verify library installation and compilation.
The runner checks settings, bootstrap, HTTP, recipes, publishing workflows, scheduling widgets, generated files and deployment.
Admin-only also runs host `composer ci`, backup, `npm run setup` and `npm run rebuild`.
Logs and `result.json` stay in the artifacts directory; `--help` covers retention and cleanup.

## Installer choices

The product [README](../../README.md#installation) documents content, recipe, CI and dessert choices. Preset `SOUS_CONTENT`, `SOUS_RECIPES`, `SOUS_CI` and `SOUS_LIBRARY` for unattended installs.
Media, Config Split and the publishing workflow apply to every course; Paragraph Types belongs only to Paragraphs.
The runner sets `SOUS_RECIPES`, so smoke tests never fetch the menu.
To inspect it interactively, leave that variable unset; the newest matching-major
metadata offers packages with `extra.sous.menu: true`; currently Events, News Post and Profile.
`SOUS_INDEX` defaults to `https://fourkitchens.github.io/sous-drupal-packages/packages.json`;
point it at a local build to inspect unpublished metadata:

```sh
SOUS_INDEX=/path/to/sous-drupal-packages/build/site/packages.json \
  composer create-project fourkitchens/sous-drupal-project:^8.0 my-site
```

Successful installation retires starter-only code and metadata; shared site guides contain no starter branding.
The theme builds when a library is selected; `SOUS_LIBRARY=none` keeps the scaffold only. `SOUS_LIBRARY_REF` selects a branch/tag; UI Kit temporarily uses the [upstream lint-fix branch](https://github.com/emulsify-ds/emulsify-ui-kit/pull/189) until a released version passes lint and build.
Keep product CI, maintainer docs and agent rules out of generated sites.

## CI and releases

PRs run admin-only with GitHub CI. Main pushes, nightly runs at 06:17 UTC
and manual runs add Events, News Post and Profile to Layout Builder/GitLab, Paragraphs/CircleCI and admin-only/no CI. Layout Builder selects UI Kit; Paragraphs tests a custom Git URL.
Manual `packages_ref` selects a full reviewed packages commit.
Keep action SHAs pinned; checks read only, publishing writes after checks pass.

Publish packages first, then verify all starter options against the published index.
Keep tags, ZIPs and index metadata immutable; resolve production locks without local path repositories.
Recipe updates do not replay configuration; describe fresh installs and existing-site adoption separately.
Release notes live on [GitHub Releases](https://github.com/fourkitchens/sous-drupal-project/releases).

Conventional Commits select versions: `fix:`/`perf:` patch, `feat:` minor, `!` or
`BREAKING CHANGE:` major. Nonbreaking docs, CI and refactors do not release.
Sites release `main` with `v` tags; Sous keeps unprefixed tags with its workflow override:

```sh
GITHUB_TOKEN='<token>' npm run release:dry-run -- \
  --tag-format '${version}' --branches codex/release-starter-8.x
```

Only canonical `main` publishes after checks and without a packages override.
Review advisories, dependency audits, compatibility and upstream fixes before release.
Remove workarounds once published fixes pass fresh lint and builds; track adoption work in issues and PRs.
