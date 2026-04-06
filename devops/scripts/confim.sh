#!/bin/sh

ddev drush updatedb -y
ddev drush cache-rebuild
ddev drush config-import --source="../config/_splits" --partial --yes
ddev drush cache-rebuild
ddev drush config-import --yes
