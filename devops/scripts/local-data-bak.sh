#!/bin/sh
# Keep a timestamped, compressed local backup outside the public web directory.
set -eu
. "$(dirname "$0")/_runtime.sh"
runtime_drush sql-dump --result-file="../reference/db-$(date +%Y-%m-%d-%H%M).sql" --gzip "$@"
