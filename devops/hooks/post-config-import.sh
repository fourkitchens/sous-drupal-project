#!/bin/sh
set -eu
. "$(dirname "$0")/../scripts/_runtime.sh"

# Runs after confim locally, in CI deploys and on hosts; receives any supplied alias.
# Put local-only values in settings.local.php.

# runtime_drush "$@" cache:rebuild
:
