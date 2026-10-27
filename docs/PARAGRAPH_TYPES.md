# Paragraph Types integration follow-up

For the 2.x collection, source lives under
[`packages/sous-paragraph-types`](https://github.com/fourkitchens/sous-drupal-packages/tree/main/packages/sous-paragraph-types).
Paragraphs and Core Layout Builder are separate optional ZIP packages; the
starter no longer ships bundled composition recipes. Current operation and
acceptance are documented in [Setup](SETUP.md) and [Integration](INTEGRATION.md).
The earlier implementation details below remain historical evidence.

This records an earlier implementation phase and its actual checks. Current
source branches, supported platform, setup commands and update policy are
defined in [Platform](PLATFORM.md), [Setup](SETUP.md), and
[Maintenance](MAINTENANCE.md); the branch name below is historical.

The later [shared integration pipeline](INTEGRATION.md) extends this review
with automated four-option installation checks and release gating. The offered
Layout Builder option now uses a bundled native core recipe, removing the
public package's Gin dependency conflict from the installer.

The newly attached `sous-paragraph-types` checkout is included in the Sous
installation, recipe, Composer dependency, frontend, and automated release
reviews. The original six repositories retain their existing changes. This
seventh checkout started clean at public `main` revision
`2cdeca6572b3f46947d84ad60e9cfb7ee0ab3e46`; its dedicated working branch is
`codex/sous-paragraph-types-integration`. No source commit, remote push, tag,
release, or deployment is part of this work.

## Recipe and dependency review

The recipe provides Image, Text, Text with Media, and Video paragraph bundles.
It requires **Sous Formats and Sous Media**, which supply the text formats,
media types, and their declared dependencies. It does not require Sous Admin,
Sous Content Types, or a Page host to define/render its own paragraph entities.
A Page using these components additionally needs the public Sous Paragraphs
composition recipe and its Sous Content Types dependency. That parent recipe
is inspected and tested as an external consumer, not edited as another local
source repository.

| Direct dependency | Before constraint | Selected newest stable line |
| --- | --- | --- |
| `drupal/paragraphs` | `^1.20` | [1.23.0](https://www.drupal.org/project/paragraphs/releases/8.x-1.23) / `^1.23` |
| `drupal/paragraphs_collapsible` | `^1.2` | [1.6.0](https://www.drupal.org/project/paragraphs_collapsible/releases/8.x-1.6) / `^1.6` |
| `drupal/paragraphs_ee` | `^10.0` | [10.2.0](https://www.drupal.org/project/paragraphs_ee/releases/10.2.0) / `^10.2` |
| `fourkitchens/sous-formats` | `dev-main` | Existing explicit development exception retained until an actual stable tag exists |
| `fourkitchens/sous-media` | `dev-main` | Existing explicit development exception retained until an actual stable tag exists |

The recipe uses stable minimum stability. Consuming installations retain their
reviewed PHP 8.4/Drupal 11.4.11 platform and normal Composer security checks.
No production path repository or platform/security bypass is introduced.
Temporary local recipe repositories/copies are for integration testing only.
Editor Enhancements 10.2 requires Drupal 11.4; this selected set does not support
Drupal 10 or 11.3. Collapsible is retained to preserve existing capability,
although its [upstream project](https://www.drupal.org/project/paragraphs_collapsible)
is no longer developed and is not covered by Drupal's security advisory policy.

The exported Preview view mode is supplied by Paragraphs itself. Use that
native configuration instead of retaining a duplicate export with an
installation-specific `_core.default_config_hash`. Recipe module installation
suppresses automatic configuration-entity installation, so the shared view
mode is imported explicitly. Native strict matching protects this recipe's
owned fields, bundles, categories, and displays while accepting an existing
customized shared Preview mode. Text is installed explicitly. Options was
already declared for the list field and remains installed; the current
`options_select` widget is provided by Drupal Core, so the form display needs
no additional Options dependency or existing-site configuration migration.

The four icon UUIDs and corresponding content dependencies are **preserved**.
Each bundle embeds its PNG as `icon_default`; current Paragraphs reconstructs
that managed file with the configured `icon_uuid`. Clearing those UUIDs would
disable native icon restoration. These are portable definitions backed by
included data, rather than disposable site metadata. Embedded icon bytes remain
unchanged.

## Starter and frontend integration

The starter already requires `fourkitchens/sous-paragraph-types:dev-main` in its
Paragraphs option, then applies the parent Sous Paragraphs recipe through the
shared error-propagating recipe command. Two new installer regressions check
that selection and that a failed Paragraph Types dependency stops the parent
application and bootstrap cleanup.

This recipe has no independent frontend npm asset pipeline. Its npm manifest
is private release tooling, with the same Node 24.22.1/npm 11.21.1 runtime and
locked release packages as the other Sous recipes. It adds no global CLI,
startup hook, cache-clearing step, or theme build override. The starter's
project-local Emulsify CLI and generated-theme build remain authoritative.
The earlier generated-theme production/Storybook/lint results remain recorded
in [Frontend tooling](FRONTEND_TOOLING.md); they are not described as rerun by
this follow-up.

## Release coordination

Fresh remote inspection found no stable tags. The native preview of the
proposed commit `feat: release portable Sous Paragraph Types` selects **1.0.0**.
The shared main-only GitHub Actions workflow runs read-only verification before
one publisher with repository-wide job concurrency and `contents: write`.
Private npm metadata and the explicit plugin list prevent npm publication.
The same Conventional Commits preset and narrowly scoped writer 9 override
support breaking headers and footers. See [Release maintenance](RELEASES.md).

All seven Composer packages are now included in the coordinated plan. Formats
and Media must be available before this recipe's bounded stable requirements
can replace existing `dev-main` exceptions. Publish no fictional versions in
production manifests. The parent Sous Paragraphs recipe still needs its own
reviewed stable-release/requirement follow-up; the starter must be re-resolved
against the actual coordinated releases before its proposed 8.0.0 release.
The separately documented public Sous Layout Builder/Gin conflict is unchanged.

Sanitized validation evidence is retained locally under
`reference/dependency-validation/paragraph-types/`. Actual GitHub release
execution and credentialed Packagist webhook delivery are not performed by
local previews. Two upstream npm-bundle tooling advisories remain; they are
separate from browser/runtime asset exposure and are not suppressed.

## Validation performed

All destructive checks used named disposable databases and code installations.
The Docker project, containers, volumes, SQL copies, and temporary site code
were removed after the receipts were saved; existing developer sites were not
changed.

| Check | Result |
| --- | --- |
| Direct Composer metadata and official release notes | All five direct requirements reviewed; latest stable module lines selected, both necessary Sous development exceptions identified |
| Clean latest consuming-project install | 126 packages installed from its lockfile; lock hash unchanged; existing local settings sentinel preserved |
| Composer platform requirements | All 20 passed on PHP 8.4.20 |
| Composer security audit | No advisories; audit exits **1** for the existing abandoned `oomphinc/composer-installers-extender` dependency |
| Independent fresh application and matching reapplication | Passed with Sous Formats/Media and no required Admin/Content Types recipe |
| Shared Preview import | Fresh copy omitting the import demonstrably lacks Preview; corrected recipe supplies it and exactly preserves an existing customized shared mode |
| Owned configuration conflict | A conflicting owned customization is rejected and preserved, rather than overwritten |
| Supported Admin order | Admin before Paragraph Types and Paragraph Types before Admin both passed |
| Public Sous Paragraphs + Content Types + Paragraph Types + Admin | Page composition passed with Body and all four paragraph types |
| Native regression probe | Four forms, validation, save/edit/revision reload, image/video rendering, alignment persistence, icon recreation/file usage, and host composition passed |
| Current-core existing-site upgrade | Paragraphs 1.21.0 → 1.23.0 and Features 2.1.0 → 2.3.0 passed; representative content exactly preserved and exported config unchanged |
| Separate older-core upgrade | Version table below; five native database updates and cache rebuild passed, representative original content/media/paragraph/icon JSON exactly preserved |
| Real browser authoring | Stark and Gin editors passed; edited Body and paragraph text, saved/reopened values, four add actions/icons, and native video iframe checked |
| Gin/Emulsify integration | Gin sticky Save, both text widgets, Body, Right → Left alignment, reopened values, Collapse all AJAX, and rendered Emulsify output passed |
| Locked npm install/release tests | Clean `npm ci` and six native versioning/note cases passed; first-release preview selects 1.0.0 with no tags changed |
| Seven-repository release consistency | Composer schema, locked tool versions, private manifests, permissions, triggers, guards, publisher concurrency, and proposed versions checked across all seven |
| Starter install/scaffold regressions | **23 passed**, including the added Paragraph Types selection/failure paths |
| Full npm audit | Exit **1**: the same two known bundled npm tooling findings, one moderate and one high; zero critical |
| Generated-theme build/Storybook | No new frontend pipeline introduced; earlier verified build/lint/Storybook results retained, not rerun here |
| Actual GitHub release/Packagist delivery | Not performed |

The separate upgrade used a valid, secure older dependency set:

| Package | Before | After |
| --- | --- | --- |
| Drupal core | 11.3.20 | 11.4.11 |
| Paragraphs | 1.21.0 | 1.23.0 |
| Paragraphs Editor Enhancements | 10.1.1 | 10.2.0 |
| Paragraphs Features | 2.1.0 | 2.3.0 |
| Paragraphs Collapsible | 1.6.0 | 1.6.0 |

Native updates were `system_update_11400`, `system_update_11401`,
`field_post_update_clear_purge_batch_size`,
`system_post_update_migrate_compress_setting`, and
`system_post_update_remove_non_admin_routes_state`. Reviewed exported
configuration changed only `system.performance`: CSS/JS `gzip` settings became
`compress`, with boolean values preserved. No field/widget settings changed.
The new Paragraph Types recipe applied strictly after this upgrade without a
custom configuration migration.

The nominal original floors were not fabricated as a passing baseline:
Paragraphs 1.20 is blocked by current security advisories; Collapsible 1.2
supports Drupal 8/9, which cannot overlap Paragraphs 1.20's core requirement;
and EE 10.0's permissive Composer metadata differs from its Drupal-10-only
module declaration. Those refusals are retained as evidence. The successful
older-core fixture uses EE 10.1.1 on its supported secure Drupal 11.3.20 line.
No security or module-compatibility checks were disabled.

Video checks seed deterministic native oEmbed cache responses and verify the
real local iframe route. They do not establish external YouTube/Vimeo
availability or streaming playback. No npm browser/runtime dependency was
added by the recipe; its remaining npm audit findings affect installed release
tooling. Collapsible's lack of advisory coverage and the abandoned Composer
installer remain disclosed limitations, alongside the coordinated stable
release and owner-controlled branch-protection decisions.

## Files and repositories changed

In `sous-paragraph-types`: `composer.json`, `recipe.yml`, `README.md`, removal of
`config/core.entity_view_mode.paragraph.preview.yml`, the new
`tests/verify-paragraph-types.php`, and `.github/PULL_REQUEST_TEMPLATE.md`.
Release setup adds `package.json`, `package-lock.json`, `release.config.js`,
`.nvmrc`, `.gitignore`, `.github/workflows/semantic-release.yml`, and
`devops/tests/release.mjs`. The other 25 recipe configuration files, including
all four embedded icons, remain byte-identical to the original source.

In `sous-drupal-project`: `devops/tests/install-workflows.py`, `README.md`,
`PROJECT-README.md`, this report, and the dependency/frontend/release review
links and scope updates. The existing Paragraphs installer command already
selected this recipe and needed no new implementation path. Other Sous source
repositories retain their prior work.

The complete runtime receipts are in
`reference/dependency-validation/paragraph-types/integration/results.json`;
`browser-result.json` and `gin-browser-result.json` record actual browser
checks. Source metadata, official notes, unchanged-icon digests, clean npm
installs, and release previews are retained alongside them. No database dump,
password, login token, or temporary path-repository override is included in
production configuration.
