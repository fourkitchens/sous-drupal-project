#!/bin/sh

# Bootstrap a newly named Sous project, then retire its one-time setup helpers.
# Called by setup.sh fresh (also exposed as npm run sous-build); no arguments.
# Requires local/staging and may replace its database with Minimal Drupal.
# The workflow prompts for content composition, builds the theme, and verifies
# export/metadata/login before cleanup or success output. Failures stop dependent
# phases and retain remaining bootstrap files for diagnosis and a reviewed retry.
set -eu
. "$(dirname "$0")/_runtime.sh"
runtime_require_development

# Use a dedicated Drush wrapper to suppress terminal control-sequence noise.
# - TERM=dumb prevents TTY capability probes in some host/container terminal chains.
# - --no-ansi disables ANSI formatting and related escape output.
# - -y auto-confirms prompts for setup-safe mutating commands.
run_drush() {
  TERM=dumb runtime_drush --no-ansi "$@"
}

run_drush_yes() {
  TERM=dumb runtime_drush --no-ansi -y "$@"
}

# Use a dedicated Composer wrapper for cleaner setup logs.
# - TERM=dumb minimizes terminal capability probing noise in nested shells.
# - --no-ansi keeps output plain so escape/control sequences are not printed.
run_composer() {
  TERM=dumb runtime_composer --no-ansi "$@"
}

# Preserve contributor clones and their branch. Only a project without Git is
# initialized on main; -b sets the branch here without changing global Git prefs.
if [ ! -e .git ]; then
  echo "Initializing your project as a git repository on main"
  git init -b main
fi

echo "Starting ddev"
runtime_start

# Install PHP and root frontend tools before using Drush or the project CLI.
echo "Verify project dependencies are installed..."
# Project naming changes Composer's root metadata. Refresh its lock hash without
# changing locked package versions, including when no optional recipe is added.
if [ -f composer.lock ]; then
  run_composer update --lock --no-install --no-interaction
fi
run_composer install
runtime_npm ci --include=dev

# Install a fresh Minimal database, then apply the two always-on Sous recipes.
# The canonical recipe helper stops on application/cache failures in either one.
run_drush_yes site:install minimal --account-name=sous-project --account-name=superuser_1
./devops/scripts/install-recipe.sh fourkitchens/sous-emulsify
./devops/scripts/install-recipe.sh fourkitchens/sous-admin

# Ensure custom module path exists and is tracked in git for new projects.
mkdir -p web/modules/custom
touch web/modules/custom/.gitkeep

# The theme helper installs/builds the generated theme before Drupal enables it.
# Keep the bootstrap UID 1 account blocked and assign the administrator role to
# the separate authoring account after the administration recipe has supplied it.
echo "Creating an Emulsify based theme..."
run_drush_yes emulsify sous-project
echo "Installing theme dependencies... This may take a minute..."
sh devops/scripts/theme-setup.sh web/themes/custom/sous-project
echo "Enable sous-project and set as the default theme..."
run_drush_yes theme:install sous-project
run_drush_yes config-set system.theme default sous-project
run_drush_yes user:block superuser_1
run_drush_yes user:create sous_chef --mail="sous_chef@fourkitchens.com"
run_drush_yes user:role:add 'superuser' superuser_1
run_drush_yes user:role:add 'superuser' sous_chef

# Install the selected content/composition layer over the same administration
# base. The fallback case also covers admin-only or an unrecognized response.
echo "Which version of Sous would you like to install?"
echo "[0] Default Sous (media and content types only)"
echo "[1] Sous with Layout Builder"
echo "[2] Sous with Paragraphs"
echo "[3] Just the admin"
printf 'Enter your selection '
# Composer can close script stdin even when the caller has a terminal. Both
# installation prompts use that terminal when available; CI supplies stdin.
read_response() {
  if read -r "$1"; then return 0; fi
  if { read -r "$1" < /dev/tty; } 2>/dev/null; then return 0; fi
  return 1
}
if ! read_response RESP; then
  echo 'A content selection is required.' >&2
  exit 2
fi
case $RESP in
  0)
    # The default Page model pulls in its shared formats and media dependencies.
    run_composer require 'fourkitchens/sous-content-types:^2.0'
    ./devops/scripts/install-recipe.sh fourkitchens/sous-content-types
    ;;
  1)
    run_composer require 'fourkitchens/sous-core-layout-builder:^2.0'
    ./devops/scripts/install-recipe.sh fourkitchens/sous-core-layout-builder
    ;;
  2)
    # The Paragraphs host recipe composes the checked dependency recipes; using
    # the canonical application helper preserves a failed child recipe's status.
    run_composer require 'fourkitchens/sous-paragraphs:^2.0'
    ./devops/scripts/install-recipe.sh fourkitchens/sous-paragraphs
    ;;
  *)
    echo "No additional recipe required."
    ;;
esac

# Environment policy remains in the application; definitions arrive only when
# the project selects Config Split. A closed noninteractive input defaults off.
printf 'Install optional Config Split definitions? [y/N] '
if ! read_response SPLITS; then
  SPLITS=n
fi
case "$SPLITS" in
  [yY]|[yY][eE][sS])
    mkdir -p config/_splits config/local config/staging
    run_composer require 'fourkitchens/sous-config-split:^2.0'
    ./devops/scripts/install-recipe.sh fourkitchens/sous-config-split
    ;;
esac

# Export the final installed composition as the generated site's complete config.
# Validate the native transformed source before exporting the complete sync set.
runtime_environment_check export
run_drush_yes config:export

# Verify the generated manifest/lock pair and login before success or cleanup.
run_composer validate --no-check-publish
run_drush uli --name=sous_chef

# Remove bootstrap commands only after all installation checks above succeeded.
# Update JSON structurally so other project scripts/metadata survive cleanup.
remove_json_key() {
  file="$1"
  top_level_key="$2"
  nested_key="$3"

  if [ ! -f "$file" ]; then
    return 0
  fi

  # PHP, rather than the shell, expands these variables.
  # shellcheck disable=SC2016
  runtime_exec php -r '
  $file = $argv[1];
  $topLevelKey = $argv[2];
  $nestedKey = $argv[3];
  $json = @file_get_contents($file);
  if ($json === false) {
    fwrite(STDERR, "Unable to read {$file}\n");
    exit(1);
  }
  $data = json_decode($json, true);
  if (!is_array($data)) {
    fwrite(STDERR, "Invalid JSON in {$file}\n");
    exit(1);
  }
  if (isset($data[$topLevelKey]) && is_array($data[$topLevelKey])) {
    unset($data[$topLevelKey][$nestedKey]);
  }
  $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
  if (file_put_contents($file, $json . PHP_EOL) === false) {
    fwrite(STDERR, "Unable to write {$file}\n");
    exit(1);
  }
  ' "$file" "$top_level_key" "$nested_key"
}

remove_json_key "composer.json" "scripts" "post-create-project-cmd"
remove_json_key "package.json" "scripts" "sous-build"

# Retire only the one-time naming/install helpers; setup.sh remains available to
# dispatch existing-site restoration and reject another fresh bootstrap.
rm -f "devops/scripts/sous-setup.sh" "devops/scripts/sous-build.sh"

# Announce success only after installation, login generation, and cleanup succeed.
echo ""
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
EOF
echo ""
echo "//////////////"
echo " ORDERS UP!"
echo " Your Drupal site is ready."
echo "//////////////"
echo ""
