#!/bin/sh
# Use Drush deploy so database updates, config import and caches stay in order.
set -eu
. "$(dirname "$0")/_runtime.sh"
status=0
runtime_drush deploy --yes "$@" || status=$?
# Let project cleanup run after deployment, even when deployment fails.
# Preserve the deployment error; use the hook's error only if deployment succeeded.
if [ -f devops/hooks/post-config-import.sh ]; then
  if sh devops/hooks/post-config-import.sh; then :; else
    hook_status=$?
    [ "$status" -ne 0 ] || status=$hook_status
  fi
fi
exit "$status"
