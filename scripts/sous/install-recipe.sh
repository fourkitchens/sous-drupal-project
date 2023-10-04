#!/bin/bash

#
# Helper script to install a recipe inside lando.
#
# Usage:
# lando install-recipe <directory name of recipe inside /recipes>
#

recipe_directory_name="$1"

if [ $# -eq 0 ]; then
 echo "Usage: specify the directory name inside of /app/recipes/ that you want to install:"
 cd /app/recipes/
 ls -d -- */
 exit 1
fi

cd web
php core/scripts/drupal recipe recipes/"$recipe_directory_name"
/app/vendor/bin/drush cr

if [ $recipe_directory_name == 'sous_base' ]; then
  drush user:block superuser_1
  drush user:create sous_chef --mail="sous_chef@fourkitchens.com"
  drush user:role:add 'superuser' superuser_1
  drush user:role:add 'superuser' sous_chef

  echo ""
  echo "//////////////"
  echo " ORDERS UP!"
  echo " Use the following link to log into your new site"
  echo "//////////////"
  echo ""
  drush uli --name=sous_chef
fi