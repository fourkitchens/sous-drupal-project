#!/bin/sh

# Restore an existing generated site in local/staging, replacing its DB.
# Usage: npm run rebuild -- [BACKUP.sql|BACKUP.sql.gz]
# setup.sh restore delegates here. The backup selection/default is owned by
# import-data.sh. Failures stop dependent work; login is the final step only.

set -eu
. "$(dirname "$0")/_runtime.sh"
runtime_require_development

echo "Verify project dependencies are installed..."
# Finish locked dependency installation and asset building before touching data.
runtime_composer install
runtime_npm ci --include=dev
runtime_npm run theme-ci
sh devops/scripts/theme-build.sh
sh devops/scripts/import-data.sh "$@"
sh devops/scripts/confim.sh
runtime_drush uli --name=sous_chef
