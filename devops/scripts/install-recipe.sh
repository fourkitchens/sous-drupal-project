#!/bin/sh
# Apply downloaded recipes from Drupal's root so relative includes resolve.
set -eu
. "$(dirname "$0")/_runtime.sh"
runtime_require_ddev
[ "$#" -eq 1 ] || { echo 'Usage: npm run install-recipe -- vendor/package' >&2; exit 2; }
# Host callers use the DDEV command to reach the container's project tools.
if [ "${IS_DDEV_PROJECT:-}" != true ]; then exec ddev install-recipe "$@"; fi
cd web
# Composer can add extensions after Drupal caches the available modules and themes.
runtime_drush cache:rebuild
runtime_drush recipe "recipes/${1##*/}"
# Later recipes and page requests must see the newly installed extensions.
runtime_drush cache:rebuild
