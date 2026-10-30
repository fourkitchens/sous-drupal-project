# Maintain Sous

Start with [AGENTS.md](../../AGENTS.md) for the standing coding brief.
Site teams use [setup](../SETUP.md), [development](../DEVELOPMENT.md) and
[deployment](../DEPLOYMENT.md); keep generated sites separate from source checkouts.

## Change the right source

Change the Composer template, settings scaffold, setup scripts, CI templates and
site guides here. Change reusable recipes under `packages/` in
[sous-drupal-packages](https://github.com/fourkitchens/sous-drupal-packages).
Generated sites own custom code, complete configuration exports and deployment.
Keep the starter's `config/default` empty except for `.gitkeep`.
Recipes omit site UUIDs; complete site exports retain them.

Keep optional content compositions independent. Admin owns roles; Emulsify owns
theme defaults. Preserve existing shared Linkit profile customizations.
Recipes are applied once and unpacked; publishing a package does not replay its
defaults on existing sites. PRs explain fresh installs and existing-site adoption
separately. Test upgrades against a database copy and complete export.

## Verify changes

Use Node from `.nvmrc`, PHP 8.4, Composer, Docker, DDEV, Git, Bash, jq, curl,
ShellCheck and actionlint. Run from the repository root:

```sh
npm ci
npm run lint
find devops -name '*.sh' -print0 | xargs -0 shellcheck -x -P SCRIPTDIR
composer validate --no-check-publish
actionlint .github/workflows/ci.yml
actionlint assets/ci/github/.github/workflows/build.yml
```

Commit candidates before smoke testing: the runner exports `HEAD` with
`git archive`, registers that template as version `8.0.0` in a Composer path
repository and runs the real host `composer create-project`.
Run `bash devops/integration/run.sh --help` for options, checks and cleanup details.

```sh
bash devops/integration/run.sh --ci github --artifacts /tmp/sous-default
bash devops/integration/run.sh --ci gitlab --option layout-builder
bash devops/integration/run.sh --ci circleci --option paragraphs --recipes sous-config-split
bash devops/integration/run.sh --ci none --option admin-only --recipes sous-media
```

`--ci` accepts `github` (the default), `gitlab`, `circleci` and `none`.
`--recipes` takes comma-separated short names and defaults to empty. The runner
always sets `SOUS_RECIPES`, so smoke tests never fetch the recipe menu. Append
`--packages /path/to/sous-drupal-packages` for an unpublished package checkout;
omit it to test the published Composer index. A package checkout uses absolute
`PATH/packages/*`, version `2.0.0`, copied packages and
a read-only DDEV mount at the same path. Test published ZIPs before releasing.

Each smoke checks bootstrap, HTTP 200, composition, selected CI files, deployment
and empty config status. CI template sources must be absent from generated sites.
The default also runs `composer ci`, backs up and restores its database. Logs and
`result.json` default to `.integration-results`; `--artifacts DIR` changes that.
`--keep` retains the site; afterward run `ddev delete -Oy PROJECT_NAME` and remove
its temporary workspace. Otherwise cleanup runs on exit, including failures.

`.gitattributes` excludes product workflows, integration scripts and maintainer
guides from archives; preserve `devops/tests/.gitkeep` for site-owned tests.
Generated sites choose their own [CI and deployment](../DEPLOYMENT.md).

## CI and releases

PRs test default content with GitHub Actions. Main pushes, nightly runs at 06:17 UTC
and manual runs add Layout Builder with GitLab, Paragraphs with CircleCI and
`sous-config-split`, and admin-only with no CI plus `sous-media`. The last pairing
exercises a building-block recipe on its own. Each matrix leg supplies its
content, CI provider and recipes as data. Manual `packages_ref` selects an
explicit packages commit; CI clones it and passes `--packages`.
Actions stay pinned to SHAs. Checks have read access; publishing alone has write
access. Require source checks and smoke jobs in branch protection.

Publish compatible packages first, including their ZIPs and Composer index.
Then test every starter option against the published index without a path
override before merging the starter. Keep released tags, ZIPs and index entries
immutable. Replace test path dependencies with published distributions before
committing production locks; `composer update --lock` alone does not do that.

Use Conventional Commits: `fix:`/`perf:` produce patches, `feat:` produces a
minor, and `!` or `BREAKING CHANGE:` produces a major. Preserve that meaning in
squash commits. Nonbreaking documentation, CI and refactor commits do not release.
Git tags determine versions and have no `v` prefix; npm's version is tooling
metadata. `release.config.js` defines channels, but CI publishes only canonical
`main` pushes after all checks pass and without a package override.
`npm run release` publishes the Git tag and GitHub release.

Preview committed history on a configured release branch with remote credentials:

```sh
npm run release -- --dry-run
```

Before releases, review Drupal advisories, root/theme audits, dependency majors,
platform support and upstream patches. Remove a patch once its upstream fix is
released and tested. Review the abandoned installer extender and Paragraphs
Collapsible support before the next major. Keep current Emulsify initialization
and overrides until published upstream fixes pass fresh install, lint and builds.
Track unresolved findings and adoption steps in issues, PRs and release notes.
