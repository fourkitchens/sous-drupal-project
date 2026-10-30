# Configure Sous CI

Use `.github/workflows/ci.yml` to check and release the starter. Generated sites
choose their own pipeline using the [site CI templates](../DEPLOYMENT.md#choose-site-ci).

## Jobs and triggers

The workflow runs on pull requests and pushes to `main`, `next`, `next-major`,
`alpha`, `beta` and numeric maintenance branches. A manual run accepts an optional
`packages_ref` commit SHA. Review branches use the pull-request trigger.

| Job | Purpose |
| --- | --- |
| `checks` | PHP/JavaScript lint, ShellCheck and the config workflow suite |
| `integration` | Four parallel smoke installs; Paragraphs also enables Config Split |
| `release` | Publish after both jobs pass, only on pushes to canonical `main` without a package override |

Smoke jobs have a 30-minute timeout and always upload diagnostics. A new PR
update cancels an older run for the same PR. A new push does not cancel an
active main-branch run.
See [integration](INTEGRATION.md) for commands and logs.

## Test coordinated package changes

Pass `packages_ref` on a manual run to select a packages commit. For a paired PR,
set `head_ref` and `packages_ref` in `devops/integration/pr-refs.json`. The manual
input takes precedence. Test the published index again before releasing the
starter; path-package checks do not test published ZIP downloads.

## Manage permissions and repository settings

Keep actions pinned to commit SHAs. Dependabot proposes updates to Actions,
npm and Composer dependencies. Verification jobs receive read access. The
release job alone receives `contents: write` and uses `GITHUB_TOKEN`.

Set GitHub branch protection to require the check and smoke jobs after their
first run. Disable any obsolete CircleCI project connection and remove its
required status check when moving the starter to Actions. These are service
settings; deleting a repository file does not disable an external integration.

`.gitattributes` export-ignore rules omit product workflows, integration scripts,
tests and maintainer guides from release archives. Keep that boundary when
adding maintainer files. Client-site CI and deployment belong in the generated site's
repository. See [release setup](RELEASES.md) for publication rules.
