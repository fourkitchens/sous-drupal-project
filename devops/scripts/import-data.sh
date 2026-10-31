#!/bin/sh
# Import a database backup and sanitize copied accounts for local development.
# Run npm run import-data [-- backup] from the project root.
set -eu

backup=${1:-reference/site-db.sql.gz}
if [ ! -f "$backup" ]; then
  sh devops/scripts/get-db.sh
fi

ddev import-db --file="$backup"
ddev drush cache:rebuild
ddev drush sql:sanitize -y --sanitize-password=admin --sanitize-email='user-%uid@example.com'

# Add this site's post-import steps here.
