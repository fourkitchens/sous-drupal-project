#!/bin/sh

# Exit on first error (-e) and fail on undefined variables (-u).
set -eu

# Derive project naming variants from the current directory name.
# - composer_root: lowercase, stripped of unsafe characters.
# - emulsify_project_name: no spaces/dashes/underscores (matches Emulsify output).
# - dashed_project_name: kebab-case for project identifiers like DDEV name.
project_dir="$(basename "$(pwd)")"
composer_root="$(printf '%s' "$project_dir" | tr '[:upper:]' '[:lower:]' | sed 's/[.!|=<>\/]//g')"
emulsify_project_name="$(printf '%s' "$composer_root" | tr -d ' _-')"
dashed_project_name="$(printf '%s' "$composer_root" | sed 's/[ _]/-/g')"

# Escape replacement strings so sed does not treat special chars as operators.
escape_replacement() {
  printf '%s' "$1" | sed 's/[&|\\]/\\&/g'
}

# Safely replace a token in a file if that file exists.
# Uses a backup suffix for cross-platform sed compatibility, then removes backup.
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

# Normalize project names in runtime/config/docs files.
replace_token "sous-project" "$dashed_project_name" ".ddev/config.yaml"
replace_token "sous-project" "$emulsify_project_name" "devops/scripts/theme-build.sh"
replace_token "sous-project" "$emulsify_project_name" "devops/scripts/theme-watch.sh"
replace_token "sous-project" "$emulsify_project_name" "devops/scripts/sous-build.sh"
replace_token "sous-project" "$emulsify_project_name" "docs/BUILD_TOOLS.md"
replace_token "sous-project" "$emulsify_project_name" "docs/CONTRIBUTING.md"
replace_token "sous-project" "$emulsify_project_name" "docs/DEPLOYMENT.md"
replace_token "sous-project" "$emulsify_project_name" "docs/SETUP.md"
replace_token "sous-project" "$emulsify_project_name" "composer.json"
replace_token "fourkitchens/sous-drupal-project" "project/$dashed_project_name" "composer.json"
replace_token "\"name\": \"sous-project\"" "\"name\": \"$dashed_project_name\"" "package.json"
replace_token "web/themes/custom/sous-project" "web/themes/custom/$emulsify_project_name" "package.json"

# Enable linting in the generated project by uncommenting line 3 in Husky hook.
if [ -f ".husky/pre-commit" ]; then
  sed -i.bak '3s/^[[:space:]]*#[[:space:]]*//' .husky/pre-commit
  rm -f .husky/pre-commit.bak
fi

# Replace template README with project-specific README content.
rm -f "README.md"
if [ -f "PROJECT-README.md" ]; then
  mv "PROJECT-README.md" "README.md"
fi
