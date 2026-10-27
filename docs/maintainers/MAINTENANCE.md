# Change the Sous starter and packages

Make application setup changes in `sous-drupal-project`. Make reusable recipe
changes in [sous-drupal-packages](https://github.com/fourkitchens/sous-drupal-packages).
Keep generated client sites separate from both source checkouts.

## Put changes in the right place

| Source | Changes |
| --- | --- |
| Starter | Composer repositories and install paths, settings scaffold, setup/build scripts, CI templates and site guides |
| Packages | Recipe dependencies, configuration defaults, shared subrecipes and package releases |
| Generated site | Complete configuration export, custom code/theme, accounts, content, integrations and deployment |

Edit package source under `packages/`, rather than downloaded copies under
`web/recipes`. Preserve Composer package names. The shared version number must
not make Paragraphs, Layout Builder or Config Split mandatory.

Keep `config/default` empty in the starter except for its marker and README.
Fresh setup exports a complete site after recipes are applied. Reusable recipe
configuration must omit site UUIDs and export metadata; complete site exports
must retain them.

Sous Admin supplies the Superuser role and uses the Linkit subrecipe in Sous
Formats without applying the full text-format recipe. Emulsify supplies theme
defaults. Keep authorization separate from theme selection. The shared Linkit
profile preserves existing customizations; changes to it need explicit
existing-site instructions.

## Submit and test a change

Create a branch, make a focused change and run the [source checks](INTEGRATION.md#run-source-checks).
For coordinated changes, run the smoke test against the packages checkout or
commit. Also test published packages before releasing the starter.

Use Conventional Commits. Explain breaking dependency, command and configuration
changes in the PR and release notes. Describe fresh-install behavior separately
from steps for an existing site. A new recipe version does not replay defaults
on sites that already applied it.

Test upgrade instructions on a copy of an existing site's database and complete
export. Do not use a fresh install as proof that an existing-site upgrade works.
Keep backups and state any data or permission changes. Site teams choose when
to adopt and deploy them through their [maintenance workflow](../MAINTENANCE.md).

Follow [operations](OPERATIONS.md) for dependency reviews and
[releases](RELEASES.md) for publication order. Keep product tooling and these
guides out of generated sites with `.gitattributes` and `sous-setup.sh` cleanup.
