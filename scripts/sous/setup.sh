#!/bin/bash

# git update-index --skip-worktree devops/environment/local.env
lando start
lando composer install
lando npm install
lando npm --prefix ./web/themes/custom/smith install
lando npm run import-data
lando npm run confim
lando drush user:login --name=sous_chef
