# Composer dependency upgrade review

The dependency/version review below retains its original evidence. Current Composer patches live in patches/ with a native regenerated patches.lock.json; the CLI patch and custom theme integration helper were subsequently removed. Current operating commands use the shell tooling linked from README.md.

This is historical implementation evidence. Current responsibilities, runtime
requirements, setup/build commands, and maintenance cadence are authoritative in
[Platform](PLATFORM.md), [Setup](SETUP.md), [Build tools](BUILD_TOOLS.md), and
[Operations](OPERATIONS.md). Before/after tables below retain the versions and
checks actually reviewed at that phase.

The subsequent [integration pipeline](INTEGRATION.md) tests checked-out sources
and the four offered options. The offered Layout Builder option now uses a
bundled core recipe instead of the public package analyzed below. Current
installer options select the repaired Content Types branch until its actual
stable release is available; the original phase's results remain historical.

The subsequently added `sous-paragraph-types` checkout is reviewed in the
[Paragraph Types follow-up](PARAGRAPH_TYPES.md), including direct dependency
versions, portable recipe configuration, and supported combinations. The
original six-repository results below retain their original scope.

The [frontend tooling follow-up](FRONTEND_TOOLING.md) records the current
Node 24.22.1/npm 11.21.1 runtime and subsequent npm security fixes. The runtime
and 21-package root/15-package theme npm audit results below are the historical
Composer-upgrade validation phase, before that follow-up. Preserve them as the
record of what was checked at that stage; use the follow-up for current frontend
versions, commands, and remaining advisories.

Reviewed on **25 October 2026**. This report covers the five Sous repositories
and the approved Sous Media dependency repairs. Package versions come from
Composer metadata, with official upstream release notes and tagged source
reviewed for migration requirements. Prerelease and development versions were
not treated as stable releases.

## Before and selected versions

Repository keys: **P** = `sous-drupal-project`, **A** = `sous-admin`,
**C** = `sous-content-types`, **F** = `sous-formats`, **E** = `sous-emulsify`,
**M** = `sous-media`.

The installed baseline is the starter's saved `vendor/composer/installed.json`,
which agrees with its old lockfile. It differs from some sibling source
constraints, most visibly Emulsify 6.1.1 versus the recipe's `^7.1` declaration.
“Not installed” means the package was not present in that starter installation;
it does not mean its source recipe lacked a requirement. The selected column
records reviewed constraints, not a promise that a coordinated recipe release
has already been published.

| Direct dependency | Repositories | Before constraint | Before installed | Newest stable available | Selected constraint | Official release notes |
| --- | --- | --- | --- | --- | --- | --- |
| `composer/installers` | P | `^2.3` | 2.3.0 | 2.3.0 | `^2.3.0` | [Notes](https://github.com/composer/installers/releases/tag/v2.3.0) |
| `cweagans/composer-patches` | P | `^1.7` | 1.7.3 | 2.0.0 | `^2.0.0` | [Notes](https://www.cweagans.net/2025/10/announcing-composer-patches-2-0-0/) |
| `dropzone/dropzone` | M | `^5.9` | Not installed | 6.3.5 | `^6.3.5` | [Notes](https://github.com/enyo/dropzone/releases/tag/dropzone%406.3.5) |
| `drupal/autosave_form` | A | `^1.10` | 1.10.0 | 1.12.0 | `^1.12.0` | [Notes](https://www.drupal.org/project/autosave_form/releases/8.x-1.12) |
| `drupal/config_log` | A | `^4.0` | 4.0.4 | 4.0.6 | `^4.0.6` | [Notes](https://www.drupal.org/project/config_log/releases/4.0.6) |
| `drupal/core-composer-scaffold` | P | `~11.3.5` | 11.3.5 | 11.4.11 | `^11.4.11` | [Notes](https://www.drupal.org/project/drupal/releases/11.4.11) |
| `drupal/core-project-message` | P | `~11.3.5` | 11.3.5 | 11.4.11 | `^11.4.11` | [Notes](https://www.drupal.org/project/drupal/releases/11.4.11) |
| `drupal/core-recipe-unpack` | P | `^11.3` | 11.3.5 | 11.4.11 | `^11.4.11` | [Notes](https://www.drupal.org/project/drupal/releases/11.4.11) |
| `drupal/core-recommended` | P | `~11.3.5` | 11.3.5 | 11.4.11 | `^11.4.11` | [Notes](https://www.drupal.org/project/drupal/releases/11.4.11) |
| `drupal/core-vendor-hardening` | P | `^11.3` | 11.3.5 | 11.4.11 | `^11.4.11` | [Notes](https://www.drupal.org/project/drupal/releases/11.4.11) |
| `drupal/dropzonejs` | M | `^2.5` | Not installed | 2.11.0 | `^2.11` | [Notes](https://www.drupal.org/project/dropzonejs/releases/8.x-2.11) |
| `drupal/emulsify` | E | `^7.1` | 6.1.1 | 7.2.1 | `^7.2.1` | [Notes](https://www.drupal.org/project/emulsify/releases/7.2.1) |
| `drupal/emulsify_tools` | E | `^2.1.0` | 1.0.1 | 2.2.2 | `^2.2.2` | [Notes](https://github.com/emulsify-ds/emulsify_tools/blob/2.2.2/CHANGELOG.md) |
| `drupal/entity_usage` | A | `^2.0@beta` | 2.0.0-beta28 | 5.0.1 | `^5.0.1` | [Notes](https://www.drupal.org/project/entity_usage/releases/5.0.1) |
| `drupal/field_group` | A, C, M | `^4.0` | 4.0.0 | 4.0.0 | A: `^4.0.0`; C: `^4.0`; M: `^4.0` | [Notes](https://www.drupal.org/project/field_group/releases/4.0.0) |
| `drupal/focal_point` | M | `^2.1` | Not installed | 2.1.3 | `^2.1.3` | [Notes](https://www.drupal.org/project/focal_point/releases/2.1.3) |
| `drupal/gin` | A | `*` | 5.0.12 | 5.0.16 | `^5.0.16` | [Notes](https://www.drupal.org/project/gin/releases/5.0.16) |
| `drupal/linkit` | A, F | `^7.0` | 7.0.13 | 7.0.17 | `^7.0.17` | [Notes](https://www.drupal.org/project/linkit/releases/7.0.17) |
| `drupal/login_destination` | P, A | `^2.0@beta` | 2.0.0-beta10 | No stable release | `^2.0.0-beta10@beta` | [Notes](https://www.drupal.org/project/login_destination/releases/8.x-2.0-beta10) |
| `drupal/login_history` | A | `^2.0` | 2.0.0 | 2.0.0 | `^2.0.0` | [Notes](https://www.drupal.org/project/login_history/releases/2.0.0) |
| `drupal/media_library_edit` | A, M | `^3.0` | 3.0.5 | 3.0.5 | `^3.0.5` | [Notes](https://www.drupal.org/project/media_library_edit/releases/3.0.5) |
| `drupal/metatag` | C | `*` | Not installed | 2.2.1 | `^2.2.1` | [Notes](https://www.drupal.org/project/metatag/releases/2.2.1) |
| `drupal/navigation_extra_tools` | A | `^1.3` | 1.3.2 | 1.3.2 | `^1.3.2` | [Notes](https://www.drupal.org/project/navigation_extra_tools/releases/1.3.2) |
| `drupal/publication_date` | A, C | `^3.0` | 3.1.0 | 3.2.0 | A: `^3.2.0`; C: `^3.2` | [Notes](https://www.drupal.org/project/publication_date/releases/3.2.0) |
| `drupal/save_edit` | A | `^2.1` | 2.1.0 | 3.0.0 | `^3.0.0` | [Notes](https://www.drupal.org/project/save_edit/releases/3.0.0) |
| `drupal/scheduler` | A, C, M | `^2.2` | Not installed | 2.3.0 | A: `^2.3.0`; C: `^2.3`; M: `^2.3` | [Notes](https://www.drupal.org/project/scheduler/releases/2.3.0) |
| `drupal/tagify` | A, C | `^1.2` | Not installed | 2.0.5 | `^2.0.5` | [Notes](https://www.drupal.org/project/tagify/releases/2.0.5) |
| `drupal/trash` | A | `^3.0` | 3.0.25 | 3.1.2 | `^3.1.2` | [Notes](https://www.drupal.org/project/trash/releases/3.1.2) |
| `drupal/views_ajax_history` | A | `^1.8` | 1.8.0 | 1.8.0 | `^1.8.0` | [Notes](https://www.drupal.org/project/views_ajax_history/releases/8.x-1.8) |
| `drupal/views_bulk_operations` | A | `^4.4` | 4.4.4 | 4.4.9 | `^4.4.9` | [Notes](https://www.drupal.org/project/views_bulk_operations/releases/4.4.9) |
| `drush/drush` | P | `^13.3` | 13.7.1 | 13.8.0 | `^13.8.0` | [Notes](https://github.com/drush-ops/drush/releases/tag/13.8.0) |
| `fourkitchens/sous-admin` | P | `dev-main` | dev-sous-80 | No stable release | `dev-main` | [Notes](https://github.com/fourkitchens/sous-admin) |
| `fourkitchens/sous-emulsify` | P | `dev-main` | dev-main | No stable release | `dev-main` | [Notes](https://github.com/fourkitchens/sous-emulsify) |
| `fourkitchens/sous-formats` | P, C | `*` | Not installed | No stable release | `dev-main` | [Notes](https://github.com/fourkitchens/sous-formats) |
| `fourkitchens/sous-media` | P, C | `*` | Not installed | No stable release | `dev-main` | [Notes](https://github.com/fourkitchens/sous-media) |
| `oomphinc/composer-installers-extender` | P | `^2.0` | 2.0.1 | 2.0.1 | `^2.0.1` | [Notes](https://github.com/oomphinc/composer-installers-extender/releases/tag/2.0.1) |
| `php` | P | `>=8.4` | 8.4 DDEV baseline | Platform requirement | `^8.4` | [Notes](https://www.drupal.org/docs/getting-started/system-requirements/php-requirements) |
| `webflo/drupal-finder` | P | `^1.2` | 1.3.1 | 1.3.1 | `^1.3.1` | [Notes](https://github.com/webflo/drupal-finder/releases/tag/1.3.1) |
| `zaporylie/composer-drupal-optimizations` | P | `^1.0` | 1.2.0 | 1.2.0 | `^1.2.0` | [Notes](https://github.com/zaporylie/composer-drupal-optimizations/releases/tag/1.2.0) |

`drupal/core` is installed through `drupal/core-recommended`; the baseline is
11.3.5 and the selected stable core is **11.4.11**. Core Recommended, Scaffold,
and Project Message use the same reviewed 11.4.11 release floor. Recipe Unpack
and Vendor Hardening were each checked independently in Composer metadata;
both also publish 11.4.11, so their version numbers were not inferred.

Drupal 12 has only prereleases at the review date; Drush 14 is development-only.
Field Group 4.1 is prerelease-only. These are not newer stable targets that have
been silently skipped.

## Stable transitive versions constrained by upstream

The complete locked-package outdated review found these 30 newer stable
versions outside the mutually compatible set. Each selected version is the
newest allowed by its upstream constraints. The table names an exact blocking
parent requirement; other parents can impose the same major restriction.
Drupal Core Recommended's tested dependency ranges are preserved, rather than
removing it to force unrelated newer versions. See the official
[Core Recommended manifest](https://github.com/drupal/core-recommended/blob/11.4.11/composer.json),
[core manifest](https://github.com/drupal/drupal/blob/11.4.11/core/composer.json),
and [Drush manifest](https://github.com/drush-ops/drush/blob/13.8.0/composer.json).
The local `reference/dependency-validation/transitive-constraints.json` records
all parent requirements for each row.

| Transitive package | Selected | Newest stable | Exact blocking requirement |
| --- | --- | --- | --- |
| `composer/semver` | 3.4.4 | 3.5.2 | `drupal/core-recommended 11.4.11` requires `~3.4.4` |
| `grasmash/expander` | 3.0.1 | 4.0.0 | `consolidation/config 3.2.1` requires `^3` |
| `guzzlehttp/guzzle` | 7.16.1 | 8.3.1 | `drupal/core 11.4.11` requires `^7.10` |
| `guzzlehttp/promises` | 2.6.0 | 3.1.0 | `guzzlehttp/guzzle 7.16.1` requires `^2.6` |
| `guzzlehttp/psr7` | 2.13.2 | 3.2.0 | `drupal/core 11.4.11` requires `^2.8.0` |
| `justinrainbow/json-schema` | 6.8.2 | 6.14.0 | `drupal/core-recommended 11.4.11` requires `~6.8.2` |
| `league/container` | 4.2.5 | 5.2.0 | `drush/drush 13.8.0` requires `^4.2` |
| `masterminds/html5` | 2.10.1 | 2.12.0 | `drupal/core-recommended 11.4.11` requires `~2.10.0` |
| `mck89/peast` | 1.17.7 | 1.18.1 | `drupal/core-recommended 11.4.11` requires `~v1.17.6` |
| `sebastian/diff` | 7.0.1 | 9.0.1 | `drupal/core 11.4.11` requires `^4 \|\| ^5 \|\| ^6 \|\| ^7` |
| `symfony/console` | 7.4.21 | 8.0.15 | `drupal/core-recommended 11.4.11` requires `~v7.4.13` |
| `symfony/dependency-injection` | 7.4.21 | 8.0.15 | `drupal/core-recommended 11.4.11` requires `~v7.4.13` |
| `symfony/error-handler` | 7.4.20 | 8.0.15 | `drupal/core-recommended 11.4.11` requires `~v7.4.8` |
| `symfony/event-dispatcher` | 7.4.17 | 8.0.15 | `drupal/core-recommended 11.4.11` requires `~v7.4.9` |
| `symfony/filesystem` | 7.4.21 | 8.0.15 | `drupal/core-recommended 11.4.11` requires `~v7.4.11` |
| `symfony/finder` | 7.4.20 | 8.0.14 | `drupal/core-recommended 11.4.11` requires `~v7.4.8` |
| `symfony/http-foundation` | 7.4.21 | 8.0.15 | `drupal/core-recommended 11.4.11` requires `~v7.4.21` |
| `symfony/http-kernel` | 7.4.21 | 8.0.15 | `drupal/core-recommended 11.4.11` requires `~v7.4.13` |
| `symfony/mailer` | 7.4.19 | 8.0.15 | `drupal/core-recommended 11.4.11` requires `~v7.4.12` |
| `symfony/mime` | 7.4.19 | 8.0.15 | `drupal/core-recommended 11.4.11` requires `~v7.4.13` |
| `symfony/process` | 7.4.21 | 8.0.13 | `drupal/core-recommended 11.4.11` requires `~v7.4.13` |
| `symfony/psr-http-message-bridge` | 7.4.21 | 8.0.8 | `drupal/core-recommended 11.4.11` requires `~v7.4.8` |
| `symfony/routing` | 7.4.21 | 8.0.15 | `drupal/core-recommended 11.4.11` requires `~v7.4.13` |
| `symfony/runtime` | 7.4.21 | 8.0.14 | `drupal/core-recommended 11.4.11` requires `~v7.4.13` |
| `symfony/serializer` | 7.4.21 | 8.0.15 | `drupal/core-recommended 11.4.11` requires `~v7.4.10` |
| `symfony/string` | 7.4.21 | 8.0.15 | `drupal/core-recommended 11.4.11` requires `~v7.4.13` |
| `symfony/validator` | 7.4.21 | 8.0.15 | `drupal/core-recommended 11.4.11` requires `~v7.4.10` |
| `symfony/var-dumper` | 7.4.21 | 8.0.15 | `drupal/core-recommended 11.4.11` requires `~v7.4.8` |
| `symfony/var-exporter` | 7.4.18 | 8.0.16 | `drupal/core-recommended 11.4.11` requires `~v7.4.9` |
| `symfony/yaml` | 7.4.21 | 8.0.15 | `drupal/core-recommended 11.4.11` requires `~v7.4.21` |

## Platform and stability policy

- PHP remains on a bounded `^8.4` requirement, with DDEV/platform PHP 8.4.
  Drupal 11.4 requires PHP 8.3+, and the selected modules support the project's
  retained PHP 8.4 floor; no platform
  requirement is ignored. Platform checks must run inside the actual DDEV
  runtime, rather than relying only on Composer's emulated platform setting.
- Node is **24.x**, sourced from `.nvmrc` and DDEV. Generated Emulsify themes
  require Node 24 or newer. Their package manifest remains the source of truth
  for a stricter patch-level engine requirement.
- Composer **2.10.3** is the selected CI/tooling version. Composer Patches 2
  requires Composer 2.2+, PHP 8+, and Git; the selected tooling exceeds that
  minimum. DDEV requires `>=1.25.2 <2` and pins Composer 2.10.3/PHP 8.4.
  CircleCI uses `cimg/php:8.4`, Composer 2.10.3, and explicit `cimg/node:24`
  executors for both Node jobs. The Node orb's existing 5.0.3
  [executor schema](https://github.com/CircleCI-Public/node-orb/blob/v5.0.3/src/executors/default.yml)
  supplies the `tag` parameter. Remote CI was not executed in this work.
- Consuming Composer configuration uses `minimum-stability: stable` and
  `prefer-stable: true`. Caret requirements bound normal package upgrades to a
  reviewed major release line. No required package uses `*` or an unbounded
  version range to mean “always latest.”
- Login Destination has **no stable release for modern Drupal**. Its existing
  indispensable beta exception is retained and narrowed to
  `^2.0.0-beta10@beta`. It supplies the configured login redirects; removing it
  would remove functionality. The explicit consuming-project flag preserves
  this existing exception under stable minimum stability.
- Sous Admin, Formats, Media, and Emulsify have **no stable tags**. Existing
  development availability is named explicitly as `dev-main`, including where
  a wildcard and global development stability previously admitted it. This
  narrows existing exceptions instead of creating a blanket development policy.
  Temporary local path repositories and source-version overrides are used only
  in disposable integration manifests.

## Major migrations and update requirements

### Drupal 11.3 to 11.4 and Entity Usage 2 to 5

The selected Entity Usage 5.0.1 requires Drupal 11.4+, and conflicts with Trash
below 3.0.30 and Drush below 13.7. The selected Trash 3.1.2 and Drush 13.8 satisfy
those constraints.

[Entity Usage 5.0.0 migration notes](https://www.drupal.org/project/entity_usage/releases/5.0.0)
require rebuilding usage data after a 2.x upgrade. Update 8300 replaces the
usage table and indexes; update 8301 normalizes configured site domains. An old
site below schema 8206 must first complete the supported 2.x updates. After
normal database updates and a cache rebuild, run:

```bash
ddev drush entity-usage:recreate
```

Verify that node, media, paragraph, and revision relationships are tracked
again. Custom EntityUsageTrack plugins must use PHP attributes; annotation-only
plugins are no longer supported. The Sous recipe's base-field and local-task
settings retain their existing keys.

Publication Date 3.2 adds an index on `published_at`; database updates are
required. Run all pending module/core updates, not just these named examples.

Drupal 11.4 introduces the Symfony Runtime Composer plugin. The consuming
project explicitly allows `symfony/runtime` in `config.allow-plugins` after
reviewing [Drupal's change record](https://www.drupal.org/node/3553275).
The first install correctly stopped until this specific trusted plugin was
allowed. This does not enable unknown plugins or bypass Composer's checks.

Drupal 11.4 deprecates the old `core/scripts/drupal` entry point; its vendor-path
fallback can also fail with this project's `web` docroot. The DDEV helper and
recipe README commands now use `../vendor/bin/drush recipe recipes/RECIPE`
from the webroot. Drush 13.8 supplies the project autoloader and an initialized
Drupal request context. Direct use of the new core console exposed a null
request-context failure in Entity Usage 5.0.1 during recipe processing; the
helper therefore selects Drush's initialized context rather than either core
entry point directly. The helper also supplies the actual DDEV site URI. Its
real installation pipeline and deliberately failed recipe were verified; no
Entity Usage patch or package downgrade was needed. See the
[core console change record](https://www.drupal.org/node/3584928) and
[Drush recipe documentation](https://www.drush.org/13.x/commands/recipe/).

### Save & Edit 2 to 3, Tagify 1 to 2

Save & Edit 3 requires Drupal 11.2+ and uses class-based hooks. Its existing
`button_value` and `button_weight` settings remain compatible; test the Save
and Save & Edit actions and redirects in the editor interface.

Tagify 2 uses typed/attribute plugins and enum-backed settings. Tagged source
review confirmed that the Page's widget ID, persisted `CONTAINS`/`1` values,
and existing update hook remain compatible. Its optional AI submodule is not
enabled by this upgrade. Real form construction, HTTP authoring, and Chrome
CKEditor/Tagify autocomplete checks passed.

Autosave Form 1.12 also uses class-based hooks and changes its validator
constructor. Sous has no custom validator subclass requiring a code migration.

### Emulsify 6 to 7 and Tools 1 to 2

The installed starter had Emulsify 6.1.1/Tools 1.0.1 even though sibling source
constraints already targeted newer lines. Emulsify 7.2.1 and Tools 2.2.2 require
Drupal 11.3+ and modernize theme generation to Drupal Starterkit. Tools replaces
the Components module's namespace registration while honoring the theme's
existing `components.namespaces` mapping. The generated frontend uses Emulsify
Core 4/Vite and Node 24+.

A dependency update does not rewrite an existing generated child theme. Review
custom overrides against the new parent templates and follow
[Emulsify's upgrade guide](https://git.drupalcode.org/project/emulsify/-/blob/7.2.1/UPGRADE.md).
Tools 2's update hook backfills disabled favicon defaults in active parent and
child configuration. For an older child missing source defaults/schema, the
native `emulsify_tools:repair-favicon-config CHILD_THEME` command completes that
migration. Both paths were verified in the disposable upgrade. The active
legacy child's Components 3.2 dependency and module-provided Twig APIs were
retained for compatibility; this dependency was not added to new installations.
The old generator's erroneous Tools `^4.0` child declaration was corrected to
its actual supported 1.x baseline before testing its upgrade to 2.x.

New theme generation and a real theme build are separate checks. Optional
PNG/ICO favicon work may need GD/Imagick; do not imply those optional workflows
are validated merely because Composer's hard platform requirements pass.

### Generated theme tooling

The project-locked Emulsify CLI moves from 1.12.2 to **2.4.1**. Setup uses that
local CLI to install stable **UI Kit 1.9.0**, with Emulsify
Core **4.5.0** and Vite **8.3.5**. The old DDEV hooks that installed global npm
and CLI versions are removed. This follows the current
[CLI workflow](https://github.com/emulsify-ds/emulsify-cli/tree/v2.4.1) and
[UI Kit source](https://github.com/emulsify-ds/emulsify-ui-kit/tree/1.9.0).

The generated starter initially has empty asset entry points. The setup helper
installs the kit, includes its real shared assets from the CLI's verified cache,
declares Drupal namespaces/libraries, and builds before enabling the theme or
reporting success. It removes the kit's old component-path override so Core 4
discovers foundations and layouts. Two UI Kit component definitions declare
the same name as both a prop and a slot, which Drupal 11.4 rejects. The helper
preserves the Twig slots and removes only those duplicate prop declarations and
required keys. Actual Drupal SDC discovery and inline/slot rendering passed.

Vite and the complete Storybook build passed. Real Page create/edit/save/render
returned HTTP 200, and all 66 linked CSS, JavaScript, and font assets returned
200. The read-only `ddev drush php:script devops/tests/verify-theme.php` check
exercises native component discovery and Link/Media Box slot rendering.

### Dropzone 5 to 6

The Composer package is supplied by the project's explicit package definition;
it is not available from a public Composer repository. The newest official
stable release is **6.3.5**, not the older 6.0 beta reported by stale cached
pages. Its official distribution preserves the global `Dropzone` bundle used
by Drupal DropzoneJS 2.11.

Dropzone 6 removes IE support, auto-discovery, legacy `extend`/`version` APIs,
and AMD bundles. Drupal's integration explicitly instantiates the global class
and uses retained APIs. The installer name is corrected to `dropzone`, so its
archive supplies `web/libraries/dropzone/dropzone-min.js` and
`web/libraries/dropzone/dropzone.css` where Drupal's library discovery expects
them. Chrome smoke checks verified the initialized Dropzone 6 widget, Media
Library modal, existing media selection, and thumbnail loading without
JavaScript errors. This was a widget/integration smoke test, not an exhaustive
upload-format or transport test.

## Composer Patches 1 to 2

Both existing patches are retained because their fixes are still absent from
the newest stable upstream source. Each applies to pristine source, fails a
reverse check before application, and passes the reverse check after applying.
The patch content is vendored locally with its provenance and SHA-256, avoiding
changes to a remote merge-request diff changing future installations.

| Package | Reviewed stable source | Decision and proof | SHA-256 |
| --- | --- | --- | --- |
| `drupal/embed` | 1.10.0 | Retain: its CKEditor 5 base class still declares the wrong `Drupal\entity_embed` namespace. [Issue 3517882](https://www.drupal.org/project/embed/issues/3517882). | `6d97c1444109ec13324cbba508ca726590a3ee60eb6d483a87d2ba29d9e90b0c` |
| `drupal/field_group` | 4.0.0 | Retain: its tabs script still lacks HTML5 validation handling and the required tabs-library dependency. [Issue 2969051](https://www.drupal.org/project/field_group/issues/2969051). | `eb3492900b6f1b8376cb290ddb43a0a0acdc278c3bd93db82078e1a1ff228a2b` |

The Patches 2 configuration moves under `extra.composer-patches`. It names
`patches/patches.json`, retains the Drupal core package-depth policy,
and removes the old `enable-patching`, `composer-exit-on-patch-failure`,
`patchLevel`, and top-level `patches-file` options. Patch definitions use the
expanded format with project-relative local paths, hashes, and depth 1.
The local patch files live in `patches/`. The Node patch and patch-package have
been removed, so that directory contains only the retained Composer patch
definitions and files. Their reviewed bytes, hashes and application depths are
unchanged. Composer Patches 2 regenerated the lock after updating their paths.

Follow the [official recommended workflow](https://docs.cweagans.net/composer-patches/usage/recommended-workflows/):
run `ddev composer patches-relock` after changing patch definitions and commit `patches.lock.json`
with the application's Composer lockfile. `ddev composer patches-repatch` deletes
and reinstalls patched dependencies, so use disposable copies for verification.
Run `ddev composer patches-doctor` and prove that a clean locked installation applies
the patches. The actual clean installation and Patches doctor passed with
GitPatcher, GitInitPatcher, and FreeformPatcher available and no HTTP patch URLs.
No patch has been removed merely because an issue is marked fixed
or because application failed.

## Coordinated Sous release plan

These are proposed release versions, not releases claimed to exist. No tag or
release is published by this work.
The subsequent [release setup review](RELEASES.md) records freshly fetched
remote tags and native version previews; use it for current proposed versions.

At this original review phase, the optional installer selected the public
Content Types `^1.0.3` line. It now uses the repaired `dev-main` branch in all
three content options because 1.0.3 lacks the Body model repairs. Paragraphs
and Paragraph Types retain explicit `dev-main` requirements
where the old global development stability already admitted their untagged
packages. The new offered Layout Builder option is bundled core configuration;
the old public package is not selected. That public package remains incompatible: its Gin Layout
Builder `^1.0@RC` selects 1.0.0-rc9, which requires Gin 3, whereas Admin requires
Gin 5. Gin 3.1.0 also excludes core 11.2+, so downgrading Gin would not support
core 11.4.11. Gin Layout Builder 2 beta requires Gin 4; the compatible
3.0.0-beta1 line requires Gin 5 but has **no stable release**. Adopting that
different beta line would introduce a new prerelease exception, which this task
prohibits. The conflict was reproduced with Composer; no dependency, security,
or platform check was bypassed. Core-native Layout Builder passed independently,
and the public Paragraphs recipes passed application and real browser authoring.
These optional branches must
be included in release coordination before their stable requirements can be
declared.

1. Merge and validate the integrated changes in Formats, Media, Admin, and
   Emulsify. Prepare their first stable **1.0.0** releases, honoring dependency
   order: Formats/Media before consumers that require them.
2. Once those tags actually exist, replace their temporary explicit development
   requirements with bounded stable caret ranges, such as `^1.0`. Do not point a
   production manifest at a fictional future tag.
3. Prepare a coordinated **Sous Content Types 2.0.0** only after its required
   recipe tags are available. Its current public stable release is 1.0.3; that
   release does not contain these unmerged repairs and upgraded constraints.
   The upgraded Tagify requirement drops older Drupal support, so this needs
   a breaking-change commit rather than a minor release.
4. Re-resolve the starter against the actual public recipe releases, remove
   any test-only path repositories, validate/audit it, and complete the clean
   installation and upgrade matrix before the next reviewed **8.0.0** starter
   release. Fresh remote verification found an existing 7.0.1 tag; the explicit
   setup modes and frontend/runtime migrations warrant a breaking-change commit.

Before those merges/tags, installing the recipe packages from public metadata
cannot reproduce unmerged local recipe changes. Disposable path-source
manifests tested the coordinated code. After native recipe unpacking, a
generated application manifest/lockfile containing public dependency references
was verified with a clean install. Its local
artifact is retained in `reference/dependency-validation/fresh`. No path
repository or local package reference was copied into production configuration.
The source-package release gate remains open until coordinated merges/releases
make those recipe changes available publicly.

## Lockfile and future updates

A generated application must commit its final `composer.json`, `composer.lock`,
and `patches.lock.json` after recipe dependency unpacking and generation finish.
The template has no tracked application lockfile. Its old untracked lockfile
recorded a prior installed state and was archived unchanged as
`reference/composer.lock.before-2026-10-25.json` before removing the stale local
file. The generated portable manifest, lockfile, patch lock, and required assets
are preserved under the ignored `reference/dependency-validation/fresh` local
validation directory.

**`composer install` reproduces the exact versions in the lockfile.** It does
not select future releases or upgrade dependencies. A deliberately reviewed
`composer update` changes that resolved set within the manifest's bounds.
Future major-version upgrades require another dependency review, explicit
constraint changes, migration/patch review, and the same validation matrix.
Caret ranges do not mean that future major versions are automatically adopted.

For an already generated site, run checks in its actual runtime:

```bash
ddev composer install --no-interaction
ddev composer check-platform-reqs
ddev composer audit --locked
```

Do not ignore platform requirements, disable security blocking, or add broad
prerelease allowances to make a resolution pass.

## Validation record

Destructive checks used disposable installations and separate database copies.
The existing-site fixture started from secure Drupal 11.3.20 with historical
Emulsify 6.1.1/Tools 1.0.1 and the pre-upgrade recipe source. The original
installed 11.3.5 was recorded for the version table; it was not installed by
bypassing security blocking. Matrix baselines were restored independently from
the same Minimal SQL snapshot.

| Check | Result |
| --- | --- |
| Every direct requirement reviewed against stable metadata and official notes | Completed |
| Composer manifest validation | Passed for all six source manifests with `validate --no-check-publish` |
| Newest compatible dependency dry run | Passed; 123 packages resolved without platform/security overrides |
| Patch proof against pristine stable source | Passed for both retained patches |
| Installation workflow regression checks | 18 passed, including recipe failure, settings preservation, Git tracking, and all three theme failure stages |
| Actual Composer post-create/default option 0 generation | Passed on 11.4.11; success banner/login and bootstrap JSON/file cleanup verified after success |
| Deliberately failed recipe application | Expected exit 1; no subsequent cache rebuild |
| Newly initialized application Git tracking | Passed on local `main`; manifest, lockfiles, custom theme YAML/Twig tracked; downloaded dependencies, generated SDC mirrors, and distribution assets ignored |
| Platform requirements inside DDEV | All 20 passed on actual PHP 8.4.20, including optional Paragraphs installation |
| Composer security audit | No security advisories; exit 1 because `oomphinc/composer-installers-extender` is abandoned |
| Clean installation from generated portable lockfile | Passed; 121 packages installed, lock bytes unchanged, existing local settings preserved, Field Group patch present |
| Patches doctor | Passed; patchers available and no HTTP patch URLs |
| Locked direct dependency outdated check | No newer stable direct package release omitted; current installer extender reported abandoned |
| Independent latest recipe baselines | Formats, Media, and Content Types passed; Media's three authoring forms built |
| Recipe combinations and matching configuration | Formats/Media/Content matching reapplications passed; Admin→Content and Content→Admin passed with usable Navigation blocks |
| Linkit and editor configuration | All four CKEditor/format pairs validated; Linkit metadata survived filtering; node and media links resolved on their applicable baselines |
| Page authoring/rendering | PHP form/entity/render checks and real authenticated HTTP create/edit/save/render passed; final fresh site used active Chef UID 2 |
| Composition | Native core Layout Builder and public Sous Paragraphs passed; Chrome confirmed editable Body/Paragraphs and both rendered after saving |
| Existing disposable-site upgrade | Database updates, cache rebuild, Entity Usage recreation, and reviewed configuration changes passed; existing content/media/taxonomy/files/aliases retained |
| Admin/editor/browser smoke | Chrome CKEditor 5, Tagify autocomplete, Media Library modal/thumbnail, Dropzone 6 widget, Entity Usage tab, and legacy frontend passed with no JavaScript errors |
| Generated Emulsify theme | Fresh Vite and full Storybook build passed; Page HTTP 200 and all 66 linked assets HTTP 200; native SDC discovery and inline/slot contracts passed |
| Legacy generated theme | Existing Webpack 5.112.2 build passed on Node 24.15.0; active legacy namespaces and Components APIs preserved |
| PHP/shell/Python syntax and whitespace | Passed for changed source files |
| CircleCI runtime configuration | Updated to PHP 8.4, Composer 2.10.3, and explicit Node 24; remote CI not executed |
| Public Sous Layout Builder package | Blocked by the documented Gin Layout Builder/Gin conflict; not represented by the native core Layout Builder pass |
| Public production recipe/lockfile release gate | Open until coordinated source releases exist |

The portable lockfile SHA-256 stayed
`9bc9e4ecc464c0cd11375535b9e9ef482786eb7415e351d471e1cc97154819db`
through the clean installation. Existing-site review found four expected
configuration changes: disabled favicon defaults in parent and child theme
settings, removal of obsolete `field.settings` purge configuration, and core's
`system.performance` gzip→compress key rename. Existing content IDs and Body
values were preserved. Entity Usage rebuilt 13 derived rows, including both
existing Page→Media references; the previous 15-row count reflects the older
tracking semantics and was not treated as a required identical count.

Full Admin reapplication retains its strict Gin import guard. It can reject
settings changed by the first application's own Sous actions, because imports
compare against Gin's upstream defaults before applying those actions. Full
Admin reapplication is therefore not generally idempotent.
The focused Navigation import accepts matching configuration; its missing
`system.menu.content` import was fixed and every configured Navigation plugin
was verified. Independent Formats has no Media bundle, so its Media-download
probe skips there and passes in the Content combinations that supply Media.

The legacy theme fixture needed native zlib development headers for optipng's
fallback compilation. A malformed Puppeteer Chrome download was handled with
native Chromium and its supported `PUPPETEER_EXECUTABLE_PATH` setting. Ordinary
npm install scripts still ran; none were skipped. The unchanged legacy Webpack
build and real browser checks then passed. Fresh theme generation used the
current Vite workflow and did not need that legacy installation workaround.

## Remaining advisories and package limitations

Composer returned `advisories: []`. Its audit exits 1 because
`oomphinc/composer-installers-extender` is abandoned. Version 2.0.1 is its newest
stable release, metadata names no replacement, and its custom asset installer
paths remain necessary. Login Destination's existing beta exception, untagged
Sous packages, and the incompatible public Layout Builder recipe are described
above. No stable direct Composer release was silently retained behind an older
major constraint.

### Historical npm audit, superseded by the frontend follow-up

The current [frontend review](FRONTEND_TOOLING.md#audit-results-and-remaining-exposure)
records root tools with two remaining bundle findings and the generated theme
with none. The following results preserve the preceding Composer-validation
phase.

The generated theme smoke test also ran **npm audit**, separately from the
Composer audit. Both npm audits exit 1. These are counts of affected packages,
not distinct advisory IDs:

| Installation | Moderate | High | Critical | Total |
| --- | --- | --- | --- | --- |
| Starter Node tooling | 4 | 13 | 4 | 21 |
| Fresh generated theme | 0 | 13 | 2 | 15 |

The critical shared chain is CLI 2.4.1 → `simple-git` 3.36.0 →
`@simple-git/argv-parser` 1.1.1, also reached through theme Core 4.5.0. Fixed
stable versions 4.0.2/2.0.1 exist but are excluded by the newest CLI's
`simple-git ^3.36.0` constraint. It needs an upstream CLI dependency migration;
no unreviewed override was introduced. See the upstream
[Git configuration execution](https://github.com/advisories/GHSA-g4wm-2vf7-vfgr),
[option abbreviation](https://github.com/advisories/GHSA-858h-whjf-mvg5), and
[editor detection](https://github.com/advisories/GHSA-v5rq-49vh-5v5c) advisories.

Starter release tooling also contains critical `handlebars` 4.7.9 and bundled
npm's `tar` 7.5.11. Stable fixes 4.7.10/7.5.22 exist; Handlebars is range
compatible, while bundled npm needs a lockfile/dependency review. See the
[Handlebars injection](https://github.com/advisories/GHSA-p8wg-vrv2-v86f) and
[tar recursion](https://github.com/advisories/GHSA-r292-9mhp-454m) advisories.
A broad npm security update was not performed as part of the Composer upgrade.

The other affected starter packages are `@emulsify/cli`, `@humanfs/node`,
`@sigstore/core`, `@sigstore/verify`, `brace-expansion`, `fast-uri`,
`http-cache-semantics`, `ip-address`, `js-yaml`, `lodash-es`, `npm`, `pacote`,
`picomatch`, `postcss-selector-parser`, `sigstore`, `tmp`, and `undici`.
The theme additionally reports `@emulsify/core`, `@puppeteer/browsers`,
`basic-ftp`, `chromium-bidi`, `extract-zip`, `get-uri`, `pa11y`,
`pac-proxy-agent`, `proxy-agent`, `puppeteer`, `puppeteer-core`, and `zod`.
The local validation archive retains complete advisory URLs, ranges, dependency
paths, and available fixes in `source-root-npm-audit.json`,
`fresh-theme-npm-audit.json`, and `sous-generated-theme-upgrade-findings.json`
under `reference/dependency-validation`. These ignored local artifacts are
evidence from this run, not required files in a newly generated site.

## Files and repositories changed

These changes include the installation hardening and recipe repairs that
preceded the dependency upgrade. Unrelated `.sfdx/` files were preserved.

| Repository | Changed files and purpose |
| --- | --- |
| `sous-drupal-project` | `composer.json`, `patches.lock.json`, `composer-patches/*` (replaces `patches/composer.patches.json`), `package.json`, `package-lock.json`: stable constraints, plugin/patch migration, and local CLI. `.ddev/config.yaml`, `.ddev/commands/web/{emulsify,install-recipe}`, `.circleci/config.yml`: reviewed runtimes and canonical recipe/CLI entry points. `.gitignore`: track application Composer files/custom themes while excluding downloaded/generated files. `devops/scripts/{setup,sous-setup,sous-build,install-recipe,import-data,rebuild,confim,theme-build}.sh`: explicit fresh/restore workflows and error propagation. New `theme-setup.sh`/`theme-integrate.php`: native kit setup and build integration. `devops/tests/install-workflows.py`, `devops/tests/verify-theme.php`: focused regressions. `README.md`, `PROJECT-README.md`, `docs/{SETUP,BUILD_TOOLS,DEPENDENCY_UPGRADE}.md`: current workflows, runtimes, migration/results record. |
| `sous-admin` | `composer.json`: latest stable dependencies and narrowed existing beta. `recipe.yml`: import Navigation's Content menu. `tests/verify-navigation.php`, `README.md`: navigation/baseline checks. |
| `sous-content-types` | `composer.json`, `recipe.yml`: explicit module/recipe dependencies and metadata-aware matching. `config/core.entity_{form,view}_display.node.page.*.yml`, `config/field.{field,storage}.node.*.yml`, `config/taxonomy.vocabulary.tags.yml`: remove exported environment metadata while preserving nested plugin identifiers. New Body field configuration and `config/metatag.metatag_defaults.node.yml`: useful Page authoring/rendering and declared SEO defaults. `tests/*`, `README.md`: Page, HTTP, composition checks and supported baselines. |
| `sous-formats` | `composer.json`, `recipe.yml`: declared dependencies and matching policy. `config/editor.editor.*.yml`, `config/filter.format.{basic_html,full_html}.yml`, `config/linkit.linkit_profile.default.yml`: valid CKEditor/filter combinations, Linkit attributes/processing, metadata cleanup. `tests/*`, `README.md`: runtime filtering/link-resolution checks and independent baseline. |
| `sous-media` | `composer.json`, `recipe.yml`: Field Group and current stable requirements, explicit dependencies/matching. `config/core.entity_form_display.media.{file,image,video}.default.yml`, `config/media.settings.yml`, `config/taxonomy.vocabulary.media_tags.yml`: metadata/dependency cleanup. Removed duplicated `config/image.style.media_library.yml` in favor of core's supplied configuration. `README.md`: dependencies and independent baseline. |
| `sous-emulsify` | `composer.json`: current Emulsify/Tools stable lines. `README.md`: independent baseline and supported recipe command. |
