#!/bin/sh

# Shared backup preparation for import-data.sh and get-db.sh; source this file.
# database_prepare INPUT FORMAT_NAME OUTPUT_SQL validates and copies/decompresses
# a backup without changing INPUT. Callers own OUTPUT_SQL's private directory
# and remove it on exit. FORMAT_NAME is the selected .sql/.sql.gz name, even
# when INPUT is a temporary download. This checks the compression format and
# nonempty payload, not SQL syntax or whether the backup belongs to this site.
database_prepare() (
  database_file="$1"
  database_name="$2"
  database_sql="$3"
  if [ ! -f "$database_file" ] || [ ! -r "$database_file" ] || [ ! -s "$database_file" ]; then
    echo "Database backup is missing, unreadable or empty: $database_file" >&2
    exit 1
  fi
  # Filename suffixes alone cannot distinguish a dump from a renamed archive.
  database_signature="$(od -An -tx1 -N6 < "$database_file" | tr -d ' \n')"
  case "$database_name" in
    *.sql.gz)
      case "$database_signature" in
        1f8b*) ;;
        *) echo 'A .sql.gz backup must have a gzip signature.' >&2; exit 1 ;;
      esac
      # Validate the entire gzip stream before producing the SQL snapshot.
      gzip -t -- "$database_file" || exit $?
      gzip -dc -- "$database_file" > "$database_sql" || exit $?
      ;;
    *.sql)
      # Reject known compressed formats instead of feeding binary data to SQL.
      case "$database_signature" in
        1f8b*|504b0304*|504b0506*|504b0708*|425a68*|fd377a585a00*|28b52ffd*)
          echo 'A .sql backup must be plain SQL, not a compressed archive.' >&2
          exit 1
          ;;
      esac
      cp -- "$database_file" "$database_sql" || exit $?
      ;;
    *) echo 'Use a .sql or .sql.gz database backup.' >&2; exit 2 ;;
  esac
  # A valid gzip stream may contain zero SQL bytes; it is unsafe to import.
  if [ ! -s "$database_sql" ]; then
    echo 'Database backup contains empty SQL.' >&2
    exit 1
  fi
  # Copying a read-only source must still leave the owned snapshot readable.
  chmod 600 "$database_sql"
)
