# Product CI and generated-site CI

Sous uses GitHub Actions to test the coordinated product and gate its releases.
Generated sites own their application checks, secrets, artifacts and deployment
policy. These are separate configurations even when both use GitHub Actions.

## Maintained source repository

`.github/workflows/` and `.github/actions/` contain the Sous integration/release
pipeline. It snapshots the caller's checked-out changes with companion recipes,
installs disposable Drupal, and tests the supported product combinations.
These jobs do not deploy client sites. See [integration](INTEGRATION.md).

The Git archive excludes product workflows; Composer archives also exclude the
composite-action metadata. One-time project naming removes both directories
from source-based installs too, retaining other GitHub files such as the PR
template. The action metadata must remain in GitHub's source archive because
GitHub downloads it to execute the product action; setup removes it before a
generated installation finishes.

## Generated applications

Choose one example in the generated site's own repository:

```sh
# CircleCI application build
mkdir -p .circleci
cp assets/ci/circleci/config.yml .circleci/config.yml

# Or a GitHub Actions application build
mkdir -p .github/workflows
cp assets/ci/github-actions/build.yml .github/workflows/build.yml
```

Both templates use the site's DDEV runtimes, committed package locks, lint and
production asset build. They do not publish Sous releases or deploy anything.
Review the commands for that site's theme and add its tests and deployment
process. The examples use machine/Docker runners so they can reuse DDEV rather
than maintain a second runtime stack.

Keep the two examples in this repository for now. Separate `sous-circle-ci`
and `sous-github-actions` repositories become useful if several projects need
independently versioned shared jobs or reusable deployment logic. Splitting a
few starter templates today would add repositories, release order and access
management without removing duplicated runtime ownership.

## PR #130 and the CI cutover

The original PR head `1e2914a` had two independent failures:

- The external **CircleCI Pipeline** status reported missing configuration
  after the root CircleCI file was removed. A repository file cannot disable
  that existing CircleCI connection. Stop that project's trigger/following in
  CircleCI and remove its status from GitHub required checks if configured.
  Generated applications connect their own CircleCI projects after opting in.
- **Actions integration** stopped at Composer resolution. The starter defined
  Dropzone 6 while the unmerged `sous-media/main` still required Dropzone 5.
  It did not reach the runtime tests or the previously observed UI Kit checks.

For this coordinated release only, `devops/integration/pr-refs.json` records
reviewed companion commit SHAs. The composite action uses them only for pull
requests from `codex/release-2026-10-26` when no explicit refs are supplied.
The subject repository always uses its actual checkout. Other PRs, pushes and
release gates use merged companion branches; an explicit validation dispatch
can still supply reviewed refs. Remove/update the temporary manifest after
the companion PRs merge. Passing paired-source checks is not evidence that
publication against merged dependencies is ready.

Remote CircleCI settings and branch-protection rules require repository/service
administrator access. Source changes do not silently modify those settings.
Read-only inspection on October 27, 2026 found `main` unprotected, with no
required status checks or repository rulesets. The current CircleCI error is
an external status, rather than a configured merge requirement.
