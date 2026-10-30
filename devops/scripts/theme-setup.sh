#!/bin/sh
set -eu
. "$(dirname "$0")/_runtime.sh"
theme_directory=${1:-web/themes/custom/${npm_package_config_theme:?Run through npm or pass a theme directory.}}
runtime_exec node devops/scripts/theme-initialize.mjs --check "$theme_directory"

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
# shellcheck disable=SC2016
runtime_exec sh -c 'cd "$1"; if [ -f package-lock.json ]; then npm ci --include=dev; else npm install --include=dev; fi' sh "$theme_directory"
runtime_exec node devops/scripts/theme-initialize.mjs "$theme_directory"
sh devops/scripts/theme-build.sh "$theme_directory"
runtime_exec touch "$theme_directory/.sous-initialized"
