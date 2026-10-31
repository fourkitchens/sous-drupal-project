#!/bin/sh
# Install Node dependencies and build the theme before resetting a fresh clone.
# Run npm run setup [-- backup] from the project root.
set -eu

npm ci
npm run theme-ci
npm run theme-build
sh devops/scripts/rebuild.sh "$@"
