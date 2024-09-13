#!/bin/sh

source ./devops/environment/local.env

if [ -f ./reference/site-db.sql.gz ];
  then echo \"Reference database exists.\";
  else echo \"Reference database doesn\'t exist. Fetching from live environment.\" && npm run get-db; fi

echo \"Importing database from reference...\"

if [ "$local_dev" == "ddev" ];
  then $local_dev import-db poets-and-writers-groups --file=reference/site-db.sql.gz; fi

if [ "$local_dev" == "lando" ];
  then $local_dev db-import reference/site-db.sql.gz; fi

$local_dev drush sql-sanitize -y --sanitize-password=admin --sanitize-email=user-%uid@example.com
$local_dev drush user:login --name=sous_chef