#!/bin/sh
# Build theme assets with installed dependencies. A leading directory is optional.
set -eu
. "$(dirname "$0")/_runtime.sh"

theme_directory=web/themes/custom/sous-project
case "${1:-}" in ''|--*) ;; *) theme_directory="$1"; shift ;; esac
runtime_npm --prefix "$theme_directory" run build -- "$@"
