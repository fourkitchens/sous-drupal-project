#!/usr/bin/env bash

# Identifies a given commit as:
# "normal" - changes meant for release to site upstreams
# "nonrelease" - changes meant to modify CI or internal tooling
# "mixed" - changes both of the above
function identify_commit_type() {
  local commit=$1
  local has_normal_changes=0
  local has_nonrelease_changes=0

  affected_paths=$(git show "${commit}" --pretty=oneline --name-only | tail -n +2)
  for path in $affected_paths; do
      if [[ $path =~ ^.circleci/ || $path =~ ^devops/ || $path == "README-internal.md" ]] ; then
        has_nonrelease_changes=1
        continue
      fi

      has_normal_changes=1
  done

  if [[ $has_normal_changes -ne 0 && $has_nonrelease_changes -ne 0 ]]; then
    echo "mixed"
    return 0
  fi

  if [[ $has_normal_changes -ne 0 ]]; then
    echo "normal"
    return 0
  fi

  echo "nonrelease"
}
