#!/bin/sh

# Initialize an already generated Emulsify theme during development bootstrap.
# Usage: sh devops/scripts/theme-setup.sh [theme-directory]
# Requires a generated theme and the project-local CLI. Install the selected
# system through its native CLI, create/reproduce the theme lock, then build.
# Existing-site routine installs/builds should use theme-ci/theme-build instead.

set -eu
. "$(dirname "$0")/_runtime.sh"

# Keep the theme path relative to this project in either runtime.
theme_directory="${1:-web/themes/custom/sous-project}"
theme_directory="$(CDPATH='' cd "$theme_directory" && pwd -P)"
case "$theme_directory" in
  "$SOUS_PROJECT_ROOT"/*) theme_directory="${theme_directory#"$SOUS_PROJECT_ROOT/"}" ;;
  *) echo 'The theme directory must be inside this project.' >&2; exit 2 ;;
esac
# Reject initialized/project-owned themes before the CLI can copy any source.
runtime_exec node devops/scripts/theme-initialize.mjs --check "$theme_directory"

# Keep the library checkout reproducible; project-owned components can evolve later.
(cd "$theme_directory" && runtime_emulsify system install \
  --repository https://github.com/emulsify-ds/emulsify-ui-kit.git \
  --checkout 1.9.0 --variant drupal --all --yes)
# Keep the security overrides and one coherent Storybook peer cohort visible.
# Addons at 10.6.5 currently require a Storybook host that is not published.
runtime_npm --prefix "$theme_directory" pkg set \
  'overrides.@emulsify/cli.simple-git=^4.0.2' \
  'overrides.@emulsify/core.pa11y=^10.0.0' \
  'overrides.@emulsify/core.storybook=10.6.4' \
  'overrides.@emulsify/core.@storybook/addon-a11y=10.6.4' \
  'overrides.@emulsify/core.@storybook/addon-links=10.6.4' \
  'overrides.@emulsify/core.@storybook/addon-themes=10.6.4' \
  'overrides.@emulsify/core.@storybook/react=10.6.4' \
  'overrides.@emulsify/core.@storybook/react-vite=10.6.4' \
  'overrides.@emulsify/core.@storybook/builder-vite=10.6.4' \
  'overrides.@emulsify/core.@storybook/react-dom-shim=10.6.4' \
  'overrides.@emulsify/core.eslint-plugin-storybook=10.6.4'
# A newly generated theme has no lockfile. Subsequent installs reproduce it.
# The selected runtime expands the theme path.
# shellcheck disable=SC2016
runtime_exec sh -c 'cd "$1"; if [ -f package-lock.json ]; then npm ci --include=dev; else npm install --include=dev; fi' sh "$theme_directory"
# Normalize the pinned starter's stock source and generated-output check scope.
# This is first setup, not the routine locked theme-ci/theme-build workflow.
runtime_exec node devops/scripts/theme-initialize.mjs "$theme_directory"
sh devops/scripts/theme-build.sh "$theme_directory"
runtime_exec touch "$theme_directory/.sous-initialized"
