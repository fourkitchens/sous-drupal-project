# Maintain Sous

Read [AGENTS.md](../../AGENTS.md) for coding rules. Site teams use
[Setup](../SETUP.md), [Development](../DEVELOPMENT.md) and
[Deployment](../DEPLOYMENT.md); keep source checkouts and generated sites separate.

## Change the right source

Edit the template, settings scaffold, scripts, CI templates and site guides here.
Reusable recipes live in [sous-drupal-packages](https://github.com/fourkitchens/sous-drupal-packages).
Generated sites own custom source, complete configuration and deployment.
Keep `config/default` empty except `.gitkeep`; recipe config omits site UUIDs.

Admin owns roles and Formats owns Linkit defaults. Preserve site customization.
Recipes apply once and unpack; updates do not replay configuration. Describe
fresh installs and existing-site adoption separately; test upgrades on a copy.

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

Commit before smoke testing: the runner exports `HEAD` with `git archive`, uses
that template as `8.0.0`, and runs the real Composer project installer.

```sh
bash devops/integration/run.sh --ci github --artifacts /tmp/sous-default
```

Append `--packages /path/to/sous-drupal-packages` for unpublished recipes;
it uses copied `PATH/packages/*` at `2.0.0` with a read-only DDEV mount at the
same path. Omit it to verify published ZIPs before release.
`--ci` selects a provider; `--recipes` takes comma-separated short names.
The runner presets `SOUS_RECIPES`, bypassing menu fetches.

For the interactive menu, leave `SOUS_RECIPES` unset. It offers the newest
matching-major packages with `extra.sous.menu: true`, even if already included.
`SOUS_INDEX` defaults to `https://fourkitchens.github.io/sous-drupal-packages/packages.json`;
point it at a local packages build to inspect unpublished metadata:

```sh
SOUS_INDEX=/path/to/sous-drupal-packages/build/site/packages.json \
  composer create-project fourkitchens/sous-drupal-project:^8.0 my-site
```

The smoke checks host settings, bootstrap, HTTP, recipes, generated files,
deployment and config status. Default also runs host `composer ci`, backup,
`npm run setup` and `npm run rebuild`. Logs and `result.json` stay in the artifacts
folder. `--keep` retains the site; `--help` lists checks and cleanup commands.
Preserve archive exclusions for product workflows, integration and maintainer
files, plus `devops/tests/.gitkeep` for site-owned tests.

## CI and releases

PRs run default content with GitHub CI. Main pushes, nightly runs at 06:17 UTC
and manual runs also test Layout Builder/GitLab, Paragraphs/CircleCI with
`sous-config-split`, and admin-only/no CI with `sous-media`. Each leg is matrix
data. Manual `packages_ref` selects a full packages commit and passes `--packages`.
Keep action SHAs pinned; checks read only, publishing writes after checks pass.

Publish packages first, then test starter options against their published index.
Keep release tags, ZIPs and index metadata immutable. Remove path dependencies
before committing production locks; `composer update --lock` does not do this.

Conventional Commits select versions: `fix:`/`perf:` patch, `feat:` minor, `!` or
`BREAKING CHANGE:` major. Nonbreaking docs, CI and refactors do not release.
Site releases use `main` and `v` tags; Sous overrides this with
`npm run release -- --tag-format '${version}'` for unprefixed `7.0.1` → `8.0.0`.
Only canonical `main` publishes after checks and without a package override.
Release notes live on [GitHub Releases](https://github.com/fourkitchens/sous-drupal-project/releases).

```sh
GITHUB_TOKEN='<token>' npm run release:dry-run -- \
  --tag-format '${version}' --branches codex/release-starter-8.x
```

Before release, review Drupal advisories, root/theme audits, compatibility and
upstream fixes. Remove patches/workarounds once released fixes pass fresh lint
and builds; record outstanding adoption work in issues, PRs and release notes.
