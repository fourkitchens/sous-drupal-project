#!/bin/sh
# DDEV helpers shared by entry points two levels below the project root.
set -eu
SOUS_PROJECT_ROOT="$(CDPATH='' cd "$(dirname "$0")/../.." && pwd -P)"
cd "$SOUS_PROJECT_ROOT" || exit
if [ "${IS_DDEV_PROJECT:-}" = true ]; then
  runtime_exec() { "$@"; }
  runtime_composer() { composer "$@"; }
  runtime_npm() { npm "$@"; }
  runtime_drush() { "$SOUS_PROJECT_ROOT/vendor/bin/drush" "$@"; }
  runtime_emulsify() { "$SOUS_PROJECT_ROOT/node_modules/.bin/emulsify" "$@"; }
  runtime_start() { :; }
else
  runtime_exec() { ddev exec --dir=/var/www/html --raw "$@"; }
  runtime_composer() { ddev composer "$@"; }
  runtime_npm() { ddev npm "$@"; }
  runtime_drush() { ddev drush "$@"; }
  runtime_emulsify() { ddev emulsify "$@"; }
  runtime_start() { ddev start; }
fi
