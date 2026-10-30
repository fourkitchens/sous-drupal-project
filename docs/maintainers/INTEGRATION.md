# Test a Sous installation

Run the smoke test from a Sous source checkout. Install Docker, DDEV, Git,
PHP 8.4, Composer, Bash, jq and curl on the host first.
Commit candidate changes before testing: the runner exports `HEAD` with
`git archive` into a temporary template, registers it as version `8.0.0` in a
Composer path repository, and runs the real host `composer create-project`.

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
  [--packages PATH]
  [--artifacts DIR]
  [--keep]
```

The default option is `default`, without Config Split. Omit `--packages` to use
the published Composer index. For unpublished packages, `--packages PATH` replaces
that index in the exported template with `PATH/packages/*` as an absolute path
repository, using version `2.0.0` and copies rather than symlinks.
The temporary DDEV configuration mounts that checkout read-only at the same path.

## Understand the checks

Every option must bootstrap Drupal and serve the front page with HTTP 200.
The runner checks the selected content composition and Config Split, deploys
configuration and verifies no configuration changes remain. It also checks
that integration scripts, maintainer guides and product workflows are absent
from the generated site.

The default option additionally backs up the database, restores it through
`setup.sh`, and checks bootstrap again.

`--artifacts DIR` keeps logs; the default is `.integration-results`. The runner
deletes its DDEV project and temporary workspace on exit. Use `--keep` to inspect
a failure, then delete that project with `ddev delete -Oy PROJECT_NAME` and
remove its workspace.

## Run source checks

Use Node from `.nvmrc`, PHP, Composer, ShellCheck and actionlint:

```sh
npm ci
npm run lint
find devops -name '*.sh' -print0 | xargs -0 shellcheck -x -P SCRIPTDIR
composer validate --no-check-publish
actionlint .github/workflows/ci.yml
```

[CI](CI.md) selects smoke options by trigger. See [releases](RELEASES.md) before
publishing.
