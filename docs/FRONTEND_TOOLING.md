# Frontend tooling review

This records earlier frontend review evidence. The 2.x package cutover uses
the two-repository ownership in [Maintenance](MAINTENANCE.md); current commands
and acceptance results are in [Build tools](BUILD_TOOLS.md) and
[Integration](INTEGRATION.md). Historical passing runs below do not attest that
a later generated theme passed unchanged.

## Current simplified implementation

Use [Build tools](BUILD_TOOLS.md) for operating commands. The current source
removes the local CLI patch, patch-package, and theme-integrate.php. Setup uses
native Emulsify installation, npm lockfiles and production builds. It does not
copy assets from a cache, migrate SCSS, rewrite component schemas/namespaces or
attach assumed global styles.

CLI 2.4.1 remains the latest stable checked against official npm metadata and
release notes. Its secure simple-git 4 override is retained. Native generation,
clone/init and cold-cache recovery pass; moving-reference refresh remains an
upstream compatibility limitation. Use the native cache-clear command documented
in [Build tools](BUILD_TOOLS.md) when deliberately fetching a changed branch.

Two scoped security overrides are set using native npm configuration in the
generated theme: simple-git ^4.0.2 and Pa11y ^10.0.0. The unmodified dependency
set reported 15 vulnerabilities; these reviewed overrides reduce its audit to
zero without adding a local source patch. Root npm retains its two previously
reviewed bundled-tooling findings.

Actual selected-runtime checks passed theme generation, native UI Kit install,
production build (24 CSS and seven JavaScript assets), Drupal theme enablement,
root lockfile engine checks and the unpatched CLI probe. Stock UI Kit 1.9 still
fails Storybook for missing imported example SVG assets, SCSS lint (82 errors),
JavaScript lint that scans compiled outputs, and formatting checks (13 files).
These remain strict integration failures. Production build-assets now builds
production assets; Storybook is an explicit separate task. No release or
published artifact is claimed while the full integration gate fails.

## Historical implementation review

The evidence below records the earlier implementation. Its Python commands,
CLI patch, patch-package, custom theme helper/migration and copied assets have
since been retired; they are not current setup requirements or passing claims
for the simplified implementation.

This is implementation evidence, not a second setup manual. Use
[Platform](PLATFORM.md) for the current runtime/Emulsify selection,
[Build tools](BUILD_TOOLS.md) for supported commands, and
[Operations](OPERATIONS.md) for ongoing review ownership and cadence.

The [shared integration pipeline](INTEGRATION.md) now runs the locked toolchain,
project theme generation, production build, lint, Storybook, and dependency
security policy from clean source snapshots. The implementation-phase results
below retain their original scope.

The [Paragraph Types follow-up](PARAGRAPH_TYPES.md) covers the subsequently
added recipe checkout and its authoring/rendering integration. It has release
tooling but no independent frontend asset pipeline.

The subsequent [release setup review](RELEASES.md) adds the Conventional
Commits preset and a compatible writer 9 override across all Sous repositories,
refreshes release lockfiles, and makes GitHub Actions the sole publisher. Use
that review for current release configuration and version previews; counts
and checks below record the frontend implementation phase.

Reviewed on **25 October 2026**, following the
[Composer dependency upgrade](DEPENDENCY_UPGRADE.md). This follow-up makes
frontend installation reproducible, fixes the vulnerable CLI/browser-tooling
chains, and separates starter checks from generated-theme checks. No release,
tag, or deployment was published.

### Runtime and installation

The selected runtime is **Node 24.22.1 and npm 11.21.1**. `.nvmrc`, DDEV,
generated themes, and CI agree; `package.json` declares Node `^24.22.1` and
`packageManager: npm@11.21.1`.

DDEV's `Dockerfile.npm` installs Node/npm once while building the web image.
DDEV 1.25 otherwise re-extracts Node's bundled npm at startup, so that image
clears `N_INSTALL_VERSION` after installing the reviewed runtime under
`/usr/local`. Browser libraries are native image packages; the old startup
multiarch package-install hooks are removed. Node/release CI jobs select the
same Node release and install the reviewed npm once per job. JavaScript checks
run in the Node image; PHP syntax checks run in the PHP 8.4 image. Runtime rebuild/restart validation
is recorded below.

Locked applications use `ddev npm ci --include=dev` and `ddev npm run theme-ci`. Initial theme
generation uses `npm install --include=dev` only to create its first lockfile; commit that
lockfile with the theme manifest and patch files. Routine asset builds, watch,
lint, and Storybook commands use installed dependencies. The complete
`ddev composer build-assets` pipeline installs root/theme Node dependencies
once each and invokes the native Storybook task, which also builds production
assets directly into the requested output locations.

Build/bootstrap/restore installations explicitly include Node development
dependencies even under `NODE_ENV=production`: the CLI, patcher, compiler, and
linters are required during the build. PHP's production Composer installation
still uses `--no-dev`; Node tooling is separate from the compiled browser output.

Existing-site restoration also installs the locked root/theme tools and builds
assets before importing the backup. This makes a generated project's fresh
checkout usable and prevents an asset failure from starting database import.

See [Build Tools](BUILD_TOOLS.md) for commands and
[Local Setup](SETUP.md) for fresh/restore workflows. For host-only starter
checks, use the Node version in `.nvmrc` and the declared npm version before
running `npm ci --include=dev` and `npm run lint:starter`.

### Root dependency versions

“Before” records the lockfile entering this follow-up, rather than the source
checkout's stale `node_modules`. All successful dependency checks used clean
temporary installations. Existing source dependencies were preserved on disk.
Every direct tool was checked against current npm metadata; ordinary stable
dependencies use bounded caret constraints.

| Direct tool | Before locked | Selected stable / constraint |
| --- | --- | --- |
| `@commitlint/cli` | 20.5.0 | 21.2.4 / `^21.2.4` |
| `@commitlint/config-conventional` | 20.5.0 | 21.2.4 / `^21.2.4` |
| `@emulsify/cli` | 2.4.1 | 2.4.1 / `^2.4.1` |
| `@eslint/js` | Added direct requirement | 10.0.1 / `^10.0.1` |
| `@semantic-release/commit-analyzer` | 13.0.1 | 13.0.1 / `^13.0.1` |
| `@semantic-release/github` | 12.0.6 | 12.0.10 / `^12.0.10` |
| `@semantic-release/release-notes-generator` | 14.1.0 | 14.1.1 / `^14.1.1` |
| `@simple-git/argv-parser` | 1.1.1, previously transitive | 2.0.1 / `^2.0.1` |
| `eslint` | 10.1.0 | 10.13.0 / `^10.13.0` |
| `globals` | Added direct requirement | 17.13.0 / `^17.13.0` |
| `husky` | 9.1.7 | 9.1.7 / `^9.1.7` |
| `lint-staged` | 16.4.0 | 17.6.0 / `^17.6.0` |
| `patch-package` | 8.0.1 | 8.0.1 / `^8.0.1` |
| `semantic-release` | 25.0.3 | 25.0.9 / `^25.0.9` |

Compatible transitives were refreshed with native `npm update`, without force,
ignored scripts, or peer/platform bypasses. The release npm plugin moved from
13.1.5 to **13.2.0**, whose
[release notes](https://github.com/semantic-release/npm/releases/tag/v13.2.0)
add CircleCI trusted publishing. The final lock contains 645 dependency entries.
`npm outdated` reports no newer direct tool release.

[Commitlint 21](https://github.com/conventional-changelog/commitlint/blob/v21.2.4/CHANGELOG.md)
drops Node 18/20, and
[lint-staged 17](https://github.com/lint-staged/lint-staged/blob/v17.6.0/MIGRATION.md)
requires Node 22.22.1+ and Git 2.32+. Its YAML configuration dependency is
optional; this project has no lint-staged YAML configuration needing that
addition. The selected Node runtime supports both migrations. Valid and invalid
commit messages and real release analyzer/note-generator APIs were tested.

### CLI security migration

`ddev emulsify` always executes the root project's locked CLI 2.4.1. Emulsify
Core 4 uses a separate theme-local SDK dependency on that same CLI version.
Generated themes carry their own manifest, lockfile, parser dependency, and
compatibility patch, so ordinary standalone theme installation remains usable.

The latest CLI still constrains `simple-git` to 3.x. A scoped override selects
fixed **4.0.2**, with parser **2.0.1**. The
[upstream major migration](https://github.com/steveukx/git-js/blob/main/simple-git/CHANGELOG.md)
requires named exports and rejects unsafe Git options/environment overrides.
CLI 2.4.1 already uses the named export, but its remote-cache lookup explicitly
re-passes the whole environment. Under version 4 that throws, and the CLI catches
it as an unavailable remote, allowing stale cache reuse.

The small `patches/@emulsify+cli+2.4.1.patch` uses the upstream parser's guard
predicate and explicitly allows only `GIT_TERMINAL_PROMPT=0` for that lookup.
It preserves ordinary environment values, including `PATH` and `SSH_AUTH_SOCK`.
Custom `GIT_SSH_COMMAND`, editor, pager, and other guarded overrides are removed;
standard SSH configuration and agent access remain available. No `allowUnsafe`
flag is enabled. The patch is copied into generated themes and applied with
`patch-package --error-on-fail` during normal installation.

The focused native CLI regression verifies a real clone, moves a local branch,
and proves cache refresh obtains the new commit. It also checks mixed-case
guarded variables and runs CLI initialization through clone, dependency
installation, and starter Git cleanup. The unpatched version fails the probe.

### Generated theme and lint behavior

Core **4.5.0**, CLI **2.4.1**, UI Kit **1.9.0**, and Drupal Emulsify **7.2.1**
retain their selected stable versions. The scoped Core override upgrades
**Pa11y 9.1.1 to 10.0.0**, resolving its browser-tooling chain with Puppeteer and
Puppeteer Core **25.16.0**, browser installer **3.2.6**, Chromium BiDi
**157.0.8090-0**, and Zod **4.6.7**. Pa11y 10's
[release notes](https://github.com/pa11y/pa11y/releases/tag/10.0.0) and
[Puppeteer requirements](https://pptr.dev/guides/system-requirements)
were reviewed; Core uses the retained public `pa11y(url, options)` Promise API.
Native browser/API checks are recorded below.

The theme uses its own Core configuration: ESLint **9.39.5**, Stylelint
**17.16.0**, Sass **1.106.1**, and Storybook **10.6.3**. Root ESLint is **10.13.0**.
The theme cannot adopt ESLint 10 with its current native integration because
`eslint-plugin-import` 2.32.0 and `@babel/eslint-parser` 7.29.7 declare peers
through ESLint 9. Their peer requirements were preserved.

Starter `lint` works without a generated site; naming setup switches a generated
application to `lint:project`, which also requires its theme checks. PHP checks
use native syntax validation. Theme checks retain Core's own lint rules and
exclude only dependencies/output/generated component mirrors. Aggregate lint
and formatting scripts stop dependent work on failure; Prettier uses `--check`
for the check task, with separate fix commands.

Initial UI Kit source normalization uses `postcss-scss` **4.0.9** and its native
SCSS parser/stringifier. Removing obsolete wrappers produced byte-identical
compiled CSS across 29 checked files, including SDC mirrors. Deprecated
word-break declarations were replaced with the documented equivalent, and the
visually-hidden helper uses modern clipping. Actual Chrome checks confirmed
equal wrapping and hidden text remaining in the accessibility tree. Formatting
runs once during theme setup; ordinary builds do not rewrite source.

### Audit results and remaining exposure

| Locked installation | Before | After |
| --- | --- | --- |
| Root tools | 21 affected packages: 4 moderate, 13 high, 4 critical | 2: 1 moderate, 1 high, 0 critical; audit exits 1 |
| Generated theme | 15 affected packages: 13 high, 2 critical | 0; audit exits 0 |

The two remaining root findings belong to npm's published bundle, reached
through release tooling and also present in the selected npm installer:

| Bundled package | Severity / fixed release | Advisory |
| --- | --- | --- |
| `ip-address` 10.7.2 | Moderate; fixed in 10.7.4+, latest 10.7.5 | [Zone identifier complexity](https://github.com/advisories/GHSA-rxp7-6hxx-x8q5) |
| `undici` 6.29.0 | High; fixed in 6.29.1 | [EventSource memory](https://github.com/advisories/GHSA-4qv8-2p9j-2685), [content-encoding errors](https://github.com/advisories/GHSA-xjj6-f33q-929c) |

Published npm **11.21.1** and newest stable **12.2.1** tarballs both contain those
copies. Consumer overrides were tested with normal install and clean `npm ci`;
they cannot replace these bundled dependencies. They were removed from the
manifest after proving ineffective. An upstream npm bundle refresh is required.
The release plugin's npm `^11.6.2` requirement also excludes npm 12. No bundled
distribution was hand-edited or advisory hidden.

Compared independently, Node's bundled npm **11.19.1** has five affected
packages, including three high findings. Pinning npm **11.21.1** removes the
additional `brace-expansion`, `http-cache-semantics`, and selector-parser
findings. Both npm bundles already contain fixed `tar` 7.5.22.

The remaining findings affect installer/build/release processes. The original
CLI Git chain and Pa11y/Puppeteer chain also execute as Node tooling; Drupal
production assets are compiled UI Kit CSS and browser behaviors. Storybook is
a separate static preview output. Final module-graph verification
is recorded below.

### Validation record

All application/database checks used disposable fixtures. The root source's
existing `node_modules` was preserved.

| Check | Actual result |
| --- | --- |
| Latest direct metadata and bounded compatible transitive update | Completed; no newer direct release omitted |
| Clean final root `npm ci` on Node 24.22.1/npm 11.21.1 | Passed; 502 packages installed, 646 audited, CLI patch applied |
| Root lockfile reproduction | Passed; SHA-256 unchanged: `c9ada85e3aed89e63a03fdea6f63b46ed9f0ecfa9dbbc92799e1db7d2b2fb5c9` |
| CLI guards/cache refresh/native initialization | Passed; unpatched probe fails and patched probe passes |
| Deliberately invalid CLI patch | Expected exit 1 with fail-fast patch application |
| Commitlint valid/invalid messages | Expected exits 0/1 |
| Release analyzer/notes/GitHub plugin APIs | Passed without publishing |
| Starter lint regressions | 9 passed; full starter JavaScript/PHP checks passed |
| Theme clean install/audit/lint/format/build/Storybook | Passed through a fresh complete setup on Node 24.22.1/npm 11.21.1; theme audit 0 |
| Initial theme failure checks | JavaScript stops styles/formatter (exit 1); invalid SCSS exits 2; formatting mismatch exits 1 |
| Theme HTTP/browser/SDC | Page, 33 assets, and Storybook returned 200; native SDC contracts and Pa11y 10 API passed |
| Trusted browser fixture | ARM Chrome 156: zero Pa11y issues; wrapping equivalent; visually hidden text accessible |
| Native browser image libraries | Image build/restart passed on ARM with no foreign architectures |
| Final pinned Node/npm image rebuild/restart | Passed on ARM: Node 24.22.1/npm 11.21.1 under `/usr/local`, unchanged after a second normal restart; no startup npm/CLI installation |
| Theme lock/install and pure builds | Clean `theme-ci` preserved 224 source/manifest files; pure build/Storybook preserved all 332 checked source/config/asset/lock files byte for byte |
| Production browser module graphs | Drupal's 37 modules and Storybook's 469 modules contained no Pa11y, Puppeteer, simple-git, or CLI tooling |
| Initial SCSS migration regression | Native helper→Stylelint/Sass/CSS-minifier checks passed for nested wrappers, equivalent CSS, idempotency, and invalid-input failure |
| `NODE_ENV=production` complete build | Passed; Composer pipeline installed root/theme development tools and both CLI postinstall patches before the production build |
| Starter maintainer installation workflow regressions | 21 passed after final flags, including locked installs, pure build/watch/Storybook, generated lint wiring, and six failure stages |
| Restore build-before-import ordering | Passed with command doubles; failed theme build stops before database import |
| CI runtime image | Node 24.22.1 image manifests verified for ARM64/AMD64; remote pipelines not executed |
| CI PHP syntax command | Valid/invalid PHP fixtures produced expected success/failure locally; remote pipelines not executed |

The DDEV kernel blocks Chrome's normal sandbox in the disposable fixture. Only
the explicitly trusted local browser/API probe used `--no-sandbox`; production
defaults were not weakened. A native browser probe is not an exhaustive site
accessibility audit.

All 33 declared Page assets returned 200 and browser scripts reported no runtime
errors. The bare Storybook iframe also requested a default `/favicon.ico`, which
returned 404; that incidental request remains in the recorded browser evidence.
Disposable test projects and databases were removed after archiving the results.

Restore ordering was checked with command doubles; the real dependency/theme
build pipeline was exercised separately. This frontend follow-up did not
repeat a database restore. The preceding Composer review records the actual
disposable database upgrade and content-preservation checks.

### Changed files and follow-up

This frontend follow-up changes `sous-drupal-project`:

- `package.json`, `package-lock.json`, `patches/@emulsify+cli+2.4.1.patch`: reviewed
  tools, compatible lock, scoped override, and reproducible compatibility fix.
- `.nvmrc`, `.ddev/config.yaml`, `.ddev/web-build/Dockerfile.npm`,
  `.circleci/config.yml`, `.github/workflows/semantic-release.yml`: matching
  runtime pins and native image dependencies.
- `.ddev/commands/web/emulsify`, `devops/scripts/theme-{setup,integrate,build,watch,storybook}.*`,
  `composer.json`: project CLI, standalone theme integration, and explicit
  install/build boundaries.
- `eslint.config.js`, `devops/scripts/lint-php.mjs`,
  `devops/scripts/{sous-setup,rebuild}.sh`, `devops/tests/{lint-workflows,install-workflows}.py`,
  `devops/tests/{verify-cli-cache,verify-theme-source-migration}.mjs`:
  starter/theme lint behavior and native CLI/SCSS regressions.
- `README.md`, `PROJECT-README.md`, and the setup, build, contributing, and
  review documents: current commands and this evidence record.

Keep root and theme lockfiles committed. `npm ci --include=dev` reproduces the build tools; future major
upgrades and override/patch removal require another review of upstream fixes,
APIs, audits, and the native build/browser checks. The local ignored validation
archive retains raw audits, version metadata, logs, and browser evidence. The
remaining action is an upstream npm bundle refresh for the two documented
tooling advisories; the Composer recipe release gate remains separate.
