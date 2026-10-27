# Sous integration and release checks

The starter owns one full product gate in `devops/integration/run.sh`. It creates
an owned disposable DDEV application, exercises the generated theme, installs
only selected package ZIPs, and checks configuration and environment behavior.
The packages repository separately runs syntax, code-sniff and formatting checks.
Neither gate modifies a developer database or publishes a package release.

## Review branch and publication status

The package sources are consolidated in
[fourkitchens/sous-drupal-packages](https://github.com/fourkitchens/sous-drupal-packages).
The starter's production Composer root targets the scoped public index and
stable `^1.0` requirements. The package collection starts at `1.0.0` and
publishes semantic releases, package ZIPs and the static index automatically
from `main`. A normal installation requires that published index; it must not
silently substitute branches or local sources.

The review branch `codex/release-starter-packages-2.0` explicitly validates the
reviewed packages commit in `devops/integration/pr-refs.json`. This override is
limited to the disposable test application. Its temporary loopback index and
candidate lockfiles are diagnostic artifacts, not publishable starter locks.
Only a canonical `main` run with no candidate override may enter the starter
publication job. Both the caller and reusable publisher enforce that guard.

Earlier PR #130 full runs failed the generated-theme lint gate. Their separate
configuration diagnostics did not establish full acceptance. The migration
must pass the unchanged categories below, including actual source lint,
formatting, production build and Storybook; excluding generated output from
source lint must not hide authored source failures.

## Run the full gate

Install Git, Bash 3.2+, jq, curl, realpath, shasum, Docker and DDEV 1.25.2+ within
1.x. PHP, Node, npm and Composer run in DDEV at the versions in
[Platform](PLATFORM.md). A developer site's installed dependencies or database
are not inputs. CI uses Ubuntu 24.04 and pinned DDEV 1.25.4 setup.

After package publication, the ordinary gate uses the production index:

```sh
bash devops/integration/run.sh
```

Before publication, select an explicit reviewed full commit:

```sh
bash devops/integration/run.sh \
  --packages-ref 283f18089ecb86bd6b495253357a117c8f7f81a1
```

To test local edits, provide the monorepo checkout instead:

```sh
bash devops/integration/run.sh --packages /path/to/sous-drupal-packages
```

The compatibility `--sources /path/to/Projects` spelling selects
`/path/to/Projects/sous-drupal-packages`. A package override is always explicit;
`--packages-ref` accepts only a lowercase 40-character commit, never a floating
branch. GitHub's manual validation input is `packages_ref` with the same meaning.
No companion-repository ref map or path-distribution lock remains.

The runner snapshots tracked and non-ignored source files, excludes installed
code/caches/reference artifacts and records each actual revision plus copied
source digest. Symlinks leaving a source checkout are rejected. Candidate ZIPs
are built by the package repository's deterministic builder and served through
an owned loopback HTTP index supervised by DDEV inside the disposable container. Production runs
obtain the reviewed package source revision from published dist metadata.

Use `--artifacts DIRECTORY` to retain logs and machine-readable `result.json`.
`--keep` preserves only this run's disposable fixture for diagnosis. Otherwise
the runner verifies the registered DDEV application's actual directory before
deleting its own containers/database/workspace. It never deletes a project by
name alone. `--option default|paragraphs|layout-builder|admin-only` narrows content
checks while keeping both split states; `all` also runs the six environment
cases. CI runs the full gate, without `--keep`.

## Generated application and optional packages

The baseline requires Admin and Emulsify, with Formats as Admin's shared Linkit
prerequisite. It applies Admin once and generates the custom theme through the
actual starter tools. Every selected Sous recipe remains in Composer's native
require/lock records through the exact nine-name unpack exclusion. The ignore
list does not install any package. No blanket unpack command removes those
records, and no maintainer probe ships in a package ZIP.

| Content choice | Additional package | Native assertions |
| --- | --- | --- |
| Default | `fourkitchens/sous-content-types` | Page/Frontpage setup and HTTPS Page authoring |
| Paragraphs | `fourkitchens/sous-paragraphs` | Selected host fields and absence of Layout Builder composition |
| Layout Builder | `fourkitchens/sous-core-layout-builder` | Body field blocks, Basic inline blocks, enabled layouts and saved per-node overrides |
| Admin only | None | Gin; no Page/Frontpage or Sous media bundles |

Each choice runs with and without `fourkitchens/sous-config-split`, giving eight
combinations. The runner restores the baseline database, exported configuration directory
and Composer manifest/lock before each case, then resolves only the selected composition and its
shared dependencies. It checks retained ZIP-only lock records and actual module
and recipe directories. Layout Builder must never resolve/install Paragraphs.
Paragraphs/default must omit the Core Layout Builder composition. Navigation
itself enables the shared core Layout Builder module; tests enforce per-bundle
layout/override configuration independently of that shared module state.

Each case commits only the generated application's source and manifests into a
disposable Git fixture, clones it with no Composer-managed code/recipes, then
installs its exact lock from a new cache and reinstalls selected Sous packages
with `--prefer-source`. Dist-only metadata must still use individual ZIPs.
Recipe source includes and nested Linkit paths remain
available without copying sibling packages. Project recipe directories stay
ignored; a recipe update never implicitly reapplies configuration.

Every runtime case exports complete configuration, deliberately mutates the
site name, runs native Drush deployment/import, and compares all active
configuration collections. Starter-owned checks cover its installation,
environment and theme workflows; it does not load feature probes from the
packages repository.

## Environments, frontend and source checks

The full run checks local/staging/production with split support absent, then
with native Config Split enabled: six real environment round trips. The recipe
supplies `_splits` definitions; the application owns `config/_splits`,
`config/local`, `config/staging` and runtime activation policy. Contact supplies
disposable environment-owned module/config values. Transitions to production
must remove split-only modules without contaminating stored status defaults.
Missing/public split paths and unsafe development settings must fail before
configuration import or database replacement. See [Environments](ENVIRONMENTS.md).

The generated-theme gate runs the selected Emulsify/UI Kit source checks,
formatting, production asset build, Storybook, Drupal theme/asset assertions,
generated-project lint and a native prepared build. Source checks validate the
starter and all nine package manifests/YAML, documentation paths and licenses,
installer/runtime/action/artifact regressions, PHP/JavaScript lint and native
semantic-release dry runs. Composer and npm audits include development tools;
[audit policy](../devops/integration/audit-policy.json) applies only its existing
narrow exceptions and does not hide newly introduced findings.

Maintainer source validation remains excluded from generated Git/Composer
artifacts through archive rules and one-time naming cleanup. Application CI
continues to come from the optional [CircleCI or Actions templates](CI.md).
A passing candidate full gate verifies the starter against reviewed package
source. The public index and a cold-cache public consumer installation must be verified
before the starter's final release. This review branch never publishes tags,
releases or Pages deployments.
