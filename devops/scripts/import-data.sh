#!/bin/sh

if [ -f ./reference/site-db.sql.gz ];
  then echo \"Reference database exists.\";
  else echo \"Reference database doesn\'t exist. Fetching from live environment.\" && npm run get-db; fi

echo \"Importing database from reference...\"

ddev import-db --file=reference/site-db.sql.gz

ddev drush sql-sanitize -y --sanitize-password=admin --sanitize-email=user-%uid@example.com
ddev drush user:login --name=sous_chef
