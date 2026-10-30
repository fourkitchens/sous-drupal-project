#!/bin/sh
set -eu
. "$(dirname "$0")/_runtime.sh"
[ "$#" -eq 1 ] || { echo 'Usage: npm run install-recipe -- vendor/package' >&2; exit 2; }
if [ "${IS_DDEV_PROJECT:-}" != true ]; then exec ddev install-recipe "$@"; fi
cd web
# Composer can add extensions after Drupal caches the available modules and themes.
runtime_drush cache:rebuild
runtime_drush recipe "recipes/${1##*/}"
runtime_drush cache:rebuild
