#!/bin/sh

echo "Verify project dependencies are installed..."
ddev composer install
npm run import-data
npm run confim
ddev drush uli --name=sous_chef
