# Recipe defaults and site maintenance

The later starter simplification replaces maintainer Python entry points with shell and excludes product integration/tests from generated applications. Historical validation/file lists below retain their original scope. Current commands are in the setup/build/integration guides.

Sous recipes provide reusable starting configuration. A generated Drupal site
owns its packages, lockfiles, custom code/theme, database, and **complete**
configuration export after those recipes are applied. Recipe files are not the
site's configuration sync directory. Downloading a newer recipe does not apply
its configuration to an existing site: Drupal recipes are applied operations,
not installed extensions with an update lifecycle. See the official
[recipe application guide](https://www.drupal.org/docs/extending-drupal/drupal-recipes/how-to-download-and-apply-drupal-recipes).

## Ownership

| Layer | Reusable responsibility |
| --- | --- |
| Starter | Scaffold/setup, runtime environment policy, installer choices and initially empty site sync directory |
| Sous Admin | Gin, Navigation, administration defaults, native management Views, the `superuser` role, and the offered editorial/operations conveniences |
| Sous Emulsify | Theme dependencies and the default frontend theme; no roles or user assignments |
| Sous Formats | Text formats/editor defaults and the shared Linkit profile subrecipe |
| Sous Content Types | Page and Frontpage fields, forms, displays, metadata, teasers and title-based URL aliases; Page tags |
| Sous Media | Media bundles/fields/displays and imports of native module defaults |
| Sous Paragraph Types | Reusable Paragraph types, fields, displays, and portable icon assets |
| Sous Paragraphs | Optional Paragraphs composition and host fields/displays on Page and Frontpage |
| Sous Core Layout Builder | Optional native layouts and per-node overrides on both content types |
| Sous Config Split | Optional native `_splits`, local and staging definition defaults |
| Generated site | Complete exported configuration, site-specific overrides, content, accounts/assignments, and explicit adoption of future changes |

All nine recipe packages live in
[Sous Drupal Packages](https://github.com/fourkitchens/sous-drupal-packages).
The seven existing Composer names remain unchanged; Core Layout Builder and
Config Split are new independent packages. Each downloads as its own ZIP.
Common packages require neither optional content composition. The Paragraphs
host composes Content Types and Paragraph Types; Core Layout Builder composes
Content Types without any Paragraphs dependency.

Admin and Formats share `sous-formats/recipes/linkit`, rather than independently
writing the default profile. Both include it using Drupal's native
`recipes/sous-formats/recipes/linkit` root-relative path. The supported layout
places Composer-managed recipes in **`web/recipes`**, as configured by the starter and
shown in the recipe READMEs. Other installation layouts must adapt those
includes in a project-owned copy before applying them; they are not a
tested baseline. Admin requires the Formats package for this subrecipe, but
does not apply its text formats or CKEditor configuration.

The shared profile supplies node, media, and external matchers only when the
profile is missing. Existing profile UUIDs, matcher choices, and customized
settings win. Adding a media matcher to an older Admin profile is an explicit
site change through Linkit's profile UI or reviewed project configuration,
followed by export; reapplying the subrecipe will not add it automatically.

Other overlap is intentional: Admin sets `system.theme:admin` while Emulsify
sets `system.theme:default`. Media and Formats can both import native media
view modes so they work on their documented baselines. Media's redundant local
copies of `media.full` and `media.media_library` were removed only after proving
they match the selected Drupal 11.4.11 sources; native module imports retain
the modes.

## The sync directory belongs to the site

The configured path remains **`config/default`** for compatibility. The starter's
129 legacy YAML files were a partial historic export without `core.extension.yml`
or `system.site.yml`. Removing that partial export must retain the starter's
useful defaults: Content Types supplies Frontpage and Page/Frontpage aliases;
Admin imports current native Views. Recipe/module providers retain Page, media,
Paragraph types and Superuser. See the complete [configuration audit](CONFIGURATION_AUDIT.md).
The source sync directory contains only a marker and ownership README.

Fresh installation runs native configuration export after its recipes, theme,
and administrator setup, before success or bootstrap cleanup. Commit that
complete export, including `core.extension.yml`, `system.site.yml`, and the
site's configuration UUIDs. **Keep UUIDs in site exports**; removing exported
environment metadata applies to reusable recipe defaults, not site sync files.
An existing generated site keeps its own full export when adopting starter
changes. Do not copy the template's deletions over that site's configuration.

Composer initializes `settings.php` from the fully scaffolded core
`default.settings.php` only when it is missing. This ensures a new DDEV project
uses the project's sync path and loads local settings, rather than relying on
DDEV's embedded settings template. Composer preserves existing `settings.php`
and `settings.local.php` files, including symlinks. Verify an older
site's actual directory with `ddev drush status --field=config-sync`; projects
can retain a deliberately configured alternative tracked directory. If an old
site uses DDEV's public-files fallback or does not load local settings, explicitly
review its project-owned `settings.php`, configure the intended sync path and
local include, then export there. The new hook does not rewrite existing
settings or migrate a site's path automatically.

Project-specific fields, displays, filters, and enabled-module choices belong
in that complete export. Environment-specific values and credentials belong in
the project's settings/environment integration or ignored local settings, using
Drupal's native configuration overrides. Those runtime overrides are separate
from active stored defaults and are not copied into recipe YAML. See Drupal's
[configuration override system](https://www.drupal.org/docs/drupal-apis/configuration-api/configuration-override-system).

For an older site with only the partial template files, first restore its real
database in a disposable/local copy and run:

```sh
ddev drush config:export --yes
git diff -- config/default
```

Review and commit the complete result before importing or deploying it to a
copy of the **same site**. Preserve its UUID; a fresh unrelated site is not a
substitute for that database. Configuration export replaces the configured
sync contents, so keep backups and review deletions. Drupal documents the
[export/import workflow](https://www.drupal.org/docs/administering-a-drupal-site/configuration-management/workflow-using-drush).

The `confex` helper checks and exports through the site's configured sync
directory and native transforms. `confim` checks source configuration, uses
native `drush deploy` for database updates, import, cache work and deployment
hooks, then checks the active state. The [environment contract](ENVIRONMENTS.md)
defines local/staging/production policy, guarded commands, and explicit
adoption for existing settings files. The preserved Pantheon helper
`devops/ci/drush-config-import SITE ENV` invokes that command on the selected
remote site's own configured directory. It does not search the caller's files
to decide whether the remote site should import. No remote deployment was
performed while changing these helpers.

Config Split is optional. The `fourkitchens/sous-config-split` package supplies
inactive local/staging definitions only when explicitly selected; sites
own its package, lists and exported split files. Ordinary configuration commands
also work without it or its directories. The active `_splits` bootstrap routes
definitions into `config/_splits` through native Config Split transforms;
there is no unconditional partial import or broad Config Ignore policy. Follow the
[environment guide](ENVIRONMENTS.md#optional-moduleconfiguration-differences)
for adoption. Existing `settings.local.php` files remain untouched by Composer.

## Existing-site update workflow

1. Create a project branch and back up its database/files. Test on a disposable
   copy of the existing site. Keep its complete committed export and inspect
   outstanding active-configuration changes before updating packages.
2. Review official release notes, platform requirements, patches, and advisories.
   Edit bounded Composer constraints when adopting a new release line, keep
   coupled core packages aligned, and resolve the reviewed packages and their
   compatible dependencies, for example:

   ```sh
   ddev composer update vendor/package --with-all-dependencies
   ddev composer validate --no-check-publish
   ddev composer check-platform-reqs
   ddev composer audit
   ```

   Replace the example package name. Commit the resulting manifest/lockfile.
   This repository's [dependency review](DEPENDENCY_UPGRADE.md) records current
   historical constraints, patch migrations and advisories. For the shared collection,
   keep selected Sous requirements/locks and the root's exact nine-name native
   recipe-unpack exclusions. A clean locked install restores their files from
   independent ZIPs; downloaded recipe directories stay ignored. Other recipes
   retain normal native unpack behavior. The generated manifest is the authority
   for subsequent package updates.
3. On that development copy, run database updates and rebuild caches:

   ```sh
   ddev drush updatedb --yes
   ddev drush cache:rebuild
   ```

   Core and module update hooks maintain their schema/configuration. They do
   not turn a changed recipe into an automatic configuration update.
4. Adopt desired recipe changes explicitly. Compare the new defaults and
   release notes with the project's existing fields, displays, permissions,
   and overrides. Use Drupal's UI, a narrowly scoped native migration recipe,
   or project-owned update/deploy hooks when code/data changes require them.
   Review the resulting active configuration and export it. Do not routinely
   reapply an entire recipe: strict defaults can reject customized config, and
   configuration actions can change already customized values.
5. Test content, editor/admin access, integrations, assets, and configuration
   import against a database copy. Review the entire configuration diff, then
   commit project-owned changes. Module removal must use native uninstall
   validation first; deleting a package while its fields/configuration still
   depend on it is not an opt-out mechanism.
6. Build from committed locks and deploy the site's own configuration with the
   usual reviewed deployment process:

   ```sh
   ddev composer install
   ddev npm ci --include=dev
   ddev npm run theme-ci
   ddev npm run theme-build
   ddev drush deploy --yes
   ```

   These are local-copy commands, not authorization to deploy production.
   The existing [`rebuild` workflow](BUILD_TOOLS.md) restores a database backup;
   it is for local restoration, not routine production maintenance. Native
   [Drush deploy](https://www.drush.org/13.x/deploycommand/) already supports
   update, post-update, and deployment hooks. No Sous update registry, replay
   service, custom migration framework, or automatic package-update hook is
   needed for the demonstrated changes.

`composer install` reproduces the committed application lockfile; `npm ci`
reproduces npm lockfiles. Neither requests future major upgrades. Those require
another reviewed dependency update. Never replace a site's complete export
with a recipe's partial `config` directory or a new template's sync directory.

## Administrator-role migration

`superuser` now belongs to Sous Admin. Its default file is moved unchanged from
Emulsify, retaining the same role ID and `is_admin: true`. Existing active roles
and user assignments are not removed when the recipe source file moves. Admin
preserves a matching or customized existing role; it does not silently promote
an explicit-permission role to an all-permissions administrator. Theme-only
installations now obtain authorization from their site's chosen base layer.

An existing site that already has the role needs no role rewrite: keep its role
and assignments, verify a real active administrator account other than UID 1,
and export. For a site missing the role, review its authorization policy and
create the appropriate role through Drupal, then export the application-owned
configuration. The package collection provides fresh-install defaults rather
than an existing-site migration recipe. If a trusted administrator needs
membership, assign it deliberately through Drupal
or `ddev drush user:role:add superuser ACCOUNT_NAME`. User assignments are
database data, not configuration export. Carry any assignment change through
the project's normal account-management process for each environment.
Changing an existing role's privilege policy requires a separate reviewed site
decision.

## Compatibility and releases

The supported starter/runtime baseline is defined in [Platform](PLATFORM.md).
The compact product suite covers Minimal plus default Page, Paragraphs, native
Layout Builder, and admin-only installations. Independent recipe baselines and
ordering requirements are listed in each recipe README; installed recipe
copies may differ from `sous-drupal-packages/packages/*` source.

Published tags are immutable. A recipe release describes reusable defaults and
package requirements for new applications; release notes must separately state
any explicit adoption steps for existing sites. Changing a required major
dependency, removing an offered capability, or changing ownership assumptions
requires a breaking-change note and appropriate SemVer treatment. The theme's
authorization removal and Admin's new shared Formats dependency are such
compatibility changes, even though an existing active role is retained.

The starter targets `^2.0` through the scoped static Composer index. The
collection starts at `2.0.0` and publishes shared semantic releases from `main`.
Candidate integration against an explicit reviewed commit is validation only;
verify the published packages and index before the starter release. Retain
historical repositories/tags/download URLs for older locks. Existing sites
review customized recipe folders before Composer takes ownership and adopt
configuration changes explicitly. Review the [release sequence](RELEASES.md).

Keep the scoped index restricted to the nine IDs. Its canonical priority does
not fall through to legacy metadata when an incompatible version is requested.
Remove contributor path overrides and re-resolve from published ZIPs before
releasing an application. `composer update --lock` alone does not replace
locked path distributions. Package updates still do not replay configuration.

## Verification and changed files

The following is historical ownership-review evidence, before the monorepo
cutover. Current release-branch acceptance and publication readiness are
reported in [Integration](INTEGRATION.md); these receipts do not prove a new
revision passed.

The final clean-source entry point completed **93 native commands**, exited
**0**, and passed all four options on Drupal **11.4.11**, PHP **8.4.20**, Node
**24.22.1**, npm **11.21.1**, and Composer **2.10.3**. All source changes were
tested together; the external Paragraphs companion's clean checkout matched a
fresh remote-head query. No developer database or installed dependency tree
was an input. Work remains uncommitted on `codex/config-ownership` in all seven
repositories, preserving the preceding changes and unrelated files.

| Check actually performed | Result |
| --- | --- |
| Fresh theme-only application | No authorization role created |
| Fresh Admin and role member other than UID 1 | Role supplied; administration, user management, and permission routes accessible |
| Existing customized administrator role | Exact configuration/UUID, assignments, active status, and administrator access preserved through Admin/Emulsify application, repeat targeted migration, export, and native deployment |
| Explicit missing-role migration | Creates a missing role; repeat application and an existing customized explicit-permission role remain unchanged; account access checked |
| Shared Linkit ownership | Canonical node/media/external matchers in fresh Formats-before-Admin; existing custom matcher settings, UUID, and omitted media matcher preserved through Admin-before-Formats |
| Initial settings scaffolding | Settings initialized once from final core defaults; existing files/symlinks retained; native target loads preserved local sentinel and uses tracked `config/default` |
| Installer and configuration helpers | 32 installer/scaffold regressions and six helper regressions passed inside the selected runtime |
| Composer and recipes | All seven manifests, nested recipe/migration YAML, dependency closure, platform requirements, native recipe unpacking, and portable final lock passed |
| Clean generated locked install | Downloaded dependencies deleted/reinstalled; lock bytes and existing local settings preserved |
| Default, Paragraphs, core Layout Builder, admin-only | All passed their runtime/content/composition checks from separate database snapshots |
| Project-owned configuration | Complete exports include `core.extension.yml` and `system.site.yml`; configured path asserted; deliberate mutation restored by native Drush deploy; all active collections unchanged afterward |
| Authoring and frontend | Verified HTTPS Page create/edit/save/render, production assets, Storybook, lint, SDC/source-migration checks, and native CLI/release regressions passed |
| Security checks | Zero Composer security advisories; existing abandoned installer remains visible. Two affected bundled npm tooling packages remain under the narrow reviewed policy. Generated-theme audit has zero findings |
| Cleanup | All runner/probe projects, containers, databases, and temporary workspaces removed with ownership checks; developer sites untouched |
| Remote Pantheon deploy, live GitHub run, publishing | Not performed |

An earlier full run exposed DDEV's untracked public-files sync fallback. It is
retained as a diagnostic, not ownership proof. The corrected final run asserts
both the tracked directory and actual loading of preserved local settings.
Two existing transient Layout Builder field-block warnings remain visible;
fresh plugin discovery, default/overridden rendering, and native deployment
pass after cache rebuilding. Other recipe installation layouts and production
deployments are not claimed as tested. Remote CI rollout/release cutover remains
as documented in [integration checks](INTEGRATION.md).

| Repository | Files changed for this ownership follow-up |
| --- | --- |
| Starter | Removed the 129 traced `config/default/*.yml` files; retained `.gitkeep` and added its ownership README. Updated `composer.json` first-scaffold settings initialization, local scaffold additions, `devops/scripts/{sous-build,confim,confex}.sh`, the retained `devops/ci/drush-config-import`, integration runner/validators, installer regressions, and new `devops/tests/config-workflows.py`. Updated source/generated READMEs and setup/build/features/deployment/integration/release docs; added this guide |
| Admin | Added the unchanged `config/user.role.superuser.yml`, `migrations/2026-10-superuser-role/{recipe.yml,README.md}`, and `tests/verify-authorization.php`; updated `composer.json`, `recipe.yml`, and README for shared Linkit ownership, module classification, and migration policy |
| Emulsify | Removed `config/user.role.superuser.yml`; updated README to describe theme-only responsibility and existing-role preservation |
| Formats | Moved the canonical Linkit profile into `recipes/linkit/config`, added its native `recipe.yml` and ownership probe, and updated the parent recipe/README |
| Media | Removed two proved-identical `config/core.entity_view_mode.media.{full,media_library}.yml` copies; retained existing native imports; updated README |
| Content Types and Paragraph Types | Updated READMEs with maintenance ownership and the current native Layout Builder option |

Local receipts are retained under `reference/config-ownership/`: legacy
consumer/file hashes, exact native media comparison, remote heads, settings
diagnostics/probes, and `clean-run/result.json` with raw command logs, source
digests, generated locks, final entry-point status, and cleanup evidence. These
ignored receipts are evidence, not inputs required by a clean checkout. No
source commit, push, tag, release, remote policy change, or deployment was made.
`final-results.json` and `cleanup.json` summarize that run and removal of
all four owned diagnostic/probe/integration projects and volumes.
The subsequent documentation/real-command verification is recorded in
[documentation verification](DOCUMENTATION_VERIFICATION.md).
