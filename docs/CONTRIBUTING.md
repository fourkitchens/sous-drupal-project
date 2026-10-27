# Contributing to Sous

The starter and companion packages repository are one product. Work on dedicated
branches, preserve unrelated changes, and verify current remote state before
choosing a base. Installed copies under `web/recipes` are application inputs;
edit `sous-drupal-packages/packages/<basename>` for reusable recipe changes.

Use the [supported platform](PLATFORM.md). The [maintenance ownership and cadence](OPERATIONS.md)
guide assigns security, dependency, support-window, patch, and release reviews.
Generated applications choose their own contribution, deployment, and review
policy; this guide describes the reusable Sous sources.

## Change and release flow

Use [Conventional Commits](https://www.conventionalcommits.org/) so the final
squash commit communicates release intent. `fix:` and `perf:` produce patches,
`feat:` produces a minor release, and `!` or a `BREAKING CHANGE:` footer produces
a major release. Ordinary `docs:`, `chore:`, `test:`, `ci:`, and `build:` commits
do not release by themselves. Include ticket references when the project uses
them; a particular ticket tracker is not required.

`main` is the source default and the configured stable publication branch. An
unmerged branch or untagged change is not a published stable release. The
starter's other configured channels, package release line, coordinated order,
and native dry-run limitations are documented in [Release maintenance](RELEASES.md).
Publish the immutable package collection/index before a compatible starter;
candidate-commit integration never authorizes a production release.

Use a pull request and the existing [PR template](../.github/PULL_REQUEST_TEMPLATE.md).
Describe the problem, final behavior, actual checks, downstream migrations, and
remaining limitations. Preserve breaking-change details in the squash commit.
Use draft PRs while work is in progress. A designated maintainer reviews and
merges after the applicable integration checks; labels are optional and do not
replace checks or repository policy. Repository owners configure actual
required reviews/checks after the shared workflow has run; source configuration
alone does not enforce branch protection.

Keep changes focused enough to review. Urgent security fixes follow the
[security ownership and escalation policy](OPERATIONS.md); do not delay them
behind unrelated feature work. Carry compatible fixes forward to other
supported branches and publish downstream upgrade instructions with the release.

## Package repository development

Use clean checkouts for `sous-drupal-project` and
[sous-drupal-packages](https://github.com/fourkitchens/sous-drupal-packages).
The latter contains reusable packages in `packages/` with one catalog, shared
semantic releases, and syntax/style checks in `devops/tests/`. Use
`npm run create-package sous-event` there to scaffold a new recipe. The
[integration runner](INTEGRATION.md) tests the starter
with released ZIPs by default or explicit candidate source for review. Local
`--packages /path/to/sous-drupal-packages` selects that working checkout;
`--packages-ref COMMIT` selects an exact reviewed commit. See that guide for
source provenance and diagnostics. No
path override, floating branch or candidate distribution belongs in a published
application lockfile. Legacy repositories remain historical download sources.

Do not copy a full site export over a recipe. Select the defaults owned by that
recipe, remove only exported top-level site UUIDs/settings hashes, and preserve
legitimate nested plugin and icon identifiers. Existing sites retain their
complete exports, including UUIDs, and adopt later changes explicitly. Test
both fresh application and documented existing-configuration behavior. See
[Site maintenance](MAINTENANCE.md) for migrations and project-owned overrides.

## Code standards and linting

Use Drupal's [coding standards](https://project.pages.drupalcode.org/coding_standards/)
for custom code, including its [Twig](https://project.pages.drupalcode.org/coding_standards/twig/coding/),
[JavaScript](https://project.pages.drupalcode.org/coding_standards/javascript/coding/), and
[CSS](https://project.pages.drupalcode.org/coding_standards/css/architecture/)
guidance. Automated syntax, lint, and format checks have the following scopes:

| Command | Check |
| --- | --- |
| `ddev npm run lint:starter` | Starter/custom-module JavaScript and custom PHP syntax; no generated theme required |
| `ddev npm run lint:js` | ESLint with the appropriate Node/Drupal globals |
| `ddev npm run lint:php` | Native `php -l`, including Drupal PHP file extensions and paths with spaces |
| `ddev npm run lint:project` | Starter checks followed by the generated theme's checks |
| `ddev npm run lint:theme` | Theme-owned JavaScript and SCSS checks |
| `ddev npm run lint:styles` | Theme Stylelint check |
| `ddev npm run prettier` | Theme formatting check |

Install tools as described in [Build tools](BUILD_TOOLS.md). Naming setup changes
a generated application's `lint` alias from `lint:starter` to `lint:project`.
Theme checks require that theme and its locked dependencies. Downloaded code,
minified output, and generated component mirrors are excluded. PHP syntax
checking does not imply Drupal coding-standard compliance.

For supported fixes:

```sh
ddev npm run lint:js -- --fix
ddev npm --prefix web/themes/custom/sous-project run lint-fix
ddev npm --prefix web/themes/custom/sous-project run prettier-fix
```

The theme path shown is the source placeholder; naming setup rewrites it in a
generated project. Review autofix changes before committing them.

## Maintainer and application checks

The ungenerated starter's focused regressions use Bash, jq, Git, PHP, Composer,
and the reviewed Node/npm tools; they are distinct from generated-site checks.
Run the relevant scripts in `devops/tests` before changing their shared
installation, configuration or lint implementations. With the reviewed Node/npm
and PHP tools prepared, `bash devops/tests/check.sh` runs the local source checks
without a Drupal database or DDEV. CI uses the [integration runner](INTEGRATION.md)
on pull requests, release-branch pushes and manual runs to cover the generated
theme, all four installation options, configuration export/deploy and Page
authoring. That full gate creates and removes its own database.
These checks stay in the source checkout. Generated sites receive an empty
`devops/tests/` and supply their own application checks.

From an ungenerated starter with those tools prepared, run the isolated
database-refresh and command regressions without a developer database:

```sh
bash devops/tests/config-workflows.sh
```

These use temporary files and mocked tools for corrupt/missing backups,
downloads, hook failures, paths with spaces and failure-status propagation.
The [refresh guide](SETUP.md#restore-an-existing-generated-site) owns the actual
site workflow and project hook contract.

Documentation can be checked without installed application dependencies:

```sh
bash devops/tests/documentation.sh --sources /path/to/checked-out-sous-projects
```

With locked tools installed, these focused native probes are also available:

```sh
ddev exec node devops/tests/verify-cli-cache.mjs
```

The CLI probe uses local Git fixtures and removes its own cache bucket. Theme
checks use native npm tasks; there is no starter-owned SCSS migration. Site
checks that mutate content/configuration require `SOUS_DISPOSABLE_TEST=1` and a
disposable installation; do not run them on a developer's canonical database.

Generated sites run their own project lint, build, configuration and content
checks. Starter bootstrap tests require files removed after successful
installation and are not application tests. Full command details and output
paths are in [Build tools](BUILD_TOOLS.md); supported production steps are in
[Deployment](DEPLOYMENT.md).
