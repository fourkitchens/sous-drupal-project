#!/bin/sh
# Deploy local database updates, configuration and deployment hooks.
# Run npm run confim from the project root.
set -eu

ddev drush deploy -y "$@"
