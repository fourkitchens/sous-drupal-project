#!/bin/bash

composer install
yarn import-data
yarn confim
lando drush uli
