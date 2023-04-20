#!/bin/bash

echo "Initialize your project as a git repository and set the default branch to main"
git config --global init.defaultBranch main
git init
echo "Starting lando"
lando start
echo "Install tooling dependencies"
lando npm --silent --prefix ./ install
echo "Initialize your custom project theme based on Emulsify"
lando emulsify init sous-project --platform drupal
echo "Install theme dependencies"
lando npm --silent --prefix ./web/themes/custom/sous-project install
lando drush site:install --existing-config --account-name=sous-project --account-name=superuser -y
