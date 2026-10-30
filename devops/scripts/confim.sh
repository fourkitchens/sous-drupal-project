#!/bin/sh
# Use Drush deploy so database updates, config import and caches stay in order.
set -eu
. "$(dirname "$0")/_runtime.sh"
site=''
case "${1:-}" in
  @*)
    site=$1
    shift
    ;;
esac
status=0
if [ -n "$site" ]; then
  runtime_drush "$site" deploy --yes "$@" || status=$?
else
  runtime_drush deploy --yes "$@" || status=$?
fi
# The hook receives only the selected site, not deploy-specific arguments.
if [ -n "$site" ]; then
  set -- "$site"
else
  set --
fi
# Let project cleanup run after deployment, even when deployment fails.
# Preserve the deployment error; use the hook's error only if deployment succeeded.
if [ -f devops/hooks/post-config-import.sh ]; then
  if sh devops/hooks/post-config-import.sh "$@"; then :; else
    hook_status=$?
    [ "$status" -ne 0 ] || status=$hook_status
  fi
fi
exit "$status"
