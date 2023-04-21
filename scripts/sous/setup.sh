#!/bin/bash

echo "Initialize your project as a git repository and set the default branch to main"
git config --global init.defaultBranch main
git init
echo "Starting lando"
lando start
echo "Install tooling dependencies"
lando npm --silent --prefix ./ install
echo "Initialize your custom project theme based on Emulsify"
lando emulsify init midcamp2 --platform drupal
echo "Install theme dependencies"
lando npm --silent --prefix ./web/themes/custom/midcamp2 install
lando drush site:install --existing-config --account-name=midcamp2 --account-name=superuser_1 -y
lando drush user:block superuser_1
lando drush role:create 'superuser' 'Super User'
lando drush user:create sous_chef --mail="sous_chef@fourkitchens.com"
lando drush user:role:add 'superuser' superuser_1
lando drush user:role:add 'superuser' sous_chef
echo ""
echo "//////////////"
echo "ORDERS UP!"
echo "Use the following link to log into your new site"
echo "//////////////"
lando drush uli --name=sous_chef
