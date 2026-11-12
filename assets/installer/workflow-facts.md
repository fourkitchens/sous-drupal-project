The Sous installer wrote these facts on INSTALL_DATE.

# Workflow facts

## Sites

| Site | Local URL | Database | Local copy |
| --- | --- | --- | --- |
| default | LOCAL_URL | LOCAL_DATABASE | Blank: a fresh install with no content. |

(source: `ddev drush status --fields=uri,db-name` and the fresh `ddev drush site:install minimal` on INSTALL_DATE)

## Database refresh

See [local restore and refresh commands](../SETUP.md#restore-or-refresh-locally) for commands and sanitizing (source: `docs/SETUP.md#restore-or-refresh-locally`).
No remote database source is configured: `devops/scripts/get-db.sh` exits 1 until customized (source: `devops/scripts/get-db.sh`).

## Test roles

Test editorial work as Site Editor (`site_editor`). Superuser (`superuser`) is an admin role with every permission, so testing as it proves nothing about access (source: `config/default/user.role.site_editor.yml`, `config/default/user.role.superuser.yml`).
A throwaway user needs only its role: the site has no Domain, Group or Workbench Access modules (source: `config/default/core.extension.yml`).
See [user roles and permissions](../features.md#user-roles-and-permissions) for the `sous_chef` and UID 1 accounts (source: `docs/features.md#user-roles-and-permissions`).

## Public files

Stage File Proxy runs only in the local split, with an empty origin (source: `config/default/config_split.config_split.local.yml`, `config/local/stage_file_proxy.settings.yml`).
See [local settings and Config Split](../SETUP.md#local-settings-and-config-split) before configuring the origin (source: `docs/SETUP.md#local-settings-and-config-split`).

## Browser testing

None is configured: the repository has no Playwright or Cypress configuration, and DDEV runs no browser service (source: `.ddev/config.yaml`; `find . -name node_modules -prune -o -name vendor -prune -o -name 'playwright.config.*' -print -o -name 'cypress.config.*' -print` on INSTALL_DATE).

## Git workflow

The Husky commit-msg hook runs commitlint with the Conventional Commits preset (source: `.husky/commit-msg`, `commitlint.config.js`).
Releases come from `main` with `v` tags (source: `release.config.js`).
No preview environments are configured (source: `docs/DEPLOYMENT.md#set-up-site-ci` and the provider configuration cited under Review and deploy).
See [Git workflow](../CONTRIBUTING.md#git-workflow) and [releases](../DEPLOYMENT.md#releases) for the human workflow (source: `docs/CONTRIBUTING.md#git-workflow`, `docs/DEPLOYMENT.md#releases`).

## Review and deploy

CI_FACTS

See [pull request review checks](../CONTRIBUTING.md#pull-requests), [site CI setup](../DEPLOYMENT.md#set-up-site-ci) and [build and deploy](../DEPLOYMENT.md#build-and-deploy) before configuring hosting (source: `docs/CONTRIBUTING.md#pull-requests`, `docs/DEPLOYMENT.md#set-up-site-ci`, `docs/DEPLOYMENT.md#build-and-deploy`).
