#!/bin/sh
# Export configuration for review and version control in any environment.
# Run npm run confex [-- @alias or --uri=URL] from the project root.
set -eu

case "${1:-}" in
  @*)
    site=$1
    shift
    set -- "$site" config:export -y "$@"
    ;;
  *) set -- config:export -y "$@" ;;
esac

exec sh devops/scripts/run-php.sh vendor/bin/drush "$@"
