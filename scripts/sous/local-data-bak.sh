#!/bin/bash

lando drush sql-dump --result-file=../reference/db-`date +%Y-%m-%d-%H%M`.sql --gzip
