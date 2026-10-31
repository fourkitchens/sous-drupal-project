#!/bin/sh
# Export local configuration for review and version control.
# Run npm run confex from the project root.
set -eu

ddev drush config:export -y "$@"
