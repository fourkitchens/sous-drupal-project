#!/bin/sh
# Shared command routing lets scripts and project hooks run from the host or DDEV.
set -eu
# Entry points live two directories below the root; resolve project paths once
# so commands do not depend on the terminal's starting directory.
SOUS_PROJECT_ROOT="$(CDPATH='' cd "$(dirname "$0")/../.." && pwd -P)"
cd "$SOUS_PROJECT_ROOT" || exit

# Use installed tools inside DDEV and on CI runners or hosts without DDEV.
# Neither direct environment needs runtime_start to start a local container.
if [ "${IS_DDEV_PROJECT:-}" = true ] || ! command -v ddev >/dev/null 2>&1; then
  runtime_exec() { "$@"; }
  runtime_composer() { composer "$@"; }
  runtime_npm() { npm "$@"; }
  runtime_drush() { "$SOUS_PROJECT_ROOT/vendor/bin/drush" "$@"; }
  runtime_emulsify() { "$SOUS_PROJECT_ROOT/node_modules/.bin/emulsify" "$@"; }
  runtime_start() { :; }
else
  # On a computer with DDEV, route commands through it so PHP, Node and the database
  # use the same environment as commands started inside the container.
  runtime_exec() { ddev exec --dir=/var/www/html --raw "$@"; }
  runtime_composer() { ddev composer "$@"; }
  runtime_npm() { ddev npm "$@"; }
  runtime_drush() { ddev drush "$@"; }
  runtime_emulsify() { ddev emulsify "$@"; }
  runtime_start() { ddev start; }
fi

# Keep local database replacement and recipe operations off CI and hosting servers.
runtime_require_ddev() {
  if [ "${IS_DDEV_PROJECT:-}" != true ] && ! command -v ddev >/dev/null 2>&1; then
    printf '%s changes a local database; run it in a DDEV project.\n' "${0##*/}" >&2
    exit 1
  fi
}
