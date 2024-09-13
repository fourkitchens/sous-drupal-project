#!/bin/sh

source ./devops/environment/local.env

$local_dev drush config-export -y
