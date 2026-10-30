#!/bin/sh
# Fetch through the site's download hook without replacing a usable backup
# until the new file has passed validation.
set -eu
. "$(dirname "$0")/_runtime.sh"
[ "$#" -le 1 ] || { echo 'Usage: get-db.sh [DESTINATION.sql|DESTINATION.sql.gz]' >&2; exit 2; }
destination=${1-reference/site-db.sql.gz}
case "$destination" in
  *.sql|*.sql.gz) ;;
  *) echo 'Use a .sql or .sql.gz download destination.' >&2; exit 2 ;;
esac
[ ! -d "$destination" ] || { echo 'The download destination must be a file.' >&2; exit 1; }
# The hook receives this temporary filename as $1, keeping partial downloads
# separate from the final backup that import-data.sh reads.
partial="$destination.part"
mkdir -p "$(dirname "$destination")"
rm -f -- "$partial"
status=0
if [ -f devops/hooks/download-db.sh ]; then sh devops/hooks/download-db.sh "$partial" || status=$?; fi
# A successful download must produce data; compressed backups must also open.
if [ "$status" -eq 0 ] && { [ ! -f "$partial" ] || [ ! -s "$partial" ]; }; then
  echo 'The download did not produce a non-empty backup.' >&2
  status=1
fi
if [ "$status" -eq 0 ]; then
  case "$destination" in *.sql.gz) gzip -t -- "$partial" || status=$? ;; esac
fi
# Publish only validated data; otherwise keep the last backup and return the error.
if [ "$status" -eq 0 ]; then mv -f -- "$partial" "$destination" || status=$?; fi
if [ "$status" -ne 0 ]; then rm -f -- "$partial"; exit "$status"; fi
