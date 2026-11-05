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
It copies `PATH/packages/*` at version `2.0.0`, mounts that path read-only in DDEV, and supplies a matching index for preflight.
Omit the override to verify published ZIPs before release.
Use `--library ui-kit` or `--library URL`, optionally with `--library-ref REF`, to verify library installation and compilation.
The runner checks settings, bootstrap, HTTP, recipes, publishing workflows, scheduling widgets, generated guidance, site archives and deployment.
Admin-only also runs host `composer ci`, backup, `npm run setup` and `npm run rebuild`. The no-library admin smoke builds a project-owned asset after checking CI on the empty scaffold.
Logs and `result.json` stay in the artifacts directory; `--help` covers retention and cleanup.

## Installer choices

The product [README](../../README.md#installation) documents content, recipe, CI and dessert choices. Preset `SOUS_CONTENT`, `SOUS_RECIPES`, `SOUS_CI` and `SOUS_LIBRARY` for unattended installs.
Media, Config Split and the publishing workflow apply to every course; Paragraph Types belongs only to Paragraphs.
Every install checks the index for the baseline and main-course recipes before prompting.
The runner sets `SOUS_RECIPES` to skip the sides prompt.
To inspect it interactively, leave that variable unset; the newest matching-major
metadata offers optional packages with `extra.sous.menu: true`.
`SOUS_INDEX` is a maintainer override for the preflight and menu. It defaults to
`https://fourkitchens.github.io/sous-drupal-packages/packages.json`. To inspect
unpublished metadata, point it at a local build.

```sh
SOUS_INDEX=/path/to/sous-drupal-packages/build/site/packages.json \
  composer create-project fourkitchens/sous-drupal-project:^8.0 my-site
```

Successful installation retires starter-only code and metadata; shared site guides contain no starter branding.
Composer unpacks recipe dependencies during `require`. Keep recipe sources through application and integrations, then retire the applied sources, the setup repository and the creation hook. The generated lockfile must install without the setup repository.
The theme builds when a library is selected; `SOUS_LIBRARY=none` keeps the scaffold only. `SOUS_LIBRARY_REF` selects a branch/tag; UI Kit temporarily uses the [upstream lint-fix branch](https://github.com/emulsify-ds/emulsify-ui-kit/pull/189) until a released version passes lint and build.
Keep product CI, maintainer docs and the starter's agent brief scoped to the starter.
The installer creates `AGENTS.md` and `docs/agents/workflow-facts.md` from `assets/installer/`, then retires the inputs.

## CI and releases

PRs run admin-only with GitHub CI. Main pushes, nightly runs at 06:17 UTC
and manual runs add Events, News Post and Profile to Layout Builder/GitLab, Paragraphs/CircleCI and admin-only/no CI. Layout Builder/GitLab and admin-only/no CI add Alerts and Search. Every row includes SEO and Dev Tools. Layout Builder selects UI Kit by name. Paragraphs selects the same library by Git URL and ref.
Manual `packages_ref` selects a full reviewed packages commit.
Keep action SHAs pinned; checks read only, publishing writes after checks pass.

Release notes live on [GitHub Releases](https://github.com/fourkitchens/sous-drupal-project/releases).

Conventional Commits select versions: `fix:`/`perf:` patch, `feat:` minor, `!` or
`BREAKING CHANGE:` major. Nonbreaking docs, CI and refactors do not release.
Sites release `main` with `v` tags; Sous keeps unprefixed tags with its workflow override:

```sh
GITHUB_TOKEN='<token>' npm run release:dry-run -- \
  --tag-format '${version}' --branches codex/release-starter-8.x
```

Only canonical `main` publishes after checks and without a packages override.

## Sous 8.0.0 release checklist

Use [starter PR #132](https://github.com/fourkitchens/sous-drupal-project/pull/132) for the current release summary, reviewer instructions and CI evidence. Use [packages PR #2](https://github.com/fourkitchens/sous-drupal-packages/pull/2) to confirm the companion revision and publication status.

- [ ] Run the [source checks](#verify-changes) on the final starter candidate. Parse all three generated provider YAML templates. Record any validator that was unavailable.
- [ ] Publish the companion `2.0.0` release, recipe ZIPs, and [public Composer index](https://fourkitchens.github.io/sous-drupal-packages/packages.json) first. Verify the index responds successfully and lists the `2.0.0` metadata and downloadable ZIPs for the baseline, composition, and optional recipes. Keep the published tags, archives, and index entries immutable.
- [ ] Run the final starter commit against the public index with the matrix below. Leave `--packages` off and omit the workflow's `packages_ref`. For a 404, run the same matrix with `--packages` pointing to the reviewed companion checkout. Keep the public matrix open as a release blocker until it passes.
- [ ] Check the empty scaffold, its project-owned asset build, and the installed library builds. Retain the runner results for retirement of recipe sources and setup metadata, the lockfile reinstall, and the clean deploy. Review the generated npm lockfiles as described below.
- [ ] Record a build job URL and tested revision for each generated provider pipeline. Record local template checks and shared build commands separately.
- [ ] Prepare the 7.x adoption notes. After the release gates pass, squash-merge PR #132 with its breaking `feat!:` title. Verify that canonical `main` tags `8.0.0` without a `v` prefix and that Packagist lists it.

### Final installation matrix

Run these from the starter root with the final candidate checked out. For local fixture coverage, append `--packages "$packages"` to each command, with `packages` set to the absolute path of the reviewed companion checkout. Keep each row's artifacts separate.

```sh
sides=sous-events,sous-news-post,sous-profile
ui_kit=https://github.com/emulsify-ds/emulsify-ui-kit.git
ui_kit_ref=codex/fix-drupal-theme-lint

bash devops/integration/run.sh --option admin-only --ci github \
  --library none --artifacts /tmp/sous-8-admin
bash devops/integration/run.sh --option layout-builder --ci gitlab \
  --recipes "$sides,sous-alerts,sous-search" --library ui-kit --artifacts /tmp/sous-8-layout
bash devops/integration/run.sh --option paragraphs --ci circleci \
  --recipes "$sides" --library "$ui_kit" --library-ref "$ui_kit_ref" \
  --artifacts /tmp/sous-8-paragraphs
bash devops/integration/run.sh --option admin-only --ci none \
  --recipes "$sides,sous-alerts,sous-search" --library none --artifacts /tmp/sous-8-optional
```

The Admin-only baseline runs `composer ci` with an empty scaffold and then builds a disposable project-owned Sass entrypoint. All rows verify Dev Tools in the local split. Layout Builder tests named UI Kit. Paragraphs tests the custom Git URL and ref route with the same UI Kit repository. Keep the named and URL cases until a distinct, compatible fixture is reviewed.

### Provider evidence

| Check | Evidence and release gate |
| --- | --- |
| Provider templates | Parse all three YAML templates and verify each generated checkout contains the selected provider's files. Starter fixture [run 41955643729](https://github.com/fourkitchens/sous-drupal-project/actions/runs/41955643729) passed all four rows at starter `7a93ac9` with `packages_ref=cd41bf2`. |
| Shared build | The local runner exercises `composer ci` for Admin-only and selected-library rows. This verifies the shared command with the generated dependencies and assets. |
| Starter GitHub Actions | See the [PR #132 test evidence](https://github.com/fourkitchens/sous-drupal-project/pull/132#functional-testing) for the latest PR run. Its Admin-only/GitHub row uses the public index. Rerun the full public matrix after packages publish. |
| Generated GitHub Actions | Run `Site build` in a configured generated repository and record the job URL and revision. Available GitHub evidence is from the starter's workflow. |
| Generated GitLab CI/CD | Run the generated `build` job in a configured GitLab project and record the pipeline URL and revision. Project access was unavailable during this check. |
| Generated CircleCI | Run the generated `build` job in a configured CircleCI project and record the pipeline URL and revision. Project access was unavailable during this check. |

Use a test branch or pull request for provider build checks. Provider runner provisioning, caches, and release jobs require evidence from the actual provider.

### Generated dependency review

Run `npm audit --json` from each generated site root and from its custom theme directory. Keep the audit JSON and the matching lockfiles in the verification artifacts. Use `npm explain <package>` to trace each high and critical result through the lockfile. For each path, record where it executes in release CI, developer tooling, or generated assets. Review the actual build output or bundle inputs before classifying an advisory as shipped. Recheck the locks from the published matrix and resolve or record acceptance of remaining high and critical paths before release.

Before these fixes, all four November 4, 2026 fixture rows reported the same high and critical paths, including the two UI Kit installations. npm counts affected packages, including parents that inherit a dependency's severity.

| Lockfile | High / critical | Decision and evidence |
| --- | --- | --- |
| Generated project root | 0 / 0 | Fixed November 4, 2026. The targeted lock refresh keeps `semantic-release@25.0.9 → @semantic-release/npm@13.2.0` and updates bundled npm to `11.21.2`. Its `node-gyp@12.4.0` now uses `undici@6.29.1` in place of `6.29.0`. `npm audit --audit-level=high` passed with 38 moderate findings remaining. [npm 11.21.2 release](https://github.com/npm/cli/releases/tag/v11.21.2) · [Undici advisory](https://github.com/nodejs/undici/security/advisories/GHSA-2m5v-pch6-74hx) |
| Generated custom theme | 0 / 0 | Fixed November 4, 2026. The generated theme override changes `@emulsify/core@4.5.2 → @emulsify/cli@2.4.1` to resolve `simple-git@4.0.2` in place of `3.36.0`. The generated UI Kit theme passed `npm audit --audit-level=high` with one low and 29 moderate findings remaining. The GitHub-provider smoke test passed with UI Kit installed, and separate runs of `npm run theme-build` and theme lint passed. [Git option abbreviations advisory](https://github.com/advisories/GHSA-858h-whjf-mvg5) |

Semantic-release 25.0.9 replaces its default plugin list with the configured list and imports only the selected plugins. Running its loader against the GitHub and GitLab configs loaded the commit analyzer, release notes generator and the corresponding publisher. The npm plugin stays installed as a dependency of semantic-release. Nested npm scripts can still reach its bundled CLI via `node_modules/.bin/npm`, so the lock refresh also covers that tooling path. [Config loader](https://github.com/semantic-release/semantic-release/blob/v25.0.9/lib/get-config.js#L62-L92) · [Plugin loader](https://github.com/semantic-release/semantic-release/blob/v25.0.9/lib/plugins/index.js#L13-L28)

The installer runs the root CLI with its override and copies that rule into the generated theme before its first npm install. Site deployment ships compiled theme assets and omits `node_modules`. The upstream CLI fix has merged, and npm still served `@emulsify/cli@2.4.1` on November 4, 2026. Remove the root override and the installer’s theme override once npm publishes `@emulsify/cli@2.4.2` or later and `@emulsify/core` depends on a fixed version. Re-run both audits after removing them. [Emulsify CLI fix](https://github.com/emulsify-ds/emulsify-cli/pull/377)

### Recorded fixture verification

On November 4, 2026, the runner exported starter commit `513495a43d59c0b562132defa592e4f26a63d71a` and used packages commit `432da88e8feb76b1604ae2a73889a2da2fc01733`. All four installations passed their clean lockfile reinstall and configuration deploy after the fixture mount was removed. The lockfiles stayed byte-identical, and Drush reported an empty configuration diff.

| Matrix row | Verified evidence |
| --- | --- |
| Admin only with GitHub template | Baseline dependencies and splits, empty scaffold `composer ci`, project-owned Sass output, backup, restore, and rebuild. |
| Layout Builder with GitLab template | Named UI Kit installed and compiled, optional recipe integrations, and shared `composer ci`. |
| Paragraphs with CircleCI template | UI Kit installed and compiled through its custom Git URL and ref, optional recipe integrations, shared `composer ci`, and retained MIT attribution. |
| Admin only with Dev Tools and no CI template | Opt-in dependencies, local-only module activation and split exports, shared `composer ci`, and project-owned Sass output. |

Source checks passed with Node from `.nvmrc`, Composer validation, ShellCheck 0.11.0, and YAML parsing of all three provider templates. `actionlint` was unavailable locally. The macOS fixture runs used disposable DDEV mount settings and stopped the test site before rebuild. The public Composer index still returned HTTP 404, which blocks the published installation matrix.

### Adopting 8.0 from 7.0.1

Sous 8.0 is for new sites. Existing 7.x sites adopt changes through site-owned updates and migrations. Starter fixes for 7.x go to [`sous-7.x`](https://github.com/fourkitchens/sous-drupal-project/tree/sous-7.x).

| 7.0.1 main course | 8.0 equivalent |
| --- | --- |
| `0` Default Sous with Media and basic content types | Choose the content composition explicitly. `0` now selects Admin only. |
| `1` Layout Builder | `layout-builder` (`2`) |
| `2` Paragraphs | `paragraphs` (`1`) |
| `3` Just the admin | `admin-only` (`0`) |

Use named choices for automation. Optional menu numbers follow package metadata and can change as packages are added.

```sh
SOUS_CONTENT=paragraphs SOUS_RECIPES=sous-events SOUS_CI=github SOUS_LIBRARY=none \
  composer create-project fourkitchens/sous-drupal-project:^8.0 my-site
```

- Fresh installs use `composer create-project`. Use `npm run setup -- 'reference/site-db.sql.gz'` for an existing clone with a backup. Setup builds available theme assets before restoring the database. `npm run rebuild -- 'reference/site-db.sql.gz'` restores and deploys without rebuilding Node assets. `npm run import-data` imports and sanitizes the local database.
- Replace the old DDEV recipe command with the [recipe workflow](../SETUP.md#add-a-recipe). Composer unpacks dependencies when requiring the package, then the shared recipe script applies its source once. Keep the existing site's UUID, export, content, and accounts. Treat content model changes as site migrations.
- Configuration import now runs `drush deploy -y`, including update and deploy hooks, in place of the old manual update and partial/full import sequence. See [Deployment](../DEPLOYMENT.md#build-and-deploy) for host and CI aliases, URI selection, and native tools.
