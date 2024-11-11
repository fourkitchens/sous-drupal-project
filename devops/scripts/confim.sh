#!/bin/sh

source ./devops/environment/local.env

$local_dev drush updatedb -y
$local_dev drush cache-rebuild
$local_dev drush config-import --source="../config/_splits" --partial --yes
$local_dev drush cache-rebuild
$local_dev drush config-import --yes
