# Sous release maintenance

## Sous 8 release contract

The starter review branch `codex/release-starter-8.x` prepares `8.0.0` from the
latest starter tag `7.0.1`. Its breaking changes are the Drupal `^11.4.11`
baseline, shared Sous `^2.0` ZIP packages with independent composition choices,
explicit `fresh|restore` setup, and project-owned exports/runtime environment
settings. Existing sites retain their database and complete configuration export
and adopt source/configuration changes through the [maintenance workflow](MAINTENANCE.md).

Keep `feat!:` or a `BREAKING CHANGE:` footer in the final merge/squash commit.
Native semantic-release checks cover the `7.0.1` → `8.0.0` decision. Only the
canonical `main` publication job creates starter tags and releases.
Pull requests run focused source checks, while release-branch pushes and manual
runs retain full integration before publication.

## Current two-repository release sequence

The active development repositories are this starter and
[Sous Drupal Packages](https://github.com/fourkitchens/sous-drupal-packages).
Each package retains its Composer name and independent dependency graph. The
collection starts at `2.0.0` with bounded `^2.0` sibling requirements and one
shared version for all package ZIPs. The compatible starter candidate remains
`8.0.0`; preserve the starter's breaking-change semantics when merging.
The packages repository uses a tag-only `1.0.0` history baseline; downloadable
collection releases begin at `2.0.0`.

1. Review the packages changes and merge them to its `main` branch. Its
   semantic-release workflow determines the version from Conventional Commits,
   creates the release and package ZIPs, and publishes the complete static
   Composer index. See the package
   [release guide](https://github.com/fourkitchens/sous-drupal-packages/blob/main/docs/RELEASING.md).
   The first package release is `2.0.0`; later `fix:`/`perf:`, `feat:` and breaking
   changes select patch, minor and major releases respectively.
2. Verify the public index and a cold-cache installation. Historical release
   metadata and ZIP URLs remain available; an application can keep its lock.
3. Pass the full starter acceptance gate against those published packages,
   including generated-theme lint/build, all content choices, optional Config
   Split, environments and clean-clone recovery. An explicit candidate commit
   can be used before package publication for review, but cannot publish a
   starter release. Resolve public ZIPs for the final gate;
   `composer update --lock` does not convert a path distribution to a ZIP.
4. Review and merge the compatible starter changes. Its own semantic-release
   workflow runs after the published-index gate. Existing applications retain
   their database and complete export and adopt configuration changes explicitly.

The static index is canonical only for the exact nine package names, preserving
access to unrelated Four Kitchens packages. Selected recipes remain in root
requirements and locks through exact native recipe-unpack exclusions. Neither
the collection version nor the ignore list installs an unselected package.
Application CI/deployment remains independent of both upstream publishers.

## Historical pre-consolidation review

The remaining review below records the earlier multi-repository release setup.
Its per-repository version proposals, temporary ref maps and rollout state are
historical evidence, superseded by the sequence above; do not follow that old
publication order for the shared package collection.

Later simplification replaced maintainer Python entry points with shell, excluded product integration/tests from generated projects, and removed the custom theme helper/CLI patch. Historical file lists below retain the earlier review scope; source CI still gates publication on the current integration checks.

The [shared integration pipeline](INTEGRATION.md) supersedes the original CI
job implementation described during this release setup: all seven callers now
require product integration before the shared publisher. It also replaces the
offered public Layout Builder package with a bundled core recipe.
The subsequent [ownership and maintenance review](MAINTENANCE.md) moves the
administrator role into Admin, shares Linkit defaults, removes the obsolete
partial template export, and documents explicit existing-site adoption.

Reviewed on 25 October 2026 across the starter, Admin, Content Types, Formats,
Emulsify, Media, and the subsequently added Paragraph Types checkout. The
[Paragraph Types follow-up](PARAGRAPH_TYPES.md) records its additional Drupal
integration review. This work configures future releases;
it has not committed source changes, pushed branches, or published tags/releases.

## Common release setup

Each repository has a private npm manifest and npm lockfile for release
tooling, `.nvmrc`, `release.config.js`, an offline native regression check, and
`.github/workflows/semantic-release.yml`. Use the Node/npm selection in
[Platform](PLATFORM.md).
Run `npm ci --include=dev` and `npm run release:test` in recipe checkouts; the
starter also provides these commands through `ddev npm`.

The selected newest stable direct release packages are:

| Package | Locked release / bounded requirement |
| --- | --- |
| `semantic-release` | 25.0.9 / `^25.0.9` |
| `@semantic-release/commit-analyzer` | 13.0.1 / `^13.0.1` |
| `@semantic-release/release-notes-generator` | 14.1.1 / `^14.1.1` |
| `@semantic-release/github` | 12.0.10 / `^12.0.10` |
| `conventional-changelog-conventionalcommits` | 10.4.1 / `^10.4.1` |

Both analyzer and notes generator explicitly use `conventionalcommits`. The
inherited default Angular analyzer ignored the `feat!:` header syntax. The
latest preset also requires the writer 9 rendering API, while notes generator
14.1.1 declares writer 8. A scoped override upgrades only its
`conventional-changelog-writer` to **`^9.3.0`**. The
[writer 9 migration](https://github.com/conventional-changelog/conventional-changelog/releases/tag/conventional-changelog-writer-v9.0.0)
replaces Handlebars templates with render functions and requires Node 22+;
preset 10 supplies those functions. Native analysis and release-note generation
are exercised together to check this compatibility rather than merely accepting
an npm resolution.

There is one publisher per repository: GitHub Actions. The starter's duplicate
CircleCI source configuration is removed; PHP/Node and integration checks run
in the shared read-only product job. Publishing requires that job to pass and runs only in
the canonical repository on push/manual dispatch. Full Git history, immutable
Actions references, lockfile installation, no persisted checkout credentials,
no package cache, and a serialized release group are configured. Only the
publication job receives `contents: write` and `GITHUB_TOKEN`. GitHub plugin
comments, failure issues, labels, and release links on issues/PRs are disabled,
so it needs no issue or pull-request write permission. No npm publisher is
configured; all npm manifests are private. Versions come from Git tags, not
npm's tooling-only `version` field. No release commit or changelog file is
written back into the source tree.
Publication has a repository-wide job concurrency group because different
release branches share one Git tag namespace; workflow-level groups still
allow separate pull requests to run verification independently.

Actions pins were checked against official releases:
[checkout 7.0.1](https://github.com/actions/checkout/releases/tag/v7.0.1) and
[setup-node 7.1.0](https://github.com/actions/setup-node/releases/tag/v7.1.0).
Hosted `ubuntu-24.04` is used. Self-hosted runners would need the Actions
versions' documented Node 24 runner requirements before adopting these pins.

Recipes release from `main`. The starter preserves its existing `main`, `next`,
`next-major`, `alpha`, `beta`, and numeric maintenance branch configuration;
the workflow now includes those channels. Existing bare tags (`1.0.3`, `7.0.1`)
remain compatible with `tagFormat: '${version}'`. Prerelease tags are valid;
alpha/beta are supported by the starter's configured branches.
`next` and `next-major` are ordinary release channels, so their version tags
are stable tags visible to every Composer consumer. Packagist has no npm
dist-tag isolation. Use the configured alpha/beta prerelease branches when a
tag should remain a prerelease; review this distinction before using the
preserved ordinary channels.

## Commits determine releases

Use `fix:` for patch, `perf:` for patch, and `feat:` for minor. A `!` after the
type/scope or a `BREAKING CHANGE:` footer produces a major release. Ordinary
`chore:`, `ci:`, `build:`, `docs:`, `refactor:`, `style:`, and `test:` commits do
not release. For the first stable release, a release-worthy commit produces
**1.0.0**. A configuration-only `chore:` commit does not bootstrap a first tag.
For squash merges the final commit title/body must retain this information.

`npm run release:dry-run` is the normal authenticated native preview on a
configured release branch. It analyzes committed history; uncommitted changes
and a feature branch outside `branches` do not preview a future squash merge.
Dry runs can still verify remote credentials. The offline regression command
instead uses disposable local Git repositories, file-only remotes, analyzer and
notes hooks, and verifies that no tags change. It does not invoke GitHub API
verification or publishing hooks.

Manual CI dispatches may pass `recipe_refs` JSON to validate companion feature
or PR revisions. Those runs are validation-only: both the caller and shared
publisher skip release unless the input is absent, empty, or exactly `{}`.
Ordinary pushes retain their normal release path. Validate the default refs
after companion changes merge before publishing.

## Current changes and coordinated releases

Fresh remote fetch/`ls-remote` verification found these tags. Proposed versions
below require merging the existing implementation work with the indicated
commit semantics; they are not releases claimed to exist.

| Repository | Current public tag | Proposed next tag | Proposed squash title |
| --- | --- | --- | --- |
| `sous-formats` | None | 1.0.0 | `feat: release corrected Sous text formats` |
| `sous-media` | None | 1.0.0 | `feat: release self-contained Sous media recipe` |
| `sous-paragraph-types` | None | 1.0.0 | `feat: release portable Sous Paragraph Types` |
| `sous-admin` | None | 1.0.0 | `feat!: own Sous authorization and consume shared Linkit defaults` |
| `sous-emulsify` | None | 1.0.0 | `feat!: keep Sous theme configuration separate from authorization` |
| `sous-content-types` | 1.0.3 | 2.0.0 | `feat!: provide usable Pages on the updated Drupal baseline` |
| `sous-drupal-project` | 7.0.1 | 8.0.0 | `feat!: harden installation and modernize the Sous toolchain` |

Content Types needs a major release because its upgraded Tagify dependency
requires Drupal 11.1+ and drops the older supported core lines. Its Page Body
model and composition compatibility are documented in the recipe README. The
starter changes the setup interface to explicit `fresh`/`restore` modes,
requires the reviewed Node/npm and PHP versions, and changes frontend build
contracts. Carry a breaking-change footer describing those migrations even
when using the `!` header; the generated notes will then explain the upgrade.

Release Formats/Media before Admin, Content Types, and Paragraph Types consumers, and release
Emulsify before the starter. Once tags actually exist and Packagist has indexed
them, update current explicit `dev-main` recipe requirements to bounded stable
caret ranges in a reviewed follow-up. Update the optional installer to Content
Types `^2.0` at that point. Re-resolve and validate the integrated starter before
its 8.0.0 release. Do not point production manifests at fictional future tags.
The incompatible public Sous Layout Builder package is no longer selected.
The owned core recipe supports visible Page fields and per-Page overrides;
the Paragraphs option remains separate. Default, Paragraphs, and Layout Builder
all select the repaired Content Types branch until its stable release exists.
The Admin/Formats shared Linkit subrecipe is released from the Formats package;
Admin requires that package without applying the full editor recipe. Record
the theme's role-ownership removal and the shared subrecipe's documented
installation layout as compatibility changes. Initial untagged recipe releases
still start at 1.0.0; a breaking header does not invent an earlier stable tag.
Existing roles and complete site exports remain owned by their applications.

Generated sites keep private npm tooling, but bootstrap removes the source npm
`repository` value. Native semantic-release discovery then uses the site's own
Git origin. The copied publisher is guarded to `fourkitchens/sous-drupal-project`
and stays inactive elsewhere until the project owner reviews that guard and
its release branches. Do not hardcode the Sous repository URL in a generated
site's release configuration.

The template's Composer name is aligned with its registered Packagist name,
`fourkitchens/sous-drupal-project`. Bootstrap rewrites it to the new site's
`project/<directory-name>`; the regression also checks that private npm tooling
no longer carries the source repository URL.

## Repository policy and remaining checks

Read-only GitHub inspection confirmed Actions enabled, the necessary Actions
allowed, and the connected account's repository administration access. All seven
repositories currently have no main-branch protection or rulesets, and their
default token policy permits writes. The workflows narrow their own permissions
explicitly. Repository owners still need to choose review requirements and
required checks (select the actual shared integration check after its first run), and may
set the repository default token to read-only. No remote policy was changed.
The workflow uses the built-in token; a separate personal release token is not
needed by this configuration. Retire a legacy CircleCI release token only after
checking whether another integration uses it.

Actual GitHub execution/token authorization and Packagist credentialed webhook
delivery require a pushed workflow/release event; local tests do not establish
those outcomes. Read-only API inspection confirmed that all seven Composer
packages are registered, their current main revisions are indexed, and each
repository has an active Packagist push webhook. An active webhook does not
prove successful delivery of the next tag. Preserve immutable published tags.
`composer install` reproduces
a generated application's lockfile. `npm ci` reproduces its npm lockfiles.
Future major upgrades require another reviewed dependency update.

Full npm audits retain the two known vulnerable npm-bundle packages: moderate
`ip-address` and high `undici`. They affect installer/release tooling, not the
browser asset graph. The unused default npm release plugin is still a mandatory
semantic-release dependency; removing it from production configuration does
not remove its installed bundle. No audit suppression or unsafe forced npm
major override was used. See [frontend audit evidence](FRONTEND_TOOLING.md).

## Checks performed

| Check | Result |
| --- | --- |
| Fresh remote Git tags/heads and GitHub/Packagist metadata | Checked all seven repositories; tags above confirmed |
| Clean locked `npm ci --include=dev` on Node 24.22.1/npm 11.21.1 | Passed in all seven repositories; existing dependency directories preserved; new Paragraph Types tooling installed from its lockfile |
| Native analyzer plus note-generator regressions | Six cases passed in each repository (42 total): first release, no release, patch, minor, breaking header, breaking footer; fixture tags unchanged |
| Native previews with proposed squash commits | All seven versions in the table confirmed against real fetched tag history; source refs and local fixture tags unchanged |
| Starter's complete advertised branch/tag/note graph | Fresh public mirror checked with the preserved release channels: tagged main produces no release, prospective breaking main commit produces 8.0.0; fixture tags unchanged |
| Composer schema validation | Passed in all seven repositories; no dependency installation claimed by this check |
| Workflow structure, triggers, publisher guard, permissions, and lock metadata | Checked all seven; release waits for read-only verification |
| Starter JavaScript lint with locked tools | Passed |
| Starter install/scaffold regression suite | Latest 32 passed in the ownership follow-up, including first-scaffold settings initialization, existing settings/symlinks, initial full config export, generated locks, Git tracking, and failure handling; six separate native-helper regressions also passed |
| Full npm audits | Completed in all seven; exit 1 with the same two known tooling findings (one moderate, one high), zero critical |
| Real GitHub Actions run, tag publication, Packagist delivery | Not performed; no source commits or releases created |

Sanitized metadata, logs, native preview notes, and JSON receipts are retained
locally in `reference/dependency-validation/semantic-release/` (ignored evidence,
not a production dependency or a committed lockfile substitute), and
`reference/dependency-validation/paragraph-types/` for the added recipe. Drupal,
generated-theme build/browser, and upgrade checks from earlier phases are
recorded in the dependency/frontend reviews; those integrations were not
rerun for the original release-only change. The added recipe's Drupal matrix
is recorded separately in the Paragraph Types follow-up.
The [shared integration follow-up](INTEGRATION.md) records the subsequent clean
source, Drupal option, theme, configuration round-trip, and HTTP authoring runs.

The current changes are on `codex/documentation` in all
seven repositories, preserving the earlier release/recipe/frontend work and
unrelated changes. The original release files in every repository
are `package.json`, `package-lock.json`, `release.config.js`, `.nvmrc`,
`.gitignore`, `.github/workflows/semantic-release.yml`,
`devops/tests/release.mjs`, and `README.md`. The starter additionally changes
`.circleci/config.yml`, `composer.json`, `devops/scripts/sous-setup.sh`,
`devops/tests/install-workflows.py`, `PROJECT-README.md`,
`docs/CONTRIBUTING.md`, `docs/DEPENDENCY_UPGRADE.md`,
`docs/FRONTEND_TOOLING.md`, and this document.
