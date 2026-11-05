# Workflow facts

## Sites

Run `ddev describe` for the current local URLs and `ddev drush status --fields=uri,db-name` for the selected site's URL and database. Pass `--uri` to target a specific site. See [shared Drupal commands](../SETUP.md#shared-drupal-commands).

## Database refresh

See [local restore commands](../SETUP.md#restore-or-refresh-locally) for backup selection and sanitization. Customize [get-db.sh](../../devops/scripts/get-db.sh) if this project downloads its default backup.

## Test roles

Run `ddev drush role:list` to discover the site's roles. See [user roles and permissions](../features.md#user-roles-and-permissions) for the accounts created at installation.

## Public files

Stage File Proxy can fetch missing files locally. See [local settings and Config Split](../SETUP.md#local-settings-and-config-split) to set its origin.

## Git workflow

See [contribution guidelines](../CONTRIBUTING.md) for branching, commits, and pull requests.

## Review and deploy

See [pull request checks](../CONTRIBUTING.md#pull-requests), [CI setup](../DEPLOYMENT.md#set-up-site-ci), and [build and deploy](../DEPLOYMENT.md#build-and-deploy).
