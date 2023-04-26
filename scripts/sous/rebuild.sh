#!/bin/sh

composer install
npm run import-data
npm run confim
drush uli --name=sous_chef
