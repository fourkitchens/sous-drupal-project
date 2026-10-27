#!/bin/sh

# Watch and rebuild an existing generated theme in local/staging mode.
# Usage: npm run theme-watch -- [theme-directory] [watch arguments...]
# Requires installed dependencies; the theme's develop task owns the long-running
# watcher, and its exit status is returned through the shared runtime.

set -eu
. "$(dirname "$0")/_runtime.sh"

# A leading directory overrides the generated default; options pass through to
# the watcher. Resolve it inside this project before starting development work.
theme_directory=web/themes/custom/sous-project
case "${1:-}" in ''|--*) ;; *) theme_directory="$1"; shift ;; esac
theme_directory="$(runtime_theme_directory "$theme_directory")"
runtime_require_development
runtime_npm --prefix "$theme_directory" run develop -- "$@"
