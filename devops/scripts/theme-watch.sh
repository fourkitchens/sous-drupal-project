#!/bin/sh
# Watch existing source without repeating setup or rewriting components.
set -eu
. "$(dirname "$0")/_runtime.sh"
# npm supplies the theme name; direct callers can pass its directory and flags.
case "${1:-}" in ''|--*) theme_directory="web/themes/custom/${npm_package_config_theme:?Run through npm or pass a theme directory.}" ;; *) theme_directory=$1; shift ;; esac
runtime_npm --prefix "$theme_directory" run develop -- "$@"
