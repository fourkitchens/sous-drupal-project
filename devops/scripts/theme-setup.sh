#!/bin/sh
# Populate and build a newly generated Emulsify theme.
# Run sh devops/scripts/theme-setup.sh web/themes/custom/THEME on your computer.
set -eu
theme_directory=${1:-web/themes/custom/${npm_package_config_theme:?Run through npm or pass a theme directory.}}
# Check before the system installer can overwrite initialized theme source.
node devops/scripts/theme-initialize.mjs --check "$theme_directory"

# Keep the temporary source fixes tied to the reviewed UI Kit release.
(cd "$theme_directory" && npx emulsify system install \
  --repository https://github.com/emulsify-ds/emulsify-ui-kit.git \
  --checkout 1.9.0 --variant drupal --all --yes)
# Upstream: https://github.com/emulsify-ds/emulsify-core/pull/322 https://github.com/emulsify-ds/emulsify-cli/pull/377
# Remove when UI Kit and Core releases pass lint and build without this.
npm --prefix "$theme_directory" pkg set \
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
if [ -f "$theme_directory/package-lock.json" ]; then
  npm --prefix "$theme_directory" ci --include=dev
else
  npm --prefix "$theme_directory" install --include=dev
fi
# Normalize the pinned starter before its first build can consume that source.
node devops/scripts/theme-initialize.mjs "$theme_directory"
npm --prefix "$theme_directory" run build
