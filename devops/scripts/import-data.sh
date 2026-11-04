#!/bin/sh
# Import a database backup and sanitize copied accounts for local development.
# Run npm run import-data [-- backup] from the project root.
set -eu

# Only an omitted path can trigger the project's backup download.
if [ "$#" -gt 0 ]; then
  backup=$1
else
  backup=reference/site-db.sql.gz
  if [ ! -e "$backup" ] && [ ! -L "$backup" ]; then
    sh devops/scripts/get-db.sh
  fi
fi

# Check the source before DDEV can replace the local database.
if [ ! -f "$backup" ] || [ ! -r "$backup" ] || [ ! -s "$backup" ]; then
  printf 'Database backup must be a readable, nonempty file: "%s"\n' "$backup" >&2
  exit 1
fi
case "$backup" in
  *.gz|*.tgz)
    # Read through the archive to catch truncation before import.
    if ! gzip -t < "$backup"; then
      printf 'Database backup failed gzip integrity check: "%s"\n' "$backup" >&2
      exit 1
    fi
    ;;
esac

ddev import-db --file="$backup"
ddev drush cache:rebuild
ddev drush sql:sanitize -y --sanitize-password=admin --sanitize-email='user-%uid@example.com'

# Add this site's post-import steps here.
