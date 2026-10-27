#!/bin/sh
set -eu
. "$(dirname "$0")/../scripts/_runtime.sh"

# Write the backup to "$1"; omit --gzip for a plain .sql destination.
# runtime_drush @source sql:dump --gzip > "$1"
:
