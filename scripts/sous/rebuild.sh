#!/bin/bash

composer install
yarn import-data
yarn confim
yarn build-theme
lando drush uli
