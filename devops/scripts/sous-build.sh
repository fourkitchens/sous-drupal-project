#!/bin/sh
# Install a new site once; setup.sh restores an existing site.
set -eu
. "$(dirname "$0")/_runtime.sh"

if [ "${SOUS_CONTENT+x}" != x ]; then
  printf 'Content: [0] Default [1] Layout Builder [2] Paragraphs [3] Admin only: '
  read -r SOUS_CONTENT
fi
case "$SOUS_CONTENT" in
  default|0) content=sous-content-types ;;
  layout-builder|1) content=sous-core-layout-builder ;;
  paragraphs|2) content=sous-paragraphs ;;
  admin-only|3) content='' ;;
  *) echo 'SOUS_CONTENT must be default, layout-builder, paragraphs, or admin-only.' >&2; exit 2 ;;
esac
if [ "${SOUS_CONFIG_SPLIT+x}" != x ]; then
  printf 'Install Config Split? [y/N] '
  read -r SOUS_CONFIG_SPLIT || SOUS_CONFIG_SPLIT=no
fi
case "$SOUS_CONFIG_SPLIT" in
  yes|y|Y) split=yes ;;
  no|n|N|'') split=no ;;
  *) echo 'SOUS_CONFIG_SPLIT must be yes or no.' >&2; exit 2 ;;
esac

project_name=$(basename "$PWD" | tr '[:upper:] _' '[:lower:]--' | tr -cd 'a-z0-9-')
theme=$(printf '%s' "$project_name" | tr -d '-')
ddev config --project-name="$project_name"
runtime_start
runtime_npm pkg set "name=$project_name" "config.theme=$theme"
runtime_npm install --package-lock-only --ignore-scripts --offline
runtime_composer config name "project/$project_name"
[ ! -f PROJECT-README.md ] || mv PROJECT-README.md README.md
[ -e .git ] || runtime_exec git init -b main

# Composer's name change needs a fresh lock hash before locked installation.
if [ -f composer.lock ]; then runtime_composer update --lock --no-install --no-interaction; fi
runtime_composer install
runtime_npm ci --include=dev
runtime_drush -y site:install minimal --account-name=superuser_1
sh devops/scripts/install-recipe.sh fourkitchens/sous-emulsify
sh devops/scripts/install-recipe.sh fourkitchens/sous-admin
mkdir -p web/modules/custom
touch web/modules/custom/.gitkeep

runtime_drush -y emulsify "$theme"
sh devops/scripts/theme-setup.sh "web/themes/custom/$theme"
runtime_drush -y theme:install "$theme"
runtime_drush -y config:set system.theme default "$theme"
runtime_drush -y user:block superuser_1
runtime_drush -y user:create sous_chef --mail=sous_chef@fourkitchens.com
runtime_drush -y user:role:add superuser superuser_1
runtime_drush -y user:role:add superuser sous_chef

if [ -n "$content" ]; then
  runtime_composer require "fourkitchens/$content:^2.0"
  sh devops/scripts/install-recipe.sh "fourkitchens/$content"
fi
if [ "$split" = yes ]; then
  mkdir -p config/local config/staging
  runtime_composer require 'fourkitchens/sous-config-split:^2.0'
  sh devops/scripts/install-recipe.sh fourkitchens/sous-config-split
fi
runtime_drush -y config:export
runtime_composer validate --no-check-publish
runtime_drush uli --name=sous_chef
runtime_composer config --unset scripts.post-create-project-cmd
rm devops/scripts/sous-build.sh
printf '\nOrders up! Your Drupal site is ready.\n'
