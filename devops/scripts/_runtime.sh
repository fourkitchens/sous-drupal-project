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
runtime_environment() {
  if [ "${SOUS_ENVIRONMENT+x}" = x ]; then
    case "$SOUS_ENVIRONMENT" in local|staging|production) printf '%s\n' "$SOUS_ENVIRONMENT" ;; *) echo 'SOUS_ENVIRONMENT must be local, staging, or production.' >&2; return 2 ;; esac
  else echo local; fi
}
runtime_require_development() {
  _sous_environment=$(runtime_environment) || return $?
  [ "$_sous_environment" != production ] || { echo 'This development workflow is disabled in production.' >&2; return 1; }
}
