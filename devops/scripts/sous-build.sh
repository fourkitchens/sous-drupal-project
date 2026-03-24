#!/bin/sh

echo "Initializing your project as a git repository and set the default branch to main"
git config --global init.defaultBranch main
git init

echo "Verify project dependencies are installed..."
ddev composer install
ddev npm install

echo "Starting ddev"
ddev start
ddev drush site:install minimal --account-name=sous-project --account-name=superuser_1 -y
ddev install-recipe fourkitchens/sous-emulsify
ddev install-recipe fourkitchens/sous-admin
echo "Creating an Emulsify based theme..."
ddev drush emulsify sous-project
echo "Installing theme dependencies... This may take a minute..."
ddev npm --prefix ./web/themes/custom/sous-project install --silent
echo "Enable sous-project and set as the default theme..."
ddev drush theme:install sous-project
ddev drush config-set system.theme default sous-project -y
ddev drush user:block superuser_1
ddev drush user:create sous_chef --mail="sous_chef@fourkitchens.com"
ddev drush user:role:add 'superuser' superuser_1
ddev drush user:role:add 'superuser' sous_chef

echo "Which version of Sous would you like to install?"
echo "[0] Default Sous (media and content types only)"
echo "[1] Sous with Layout Builder"
echo "[2] Sous with Paragraphs"
echo "[3] Just the admin"
read -p "Enter your selection " RESP
case $RESP in
  0)
    ddev composer require fourkitchens/sous-content-types && ddev drush cr && ddev install-recipe fourkitchens/sous-content-types
    ;;
  1)
    ddev composer require fourkitchens/sous-layout-builder && ddev drush cr && ddev install-recipe fourkitchens/sous-layout-builder
    ;;
  2)
    ddev composer require fourkitchens/sous-paragraphs && ddev drush cr && ddev install-recipe fourkitchens/sous-paragraphs
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
ddev drush uli --name=sous_chef
