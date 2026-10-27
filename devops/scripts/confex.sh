#!/bin/sh
set -eu
. "$(dirname "$0")/_runtime.sh"
runtime_drush config:export --yes "$@"
