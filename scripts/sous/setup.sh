#!/bin/bash

echo "Initializing your project as a git repository and set the default branch to main"
git config --global init.defaultBranch main
git init
echo "Starting lando"
lando start
echo "Installing project tooling dependencies... (this may take a minute)"
lando npm --silent --prefix ./ install
echo "Initializing your custom project theme based on Emulsify"
lando emulsify init sous-project --platform drupal
echo "Installing theme dependencies... (this may take a minute)"
lando npm --silent --prefix ./web/themes/custom/sous-project install
lando drush site:install --existing-config --account-name=sous-project --account-name=superuser -y
