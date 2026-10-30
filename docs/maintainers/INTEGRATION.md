# Test a Sous installation

Run the smoke test from a Sous source checkout to test the same setup commands
that generate client sites. Install Docker, DDEV, Git, PHP, Bash, jq and curl on
the host first. The runner creates its own temporary project.

```sh
bash devops/integration/run.sh --option default --artifacts /tmp/sous-default
bash devops/integration/run.sh --option paragraphs --config-split \
  --packages /path/to/sous-drupal-packages
```

## Choose the inputs

```text
bash devops/integration/run.sh
  [--option default|layout-builder|paragraphs|admin-only]
  [--config-split]
  [--packages PATH | --packages-ref SHA]
  [--artifacts DIR]
  [--keep]
```

The default option is `default`, without Config Split. Omit package overrides
to use the published Composer index. `--packages PATH` uses a local packages
checkout. `--packages-ref SHA` clones the packages repository at a full commit
SHA. Both overrides use a Composer path repository inside the test project.

The runner copies tracked files from the working tree, installs dependencies in
DDEV, then dispatches Composer's post-create hook from the host with
`SOUS_CONTENT` and `SOUS_CONFIG_SPLIT`. Restoration uses `setup.sh` with the
backup path. Add new test inputs to Git before running it.

## Understand the checks

Each option must bootstrap Drupal, serve the front page with HTTP 200 and return
a login URL. The runner checks Page, Paragraphs, the Page Layout Builder display
and Config Split against the selected option. It also runs project lint and a
configuration export/import round trip.

The default option additionally backs up and restores the database, checks
bootstrap again, and runs `composer audit --abandoned=report` and
`npm audit --omit=dev --audit-level=high`.

`--artifacts DIR` writes `result.json` and a log for each step. The default
directory is `.integration-results`. The runner deletes its DDEV project and
workspace on exit. Use `--keep` to inspect a failure; delete the retained project
with `ddev delete -Oy PROJECT_NAME` and remove its workspace afterward.

## Run source checks

Use Node from `.nvmrc`, Composer, PHP and ShellCheck:

```sh
npm ci
npm run lint
find devops/scripts devops/hooks devops/integration -name '*.sh' -print0 | xargs -0 shellcheck -x -P SCRIPTDIR
bash devops/tests/config-workflows.sh
```

[CI](CI.md) runs these checks and all four smoke options. See
[releases](RELEASES.md) before publishing.
