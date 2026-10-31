#!/bin/sh
# Restore a local database and replace copied account passwords and emails.
# setup.sh uses this same import flow when restoring a complete site.
set -eu
. "$(dirname "$0")/_runtime.sh"
runtime_require_ddev

# Use the shared backup path by default; get-db.sh can fetch it when needed.
[ "$#" -le 1 ] || { echo 'Usage: import-data.sh [BACKUP.sql|BACKUP.sql.gz]' >&2; exit 2; }
backup=${1-reference/site-db.sql.gz}
if [ "$#" -eq 0 ] && [ ! -f "$backup" ]; then sh devops/scripts/get-db.sh; fi
if [ ! -s "$backup" ]; then
  echo "Missing or empty database backup: $backup" >&2
  exit 1
fi
# Check the archive before running commands that replace the current database.
case "$backup" in
  *.sql.gz) gzip -t -- "$backup" ;;
  *.sql) ;;
  *) echo 'Use a .sql or .sql.gz database backup.' >&2; exit 2 ;;
esac

# Capture the import result so the project hook cannot hide an import failure.
status=0
# DDEV's CLI runs on the host. Inside its container, connect with Drush directly
# instead of trying to invoke DDEV again.
if [ "${IS_DDEV_PROJECT:-}" = true ]; then
  runtime_drush sql:drop --yes || status=$?
  # Stream SQL to Drush; gzip -dcf also passes plain .sql files through unchanged.
  if [ "$status" -eq 0 ]; then gzip -dcf -- "$backup" | runtime_drush sql:cli || status=$?; fi
else
  # Host imports keep DDEV's standard import handling and its own import hooks.
  ddev import-db --file="$backup" || status=$?
fi

# Give the project hook a cleanup opportunity, then return the original error.
# Do not continue to cache rebuilding or sanitization after a failed import.
if [ "$status" -ne 0 ]; then
  if [ -f devops/hooks/post-import.sh ]; then
    # ":" prevents a cleanup failure from replacing the original import error.
    sh devops/hooks/post-import.sh || :
  fi
  exit "$status"
fi

# Rebuild against the imported database before running project adjustments.
runtime_drush cache:rebuild
if [ -f devops/hooks/post-import.sh ]; then
  sh devops/hooks/post-import.sh
fi
# Replace copied account credentials before developers use the local site.
runtime_drush sql:sanitize -y --sanitize-password=admin --sanitize-email=user-%uid@example.com
