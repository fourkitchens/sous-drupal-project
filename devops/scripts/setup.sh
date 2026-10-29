#!/bin/sh

# Select fresh/restore; after bootstrap, an omitted mode restores the site.
# Usage: npm run setup -- [fresh|restore] [backup.sql|backup.sql.gz]
# Fresh installs replace the development database; restore forwards the optional
# backup to rebuild.sh. Both reject production before destructive work. Shared
# Shared helpers use DDEV and resolve the project root.
set -eu
. "$(dirname "$0")/_runtime.sh"

# A completed bootstrap retires its installer, so ordinary setup means restore.
if [ "$#" -eq 0 ] && [ ! -f ./devops/scripts/sous-build.sh ]; then set -- restore; fi

# Reject ambiguous invocations before starting either workflow.
if [ "$#" -lt 1 ] || [ "$#" -gt 2 ]; then
  echo 'Usage: npm run setup -- fresh|restore [backup.sql|backup.sql.gz]' >&2
  exit 2
fi

case "$1" in
  fresh)
    if [ "$#" -ne 1 ]; then echo 'Fresh setup does not accept a database backup.' >&2; exit 2; fi
    runtime_require_development
    # Successful bootstrap removes this child, so a generated site cannot
    # accidentally repeat fresh installation through the normal setup command.
    if [ ! -f ./devops/scripts/sous-build.sh ]; then
      echo "Fresh installation is only available before bootstrap cleanup. Use restore for an existing site." >&2
      exit 1
    fi
    exec ./devops/scripts/sous-build.sh
    ;;
  restore)
    # Consume only the mode; keep the selected backup as one quoted argument.
    shift
    runtime_require_development
    runtime_start
    runtime_npm run theme-ci
    sh devops/scripts/theme-build.sh
    # Replace the dispatcher so callers receive the child workflow's exit code.
    exec ./devops/scripts/rebuild.sh "$@"
    ;;
  *)
    echo "Usage: npm run setup -- fresh|restore" >&2
    exit 2
    ;;
esac
