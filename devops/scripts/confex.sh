#!/bin/sh
# Export the complete active configuration so the site can reproduce its settings.
set -eu
. "$(dirname "$0")/_runtime.sh"
runtime_drush config:export --yes "$@"
