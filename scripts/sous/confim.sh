#!/bin/sh

lando drush updatedb -y
lando drush cache-rebuild
lando drush config-import -y
lando drush cache-rebuild
