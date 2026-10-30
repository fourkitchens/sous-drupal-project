#!/bin/sh
# Populate a newly generated theme; theme-build.sh handles routine rebuilds.
set -eu
. "$(dirname "$0")/_runtime.sh"
theme_directory=${1:-web/themes/custom/${npm_package_config_theme:?Run through npm or pass a theme directory.}}
# Check before the system installer can overwrite initialized theme source.
runtime_exec node devops/scripts/theme-initialize.mjs --check "$theme_directory"

# Keep the temporary source fixes tied to the reviewed UI Kit release.
(cd "$theme_directory" && runtime_emulsify system install \
  --repository https://github.com/emulsify-ds/emulsify-ui-kit.git \
  --checkout 1.9.0 --variant drupal --all --yes)
# The published CLI and Core still need these dependency fixes.
# Storybook addons must match their published host version.
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
# Reproduce a lockfile when present, or resolve the new theme's first install.
# shellcheck disable=SC2016
runtime_exec sh -c 'cd "$1"; if [ -f package-lock.json ]; then npm ci --include=dev; else npm install --include=dev; fi' sh "$theme_directory"
# Normalize the pinned starter before its first build can consume that source.
runtime_exec node devops/scripts/theme-initialize.mjs "$theme_directory"
sh devops/scripts/theme-build.sh "$theme_directory"
# Mark success only after normalization and the first build have both passed.
runtime_exec touch "$theme_directory/.sous-initialized"
