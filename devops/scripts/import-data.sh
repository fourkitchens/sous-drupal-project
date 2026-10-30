#!/bin/sh
set -eu
. "$(dirname "$0")/_runtime.sh"
[ "$#" -le 1 ] || { echo 'Usage: import-data.sh [BACKUP.sql|BACKUP.sql.gz]' >&2; exit 2; }
backup=${1-reference/site-db.sql.gz}
if [ "$#" -eq 0 ] && [ ! -f "$backup" ]; then sh devops/scripts/get-db.sh; fi
[ -f "$backup" ] && [ -s "$backup" ] || { echo "Missing or empty database backup: $backup" >&2; exit 1; }
case "$backup" in
  *.sql.gz) gzip -t -- "$backup" ;;
  *.sql) ;;
  *) echo 'Use a .sql or .sql.gz database backup.' >&2; exit 2 ;;
esac
status=0
if [ "${IS_DDEV_PROJECT:-}" = true ]; then
  runtime_drush sql:drop --yes || status=$?
  if [ "$status" -eq 0 ]; then gzip -dcf -- "$backup" | runtime_drush sql:cli || status=$?; fi
else
  ddev import-db --file="$backup" || status=$?
fi
if [ "$status" -ne 0 ]; then
  if [ -f devops/hooks/post-import.sh ]; then
    sh devops/hooks/post-import.sh || :
  fi
  exit "$status"
fi
runtime_drush cache:rebuild
if [ -f devops/hooks/post-import.sh ]; then
  sh devops/hooks/post-import.sh
fi
runtime_drush sql:sanitize -y --sanitize-password=admin --sanitize-email=user-%uid@example.com
