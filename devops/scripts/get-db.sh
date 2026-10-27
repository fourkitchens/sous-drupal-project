#!/bin/sh

# Complete a local/staging backup download without exposing a partial file.
# Usage: npm run get-db -- [DESTINATION.sql|DESTINATION.sql.gz]
# The default is reference/site-db.sql.gz; paths are project-root relative or
# absolute. Projects configure devops/hooks/download-db.sh explicitly. Its sole
# argument is an absolute private output path in the caller's environment.
# A failed producer/validation preserves the completed destination and exit code.

set -eu
. "$(dirname "$0")/_runtime.sh"
. "$SOUS_PROJECT_ROOT/devops/scripts/_database.sh"
runtime_require_development

if [ "$#" -gt 1 ]; then
  echo 'Usage: npm run get-db -- [destination.sql|destination.sql.gz]' >&2
  exit 2
fi
if [ "$#" -eq 0 ]; then download_destination=reference/site-db.sql.gz; else download_destination=$1; fi
case "$download_destination" in
  *.sql|*.sql.gz) ;;
  *) echo 'Use a .sql or .sql.gz download destination.' >&2; exit 2 ;;
esac
if [ -d "$download_destination" ]; then
  echo 'The database download destination must be a file.' >&2
  exit 1
fi
# Keep the first downloaded byte private. Placing temporary storage beside the
# destination also keeps the final rename on the same filesystem and atomic.
umask 077
download_parent="$(dirname "$download_destination")"
mkdir -p -- "$download_parent"
download_parent="$(CDPATH='' cd "$download_parent" && pwd -P)"
download_name="$(basename "$download_destination")"
download_directory="$(mktemp -d "$download_parent/.sous-download.XXXXXX")"
finish_download() {
  # Preserve an earlier producer/validation error if removing partial files also
  # fails. Disable EXIT before exiting so the cleanup cannot call itself again.
  download_status=$?
  trap - 0
  if rm -rf -- "$download_directory"; then :; else
    cleanup_status=$?
    echo "Could not remove the private download directory ($cleanup_status)." >&2
    if [ "$download_status" -eq 0 ]; then download_status=$cleanup_status; fi
  fi
  exit "$download_status"
}
trap 'finish_download' 0
trap 'exit 129' HUP
trap 'exit 130' INT
trap 'exit 143' TERM

# Projects configure this hook explicitly. No provider or remote source is set.
sh devops/hooks/download-db.sh "$download_directory/$download_name"
# Reject symbolic links before validation/chmod; the producer must write a
# regular payload file at the supplied path in this private directory.
if [ -L "$download_directory/$download_name" ]; then
  echo 'A downloaded backup must be a regular file, not a symlink.' >&2
  exit 1
fi
# A separate validation subdirectory avoids collisions with any selected name,
# including backup.sql or verified.sql. Never publish the decompressed scratch.
mkdir "$download_directory/validation"
database_prepare "$download_directory/$download_name" "$download_name" "$download_directory/validation/backup.sql"
# Only a complete, validated payload replaces an existing completed backup.
chmod 600 "$download_directory/$download_name"
mv -f -- "$download_directory/$download_name" "$download_parent/$download_name"
echo "Database backup completed: $download_destination"
