#!/bin/sh
# Shared command routing lets scripts and project hooks run from the host or DDEV.
set -eu
# Entry points live two directories below the root; resolve project paths once
# so commands do not depend on the terminal's starting directory.
SOUS_PROJECT_ROOT="$(CDPATH='' cd "$(dirname "$0")/../.." && pwd -P)"
cd "$SOUS_PROJECT_ROOT" || exit

# Inside the web container, use its installed tools and project executables.
# runtime_start is a no-op because DDEV is already running.
if [ "${IS_DDEV_PROJECT:-}" = true ]; then
  runtime_exec() { "$@"; }
  runtime_composer() { composer "$@"; }
  runtime_npm() { npm "$@"; }
  runtime_drush() { "$SOUS_PROJECT_ROOT/vendor/bin/drush" "$@"; }
  runtime_emulsify() { "$SOUS_PROJECT_ROOT/node_modules/.bin/emulsify" "$@"; }
  runtime_start() { :; }
else
  # From the host, route commands through DDEV so PHP, Node and the database
  # use the same environment as commands started inside the container.
  runtime_exec() { ddev exec --dir=/var/www/html --raw "$@"; }
  runtime_composer() { ddev composer "$@"; }
  runtime_npm() { ddev npm "$@"; }
  runtime_drush() { ddev drush "$@"; }
  runtime_emulsify() { ddev emulsify "$@"; }
  runtime_start() { ddev start; }
fi
