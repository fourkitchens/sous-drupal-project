#!/bin/bash

if [ -f ./reference/site-db.sql.gz ];
  then rm ./reference/site-db.sql.gz; fi
terminus backup:get poets-and-writers-groups.live --element=db --to=reference/site-db.sql.gz
