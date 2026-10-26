#!/bin/sh

# Build a generated theme's production assets using its installed npm tools.
# Usage: npm run theme-build -- [theme-directory] [build arguments...]
# Install locked dependencies first; this command does not install packages.

set -eu
. "$(dirname "$0")/_runtime.sh"

# Naming setup replaces this source placeholder. Consume a leading directory,
# while leaving options for the theme's own build task unchanged.
theme_directory=web/themes/custom/sous-project
case "${1:-}" in ''|--*) ;; *) theme_directory="$1"; shift ;; esac
theme_directory="$(runtime_theme_directory "$theme_directory")"
runtime_npm --prefix "$theme_directory" run build -- "$@"
