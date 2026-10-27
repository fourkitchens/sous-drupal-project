#!/bin/sh

# Export the selected local/staging database through project Drush.
# Usage: npm run local-data-bak -- [DRUSH_ARGS]
# Default output is a gzip backup under reference/ with a minute-resolution
# timestamp. The result path is relative to Drupal's web root; caller options
# are forwarded unchanged. This command exports data and does not sanitize it.

set -eu
. "$(dirname "$0")/_runtime.sh"
runtime_require_development
runtime_drush sql-dump --result-file="../reference/db-$(date +%Y-%m-%d-%H%M).sql" --gzip "$@"
