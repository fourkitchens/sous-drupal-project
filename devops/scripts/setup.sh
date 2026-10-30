#!/bin/sh
# Restore a local site from an optional SQL backup.
set -eu
. "$(dirname "$0")/_runtime.sh"
runtime_start
runtime_composer install
runtime_npm ci --include=dev
runtime_npm run theme-ci
runtime_npm run theme-build
sh devops/scripts/import-data.sh "$@"
sh devops/scripts/confim.sh
runtime_drush uli --name=sous_chef
