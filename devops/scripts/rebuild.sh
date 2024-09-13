#!/bin/sh

source ./devops/environment/local.env

echo "Verify project dependencies are installed..."
$local_dev composer install
npm run import-data
npm run confim
$local_dev drush uli --name=sous_chef
