#!/bin/sh
# Apply a Composer-installed recipe and refresh Drupal's extension caches.
# Run npm run install-recipe -- vendor/package from the project root.
set -eu

sh devops/scripts/run-php.sh vendor/bin/drush cache:rebuild
# Resolve the path where PHP runs, since DDEV and the host mount different roots.
recipe_path=$(sh devops/scripts/run-php.sh php -r \
  'echo getcwd(), "/web/recipes/", basename($argv[1]);' "$1")
sh devops/scripts/run-php.sh vendor/bin/drush recipe "$recipe_path"
sh devops/scripts/run-php.sh vendor/bin/drush cache:rebuild
