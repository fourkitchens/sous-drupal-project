#!/bin/sh

# Create a local environment file if it doesn't exist.
if [ ! -f ./devops/environment/local.env ]; then
  cp ./devops/environment/example.local.env ./devops/environment/local.env
fi

# Load the local environment variable.
source ./devops/environment/local.env

# Skip setup process if local environment is already set.
if [ -s ./devops/environment/local.env ]; then
  $local_dev start
  npm run rebuild
else
  echo "What would you like to use for local development?"
  echo "[1] ddev - See https://ddev.com/"
  echo "[2] lando - See https://lando.dev/"
  echo "[3] I'll configure my own"
  read -p "Enter your selection " RESP
  case $RESP in
    1)
      echo "local_dev=\"ddev\"" >> ./devops/environment/local.env
      npm run sous-build
      ;;
    2)
      echo "local_dev=\"lando\"" >> ./devops/environment/local.env
      cp web/sites/example.settings.local.php web/sites/default/settings.local.php
      npm run sous-build
      ;;
    *)
      echo "Good luck! Review the manual setup section of https://github.com/fourkitchens/sous-drupal-project/blob/main/README.md for help."
      ;;
  esac
fi

