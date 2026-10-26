#!/bin/sh

# Build the generated theme's static Storybook and production assets.
# Usage: npm run theme-storybook -- [theme-directory] [Storybook arguments...]
# Requires installed theme dependencies and writes the preview to web/storybook.

set -eu
. "$(dirname "$0")/_runtime.sh"

# Keep a supplied theme path separate from arguments intended for Storybook.
theme_directory=web/themes/custom/sous-project
case "${1:-}" in ''|--*) ;; *) theme_directory="$1"; shift ;; esac
theme_directory="$(runtime_theme_directory "$theme_directory")"
# npm runs in the selected runtime: a host-side DDEV call needs the container
# path, while native execution uses this checkout's absolute project path.
if [ "$SOUS_RUNTIME" = ddev ]; then
  storybook_directory=/var/www/html/web/storybook
else
  storybook_directory="$SOUS_PROJECT_ROOT/web/storybook"
fi
# The theme's supported Storybook task also compiles production assets.
runtime_npm --prefix "$theme_directory" run storybook-build -- --output-dir "$storybook_directory" "$@"
