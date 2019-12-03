#!/bin/bash

if [ -f ./reference/db.sql.gz ];
  then echo \"Reference database exists.\"; 
  else echo \"Reference database doesn\'t exist.\" && exit 1; fi

lando db-import reference/db.sql.gz
lando drush sql-sanitize -y --sanitize-password=admin --sanitize-email=user-%uid@example.com
