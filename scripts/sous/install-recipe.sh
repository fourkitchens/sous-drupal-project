#!/bin/bash

#
# Helper script to install a recipe inside lando.
#
# Usage:
# lando install-recipe <directory name of recipe inside /recipes>
#

recipe_full_package_name="$1"
recipe_directory_name=$(echo "$recipe_directory_name" | cut -d'/' -f2)

if [ $# -eq 0 ]; then
 echo "Usage: specify the directory name inside of /app/recipes/ that you want to install:"
 cd /app/recipes/
 ls -d -- */
 exit 1
fi

cd web
php core/scripts/drupal recipe recipes/"$recipe_directory_name"
/app/vendor/bin/drush cr
cd ..
php /usr/local/bin/composer unpack "$recipe_full_package_name"