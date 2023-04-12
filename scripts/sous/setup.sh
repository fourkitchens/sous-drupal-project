#!/bin/bash

lando start
lando composer install
lando npm install
lando npm --prefix ./web/themes/custom/sous-project install
lando npm run import-data
lando npm run confim
lando drush user:login --name=sous_chef
