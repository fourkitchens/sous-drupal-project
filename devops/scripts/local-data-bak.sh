#!/bin/sh

set -eu
. "$(dirname "$0")/_runtime.sh"
runtime_drush sql-dump --result-file="../reference/db-$(date +%Y-%m-%d-%H%M).sql" --gzip "$@"
