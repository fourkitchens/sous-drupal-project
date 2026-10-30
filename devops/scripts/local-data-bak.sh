#!/bin/sh

# Export the local database to a timestamped gzip backup under reference/.
# Usage: npm run local-data-bak -- [DRUSH_ARGS]

set -eu
. "$(dirname "$0")/_runtime.sh"
runtime_drush sql-dump --result-file="../reference/db-$(date +%Y-%m-%d-%H%M).sql" --gzip "$@"
