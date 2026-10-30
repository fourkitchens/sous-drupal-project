#!/bin/sh
set -eu
. "$(dirname "$0")/_runtime.sh"
status=0
runtime_drush deploy --yes "$@" || status=$?
if [ -f devops/hooks/post-config-import.sh ]; then
  if sh devops/hooks/post-config-import.sh; then :; else
    hook_status=$?
    [ "$status" -ne 0 ] || status=$hook_status
  fi
fi
exit "$status"
