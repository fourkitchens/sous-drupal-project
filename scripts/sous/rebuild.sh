#!/bin/bash

lando composer install
npm run import-data
npm run confim
lando drush user:unblock superuser_1
lando drush uli
