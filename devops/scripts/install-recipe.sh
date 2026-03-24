#!/bin/bash

#
# Helper script to install a recipe in DDEV.
#
# Usage:
# ddev install-recipe <directory name of recipe inside /recipes>
#

recipe_full_package_name="$1"
IFS='/' read -ra recipe_split <<< "$recipe_full_package_name"
recipe_directory_name="${recipe_split[1]}"

if [ $# -eq 0 ]; then
  echo "Usage: specify the directory name inside of /app/recipes/ that you want to install:"
  cd /app/recipes/
  ls -d -- */
  exit 1
fi

cd web
php core/scripts/drupal recipe recipes/"$recipe_directory_name"
ddev drush cr
#cd ..
#php /usr/local/bin/composer unpack "$recipe_full_package_name"
