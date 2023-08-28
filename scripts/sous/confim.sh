#!/bin/sh

drush updatedb -y
drush cache-rebuild
drush config-import -y
drush cache-rebuild
