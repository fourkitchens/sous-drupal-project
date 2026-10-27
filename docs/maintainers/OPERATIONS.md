# Maintain Sous dependencies and support

Assign a maintainer and backup for dependency triage, recipe changes and releases.
Use the following schedule to keep the starter and package collection compatible.
Site teams manage their own production access, incident response and deployments.

| When | Work |
| --- | --- |
| Weekly and on dependency PRs | Check Drupal advisories and Composer/npm audits. Triage urgent advisories the same working day. |
| Monthly and before releases | Check direct dependencies, newer majors, Drupal/PHP support, patches and npm overrides. |
| Every PR | Run source checks and the applicable smoke installs. |
| Before publishing | Run all smoke options against published packages and write existing-site upgrade notes. |

Use official [Drupal advisories](https://www.drupal.org/security), the
[core release schedule](https://www.drupal.org/about/core/policies/core-release-cycles/schedule)
and upstream release notes. Update [platform requirements](../PLATFORM.md),
Composer constraints and CI together when support changes.

## Review dependencies and patches

Check both direct and transitive dependencies. Read major-version upgrade notes;
an update within the existing constraint does not cover newer major releases.
Use Composer platform checks and audits, and npm audits for root and theme tools.
Do not bypass platform checks or suppress new advisories to make an update pass.

For each patch, compare it with the updated source and upstream fix. Remove it
when the fix is included, regenerate the patch lock and test the affected path.
See [site patch commands](../MAINTENANCE.md#maintain-composer-patches).

Keep these limitations in the dependency review:

- `oomphinc/composer-installers-extender` supplies asset install paths but is
  abandoned. Find a supported replacement before removing it.
- Paragraphs Collapsible needs a support/security review before retaining it in
  another major package release.
- Recheck the scoped simple-git, Pa11y and Storybook overrides when their upstream
  packages change. Remove an override only after install, lint and builds pass.
- Emulsify CLI moving-branch refresh may need `ddev emulsify cache clear`. Prefer
  tagged inputs for repeatable generation; see [build tools](../BUILD_TOOLS.md).

Record unresolved issues in issue tracking and release notes, with the affected
package and an owner. Keep test results in CI artifacts or PRs rather than site
guides. Audit findings and integration failures need a fix or a documented
release decision; previous passing results do not establish a new version works.

Follow [product maintenance](MAINTENANCE.md) for source changes and
[releases](RELEASES.md) for publication. Send site teams explicit adoption steps;
updating a recipe package does not deploy its configuration.
