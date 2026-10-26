#!/bin/sh

# Replace a local/nonproduction database from a selected .sql/.sql.gz backup.
# Usage: npm run import-data -- [--defer-sanitize] [BACKUP]
# Paths are absolute or project-root relative; the default is
# reference/site-db.sql.gz. Validate a private SQL snapshot before replacement,
# then attempt safeguards even on partial import failure. Standalone imports
# sanitize after safeguards; rebuild.sh defers that step until config deployment
# also succeeds. This command neither imports configuration nor prints a login.

set -eu
. "$(dirname "$0")/_runtime.sh"
. "$SOUS_PROJECT_ROOT/devops/scripts/_database.sh"
runtime_require_development

# Full refresh must pass both import and configuration safeguards first.
import_sanitize=1
if [ "${1:-}" = --defer-sanitize ]; then
  import_sanitize=0
  shift
fi
if [ "$#" -gt 1 ]; then
  echo 'Usage: npm run import-data -- [--defer-sanitize] [project-relative SQL backup]' >&2
  exit 2
fi
if [ "$#" -eq 0 ]; then database_backup=reference/site-db.sql.gz; else database_backup=$1; fi

# Only an omitted argument permits downloading the missing default. An explicit
# missing or empty path must fail rather than select a different database.
if [ ! -f "$database_backup" ] && [ "$#" -eq 0 ]; then
  echo 'Database backup is missing; invoking the project-owned download helper.'
  sh devops/scripts/get-db.sh
fi

# Freeze the selected input before destructive work, using an owner-only
# directory and SQL file for both host-DDEV and prepared-native imports.
umask 077
import_directory="$(mktemp -d "${TMPDIR:-/tmp}/sous-import.XXXXXX")"
import_safeguards_pending=0
finish_import() {
  # Capture the original exit immediately. Cleanup may fail too, but must not
  # replace an earlier import error; disabling EXIT prevents recursive cleanup.
  import_status=$?
  trap - 0
  # Once replacement starts, a failed import may still have changed tables.
  # Try cache/hook/environment cleanup; this does not roll the database back.
  if [ "$import_safeguards_pending" -eq 1 ]; then
    if runtime_safeguards post-import; then :; else
      cleanup_status=$?
      if [ "$import_status" -eq 0 ]; then import_status=$cleanup_status; fi
    fi
  fi
  # Remove only this invocation's private snapshot, never the selected backup.
  if rm -rf -- "$import_directory"; then :; else
    cleanup_status=$?
    if [ "$import_status" -eq 0 ]; then import_status=$cleanup_status; fi
  fi
  exit "$import_status"
}
trap 'finish_import' 0
trap 'exit 129' HUP
trap 'exit 130' INT
trap 'exit 143' TERM
# Validation failures leave the cleanup flag unset, so no database work runs.
database_prepare "$database_backup" "$database_backup" "$import_directory/backup.sql"

echo "Importing the validated SQL snapshot..."
# Arm failure cleanup immediately before the first possible database mutation.
import_safeguards_pending=1
if [ "$SOUS_RUNTIME" = ddev ]; then
  # Legacy DDEV hooks must not sanitize/log in before these safeguards.
  ddev import-db --skip-hooks --file="$import_directory/backup.sql"
else
  runtime_drush sql:drop --yes
  runtime_drush sql:cli < "$import_directory/backup.sql"
fi
# Attempt all safeguards before sanitization, then disarm the EXIT retry even
# when they fail: the safety attempt has already completed and its status wins.
if runtime_safeguards post-import; then
  import_safeguards_pending=0
else
  import_status=$?
  import_safeguards_pending=0
  exit "$import_status"
fi
# Rebuild owns this step when it requested deferred sanitization.
if [ "$import_sanitize" -eq 1 ]; then
  runtime_drush sql-sanitize -y --sanitize-password=admin --sanitize-email=user-%uid@example.com
fi
