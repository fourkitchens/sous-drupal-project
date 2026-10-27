#!/bin/sh

# Name a new project from its directory before Drupal/theme installation.
# Composer's post-create hook calls this from the project root with no arguments.
# It edits template files in place, needs PHP for JSON metadata, and must run in
# a generated/disposable copy rather than the maintained starter checkout.

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

# Replace a known placeholder only when the optional file exists. The backup
# suffix works with BSD/GNU sed; remove that temporary backup after replacement.
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

# Keep runtime paths, package metadata and documented commands aligned. The
# Emulsify directory name intentionally differs from DDEV's dashed identifier.
replace_token "sous-project" "$dashed_project_name" ".ddev/config.yaml"
replace_token "sous-project" "$emulsify_project_name" ".gitignore"
replace_token "sous-project" "$emulsify_project_name" "devops/scripts/theme-build.sh"
replace_token "sous-project" "$emulsify_project_name" "devops/scripts/theme-watch.sh"
replace_token "sous-project" "$emulsify_project_name" "devops/scripts/theme-setup.sh"
replace_token "sous-project" "$emulsify_project_name" "devops/scripts/sous-build.sh"
replace_token "sous-project" "$emulsify_project_name" "docs/BUILD_TOOLS.md"
replace_token "sous-project" "$emulsify_project_name" "docs/CONTRIBUTING.md"
replace_token "sous-project" "$emulsify_project_name" "docs/DEPLOYMENT.md"
replace_token "sous-project" "$emulsify_project_name" "docs/SETUP.md"
replace_token "sous-project" "$emulsify_project_name" "composer.json"
replace_token "fourkitchens/sous-drupal-project" "project/$dashed_project_name" "composer.json"
replace_token "\"name\": \"sous-project\"" "\"name\": \"$dashed_project_name\"" "package.json"
replace_token "\"name\": \"sous-project\"" "\"name\": \"$dashed_project_name\"" "package-lock.json"
replace_token "web/themes/custom/sous-project" "web/themes/custom/$emulsify_project_name" "package.json"
replace_token '"lint": "npm run lint:starter"' '"lint": "npm run lint:project"' "package.json"

# Remove the starter's npm repository field so release tooling discovers the
# generated site's own Git origin instead of targeting the Sous repository.
# shellcheck disable=SC2016 # PHP expands these variables, not the shell.
php -r '
$file = "package.json";
$package = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
unset($package["repository"]);
$json = json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
if (file_put_contents($file, $json . PHP_EOL) === false) {
  throw new RuntimeException("Unable to update {$file}");
}
'

# The source starter keeps its Husky lint command commented. Enable that known
# template line only after naming has selected generated-project lint paths.
if [ -f ".husky/pre-commit" ]; then
  sed -i.bak '3s/^[[:space:]]*#[[:space:]]*//' .husky/pre-commit
  rm -f .husky/pre-commit.bak
fi

# Consume the generated-site README once. A retry preserves README.md when the
# companion has already been moved, rather than restoring starter instructions.
if [ -f "PROJECT-README.md" ]; then
  mv "PROJECT-README.md" "README.md"
fi

# Git distributions exclude maintainer checks and guides. A source-based
# create-project or contributor clone needs the same boundary after naming.
# Retain an empty location for the generated site's own future tests.
rm -rf devops/integration devops/tests docs/maintainers
mkdir -p devops/tests

# The product's workflows test coordinated Sous sources and publish its releases.
# Generated sites opt into their own CI from assets/ci; retain other GitHub files.
rm -rf .github/workflows
