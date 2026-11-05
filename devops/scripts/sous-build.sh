#!/bin/sh
# Install Sous, apply the selected recipes and prepare its theme.
# Composer runs this once during composer create-project on your computer.
set -eu

sous_version='^2.0'
# Keep one baseline list for selection, Composer and Drupal.
baseline_recipes='sous-emulsify sous-admin sous-media sous-config-split sous-dev-tools sous-seo'

# Check the required recipes and prepare the sides menu with one fetch.
preflight_index() {
  # shellcheck disable=SC2016
  php -r '
    [, $url, $constraint, $required, $preset] = $argv;
    $major = (int) ltrim($constraint, "^");
    $context = stream_context_create(["http" => ["timeout" => 10]]);
    $json = @file_get_contents($url, FALSE, $context);
    if ($json === FALSE) {
      echo "The index is unreachable.";
      exit(1);
    }
    $index = json_decode($json, TRUE);
    if (json_last_error() !== JSON_ERROR_NONE) {
      echo "The index is not JSON.";
      exit(1);
    }
    $packages = $index["packages"] ?? [];
    $matching_release = fn($metadata, $version) =>
      is_array($metadata) && preg_match("/^{$major}[.][0-9]+[.][0-9]+$/D", (string) $version);
    $required = explode(" ", $required);
    foreach ($required as $recipe) {
      $name = "fourkitchens/$recipe";
      $versions = $packages[$name] ?? NULL;
      if ($versions === NULL) {
        echo "Missing package $name.";
        exit(1);
      }
      if (!is_array($versions) || !array_filter($versions, $matching_release, ARRAY_FILTER_USE_BOTH)) {
        echo "Missing a major $major release for $name.";
        exit(1);
      }
    }
    if ($preset === "x") { exit; }
    ksort($packages);
    foreach ($packages as $name => $versions) {
      $recipe = substr($name, strlen("fourkitchens/"));
      if (!is_array($versions) || in_array($recipe, $required, TRUE)) { continue; }
      $versions = array_filter($versions, fn($version) => (int) $version === $major, ARRAY_FILTER_USE_KEY);
      uksort($versions, "version_compare");
      $metadata = end($versions);
      if (($metadata["extra"]["sous"]["menu"] ?? FALSE) === TRUE) {
        echo $recipe, "\t", $metadata["description"], "\n";
      }
    }
  ' "$sous_index" "$sous_version" "$baseline_recipes sous-workflow sous-paragraphs sous-core-layout-builder" "${SOUS_RECIPES+x}"
}

sous_index=${SOUS_INDEX:-https://fourkitchens.github.io/sous-drupal-packages/packages.json}
if ! recipe_menu=$(preflight_index); then
  printf 'Sous package index: %s\n%s\n' "$sous_index" "$recipe_menu" >&2
  printf '%s\n' 'Every Sous install needs the package index.' \
    'For the SOUS_INDEX maintainer override, see https://github.com/fourkitchens/sous-drupal-project/blob/main/docs/maintainers/MAINTAINING.md#installer-choices' >&2
  exit 1
fi

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

    [0] Admin only      Administration and theme, without content types
    [1] Paragraphs      Page and Frontpage with reusable components
    [2] Layout Builder  Page and Frontpage with flexible layouts

MENU
  printf '  Your main course (number or name): '
  read -r SOUS_CONTENT
fi
case "$SOUS_CONTENT" in
  admin-only|'Admin only'|0)
    content=''
    content_label='Admin only'
    ;;
  paragraphs|Paragraphs|1)
    content=sous-paragraphs
    content_label=Paragraphs
    ;;
  layout-builder|'Layout Builder'|2)
    content=sous-core-layout-builder
    content_label='Layout Builder'
    ;;
  *) echo 'SOUS_CONTENT must be admin-only, paragraphs, or layout-builder.' >&2; exit 2 ;;
esac

# Offer sides from the preflight index when recipes were not preset.
if [ "${SOUS_RECIPES+x}" != x ]; then
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
# Resolve side choices in order so each recipe and its integration run once.
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
  # Admin brings Workflow, and replaying Emulsify would reset the project theme.
  case " $baseline_recipes sous-workflow $content $recipes " in
    *" $recipe "*) continue ;;
  esac
  # Content recipes belong to the main course and its bundled dependencies.
  case "$content:$recipe" in
    sous-paragraphs:sous-paragraph-types|sous-core-layout-builder:sous-content-types|sous-*:sous-content-base) continue ;;
  esac
  case "$recipe" in
    sous-paragraphs|sous-paragraph-types|sous-core-layout-builder|sous-content-types|sous-content-base)
      printf 'Recipe %s requires a compatible main course selected with SOUS_CONTENT.\n' "$recipe" >&2
      exit 2
      ;;
  esac
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

# Take the library order now so its installer never consumes later answers.
if [ "${SOUS_LIBRARY+x}" != x ]; then
  cat <<'MENU'

  DESSERT · Component library

    [0] None             Keep the theme ready for your own components
    [1] Emulsify UI Kit  A full helping of Drupal components
    [2] Custom library   Bring an Emulsify system from a Git repository

MENU
  printf '  Your dessert (number, name or Git URL; Enter for none): '
  read -r SOUS_LIBRARY || SOUS_LIBRARY=''
  case "$SOUS_LIBRARY" in
    custom|2)
      printf '  Component library Git URL (ending in .git): '
      read -r SOUS_LIBRARY || SOUS_LIBRARY=''
      if [ -z "$SOUS_LIBRARY" ]; then
        echo 'A custom component library requires a Git repository URL.' >&2
        exit 2
      fi
      if [ "${SOUS_LIBRARY_REF+x}" != x ]; then
        printf '  Branch or tag (Enter for the repository default branch): '
        read -r SOUS_LIBRARY_REF || SOUS_LIBRARY_REF=''
      fi
      ;;
  esac
fi

library_ref=${SOUS_LIBRARY_REF:-}
case "$SOUS_LIBRARY" in
  none|None|0|'')
    library_repository=''
    library_label=None
    ;;
  ui-kit|'Emulsify UI Kit'|1)
    library_repository=https://github.com/emulsify-ds/emulsify-ui-kit.git
    library_label='Emulsify UI Kit'
    # Use upstream's lint fix until a release includes it: https://github.com/emulsify-ds/emulsify-ui-kit/pull/189.
    library_ref=${library_ref:-codex/fix-drupal-theme-lint}
    ;;
  https://*.git|http://*.git|ssh://*.git|git://*.git|file://*.git|*@*:*.git)
    library_repository=$SOUS_LIBRARY
    library_label="Custom: $library_repository"
    ;;
  *)
    echo 'SOUS_LIBRARY must be none, ui-kit, or a Git repository URL ending in .git.' >&2
    exit 2
    ;;
esac

# Box both kitchen tickets at their widest line so long values stay intact.
box() {
  awk '
    { lines[NR] = $0; if (length($0) > width) width = length($0) }
    END {
      border = sprintf("%*s", width + 2, ""); gsub(/ /, "-", border)
      print "  +" border "+"
      for (row = 1; row <= NR; row++) printf "  | %-*s |\n", width, lines[row]
      print "  +" border "+\n"
    }'
}

kitchen_title='                  S O U S   K I T C H E N'
ticket_rule='  --------------------------------------------------------'
included=$(printf '    + %s\n' 'Emulsify' 'Gin administration' 'Media' 'Config Split' \
  'publishing workflow' 'Dev Tools' 'SEO')
prep_included=$(printf '%s\n' "$included" | sed 's/^/  /')
sides='    None'
if [ -n "$recipes" ]; then
  sides=$(for recipe in $recipes; do printf '    + %s\n' "$recipe"; done)
fi
prep_sides=$(printf '%s\n' "$sides" | sed 's/^/  /')
order_number=$(date '+%H%M%S')

# Confirm the order before tools start cooking.
printf '\n'
{
  printf '\n%s\n%s\n%s\n' "$kitchen_title" '                       PREP TICKET' "$ticket_rule"
  printf '  TICKET #%-8s                       ORDER RECEIVED\n' "$order_number"
  printf '%s\n\n  01  MAIN COURSE\n      %s\n\n  02  ON THE SIDE\n%s\n\n  03  SERVICE\n      %s\n\n  04  DESSERT\n      %s\n' \
    "$ticket_rule" "$content_label" "$prep_sides" "$ci_label" "$library_label"
  if [ -n "$library_repository" ]; then printf '      Ref: %s\n' "${library_ref:-repository default branch}"; fi
  printf "\n  PANTRY STAPLES (INCLUDED)\n%s\n\n%s\n       [x] RECEIVED       [ ] COOKING       [ ] READY\n\n                Aprons on. Let's get cooking!\n\n" "$prep_included" "$ticket_rule"
} | box

# Prevent nested tools from querying the terminal and leaking its replies.
export TERM=dumb

# Share the project name between DDEV and the theme’s npm commands.
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
[ -e .git ] || git init -b main

# Renaming Composer's root package invalidates its lock hash.
if [ -f composer.lock ]; then ddev composer update --lock --no-install --no-interaction; fi
printf '\nStocking the pantry: installing project tools.\n\n'
ddev composer install
set --
for recipe in $baseline_recipes $content $recipes; do
  set -- "$@" "fourkitchens/$recipe:$sous_version"
done
# Drupal's Composer plugin unpacks dependencies and leaves recipe sources to apply.
ddev composer require "$@"
npm ci --include=dev
# Minimal leaves the selected recipes in charge of the site's configuration.
ddev drush -y site:install minimal --account-name=superuser_1
for recipe in $baseline_recipes; do
  sh devops/scripts/install-recipe.sh "fourkitchens/$recipe"
done
mkdir -p web/modules/custom
touch web/modules/custom/.gitkeep

# Generate the theme and install its tools before adding the chosen library.
printf '\nSetting the table: preparing your custom theme and its tools.\n\n'
ddev drush -y emulsify "$theme"
# Give theme-local CLI commands the same patched Git dependency as the root CLI.
simple_git_range=$(node -p 'require("./package.json").overrides["@emulsify/cli"]["simple-git"]')
npm --prefix "web/themes/custom/$theme" pkg set "overrides.@emulsify/cli.simple-git=$simple_git_range"
npm --prefix "web/themes/custom/$theme" install --include=dev
theme_status='Scaffold and npm tools ready'
if [ -n "$library_repository" ]; then
  printf '\nBaking dessert: adding your component library and compiling the theme.\n\n'
  if [ -z "$library_ref" ]; then
    library_head=$(GIT_TERMINAL_PROMPT=0 GIT_SSH_COMMAND="${GIT_SSH_COMMAND:-ssh -o BatchMode=yes}" \
      git ls-remote --symref "$library_repository" HEAD </dev/null)
    library_ref=$(printf '%s\n' "$library_head" | awk '$1 == "ref:" {sub(/^refs\/heads\//, "", $2); print $2; exit}')
  fi
  emulsify_cli="$PWD/node_modules/.bin/emulsify"
  (
    cd "web/themes/custom/$theme"
    GIT_TERMINAL_PROMPT=0 GIT_SSH_COMMAND="${GIT_SSH_COMMAND:-ssh -o BatchMode=yes}" \
      "$emulsify_cli" system install \
      --repository "$library_repository" --checkout "$library_ref" \
      --all --yes </dev/null
    npm install --include=dev
  )
  npm run theme-build
  theme_status='Component library installed; theme build completed'
fi
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
if [ -n "$content" ]; then
  sh devops/scripts/install-recipe.sh "fourkitchens/$content"
fi
for recipe in $recipes; do
  sh devops/scripts/install-recipe.sh "fourkitchens/$recipe"
  integration="web/recipes/$recipe/recipes/${content#sous-}"
  if [ -n "$content" ] && [ -d "$integration" ]; then
    ddev drush -y recipe "/var/www/html/$integration"
  fi
done

# Export core module defaults to both splits.
ddev drush -y pm:enable field_ui syslog views_ui
ddev drush config-split:status-override staging active

# Validate the finished site and its login before retiring this installer.
printf '\nFinal taste test: exporting configuration and checking the project.\n\n'
ddev drush -y config:export
ddev drush config-split:status-override staging default
# The named recipe repository is only needed during setup.
ddev composer config --unset repositories.sous
ddev composer update --lock --no-install --no-interaction
ddev composer validate --no-check-publish
drupal_version=$(ddev drush status --field=drupal-version)
php_version=$(ddev drush status --field=php-version)
node_version=$(node --version)
local_url=$(ddev drush status --field=uri)

# Write the site's agent instructions before removing the installer templates.
mkdir -p docs/agents
node - "$project_name" "$theme" <<'JS'
const fs = require('node:fs');
const [name, theme] = process.argv.slice(2);

fs.writeFileSync('AGENTS.md', fs.readFileSync('assets/installer/site-agents.md', 'utf8')
  .replaceAll('PROJECT_NAME', name)
  .replaceAll('PROJECT_THEME', theme));
fs.copyFileSync('assets/installer/workflow-facts.md', 'docs/agents/workflow-facts.md');

// Group root and theme npm updates together.
const dependabot = '.github/dependabot.yml';
const config = fs.readFileSync(dependabot, 'utf8');
fs.writeFileSync(dependabot, config.replace(
  '    directories: [/]',
  `    directories: [/, /web/themes/custom/${theme}]`
));

// The archive exclusion applies to the starter's agent brief.
const attributes = fs.readFileSync('.gitattributes', 'utf8');
fs.writeFileSync('.gitattributes', attributes.replace('/AGENTS.md export-ignore\n', ''));
JS

orders_up=$(cat assets/installer/orders-up.txt)
# Install CI files after site validation so a failed setup leaves the templates intact.
if [ "$ci" != none ]; then
  cp -R "assets/ci/$ci/." .
fi
# GitLab uses its merge request template instead of GitHub's review tooling.
if [ "$ci" = gitlab ]; then
  mkdir -p .gitlab/merge_request_templates
  mv .github/PULL_REQUEST_TEMPLATE.md .gitlab/merge_request_templates/Default.md
  rm -rf .github
fi

# Retire the creation hook only after installation succeeds.
ddev composer config --unset scripts.post-create-project-cmd
# Preserve attribution after the recipes and their integrations have run.
for notice in web/recipes/sous-*/NOTICE; do
  if [ -f "$notice" ]; then cat "$notice" >> NOTICE; fi
done
rm -rf web/recipes/sous-*
rm -r devops/scripts/sous-build.sh assets/installer assets/ci
# Celebrate only after installation and one-time cleanup have succeeded.
printf '%s\n\n' "$orders_up"

# Print the finished project and local credentials after successful setup.
{
  printf '\n%s\n%s\n%s\n' "$kitchen_title" '                        YOUR ORDER' "$ticket_rule"
  printf '  TICKET #%-8s                     READY FOR PICKUP\n' "$order_number"
  printf '%s\n\n' "$ticket_rule"
  printf '  %-12s %s\n' \
    'Project' "$project_name" 'Main course' "$content_label" \
    'Service' "$ci_label" 'Dessert' "$library_label"
  if [ -n "$library_repository" ]; then printf '  %-12s %s\n' 'Library ref' "$library_ref"; fi
  printf '\n  PANTRY STAPLES (INCLUDED)\n%s\n\n  ON THE SIDE\n%s\n\n  THE PREP SHEET\n\n' "$included" "$sides"
  printf '  %-12s %s\n' \
    'Drupal' "$drupal_version" 'PHP' "$php_version" \
    'Node.js' "$node_version" 'Theme' "$theme"
  printf '               %s\n' "$theme_status"
  printf '\n  LOCAL COUNTER\n\n  %s\n\n  LOCAL SIGN-IN\n\n  %s\n\n' \
    "$local_url" "${local_url%/}/user/login"
  printf '  %-12s %s\n' 'Username' sous_chef 'Password' "$sous_chef_password"
  printf '\n%s\n       [x] RECEIVED       [x] COOKING       [x] READY\n\n                Freshly prepared. Happy building!\n\n' "$ticket_rule"
} | box
if [ -n "$library_repository" ]; then
  printf 'Dessert is served. Your component library is ready.\n\n'
else
  printf 'Add your own theme assets or install a component library when you are ready to cook.\n\n'
fi
