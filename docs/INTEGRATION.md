# Sous integration and release checks

The product has one shared integration implementation in
`devops/integration/run.sh` and one GitHub Actions publication implementation.
The [configuration ownership follow-up](MAINTENANCE.md) additionally verifies
theme-only authorization boundaries, existing role/access preservation,
explicit native migrations, shared Linkit ownership, and complete project-owned
configuration export followed by native Drush deployment in every option.
The supported runtime and Emulsify baseline is defined in [Platform](PLATFORM.md).
The disposable project reuses `.ddev/config.yaml` and the reviewed web-image
runtime instead of maintaining a second PHP/Node toolchain in CI. Runtime
versions are checked before dependency installation.
The [environment contract](ENVIRONMENTS.md) defines the additional runtime and
optional Config Split checks described below.

## Current shell/packaging verification

The simplified runner and all seven former Python test suites now use Bash
entry points. jq handles structured JSON; existing Drupal/PHP probes and native
Node release/CLI checks remain in their own languages. No Python program or
Python invocation remains under `devops/`.

The strict native shell run reached its theme gate after 77 recorded commands.
Source snapshots, the portable lock reinstall, settings preservation, platform
checks, seven manifests/audit gates, converted regressions, authorization and
Linkit checks passed. Stock UI Kit JavaScript lint then failed; the run remains
**failed** and release publication stays blocked. Storybook, SCSS and formatting
also have the upstream limitations recorded in [Build tools](BUILD_TOOLS.md).

Separate, explicitly recorded diagnostics on that owned fixture verified the
production-only `composer build-assets`, clean theme audit, all four installer
choices, and all six environment round trips. They also verified the unsafe
configuration/path guards and both development-database transitions to
production. The curl/PHP Page authoring port initially exposed a DDEV stdin
transport issue; an owner-only mounted response file fixed it and the exact
default case passed on retry. These diagnostics do not turn the strict theme
gate into a passing run.

The selected-runtime checks passed 30 installer, 33 runtime/refresh, seven
settings, nine lint, four audit-policy, three action and four artifact groups.
Artifact checks cover Git and Composer archives, real local-dist Composer
create-project, source initialization, retained PHP patches and the strictly
empty generated tests directory. Action checks cover the archive-omitted
runner, exact source revision and failure propagation. Documentation checks
cover seven repositories and 35 Markdown files. No hosted workflow, release,
Packagist delivery or deployment was performed. The disposable resources were
removed with ownership checks; detailed evidence is in
`reference/simplification/shell-integration` and `reference/simplify`.

## Run from a clean checkout

Install Git, Bash **3.2+**, jq, curl, realpath, shasum, Docker, and DDEV **1.25.2+ within 1.x** using their
normal installation instructions. Host PHP, Node, vendor, npm dependencies,
Drupal files, and a developer database are not prerequisites. CI uses
`ubuntu-24.04`, a pinned DDEV setup action, and DDEV 1.25.4. mkcert's development
CA is used for verified HTTPS when available; HTTP checks never disable TLS
certificate verification.

From the ungenerated starter checkout:

```sh
bash devops/integration/run.sh
```

Missing companion repositories are fetched from their public Git `main`
branches, not installed from Packagist's published recipe copies. During
coordinated development, test all checked-out changes together instead:

```sh
bash devops/integration/run.sh --sources /path/to/Projects
```

The parent directory contains `sous-admin`, `sous-content-types`,
`sous-emulsify`, `sous-formats`, `sous-media`, and `sous-paragraph-types`.
The starter itself is the primary subject by default. A recipe can be the
primary subject while the same central runner validates the whole product:

```sh
bash /path/to/sous-drupal-project/devops/integration/run.sh \
  --subject /path/to/sous-formats --sources /path/to/Projects
```

A Git checkout's tracked files and non-ignored working changes are snapshotted;
downloaded dependencies, reference artifacts, `.sfdx`, OS files and legacy
cache files are excluded. Tracked scaffold inputs remain included even if a broad
ignore rule would otherwise hide them. Downloaded Actions source archives are
also supported without a `.git` directory. Symlinks leaving a checkout are
rejected. Provenance records both the revision and the actual source digest.
No original checkout or developer database is changed.

The sources catalog is `devops/integration/sources.json`. The external Sous
Paragraphs host recipe is fetched as source too. An explicit companion ref can
select a coordinated PR or commit:

```sh
bash devops/integration/run.sh \
  --refs-json '{"sous-formats":"refs/pull/123/head","sous-media":"COMMIT_SHA"}'
```

Use real reviewed refs in place of the example. The primary subject cannot be
replaced by a companion override. On a recipe's workflow, `recipe_refs` has the
same JSON format. Such overrides are validation-only and cannot publish a
release. Without overrides, defaults must already contain the coordinated
companion changes; a failure from an older companion is a real integration
failure, not a reason to substitute cached developer files.

For diagnosis, `--option default|paragraphs|layout-builder|admin-only` selects
one option. CI always runs `all`. `--keep` retains only the newly created
fixture locally; CI never uses it. Otherwise native DDEV deletion checks the
registered project's directory ownership before deleting it. The fixture and
database are removed even when a check fails. Single-option runs skip the
additional environment matrix; `all` runs it on one Page baseline.

Product checks are source-only. `.gitattributes` and Composer archive rules
exclude this integration directory and all test contents from generated sites;
one-time naming enforces the same boundary for source installs. The harness
restores probes only inside its owned disposable fixture after naming. It does
not add maintainer tooling back to a generated application artifact.

## One baseline, four installation options

The runner creates a temporary project, resolves checked-out recipes through
**temporary path repositories**, invokes native `composer drupal:recipe-unpack`,
then removes those overrides and recipes from the consuming lock through the
native unpacker. It checks installed recipe YAML against its source snapshot.
Project naming runs before the final lock refresh: Composer hashes the root
package name too. A second installation removes vendor, Drupal core,
contributed modules/themes, and downloaded libraries before installing the
generated portable lockfile. That installation must preserve both the lock
bytes and an existing `settings.local.php` sentinel, and the generated
manifest/lock pair must validate. No local path override enters production
config. The normal installer also refreshes root lock metadata without changing
locked package versions and validates the result before announcing success,
including the admin-only option. A contributor clone without a lockfile uses
normal `composer install` resolution first; it never attempts `update --lock`
without an existing lockfile.

The starter metadata setup runs with PHP forwarded to DDEV. The generated
project theme is created once using the locked project Emulsify CLI and UI Kit,
then production assets, native lint/format checks, Storybook and Drupal asset
checks run. There is no custom SCSS migration or repeated npm installation during
ordinary build commands. A database snapshot contains Minimal Drupal, Admin,
Emulsify, and the generated theme; each option starts from that snapshot.

| Installer choice | Applied composition | Required result |
| --- | --- | --- |
| Default | Current Sous Content Types, Formats, Media | Usable Page form, create/edit/save/render, Linkit filtering and entity resolution |
| Paragraphs | Source Sous Paragraphs + current Paragraph Types/Content Types | Four component forms, media, revisions, icons, Body and component rendering |
| Layout Builder | Bundled `web/recipes/sous-layout-builder` | Core layout enabled, existing Body field block retained, per-Page overrides persist and render |
| Admin only | Shared base, with no optional Page recipe | Gin and Navigation work without requiring a Page content type |

Layout Builder uses core native actions `enableLayoutBuilder` and
`allowLayoutOverrides`. Core converts the Page's visible formatters into field
blocks; the recipe does not replace the layout with an empty section. The
native Basic Block recipe supplies usable inline blocks. The previously offered
public Sous Layout Builder package and its incompatible Gin Layout Builder RC
are no longer installer or CI dependencies. No Gin LB beta exception was added.

Default, Paragraphs, and Layout Builder use the same explicit Content Types
`dev-main` exception while its repaired Body model remains unreleased. Public
1.0.3 does not contain those repairs. Replace all three selectors with the
reviewed stable 2.x line only after that release actually exists, as described
in [Release maintenance](RELEASES.md); no fictional future release is required.

Every runtime case exports configuration through the actual Drush PHP entry
point, requires exported files to exist, changes the site name deliberately,
imports, and requires exact restoration of every active configuration
collection. Default Page authoring also submits real HTTPS forms with session
and CSRF fields. Existing versioned recipe checks provide metadata, Linkit,
Page, Navigation, and Paragraphs coverage. The static validator inspects source
Composer/YAML and the owned bundled recipe, preserves legitimate nested plugin
identifiers and embedded icon UUID relationships, and rejects exported site
UUIDs/settings hashes.

## One baseline, three environments

After the content options, the full run uses the actual `confex`/`confim`
helpers on local, nonproduction and production copies of one Page baseline.
It tests ordinary complete exports with the optional split directories absent,
then installs stable Config Split 2.0.2 and applies the bundled optional recipe.
The same three environments round-trip through native transforms; core Contact
provides disposable module/config differences, with identical stored split
lists and runtime status overrides. A local and a nonproduction database copy
then each switch to production, which must remove their split-only module and
configuration. No development package, client integration or production mail
transport is exercised by these fixtures.

The probe seeds core's database-backed development UI switches and verifies
that production/nonproduction services keep debug and null caches disabled
without mutating the stored switches. Local settings load only locally; native
nonproduction mail is actually captured. Production checks send no mail.
Deliberately unsafe staged module, capture, logging, aggregation and broad-ignore
defaults must fail before deployment and leave active configuration unchanged.
Production restore must fail before database replacement. An explicit prepared
native build checks routing without DDEV recursion. This supplements focused
installer, runtime/argument and PHP settings regressions; it does not imply an
old-major database upgrade or arbitrary mail/integration coverage.
Missing active split directories and incoming paths that are missing or inside
the public root must fail before native export/import can create files or
change active configuration.

## Dependency security and diagnostics

The pipeline validates all seven Composer manifests, source recipe YAML, root
PHP/JavaScript, installation/lint/action regressions, semantic-release behavior,
and native CLI cache handling. It runs platform checks and native Composer/npm
audits, including development tools and every recipe lockfile. Audit JSON and
actual native exit status are retained separately from stderr.

`audit-policy.json` documents the narrow existing exceptions: npm 11.21.1's
bundled `ip-address`/`undici` versions and advisories, and the existing abandoned
installer 2.0.1 without a declared replacement. They remain visible failures in
the raw audit output, accepted only by the explicit policy gate. New findings,
changed severity/path/version/scope, ignored Composer advisories, incomplete
repository coverage, malformed reports, and network failures fail integration.
Composer security advisories have no exception. Theme audits are separate from
root/release-tool audits; generated browser assets are not described as having
the tooling's exposure. No `--ignore-platform-reqs`, security-block disabling,
audit omission, or blanket successful exit is used.

Results default to `.integration-results/` or the chosen `--artifacts` directory.
`result.json` records each native command's status, all four option outcomes,
runtime/source provenance, and cleanup. It is written on failures too. Artifacts
include stdout/stderr, audit policy decisions, and generated manifests/locks;
not database dumps or login tokens. CI uploads diagnostics even if checkout or
DDEV setup fails. The disposable account's fixed test password is redacted.

## GitHub participation and publication

Each repository's thin `semantic-release.yml` triggers on its PRs and release
branch pushes. Recipes call the starter's shared workflow at `main`; the
starter uses its local workflow revision. The subject is explicitly checked out
at the PR head SHA or pushed SHA. Companion changes can be paired by reviewed
refs, with no PAT, repository dispatch, secret inheritance, or
`pull_request_target` execution. Read-only integration runs before the guarded
publisher; the publisher alone has `contents: write` and serializes across its
repository's tag namespace. Nonempty companion-ref inputs prohibit publication.

The shared workflow uses a documented GitHub.com
[workflow-relative action](https://docs.github.com/en/actions/reference/workflows-and-actions/workflow-syntax-for-github-actions)
(`$/`) so its central action/runner comes from the same workflow revision,
regardless of which recipe called it. Action downloads also honor archive
exclusions, so the composite action performs a real Git checkout of the tooling
at that exact parent action SHA into `.sous-tooling`. It captures the parent
repository/ref before the nested checkout action changes context, verifies the
resulting HEAD and leaves the caller's subject checkout separate. This needs
only the existing read permission. GitHub Enterprise deployments would need to check that feature's
availability before adopting this configuration.

Source-name guards keep same-name forks testable and skip the copied product
workflow in renamed generated projects. Generated applications use their own
build/lint scripts and need project-specific CI; the product runner requires
its seven known source identities and ungenerated bootstrap files.

## Remote cutover

Read-only inspection preceded source retirement. All seven repositories had no
required status contexts or rulesets. The starter's legacy `next`, `next-major`,
and `8.x-1.x` protections exist without required checks and were not changed.
The starter's remote CI exposed `Attempt Semantic Release`, CircleCI `build`,
and `ci/circleci: build_node`, `build_php`, `release`. The latest CircleCI build
failed in release despite its PHP/Node jobs succeeding. Recipe repositories
had no published workflows/checks; their newly added callers are deliberate.

The starter's `.circleci/config.yml` is removed and its responsibilities are
covered here. Its remote CircleCI webhook/project and credentials are not
silently disabled while replacement code is unmerged. Roll out in this order:

1. Merge the central workflow/action/runner without publishing a release, then
   update companion callers to a reviewed central ref (current development uses
   `main`). Include/backport the caller on active release channels.
2. Validate coordinated companion PR refs; merge the source repairs in the
   documented dependency order. Run the default-ref integration in GitHub.
3. Select the actual successful shared integration check as required in the
   repository's branch policy after its first run.
4. Disable the legacy CircleCI project/hook, and retire a token only after
   checking other uses. No token is needed for the new read-only integration.

Native local execution is distinct from an actual GitHub Actions run or
Packagist delivery. Neither tags/releases nor remote CI policies are published
by this implementation task. Remote inspection receipts are retained locally
in `reference/integration-pipeline/remote/`; runtime/clean-checkout evidence is
under `reference/integration-pipeline/` and is not a production dependency.

## Initial pipeline verification record

This records the initial pipeline work. The subsequent ownership/migration
checks and latest full-run results are recorded in
[site maintenance](MAINTENANCE.md#verification-and-changed-files).

Verified on 25 October 2026 local time using clean Git snapshots of all seven
working source trees and a freshly fetched public Sous Paragraphs recipe.
Snapshots omitted installed dependencies, reference files, OS files, and
developer databases. The unmodified full entry point exited **0** after
**71 native commands**; every offered option passed. A subsequent no-lock
contributor-clone guard was verified by rerunning the updated installer suite
separately; the full Drupal matrix was not repeated for that shell guard.

| Check actually performed | Result |
| --- | --- |
| Disposable runtime | PHP 8.4.20, Node 24.22.1, npm 11.21.1, Composer 2.10.3; local DDEV 1.25.2 |
| Seven source Composer manifests and recipe YAML | Passed; installed `recipe.yml` files matched their source snapshots; metadata/plugin/icon identifier checks passed |
| Generated Composer pair | Validated after project naming; no temporary path packages/repositories; native lock refresh preserved selected package versions and source/dist references |
| Clean installation from final generated lock | Passed after deleting vendor/core/contrib/libraries; lock SHA-256 and existing local settings remained unchanged; platform requirements passed |
| Starter regressions | 27 installer cases inside the full run; latest 28-case suite separately passed after the no-lock guard, plus nine lint cases, four audit-policy cases, two composite-action cases, six release cases, and native CLI cache checks |
| Generated theme | Generation, production assets, Storybook, lint/format checks, SDC verification, SCSS migration regression, and generated-project lint passed |
| Default / Paragraphs / native Layout Builder / admin-only | All four passed from separate restored Minimal-based database snapshots |
| Page authoring | Default HTTPS form create/edit/save/render passed with verified TLS; Paragraphs and Layout Builder forms, persistence, composition and rendering passed their native checks |
| Linkit | Metadata survived filtering and entity links resolved in each applicable combination |
| Configuration | Actual export, deliberate mutation, import, and complete active-collection restoration passed in all four options |
| Composer audit | Zero security advisories; native exit 1 for the explicitly reviewed abandoned installer |
| Root and six recipe npm audits | Native exit 1 with the same two affected bundled tooling packages; exact policy checks passed |
| Generated-theme npm audit | Zero findings, native exit 0 |
| Workflow/action review | All seven caller structures, immutable pins, permissions, publication guards, paired-ref behavior, source provenance, and whitespace checks passed |
| Cleanup | Ownership-checked removal of the disposable project, containers, database, and temporary source workspace passed |
| Live GitHub workflow / release / Packagist delivery | Not performed; rollout remains as documented above |

Core Layout Builder emits two transient `field_block:node:page:body` plugin
warnings during recipe application. They remain visible in raw stderr. The
shared entry point rebuilds caches; subsequent fresh plugin discovery, visible
default Body rendering, saved per-Page overrides, and configuration import all
pass. No warning was hidden or interpreted as a passing assertion.

The remaining npm findings affect installation/release tooling; the generated
theme audit has no findings. The abandoned Composer installer remains required
by the current asset installation paths. These findings, the transient core
warnings, and the pending remote workflow/CircleCI cutover are the remaining
limitations; no dependency conflict or failed installation option remains.
Local receipts are `reference/integration-pipeline/clean-run/result.json`, its
raw logs/generated manifests and locks, the workflow/remote/static receipts,
and `latest-installer-regressions.log`. The earlier feature pass with a stale
post-naming lock is retained separately as a diagnostic, not final proof.

## Files changed for this integration follow-up

The starter owns the shared workflow/action, `devops/integration/` runner,
validators, security policy, focused `devops/tests/` regressions, bundled core
Layout Builder recipe, and this guide. Its installer, ignore rules, lint scope,
README, generated README, and related release/frontend/setup documentation are
updated to match. The obsolete `.circleci/config.yml` is removed after the
remote inspection described above.

All six owned recipe repositories change their thin
`.github/workflows/semantic-release.yml` caller and README integration guidance.
Content Types additionally uses a trusted CA for its real HTTP authoring check.
The dependency, recipe, frontend, and semantic-release repairs from the earlier
requests remain present on the dedicated `codex/documentation` branches
in all seven repositories. No source commit, push, tag, release, webhook change,
or deployment is part of this implementation.
