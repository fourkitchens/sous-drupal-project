#!/bin/sh

echo "Initializing your project as a git repository and set the default branch to main"
git config --global init.defaultBranch main
git init
echo "Starting lando"
lando start
echo "Installing tooling dependencies..."
lando npm --prefix ./ install --silent
echo "Initializing your custom project theme based on Emulsify... (this may take a minute)"
lando emulsify init sous-project --platform drupal
echo "Installing theme dependencies..."
lando npm --prefix ./web/themes/custom/sous-project install --silent
lando drush site:install minimal --account-name=sous-project --account-name=superuser_1 -y
