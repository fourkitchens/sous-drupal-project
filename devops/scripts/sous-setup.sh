#!/bin/sh

set -eu

project_dir="$(basename "$(pwd)")"
composer_root="$(printf '%s' "$project_dir" | tr '[:upper:]' '[:lower:]' | sed 's/[.!|=<>\/]//g')"
emulsify_project_name="$(printf '%s' "$composer_root" | tr -d ' _-')"
dashed_project_name="$(printf '%s' "$composer_root" | sed 's/[ _]/-/g')"

escape_replacement() {
  printf '%s' "$1" | sed 's/[&|\\]/\\&/g'
}

replace_token() {
  token="$1"
  replacement="$2"
  file="$3"

  if [ ! -f "$file" ]; then
    return 0
  fi

  safe_replacement="$(escape_replacement "$replacement")"
  sed -i.bak "s|$token|$safe_replacement|g" "$file"
  rm -f "$file.bak"
}

replace_token "sous-project" "$dashed_project_name" ".ddev/config.yaml"
replace_token "sous-project" "$emulsify_project_name" "devops/scripts/theme-build.sh"
replace_token "sous-project" "$emulsify_project_name" "devops/scripts/theme-watch.sh"
replace_token "sous-project" "$emulsify_project_name" "devops/scripts/sous-build.sh"
replace_token "sous-project" "$emulsify_project_name" "composer.json"
replace_token "fourkitchens/sous-drupal-project" "project/$dashed_project_name" "composer.json"
