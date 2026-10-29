#!/bin/sh

# Apply one already installed recipe through DDEV.
# Usage: npm run install-recipe -- vendor/package
# The package basename identifies the Composer-managed web/recipes/<name>.
# This command does not download or unpack Composer packages. Refresh extension
# discovery before validation; rebuild caches only after a successful recipe.
set -eu
# shellcheck source=_runtime.sh
. "$(dirname "$0")/_runtime.sh"

# Restrict the selector to a package name, not a path or arbitrary Drush input.
usage() {
  echo 'Usage: npm run install-recipe -- vendor/package' >&2
  exit 2
}
[ "$#" -eq 1 ] || usage
case "$1" in
  *[!a-z0-9_./-]*|/*|*/|*/*/*) usage ;;
  */*) ;;
  *) usage ;;
esac
recipe_directory_name="${1##*/}"
if [ "$recipe_directory_name" = . ] || [ "$recipe_directory_name" = .. ]; then
  echo "Invalid recipe package name: $1" >&2
  exit 2
fi

if [ "${IS_DDEV_PROJECT:-}" != true ]; then
  # The DDEV wrapper re-enters this implementation inside the container, where
  # the helpers use project tools without invoking DDEV recursively.
  exec ddev install-recipe "$@"
fi

# Core resolves recipe includes relative to the Drupal root, not project root.
cd web
# Composer can add extensions after Drupal cached the available module/theme
# lists. Reset those native lists so Core validates the files just installed.
runtime_drush php:eval '\Drupal::service("extension.list.module")->reset(); \Drupal::service("extension.list.theme")->reset();'
runtime_drush recipe "recipes/$recipe_directory_name"
runtime_drush cr
