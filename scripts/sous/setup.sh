#!/bin/sh

echo "Initializing your project as a git repository and set the default branch to main"
git config --global init.defaultBranch main
git init
echo "Starting lando"
lando start
lando drush site:install minimal --account-name=sous-project --account-name=superuser_1 -y
lando install-recipe fourkitchens/sous-emulsify
lando install-recipe fourkitchens/sous-admin
echo "Creating an Emulsify based theme..."
lando drush emulsify sous-project
echo "Installing theme dependencies... This may take a minute..."
lando npm --prefix ./web/themes/custom/sous-project install --silent
echo "Enable sousdrupalrecipe set as the default theme..."
lando drush theme:install sous-project
lando drush config-set system.theme default sous-project -y
lando drush user:block superuser_1
lando drush user:create sous_chef --mail="sous_chef@fourkitchens.com"
lando drush user:role:add 'superuser' superuser_1
lando drush user:role:add 'superuser' sous_chef

echo "Which version of Sous would you like to install?"
echo "[0] Default Sous (media and content types only)"
echo "[1] Sous with Layout Builder"
echo "[2] Sous with Paragraphs"
echo "[3] Just the admin"
read -p "Enter your selection " RESP
case $RESP in
  0)
    lando composer require fourkitchens/sous-content-types && lando install-recipe fourkitchens/sous-content-types
    ;;
  1)
    lando composer require fourkitchens/sous-layout-builder && lando install-recipe fourkitchens/sous-layout-builder
    ;;
  2)
    lando composer require fourkitchens/sous-paragraphs && lando install-recipe fourkitchens/sous-paragraphs
    ;;
  *)
    echo "No additional recipe required."
    ;;
esac

echo ""
echo "//////////////"
echo " ORDERS UP!"
echo " Use the following link to log into your new site"
echo "//////////////"
echo ""
lando drush uli --name=sous_chef
