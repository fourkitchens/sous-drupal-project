#!/bin/sh
# Install Sous, apply the selected recipes and prepare its theme.
# Composer runs this once during composer create-project on your computer.
set -eu

sous_version='^2.0'

cat <<'MENU'

┌────────────────────────────────────────────────────────────┐
│  SOUS                                                      │
│  Welcome to the kitchen. Let's take your order.            │
└────────────────────────────────────────────────────────────┘
MENU

# Collect choices before Composer, npm or Drush can consume the input stream.
if [ "${SOUS_CONTENT+x}" != x ]; then
  cat <<'MENU'

  MAIN COURSE · Content setup

    [0] default         Page and Frontpage with a Body field
    [1] layout-builder  Page and Frontpage with flexible layouts
    [2] paragraphs      Page and Frontpage with reusable components
    [3] admin-only      Administration and theme, without content types

MENU
  printf '  Your main course (number or name): '
  read -r SOUS_CONTENT
fi
case "$SOUS_CONTENT" in
  default|0)
    content=sous-content-types
    content_label=Default
    ;;
  layout-builder|1)
    content=sous-core-layout-builder
    content_label='Layout Builder'
    ;;
  paragraphs|2)
    content=sous-paragraphs
    content_label=Paragraphs
    ;;
  admin-only|3)
    content=''
    content_label='Admin only'
    ;;
  *) echo 'SOUS_CONTENT must be default, layout-builder, paragraphs, or admin-only.' >&2; exit 2 ;;
esac

# Let each package decide whether it belongs in the optional recipe menu.
recipe_menu=''
if [ "${SOUS_RECIPES+x}" != x ]; then
  # shellcheck disable=SC2016
  recipe_menu=$(php -r '
    $major = (int) ltrim($argv[1], "^");
    $index = json_decode(@file_get_contents($argv[2]) ?: "", TRUE);
    if (!$index) { exit(1); }
    ksort($index["packages"]);
    foreach ($index["packages"] as $name => $versions) {
      $versions = array_filter($versions, fn($version) => (int) $version === $major, ARRAY_FILTER_USE_KEY);
      uksort($versions, "version_compare");
      $metadata = end($versions);
      if (($metadata["extra"]["sous"]["menu"] ?? FALSE) === TRUE) {
        echo substr($name, strlen("fourkitchens/")), "\t", $metadata["description"], "\n";
      }
    }
  ' "$sous_version" "${SOUS_INDEX:-https://fourkitchens.github.io/sous-drupal-packages/packages.json}") ||
    echo "Couldn't reach the Sous package index; add optional recipes later with ddev composer require." >&2
  SOUS_RECIPES=''
  if [ -n "$recipe_menu" ]; then
    printf '\n  ON THE SIDE · Optional recipes\n\n'
    printf '%s\n' "$recipe_menu" | awk -F '\t' '{printf "    [%d] %s\n        %s\n\n", NR, $1, $2}'
    printf '  Add sides (numbers or names; spaces or commas; Enter for none): '
    read -r SOUS_RECIPES || SOUS_RECIPES=''
  fi
fi

recipe_choices=$(printf '%s' "$SOUS_RECIPES" | tr ',' ' ')
recipes=''
set -f
# shellcheck disable=SC2086
for recipe in $recipe_choices; do
  case "$recipe" in
    *[!0-9]*) ;;
    *) recipe=$(printf '%s\n' "$recipe_menu" | awk -F '\t' -v number="$recipe" 'NR == number {print $1}') ;;
  esac
  if ! printf '%s\n' "$recipe" | grep -Eq '^sous-[a-z0-9-]+$'; then
    echo "Invalid recipe choice: $recipe" >&2
    exit 2
  fi
  recipes="${recipes:+$recipes }$recipe"
done
set +f

if [ "${SOUS_CI+x}" != x ]; then
  cat <<'MENU'

  SERVICE · Continuous integration

    [0] circleci  CircleCI
    [1] github    GitHub Actions
    [2] gitlab    GitLab CI/CD
    [3] none      No CI

MENU
  printf '  Your service (number or name): '
  read -r SOUS_CI
fi
case "$SOUS_CI" in
  circleci|0)
    ci=circleci
    ci_label=CircleCI
    ;;
  github|1)
    ci=github
    ci_label='GitHub Actions'
    ;;
  gitlab|2)
    ci=gitlab
    ci_label='GitLab CI/CD'
    ;;
  none|3)
    ci=none
    ci_label=None
    ;;
  *) echo 'SOUS_CI must be circleci, github, gitlab, or none.' >&2; exit 2 ;;
esac

printf '\n  Order received: %s, with %s. Aprons on!\n\n' "$content_label" "$ci_label"

# Prevent nested tools from querying the terminal and leaking its replies.
export TERM=dumb

# DDEV and the theme derive their names from the target project directory.
# Store the theme name once so routine npm commands use the same directory.
project_name=$(basename "$PWD" | tr '[:upper:] _' '[:lower:]--' | tr -cd 'a-z0-9-')
theme=$(printf '%s' "$project_name" | tr -d '-')
ddev config --project-name="$project_name"
ddev start
npm pkg set "name=$project_name" "config.theme=$theme"
npm install --package-lock-only --ignore-scripts --offline
ddev composer config name "project/$project_name"
# Begin the generated site with its own instructions and Git history.
[ ! -f PROJECT-README.md ] || mv PROJECT-README.md README.md
node -e 'const fs = require("node:fs"); fs.writeFileSync("README.md", fs.readFileSync("README.md", "utf8").replaceAll("PROJECT_NAME", process.argv[1]));' "$project_name"
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
[ -e .git ] || git init -b main

# Renaming Composer's root package invalidates its lock hash.
if [ -f composer.lock ]; then ddev composer update --lock --no-install --no-interaction; fi
# Install project tools before Drush starts applying the recipes.
printf '\nStocking the pantry: installing project tools.\n\n'
ddev composer install
npm ci --include=dev
# Minimal leaves the selected recipes in charge of the site's configuration.
ddev drush -y site:install minimal --account-name=superuser_1
sh devops/scripts/install-recipe.sh fourkitchens/sous-emulsify
sh devops/scripts/install-recipe.sh fourkitchens/sous-admin
mkdir -p web/modules/custom
touch web/modules/custom/.gitkeep

# Generate the theme and install its tools; the project chooses its library later.
printf '\nSetting the table: preparing your custom theme and its tools.\n\n'
ddev drush -y emulsify "$theme"
npm --prefix "web/themes/custom/$theme" install --include=dev
ddev drush -y theme:install "$theme"
ddev drush -y config:set system.theme default "$theme"
# Keep UID 1 blocked and use a separate local administrator account.
sous_chef_password=$(php -r 'echo bin2hex(random_bytes(12));')
ddev drush -y user:block superuser_1
ddev drush -y user:create sous_chef --mail=local-admin@example.com \
  --password="$sous_chef_password"
ddev drush -y user:role:add superuser superuser_1
ddev drush -y user:role:add superuser sous_chef

# Apply optional recipes before exporting the site's complete configuration.
printf '\nAdding your chosen recipes to the pot.\n\n'
set --
if [ -n "$content" ]; then
  set -- "fourkitchens/$content:$sous_version"
fi
for recipe in $recipes; do
  set -- "$@" "fourkitchens/$recipe:$sous_version"
done
if [ "$#" -gt 0 ]; then
  ddev composer require "$@"
fi
if [ -n "$content" ]; then
  sh devops/scripts/install-recipe.sh "fourkitchens/$content"
fi
for recipe in $recipes; do
  sh devops/scripts/install-recipe.sh "fourkitchens/$recipe"
done
# Validate the finished site and its login before retiring this installer.
printf '\nFinal taste test: exporting configuration and checking the project.\n\n'
ddev drush -y config:export
# Recipes have unpacked their dependencies; the setup-only repository can go.
node -e '
  const fs = require("node:fs");
  const composer = JSON.parse(fs.readFileSync("composer.json", "utf8"));
  composer.repositories = composer.repositories.filter((repo) =>
    !repo.only?.includes("fourkitchens/sous-*") &&
    !Object.keys(repo.options?.versions ?? {}).some((name) => name.startsWith("fourkitchens/sous-")));
  fs.writeFileSync("composer.json", JSON.stringify(composer, null, 4) + "\n");
'
ddev composer update --lock --no-install --no-interaction
ddev composer validate --no-check-publish
# Read the running site's versions and URL for the finished order.
drupal_version=$(ddev drush status --field=drupal-version)
php_version=$(ddev drush status --field=php-version)
node_version=$(node --version)
local_url=$(ddev drush status --field=uri)
# Retire the creation hook only after installation succeeds.
ddev composer config --unset scripts.post-create-project-cmd
# Preserve attribution before retiring the applied recipe sources.
for notice in web/recipes/sous-*/NOTICE; do
  if [ -f "$notice" ]; then cat "$notice" >> NOTICE; fi
done
rm -rf web/recipes/sous-*
rm devops/scripts/sous-build.sh
# Celebrate only after installation and one-time cleanup have succeeded.
cat <<'EOF'

       .--,--.
       `.  ,.'
        |___|
        :o o:   O
       _`~^~'_  |
     /'   ^   `\=)
   .'  _______ '~|
   `(<=|     |= /'
       |     |
       |_____|
~~~~~~~ ===== ~~~~~~~~

//////////////
 ORDERS UP!
 Your Drupal site is ready.
//////////////

EOF

printf '┌─ YOUR ORDER ────────────────────────────────────────────────\n'
printf '│  %-12s %s\n' \
  'Project' "$project_name" \
  'Drupal' "$drupal_version" \
  'PHP' "$php_version" \
  'Node.js' "$node_version" \
  'Content' "$content_label" \
  'Extras' "${recipes:-None}" \
  'CI' "$ci_label" \
  'Theme' "$theme (scaffold; dependencies installed)" \
  'Local URL' "$local_url"
printf '│\n│  LOCAL SIGN-IN\n│\n'
printf '│  %-12s %s\n' \
  'Login' "${local_url%/}/user/login" \
  'Username' sous_chef \
  'Password' "$sous_chef_password" \
  'UID 1' 'superuser_1 is blocked'
printf '└────────────────────────────────────────────────────────────\n\n'
printf 'Your kitchen is ready. Add a component library when you are ready to cook.\n\n'
