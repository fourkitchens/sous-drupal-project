#!/bin/sh
# Shared by entry points two levels below the project root.
set -eu
SOUS_PROJECT_ROOT="$(CDPATH='' cd "$(dirname "$0")/../.." && pwd -P)"
cd "$SOUS_PROJECT_ROOT" || exit
SOUS_RUNTIME=${SOUS_RUNTIME-ddev}
case "$SOUS_RUNTIME" in ddev|native) ;; *) echo 'SOUS_RUNTIME must be ddev or native.' >&2; exit 2 ;; esac
if [ "${IS_DDEV_PROJECT:-}" = true ]; then SOUS_RUNTIME=native; fi
export SOUS_RUNTIME

runtime_exec() { if [ "$SOUS_RUNTIME" = ddev ]; then ddev exec --dir=/var/www/html --raw "$@"; else "$@"; fi; }
runtime_composer() { if [ "$SOUS_RUNTIME" = ddev ]; then ddev composer "$@"; else composer "$@"; fi; }
runtime_npm() { if [ "$SOUS_RUNTIME" = ddev ]; then ddev npm "$@"; else npm "$@"; fi; }
runtime_start() { if [ "$SOUS_RUNTIME" = ddev ]; then ddev start; fi; }
runtime_drush() {
  if [ "$SOUS_RUNTIME" = ddev ]; then ddev drush "$@"; else
    [ -x "$SOUS_PROJECT_ROOT/vendor/bin/drush" ] || { echo 'Project Drush is unavailable; run Composer install first.' >&2; return 1; }
    "$SOUS_PROJECT_ROOT/vendor/bin/drush" "$@"
  fi
}
runtime_environment() {
  if [ "${SOUS_ENVIRONMENT+x}" = x ]; then
    case "$SOUS_ENVIRONMENT" in local|staging|production) printf '%s\n' "$SOUS_ENVIRONMENT" ;; *) echo 'SOUS_ENVIRONMENT must be local, staging, or production.' >&2; return 2 ;; esac
  elif [ "$SOUS_RUNTIME" = ddev ] || [ "${IS_DDEV_PROJECT:-}" = true ]; then echo local; else echo production; fi
}
runtime_require_development() {
  _sous_environment=$(runtime_environment) || return $?
  [ "$_sous_environment" != production ] || { echo 'This development workflow is disabled in production.' >&2; return 1; }
}
runtime_emulsify() {
  if [ "$SOUS_RUNTIME" = ddev ]; then ddev emulsify "$@"; else
    [ -x "$SOUS_PROJECT_ROOT/node_modules/.bin/emulsify" ] || { echo 'Project Emulsify CLI is unavailable; run npm ci first.' >&2; return 1; }
    "$SOUS_PROJECT_ROOT/node_modules/.bin/emulsify" "$@"
  fi
}
