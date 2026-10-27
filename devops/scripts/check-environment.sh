#!/bin/sh
set -eu
. "$(dirname "$0")/_runtime.sh"
runtime_drush php:script devops/scripts/environment-check.php -- "$@"
