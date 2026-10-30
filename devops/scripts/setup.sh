#!/bin/sh
# Restore an existing site from its locked code and a database backup.
# Fresh create-project installations use sous-build.sh instead.
set -eu
. "$(dirname "$0")/_runtime.sh"

# Install and build this checkout before loading its database and configuration.
runtime_start
runtime_composer install
runtime_npm ci --include=dev
runtime_npm run theme-ci
runtime_npm run theme-build

# Import handles cache rebuilding, the post-import hook and local sanitization.
sh devops/scripts/import-data.sh "$@"
# Deploy database updates and this checkout's configuration after restoration.
sh devops/scripts/confim.sh
# Print a login link only after the full restore succeeds.
runtime_drush uli --name=sous_chef
