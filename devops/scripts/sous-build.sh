#!/bin/sh
# Composer runs this installer once; setup.sh restores sites after installation.
set -eu
. "$(dirname "$0")/_runtime.sh"
runtime_require_ddev

# Collect choices before Composer, npm or Drush can consume the input stream.
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

if [ "${SOUS_CI+x}" != x ]; then
  printf 'CI: [0] CircleCI [1] GitHub Actions [2] GitLab CI/CD [3] None: '
  read -r SOUS_CI
fi
case "$SOUS_CI" in
  circleci|0) ci=circleci ;;
  github|1) ci=github ;;
  gitlab|2) ci=gitlab ;;
  none|3) ci=none ;;
  *) echo 'SOUS_CI must be circleci, github, gitlab, or none.' >&2; exit 2 ;;
esac

# DDEV and the theme derive their names from the target project directory.
# Store the theme name once so routine npm commands use the same directory.
project_name=$(basename "$PWD" | tr '[:upper:] _' '[:lower:]--' | tr -cd 'a-z0-9-')
theme=$(printf '%s' "$project_name" | tr -d '-')
ddev config --project-name="$project_name"
runtime_start
runtime_npm pkg set "name=$project_name" "config.theme=$theme"
runtime_npm install --package-lock-only --ignore-scripts --offline
runtime_composer config name "project/$project_name"
# Begin the generated site with its own instructions and Git history.
[ ! -f PROJECT-README.md ] || mv PROJECT-README.md README.md
if [ "$ci" != none ]; then
  cp -R "assets/ci/$ci/." .
fi
# GitLab uses its merge request template instead of GitHub's review tooling.
if [ "$ci" = gitlab ]; then
  mkdir -p .gitlab/merge_request_templates
  mv .github/PULL_REQUEST_TEMPLATE.md .gitlab/merge_request_templates/Default.md
  rm -rf .github
fi
rm -rf assets/ci
[ -e .git ] || runtime_exec git init -b main

# Renaming Composer's root package invalidates its lock hash.
if [ -f composer.lock ]; then runtime_composer update --lock --no-install --no-interaction; fi
# Install project tools before Drush starts applying the recipes.
runtime_composer install
runtime_npm ci --include=dev
# Minimal leaves the selected recipes in charge of the site's configuration.
runtime_drush -y site:install minimal --account-name=superuser_1
sh devops/scripts/install-recipe.sh fourkitchens/sous-emulsify
sh devops/scripts/install-recipe.sh fourkitchens/sous-admin
mkdir -p web/modules/custom
touch web/modules/custom/.gitkeep

# Build the new theme before enabling it so Drupal can serve its assets.
runtime_drush -y emulsify "$theme"
sh devops/scripts/theme-setup.sh "web/themes/custom/$theme"
runtime_drush -y theme:install "$theme"
runtime_drush -y config:set system.theme default "$theme"
# Keep UID 1 blocked and use a separate local administrator account.
runtime_drush -y user:block superuser_1
runtime_drush -y user:create sous_chef --mail=sous_chef@fourkitchens.com
runtime_drush -y user:role:add superuser superuser_1
runtime_drush -y user:role:add superuser sous_chef

# Apply optional recipes before exporting the site's complete configuration.
if [ -n "$content" ]; then
  runtime_composer require "fourkitchens/$content:^2.0"
  sh devops/scripts/install-recipe.sh "fourkitchens/$content"
fi
if [ "$split" = yes ]; then
  mkdir -p config/local config/staging
  runtime_composer require 'fourkitchens/sous-config-split:^2.0'
  sh devops/scripts/install-recipe.sh fourkitchens/sous-config-split
fi
# Validate the finished site and its login before retiring this installer.
runtime_drush -y config:export
runtime_composer validate --no-check-publish
runtime_drush uli --name=sous_chef
# Retire the creation hook only after installation succeeds.
# Track shared settings.php while keeping local settings out of Git.
runtime_composer config --unset scripts.post-create-project-cmd
runtime_exec sed -i '/^settings\.php$/d' .gitignore
rm devops/scripts/sous-build.sh
printf '\nOrders up! Your Drupal site is ready.\n'
