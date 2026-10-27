# Installation smoke checks

`devops/integration/run.sh` creates a temporary DDEV project from the tracked
files in this checkout, runs `sh devops/scripts/sous-setup.sh`, and supplies
`SOUS_CONTENT` and `SOUS_CONFIG_SPLIT` to `sh devops/scripts/setup.sh fresh`.
It tests the shipped installer instead of rebuilding its steps in the harness.
Docker, DDEV, Git, Bash and the runner's host command-line tools must be available.
PHP, Node, npm and Composer run in the generated DDEV project.

```sh
bash devops/integration/run.sh \
  [--option default|layout-builder|paragraphs|admin-only] \
  [--config-split] [--packages PATH | --packages-ref SHA] \
  [--artifacts DIR] [--keep]
```

The default option is `default`; Config Split is off unless requested. Examples:

```sh
bash devops/integration/run.sh --option default --artifacts /tmp/sous-default
bash devops/integration/run.sh --option paragraphs --config-split \
  --packages /path/to/sous-drupal-packages
bash devops/integration/run.sh --option layout-builder --packages-ref FULL_COMMIT_SHA
```

Without a package override, Composer uses the published index in `composer.json`.
`--packages PATH` selects a packages checkout, and `--packages-ref SHA` fetches
that commit from `fourkitchens/sous-drupal-packages`. Overrides prepend a
canonical Composer path repository for `packages/*`, limited to
`fourkitchens/sous-*`, with symlinks disabled. These disposable path installs are
not release locks. There is no candidate ZIP build or local package-index server.

Each smoke install checks Drupal bootstrap, HTTP 200 from the front page, a login
URL, and the selected composition. Page must exist except for administration-only
installs; Paragraphs is enabled only for its option; the Page display uses Layout
Builder only for its option; Config Split matches its flag. Navigation enables
the shared core Layout Builder module independently of the Page display.

The runner also executes `npm run lint:project`, then `npm run confex` and
`npm run confim`, requiring a clean `drush config:status`. For the default option
only, it backs up with `npm run local-data-bak`, restores through `setup.sh restore`,
checks bootstrap again, and runs `composer audit --abandoned=report` plus
`npm audit --omit=dev --audit-level=high` in the generated site.

`--artifacts DIR` retains `result.json` and one log per step. By default, exiting
deletes the uniquely named DDEV project with `ddev delete -Oy` and removes its
temporary workspace, including on failure. `--keep` preserves both for diagnosis;
remove those disposable resources manually afterward. Existing developer sites
are not smoke-test inputs.

## GitHub Actions and local source checks

`.github/workflows/ci.yml` runs lint, ShellCheck, starter-default checks, and the
three retained shell suites before the smoke matrix. The four options run in
parallel with a 30-minute limit per leg; the Paragraphs leg also enables Config
Split. Every leg uploads its own diagnostic artifact, even after failure.
The optional `packages_ref` workflow input takes precedence over the matching
review-branch entry in `devops/integration/pr-refs.json`.

The source checks can also run locally with the reviewed Node/npm, PHP and
ShellCheck tools:

```sh
npm run lint
php devops/tests/starter-defaults.php
bash devops/tests/install-workflows.sh
bash devops/tests/config-workflows.sh
bash devops/tests/environment-settings.sh
```

Publication needs both source checks and every smoke leg. It runs only for pushes
to canonical `main` with no `packages_ref` input. Pull requests and manual runs do
not publish tags or releases.
