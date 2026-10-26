#!/bin/sh

# Deploy this site's complete configuration using its installed project Drush.
# Usage: npm run confim -- [DRUSH_ARGS]
# Preserve deployment options and local target selectors; remote aliases are
# rejected by the shared runtime. Preflight must pass before native deploy.
# Afterwards, attempt all safeguards and preserve the first deployment error.
# Local project hooks are skipped in production; the active guard still runs.

set -eu
. "$(dirname "$0")/_runtime.sh"

# Native deploy imports the site's configured complete sync directory, runs
# database updates/deploy hooks, and rebuilds caches. Projects own that export.
runtime_environment_check import "$@"

# Catch the child failure here: EXIT traps inside nested shell functions can
# see that function's arguments instead of the selected project's arguments.
# Defer a signal until the child returns so cleanup has the original context.
deployment_signal=0
trap 'deployment_signal=129' HUP
trap 'deployment_signal=130' INT
trap 'deployment_signal=143' TERM
if runtime_drush deploy --yes "$@"; then deployment_status=0; else deployment_status=$?; fi
if [ "$deployment_status" -eq 0 ] && [ "$deployment_signal" -ne 0 ]; then
  deployment_status=$deployment_signal
fi
# Configuration or deploy hooks can reactivate integrations even when deploy
# later fails. Reapply local safeguards and check active state in either case.
if runtime_safeguards post-config-import "$@"; then :; else
  cleanup_status=$?
  if [ "$deployment_status" -eq 0 ]; then deployment_status=$cleanup_status; fi
fi
# Also report a signal received during cleanup when no earlier error exists.
if [ "$deployment_status" -eq 0 ]; then deployment_status=$deployment_signal; fi
exit "$deployment_status"
