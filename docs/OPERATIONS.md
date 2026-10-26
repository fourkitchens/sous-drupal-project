# Maintenance ownership and review cadence

This policy covers the seven Sous source repositories as one product. It defines
work ownership, not an automatically scheduled service. No monitoring job,
notification, production deployment, or release is created by this document.

## Accountable roles

The **Sous maintainer on duty** coordinates cross-repository work and records the
review evidence. The **recipe maintainer** owns defaults/dependencies and adoption
notes for their recipe. The **release maintainer** owns the integrated lock/runtime
review, passing shared checks, release order, and immutable tag publication. Each
**downstream site owner** owns that site's packages, exported configuration,
database/content, custom code, overrides, deployment, and upgrade acceptance.

Four Kitchens' Sous maintainers assign a named person and backup to each role in
their current issue/release tracking process. Until that assignment is recorded,
the repository maintainers retain responsibility; this document does not invent
individual assignments. Production credentials and response commitments remain
with the downstream site's operations team.

## Cadence and required outcome

| Review | Owner | Cadence / trigger | Required outcome |
| --- | --- | --- | --- |
| Security advisories | Maintainer on duty; affected recipe maintainer; downstream security owner | Check Drupal advisories and Composer/npm findings at least weekly and on every dependency PR; assess an incoming urgent advisory the same working day | Record affected code/runtime/tooling and downstream impact, available stable fix, test/release plan, or a named mitigation owner; begin critical/exploited remediation immediately under the site's incident process |
| Urgent updates | Maintainer on duty + release maintainer; downstream owner for its deployments | Immediately after a material advisory; do not wait for the monthly feature batch | Prepare the smallest compatible stable fix, review platform/patch impact, run required checks and disposable upgrade, publish only through the authorized release process, and send downstream adoption instructions through the normal owner-managed channel |
| Latest stable dependencies, including majors | Recipe maintainers + release maintainer | Monthly, and before each coordinated starter release | Inspect every direct requirement with Composer/npm metadata and official release notes, investigate newer majors/conflicts, update bounded constraints and compatible transitives, record any exact exception; do not treat an in-range update as a complete latest-stable review |
| Drupal support windows | Release maintainer | Monthly and at every core minor/major RC or release announcement | Check the official schedule, supported/security-covered branches and required PHP/extensions; plan upgrades before support ends and update PLATFORM/CI/DDEV/Composer together |
| Patches and overrides | Owner of the affected package + release maintainer | Every affected package/tool upgrade and each monthly dependency review | Compare the patch to upgraded source/upstream fix, retain rationale/version/issue evidence, test unpatched and patched behavior when removing it, regenerate native lock/checksum data as required |
| Integration | Author + reviewer; maintainer on duty resolves cross-repo failures | Every PR, every coordinated source pairing, and before publishing | Test exact subject source plus companion refs, supported four options, configuration/authorization/Linkit regressions and clean locks; retain diagnostics and do not publish from a validation-only paired-ref run |
| Coordinated releases | Release maintainer + recipe maintainers | When reviewed changes are ready; urgent security work takes priority | Release dependencies before consumers, verify indexed tags, replace only real development exceptions, rerun the integrated starter, publish with correct Conventional Commit semantics and adoption notes |
| Downstream instructions | Recipe maintainer drafts; release maintainer verifies; downstream owner executes | Every behavior/dependency/configuration migration and every release affecting existing sites | State new-install defaults separately from existing-site steps, role/content/config risks, required backups/platform, explicit migration commands, validation and recovery; updating a recipe is not configuration deployment |

“Same working day” is a triage target, not a claim of 24/7 staffed coverage or a
guaranteed production fix deadline. Urgency, exploitability, a safe stable fix,
and each downstream site's incident policy determine the deployment schedule.
Record unresolved risks rather than marking a review complete without action.

## Sources and records

Use official [Drupal security advisories](https://www.drupal.org/security) and
the [core release schedule](https://www.drupal.org/about/core/policies/core-release-cycles/schedule),
package metadata, and upstream release notes. The schedule is reviewed, not
hardcoded as a perpetual guarantee: as checked for this implementation, Drupal
12/11.5 target the week of 7 December 2026, and Drupal 10 reaches end of life on
9 December 2026. Those announcements trigger a support/platform review; they do
not authorize an unreviewed major update. The currently selected Sous baseline
is defined only in [Platform](PLATFORM.md).

For each dependency/release review, retain the before/after versions, official
notes, resolution conflicts, PHP/extensions/toolchain changes, patch disposition,
audit output, actual check/upgrade results, and any precise deferred package
reason. Existing evidence reports are [dependencies](DEPENDENCY_UPGRADE.md),
[frontend](FRONTEND_TOOLING.md), [integration](INTEGRATION.md), and
[site maintenance](MAINTENANCE.md). Historical receipts are not proof that a new
revision was tested.

Native audits remain visible. The current narrow integration policy records two
affected npm-bundle tooling packages and the abandoned Composer installer;
generated-theme audit findings are separate. Review those exceptions monthly
and when their upstream packages change; new findings/severities/versions or
audit/network failures fail the policy check. Do not suppress them or bypass
Composer's platform/security blocking to finish an update.

## Downstream and release boundaries

[Release maintenance](RELEASES.md) is authoritative for package order, SemVer,
branch/tag behavior, untagged packages, and current rollout constraints.
[Site maintenance](MAINTENANCE.md) is authoritative for package/database updates,
explicit config migrations and project overrides. [Deployment](DEPLOYMENT.md)
defines artifact and production-environment responsibilities.

A generated site's owner must review its own complete configuration and data
on a disposable copy before accepting an update. Its lockfile reproduces a
specific reviewed dependency set; future major upgrades require another
reviewed update. No recipe replay service or Sous update framework is implied.
Communications, issue assignment, tag publishing, and downstream deployment are
performed by the authorized owners, not automatically by this documentation.
