#!/bin/sh

# Export this site's complete configuration with optional native split transforms.
# Usage: npm run confex -- [Drush options]
# Preflight checks the production-safe base before Drush writes the sync export;
# native Config Split transforms can write their own split storage during it.
# Target selectors reach both commands; other export options remain on Drush.
set -eu

. "$(dirname "$0")/_runtime.sh"
runtime_environment_check export "$@"
runtime_drush config-export --yes "$@"
