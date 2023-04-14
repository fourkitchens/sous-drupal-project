#!/bin/bash

lando start
lando emulsify init sous-project --platform drupal
lando npm --prefix ./web/themes/custom/sous-project install
lando compound-install
lando drush site:install --existing-config -y
lando drush config-export -y
lando drush cache-rebuild
