#!/bin/sh
# Run a PHP tool in DDEV locally, or directly in CI, on hosts and inside DDEV.
# Run sh devops/scripts/run-php.sh COMMAND [ARGS] from the project root.
set -eu

if [ "${IS_DDEV_PROJECT:-}" != true ] && [ -f .ddev/config.yaml ] &&
  command -v ddev >/dev/null 2>&1; then
  # Explicit raw mode preserves PHP source and arguments without shell expansion.
  exec ddev exec --raw -- "$@"
fi

exec "$@"
