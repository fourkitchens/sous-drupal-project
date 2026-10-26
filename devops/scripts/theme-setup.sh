#!/bin/sh

# Initialize an already generated Emulsify theme during development bootstrap.
# Usage: sh devops/scripts/theme-setup.sh [theme-directory]
# Requires a generated theme and the project-local CLI. Install the selected
# system through its native CLI, create/reproduce the theme lock, then build.
# Existing-site routine installs/builds should use theme-ci/theme-build instead.

set -eu
. "$(dirname "$0")/_runtime.sh"
runtime_require_development

# The runtime normalizes the path for both host DDEV and in-container/native
# commands, and rejects directories outside this project.
theme_directory="${1:-web/themes/custom/sous-project}"
theme_directory="$(runtime_theme_directory "$theme_directory")"

# Keep the library checkout reproducible; project-owned components can evolve later.
(cd "$theme_directory" && runtime_emulsify system install \
  --repository https://github.com/emulsify-ds/emulsify-ui-kit.git \
  --checkout 1.9.0 --variant drupal --all --yes)
# Upstream Core/CLI ranges still select vulnerable build tools. Keep the two
# reviewed security overrides visible in the site's manifest using native npm;
# do not patch dependency source or rewrite theme assets/component metadata.
runtime_npm --prefix "$theme_directory" pkg set \
  'overrides.@emulsify/cli.simple-git=^4.0.2' \
  'overrides.@emulsify/core.pa11y=^10.0.0'
# A newly generated theme has no lockfile. Subsequent installs reproduce it.
runtime_exec sh -c 'cd "$1"; if [ -f package-lock.json ]; then npm ci --include=dev; else npm install --include=dev; fi' sh "$theme_directory"
# Leave source, package scripts, libraries and component metadata owned by the
# selected upstream starter/system. Project-specific changes belong to the site.
runtime_exec sh devops/scripts/theme-build.sh "$theme_directory"
