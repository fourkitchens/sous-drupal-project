#!/bin/sh
# Reset this branch with Composer dependencies, a database and configuration.
# Run npm run rebuild [-- backup] from the project root.
set -eu

ddev start
ddev composer install
sh devops/scripts/import-data.sh "$@"
ddev drush deploy -y
ddev drush uli --name=sous_chef
