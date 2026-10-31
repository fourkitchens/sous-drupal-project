# Maintain Sous

Read [AGENTS.md](../../AGENTS.md) before changing this starter.
Site guides use the published layout: [Local Setup](../SETUP.md), [Build Tools](../BUILD_TOOLS.md),
[Contributing](../CONTRIBUTING.md), [Deployment](../DEPLOYMENT.md) and [Features](../features.md).
Shared guides and the generated README must remain project-generic.

## Change the right source

Edit the template, installer, settings scaffold, CI templates and site guides here.
Reusable recipes live in [sous-drupal-packages](https://github.com/fourkitchens/sous-drupal-packages).
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

Commit before the local default smoke; it exports `HEAD` and runs the real Composer installer:

```sh
bash devops/integration/run.sh --ci github --artifacts /tmp/sous-default
```

Append `--packages /path/to/sous-drupal-packages` for unpublished recipes.
It copies `PATH/packages/*` at version `2.0.0` and mounts that path read-only in DDEV.
Omit the override to verify published ZIPs before release.
The runner checks settings, bootstrap, HTTP, recipes, generated files, deployment and config status.
Default also runs host `composer ci`, backup, `npm run setup` and `npm run rebuild`.
Check shared Drupal scripts both through local DDEV and with native tools; keep database resets local.
Logs and `result.json` stay in the artifacts directory; `--help` covers retention and cleanup.

## Installer choices

The product [README](../../README.md#installation) documents content, recipe and CI choices.
Preset `SOUS_CONTENT`, `SOUS_RECIPES` and `SOUS_CI` for unattended installs.
The runner sets `SOUS_RECIPES`, so smoke tests never fetch the menu.
To inspect it interactively, leave that variable unset; the newest matching-major
metadata offers packages with `extra.sous.menu: true`, even when already included.
`SOUS_INDEX` defaults to `https://fourkitchens.github.io/sous-drupal-packages/packages.json`;
point it at a local build to inspect unpublished metadata:

```sh
SOUS_INDEX=/path/to/sous-drupal-packages/build/site/packages.json \
  composer create-project fourkitchens/sous-drupal-project:^8.0 my-site
```

Successful installation retires starter-only code and metadata; shared site guides contain no starter branding.
Keep product CI, maintainer docs and agent rules out of generated sites.
Upstream names and license identities stay unchanged.

## CI and releases

PRs run default content with GitHub CI. Main pushes, nightly runs at 06:17 UTC
and manual runs add Layout Builder/GitLab, Paragraphs/CircleCI with `sous-config-split`,
and admin-only/no CI with `sous-media`. Manual `packages_ref` selects a full reviewed packages commit.
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
