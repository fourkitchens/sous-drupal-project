#!/bin/sh

source ./devops/environment/local.env

$local_dev drush sql-dump --result-file=../reference/db-`date +%Y-%m-%d-%H%M`.sql --gzip
