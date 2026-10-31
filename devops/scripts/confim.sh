#!/bin/sh
# Deploy database updates, configuration and deployment hooks in any environment.
# Run npm run confim [-- @alias or --uri=URL] from the project root.
set -eu

case "${1:-}" in
  @*)
    site=$1
    shift
    set -- "$site" deploy -y "$@"
    ;;
  *) set -- deploy -y "$@" ;;
esac

exec sh devops/scripts/run-php.sh vendor/bin/drush "$@"
