#!/bin/sh
set -eu
. "$(dirname "$0")/../scripts/_runtime.sh"

# Write "$1" for get-db to validate before replacement; omit --gzip for plain SQL.
# runtime_drush @source sql:dump --gzip > "$1"
:
