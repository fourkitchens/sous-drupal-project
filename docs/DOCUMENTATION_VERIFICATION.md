# Documentation verification

This guide retains the earlier documentation verification record. Current checks use bash devops/tests/documentation.sh; maintainer checks are excluded from generated applications and run from a Sous source checkout.

This records checks for the documentation consolidation, separately from the
earlier [93-command ownership integration](MAINTENANCE.md#verification-and-changed-files).
Shared instructions are now authoritative in [Platform](PLATFORM.md),
[Setup](SETUP.md), [Build tools](BUILD_TOOLS.md), [Deployment](DEPLOYMENT.md),
[Maintenance](MAINTENANCE.md), [Releases](RELEASES.md), and
[Operations](OPERATIONS.md). Recipe READMEs document only their specific
responsibility, dependencies, application and migration details, then link here.

## Licensing decision

The original source audit found five MIT `LICENSE` files beside GPL Composer/npm
metadata, and no top-level license file in the starter or Emulsify repository.
The maintainer explicitly chose **GPL-2.0-or-later across all seven repositories**.
Every current checkout now has uppercase `LICENSE`, an explicit SPDX/“or later”
notice and the full, verbatim GNU GPL v2 body. Existing Four Kitchens attribution
is retained. Composer, npm and root-lock metadata already declared the chosen
expression and were checked for agreement. No third-party notices or historical
tags were rewritten. The canonical GNU body was downloaded from
<https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt> and its SHA-256 is recorded
with the before/after license inventory in local evidence.

## Scope of command verification

The disposable source fixture uses all checked-out recipe changes, native
temporary path repositories and portable unpacking/lock preparation. It then
removes those preparation overrides and runs the real documented host
`composer install` and `composer run-script post-create-project-cmd`, selecting
**3: admin only** through a terminal. Metadata naming is deferred until that
actual hook; it is not a simulated replay after manual Drupal installation.

The initialized `contributor` branch and an isolated global Git preference are
checked for preservation. The actual bootstrap must install Drupal, recipes,
theme/assets, users and the complete initial export, validate the generated
lock, print success and remove helpers only after success. An active role
member other than UID 1 is checked for administration access.

The fixture then explicitly applies Content Types, exercises real HTTPS Page
authoring with the generated `sous_chef` account, saves its own database copy,
changes content, and runs the exact host `npm run setup -- restore`. Restored
content and administrator access are checked. Production verification runs
`ddev composer build-assets`, generated-project lint and native theme tasks,
platform checks and audits. Final results are recorded below.

This actual-bootstrap choice is distinct from the earlier native four-option
composition suite; full bootstrap was not repeated four times. The public
create-project tag example is not executed against an invented future tag.
No production deploy, remote Terminus command, live GitHub workflow, Packagist
delivery, or publishing is claimed by a local fixture.

## Static checks

`python3 devops/tests/documentation.py --sources /path/to/Projects` checks all
seven source repositories' local Markdown paths, exact filename case, heading
anchors, supplied Sous GitHub main/HEAD links against the matching local source,
and agreed license metadata. `--self-test` checks its path/anchor/license guards.
It does not fetch arbitrary remote URLs or execute fenced example commands.
The shared product runner also invokes this checker against its source snapshots.

Historical implementation reports remain labeled as evidence rather than
alternate setup instructions. Their before/after versions and earlier check
counts are preserved; they are not presented as newly run checks.

## Results and files

The real documented-command verification **passed: 76 recorded commands,
entry-point exit 0**. Host PHP 8.5.12/Composer 2.10.3/Node 24.22.1/npm 11.21.1
orchestrated the workflow; Drupal 11.4.11 ran in DDEV on PHP 8.4.20. Temporary
host tool selection did not change global developer packages or Git preferences.

| Check actually performed | Result |
| --- | --- |
| Host `composer install` and actual `post-create-project-cmd`, choice 3 | Passed; first naming/install/recipes/theme/users/full export/login/success and bootstrap cleanup executed |
| Contributor Git behavior and generated sources | Existing branch/global preference preserved; manifests/locks/custom theme/full config tracked; dependencies/output excluded |
| Local settings and final Composer pair | Existing local bytes preserved and loaded; selected locked packages unchanged; generated validation/platform checks passed |
| Explicit Content Types adoption and real Chef account | Generated Page/config deployment and verified HTTPS create/edit/save/render passed with active UID 2 |
| Exact `npm run setup -- restore` | Own database backup restored after deliberate content mutation; original Page data and administrator access returned |
| Exact `ddev composer build-assets` | Production assets and Storybook passed from locked PHP/root/theme installs |
| Generated build/lint/format | `theme-build`, `lint:project`, Prettier and expected CSS/component/Storybook output checks passed |
| Audits | Zero Composer advisories; existing abandoned installer gives native exit 1. Root npm retains two affected bundled tooling packages; theme npm has zero findings |
| Local docs/license checks | All seven repositories, 32 Markdown files and 210 local/source-mapped targets passed, including exact filename case/anchors and matching SPDX metadata; self-test passed |
| Clean source snapshots | The same checks passed on seven fresh Git-inventoried snapshots, without ignored dependencies/files |
| Cleanup | Test project registration, containers, volumes, network, workspace, and temporary controller removed; developer sites untouched |

A late supplemental `ddev drush status --field=config-sync` probe raced primary
cleanup and is explicitly **inconclusive**, not a passing command check. Its
auto-started owned containers were label/path-checked and removed; final
resource checks are clean. The main run separately asserted the actual tracked
sync path, loaded local sentinel, and successful native configuration deployment.

The clean-snapshot documentation check caught Git recording lowercase
`docs/features.md` while the filesystem and links used `docs/FEATURES.md`.
The tracked case was corrected; its edited contents were preserved. The checker
now also checks Git-recorded source filename case. This documentation-only
correction and CI guard were checked separately after the application run.

Source/generated READMEs, all recipe READMEs, the shared guides and historical
report navigation were updated. All seven `LICENSE` files were reconciled;
missing standard PR templates were added only in Content Types and Formats.
The documentation checker is invoked by shared integration. Formats'
human-readable Composer/recipe descriptions now match editor/text/Linkit
contents. No dependency version was changed by this documentation task.

Local evidence is under `reference/documentation/` and
`reference/documentation-validation/`; these ignored receipts are not required
inputs. Work is on `codex/documentation` across all seven repositories. Earlier
authorized work and unrelated files remain preserved. No source commit, push,
tag, release, or deployment is part of this task.
`reference/documentation-validation/final-results.json` summarizes actual
commands/runtime/audits; `commands/final-cleanup.json` confirms final cleanup.
Published tags, full bootstrap choices 0/1/2, interactive watch servers, remote
HTTP links, CI publication and production deployment are not newly claimed as
verified. The earlier four-option native composition results retain their
separate scope. Operational roles/cadence are defined in Operations; named
staff assignments and site deployment decisions remain with the maintainers.
