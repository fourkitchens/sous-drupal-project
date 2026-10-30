#!/bin/sh
# A failed download must not replace the last usable backup.
set -eu
. "$(dirname "$0")/_runtime.sh"
[ "$#" -le 1 ] || { echo 'Usage: get-db.sh [DESTINATION.sql|DESTINATION.sql.gz]' >&2; exit 2; }
destination=${1-reference/site-db.sql.gz}
case "$destination" in
  *.sql|*.sql.gz) ;;
  *) echo 'Use a .sql or .sql.gz download destination.' >&2; exit 2 ;;
esac
[ ! -d "$destination" ] || { echo 'The download destination must be a file.' >&2; exit 1; }
partial="$destination.part"
mkdir -p "$(dirname "$destination")"
rm -f -- "$partial"
status=0
if [ -f devops/hooks/download-db.sh ]; then sh devops/hooks/download-db.sh "$partial" || status=$?; fi
if [ "$status" -eq 0 ] && { [ ! -f "$partial" ] || [ ! -s "$partial" ]; }; then
  echo 'The download did not produce a non-empty backup.' >&2
  status=1
fi
if [ "$status" -eq 0 ]; then
  case "$destination" in *.sql.gz) gzip -t -- "$partial" || status=$? ;; esac
fi
if [ "$status" -eq 0 ]; then mv -f -- "$partial" "$destination" || status=$?; fi
if [ "$status" -ne 0 ]; then rm -f -- "$partial"; exit "$status"; fi
