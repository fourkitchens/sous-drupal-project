#!/bin/bash

# Apply one already installed recipe through the shared DDEV/native entry point.
# Usage: npm run install-recipe -- vendor/package
# The package basename identifies web/recipes/<name>, including owned bundled
# recipes; this command does not download or unpack Composer packages. A failed
# application stops before cache rebuild so a later success cannot hide it.
set -euo pipefail
# shellcheck source=_runtime.sh
source "$(dirname "$0")/_runtime.sh"

# Restrict the selector to a package name, not a path or arbitrary Drush input.
if [[ $# -ne 1 || ! "$1" =~ ^[a-z0-9_.-]+/[a-z0-9_.-]+$ ]]; then
  echo 'Usage: npm run install-recipe -- vendor/package' >&2
  exit 2
fi
recipe_directory_name="${1##*/}"
if [[ "$recipe_directory_name" == . || "$recipe_directory_name" == .. ]]; then
  echo "Invalid recipe package name: $1" >&2
  exit 2
fi

if [[ "$SOUS_RUNTIME" == ddev ]]; then
  # The DDEV wrapper re-enters this implementation inside the container, where
  # the runtime switches to native tools instead of invoking DDEV recursively.
  exec ddev install-recipe "$@"
fi

# Core resolves recipe includes relative to the Drupal root, not project root.
cd web
runtime_drush recipe "recipes/$recipe_directory_name"
runtime_drush cr
