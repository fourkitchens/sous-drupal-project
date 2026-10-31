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
recipe_menu=''
if [ "${SOUS_RECIPES+x}" != x ]; then
  recipe_menu=$(php -- "$content" <<'PHP'
<?php
$indexLocation = 'https://fourkitchens.github.io/sous-drupal-packages/packages.json';
$index = json_decode(file_get_contents($indexLocation), true, 512, JSON_THROW_ON_ERROR);

// Use the newest 2.x metadata for menu descriptions and recipe dependencies.
$catalog = [];
foreach ($index['packages'] as $name => $versions) {
  if (!preg_match('~^fourkitchens/sous-[a-z0-9-]+$~', $name)) {
    continue;
  }
  $releases = array_filter(array_keys($versions), static fn($version) => preg_match('/^2\.\d/', $version));
  usort($releases, 'version_compare');
  if ($releases) {
    $catalog[$name] = $versions[end($releases)];
  }
}

// Hide this setup and its dependencies without traversing other compositions.
$excluded = [];
$pending = ['fourkitchens/sous-admin', 'fourkitchens/sous-emulsify'];
if ($argv[1] !== '') {
  $pending[] = 'fourkitchens/' . $argv[1];
}
while ($pending) {
  $name = array_pop($pending);
  if (isset($excluded[$name])) {
    continue;
  }
  $excluded[$name] = true;
  foreach (array_keys($catalog[$name]['require'] ?? []) as $dependency) {
    if (str_starts_with($dependency, 'fourkitchens/sous-')) {
      $pending[] = $dependency;
    }
  }
}
foreach (['sous-content-types', 'sous-core-layout-builder', 'sous-paragraphs'] as $composition) {
  $excluded['fourkitchens/' . $composition] = true;
}

ksort($catalog);
foreach ($catalog as $name => $metadata) {
  if (!isset($excluded[$name])) {
    echo substr($name, strlen('fourkitchens/')), "\t", $metadata['description'], "\n";
  }
}
PHP
  )
  SOUS_RECIPES=''
  if [ -n "$recipe_menu" ]; then
    printf '%s\n' "$recipe_menu" | awk -F '\t' '{printf "%d. %s: %s\n", NR, $1, $2}'
    printf 'Add recipes (numbers or names, separated by spaces; Enter for none): '
    read -r SOUS_RECIPES || SOUS_RECIPES=''
  fi
fi

# Split selections as words without expanding them as filesystem patterns.
recipe_choices=$(printf '%s' "$SOUS_RECIPES" | tr ',' ' ')
set -f
# shellcheck disable=SC2086
set -- $recipe_choices
set +f
recipes=''
for recipe do
  case "$recipe" in
    *[!0-9]*) ;;
    *)
      recipe=$(printf '%s\n' "$recipe_menu" | awk -F '\t' -v number="$recipe" 'NR == number {print $1}')
      if [ -z "$recipe" ]; then
        echo 'Recipe number is not listed in the menu.' >&2
        exit 2
      fi
      ;;
  esac
  case "$recipe" in
    sous-admin|sous-emulsify|sous-content-types|sous-core-layout-builder|sous-paragraphs)
      echo "$recipe belongs to the setup selected by SOUS_CONTENT." >&2
      exit 2
      ;;
    sous-?*)
      case "$recipe" in
        *[!a-z0-9-]*) echo "Invalid recipe name: $recipe" >&2; exit 2 ;;
      esac
      ;;
    *) echo "Invalid recipe choice: $recipe" >&2; exit 2 ;;
  esac
  recipes="${recipes:+$recipes }$recipe"
done

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
set --
if [ -n "$content" ]; then
  set -- "fourkitchens/$content:^2.0"
fi
for recipe in $recipes; do
  set -- "$@" "fourkitchens/$recipe:^2.0"
done
if [ "$#" -gt 0 ]; then
  runtime_composer require "$@"
fi
if [ -n "$content" ]; then
  sh devops/scripts/install-recipe.sh "fourkitchens/$content"
fi
for recipe in $recipes; do
  sh devops/scripts/install-recipe.sh "fourkitchens/$recipe"
done
# Validate the finished site and its login before retiring this installer.
runtime_drush -y config:export
runtime_composer validate --no-check-publish
runtime_drush uli --name=sous_chef
# Retire the creation hook only after installation succeeds.
runtime_composer config --unset scripts.post-create-project-cmd
rm devops/scripts/sous-build.sh
printf '\nOrders up! Your Drupal site is ready.\n'
