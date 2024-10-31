#!/bin/sh

# Create a local environment file if it doesn't exist.
if [ ! -f ./devops/environment/local.env ]; then
  cp ./devops/environment/example.local.env ./devops/environment/local.env
fi

# Load the local environment variable.
source ./devops/environment/local.env

# Skip setup process if local environment is already set.
# We are using ddev by default. If you prefer lando, add 'lando' to
# ./devops/environment/local.env and then 'npm run setup'.
if [ -s ./devops/environment/local.env ]; then
  $local_dev start
  npm run rebuild
else
  echo "local_dev=\"ddev\"" >> ./devops/environment/local.env
  npm run sous-build
fi

