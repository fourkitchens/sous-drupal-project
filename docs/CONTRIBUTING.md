# Contribute to this site

Set up a local copy using [Setup](SETUP.md) and the versions in
[Platform](PLATFORM.md). Create a branch for your change and keep unrelated
changes separate.

## Make a change

Edit custom modules, themes and the site's configuration. Composer manages
`vendor`, contributed modules and themes, libraries and downloaded recipes.
Keep reusable recipe changes in the
[Sous packages repository](https://github.com/fourkitchens/sous-drupal-packages).

After changing Drupal configuration through the UI, export it:

```sh
ddev npm run confex
git diff -- config
```

Keep the complete site export and its UUIDs. Review additions and deletions
before committing. Put environment values and secrets in the settings described
in [Environments](ENVIRONMENTS.md), then follow
[Maintenance](MAINTENANCE.md) for package or configuration updates.

## Check your work

Install the locked tools as described in [Build tools](BUILD_TOOLS.md). Run the
checks relevant to the changed code:

| Command | Checks |
| --- | --- |
| `ddev npm run lint` | Custom PHP syntax, JavaScript and theme JavaScript/SCSS |
| `ddev npm run prettier` | Theme formatting |
| `ddev npm run theme-build` | Production theme assets |
| `ddev npm run theme-storybook` | Storybook output, when used by the site |
| `ddev npm run confim` | Database updates, configuration import and deployment hooks on your local copy |

Use Drupal's [coding standards](https://project.pages.drupalcode.org/coding_standards/)
for PHP, Twig, JavaScript and CSS. PHP syntax checks do not enforce the full
Drupal standard. Review autofix changes before committing:

```sh
ddev npm run lint:js -- --fix
ddev npm --prefix web/themes/custom/sous-project run lint-fix
ddev npm --prefix web/themes/custom/sous-project run prettier-fix
```

Setup replaces `sous-project` with the site's theme name. Check affected pages,
editor workflows, permissions, keyboard use and accessibility. Add site tests
when the change needs them.

## Request review

Use Conventional Commits, such as `fix: correct the event date display`.
Commit custom source, configuration and changed lockfiles. Describe the problem,
resulting behavior, checks and any deployment steps in the pull request. Include
screenshots when they help explain a visual change. Follow the site's review
and [deployment process](DEPLOYMENT.md).
