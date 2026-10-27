# Product CI and generated-site CI

Sous uses GitHub Actions to test the coordinated product and gate its releases.
Generated sites own their application checks, secrets, artifacts and deployment
policy. These are separate configurations even when both use GitHub Actions.

## Maintained source repository

`.github/workflows/` and `.github/actions/` contain the Sous integration/release
pipeline. It tests the checked-out starter with independent package ZIPs from
the scoped static Composer index, installs disposable Drupal, and tests the
supported content choices and optional Config Split. Explicit candidate mode
builds ZIPs from one reviewed package commit for prepublication acceptance.
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

## Package repository cutover

The package source is consolidated in
[sous-drupal-packages](https://github.com/fourkitchens/sous-drupal-packages).
The old repositories remain historical download sources; the starter no longer
combines their floating branches. Package CI owns syntax/style checks and
automatic semantic publication from `main`;
starter CI retains the full theme, installation and environment acceptance gate.
Neither a configuration-only pass nor the package fixture replaces that gate.

At the October 27, 2026 cutover review, package source is merged but ZIPs/index
are unpublished. The explicit reviewed-commit candidate input tests this branch
without publication: a manual dispatch's `packages_ref` must be a full reviewed
commit, such as `283f18089ecb86bd6b495253357a117c8f7f81a1`.
Candidate runs cannot publish a starter release. After the
package release and public index are verified, run the normal published-index
gate before starter publication. See [Integration](INTEGRATION.md) for inputs
and diagnostics and [Releases](RELEASES.md) for the ordering.

## Historical PR #130 CI failures

The original PR head `1e2914a` had two independent failures:

- The external **CircleCI Pipeline** status reported missing configuration
  after the root CircleCI file was removed. A repository file cannot disable
  that existing CircleCI connection. Stop that project's trigger/following in
  CircleCI and remove its status from GitHub required checks if configured.
  Generated applications connect their own CircleCI projects after opting in.
- **Actions integration** stopped at Composer resolution. The starter defined
  Dropzone 6 while the unmerged `sous-media/main` still required Dropzone 5.
  It did not reach the runtime tests or the previously observed UI Kit checks.

The earlier `pr-refs.json` scheme paired individual legacy repository commits.
The monorepo cutover supersedes that map with one explicit reviewed package
commit. This history explains the old failure; it is not the current package
source-selection procedure.

Remote CircleCI settings and branch-protection rules require repository/service
administrator access. Source changes do not silently modify those settings.
Read-only inspection on October 27, 2026 found `main` unprotected, with no
required status checks or repository rulesets. The current CircleCI error is
an external status, rather than a configured merge requirement.
