#!/bin/sh
# Customize this script to download the site's database backup.
# Run npm run get-db from the project root.
set -eu

echo "Edit devops/scripts/get-db.sh to download this site's database."
exit 1

# terminus backup:get SITE.live --element=db --to=reference/site-db.sql.gz
# ddev pull also works when the site's provider is configured.
