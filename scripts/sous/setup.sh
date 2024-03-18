#!/bin/sh

echo "Initializing your project as a git repository and set the default branch to main"
git config --global init.defaultBranch main
git init
echo "Starting lando"
lando start
echo "Installing tooling dependencies..."
lando npm --prefix ./ install --silent
bash ./scripts/sous/recipe-scaffold.sh
lando drush site:install minimal --account-name=sous-project --account-name=superuser_1 -y
echo "Creating an Emulsify based theme..."
lando drush emulsify sous-project
echo "Installing theme dependencies... This may take a minute..."
lando npm --prefix ./web/themes/custom/sous-project install --silent
echo "Enable sous-project set as the default theme..."
lando drush theme:install sous-project
lando drush config-set system.theme default sous-project -y
lando drush user:block superuser_1
lando drush user:create sous_chef --mail="sous_chef@fourkitchens.com"
lando drush user:role:add 'superuser' superuser_1
lando drush user:role:add 'superuser' sous_chef

echo ""
echo "//////////////"
echo " ORDERS UP!"
echo " Use the following link to log into your new site"
echo "//////////////"
echo ""
lando drush uli --name=sous_chef
