#!/bin/sh

# Restore a local DDEV site, replacing its database.
# Usage: npm run rebuild -- [BACKUP.sql|BACKUP.sql.gz]

set -eu
. "$(dirname "$0")/_runtime.sh"

echo "Verify project dependencies are installed..."
# Refresh root dependencies; setup restore separately installs/builds the theme.
runtime_composer install
runtime_npm ci --include=dev
sh devops/scripts/import-data.sh "$@"
sh devops/scripts/confim.sh
runtime_drush uli --name=sous_chef
