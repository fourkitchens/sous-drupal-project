#!/bin/sh

source ./devops/environment/local.env

echo "Initializing your project as a git repository and set the default branch to main"
git config --global init.defaultBranch main
git init

echo "Verify project dependencies are installed..."
$local_dev composer install

echo "Starting $local_dev"
$local_dev start
$local_dev drush site:install minimal --account-name=sous-project --account-name=superuser_1 -y
$local_dev npm install-recipe fourkitchens/sous-emulsify
$local_dev npm install-recipe fourkitchens/sous-admin
echo "Creating an Emulsify based theme..."
$local_dev drush emulsify sous-project
echo "Installing theme dependencies... This may take a minute..."
$local_dev npm --prefix ./web/themes/custom/sous-project install --silent
echo "Enable sous-project and set as the default theme..."
$local_dev drush theme:install sous-project
$local_dev drush config-set system.theme default sous-project -y
$local_dev drush user:block superuser_1
$local_dev drush user:create sous_chef --mail="sous_chef@fourkitchens.com"
$local_dev drush user:role:add 'superuser' superuser_1
$local_dev drush user:role:add 'superuser' sous_chef

echo "Which version of Sous would you like to install?"
echo "[0] Default Sous (media and content types only)"
echo "[1] Sous with Layout Builder"
echo "[2] Sous with Paragraphs"
echo "[3] Just the admin"
read -p "Enter your selection " RESP
case $RESP in
  0)
    $local_dev composer require fourkitchens/sous-content-types && $local_dev drush cr && $local_dev install-recipe fourkitchens/sous-content-types
    ;;
  1)
    $local_dev composer require fourkitchens/sous-layout-builder && $local_dev drush cr && $local_dev install-recipe fourkitchens/sous-layout-builder
    ;;
  2)
    $local_dev composer require fourkitchens/sous-paragraphs && $local_dev drush cr && $local_dev install-recipe fourkitchens/sous-paragraphs
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
$local_dev drush uli --name=sous_chef