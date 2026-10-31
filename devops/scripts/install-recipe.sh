#!/bin/sh
# Apply a Composer-installed recipe and refresh Drupal's extension caches.
# Run npm run install-recipe -- vendor/package from the project root.
set -eu

ddev drush cache:rebuild
ddev drush recipe "/var/www/html/web/recipes/${1##*/}"
ddev drush cache:rebuild
