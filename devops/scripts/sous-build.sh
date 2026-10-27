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

# Read both selections before tools can consume stdin or installation starts.
read_response() {
  if read -r "$1"; then return 0; fi
  if { read -r "$1" < /dev/tty; } 2>/dev/null; then return 0; fi
  return 1
}
if [ "${SOUS_CONTENT+x}" = x ]; then
  case "$SOUS_CONTENT" in
    default|0) RESP=0 ;; layout-builder|1) RESP=1 ;;
    paragraphs|2) RESP=2 ;; admin-only|3) RESP=3 ;;
    *) echo 'SOUS_CONTENT must be default, layout-builder, paragraphs, admin-only, or 0-3.' >&2; exit 2 ;;
  esac
else
  echo 'Which version of Sous would you like to install?'
  echo '[0] Default Sous (media and content types only)'
  echo '[1] Sous with Layout Builder'
  echo '[2] Sous with Paragraphs'
  echo '[3] Just the admin'
  printf 'Enter your selection '
  if ! read_response RESP; then echo 'A content selection is required.' >&2; exit 2; fi
fi
if [ "${SOUS_CONFIG_SPLIT+x}" = x ]; then
  case "$SOUS_CONFIG_SPLIT" in
    yes) SPLITS=y ;; no) SPLITS=n ;;
    *) echo 'SOUS_CONFIG_SPLIT must be yes or no.' >&2; exit 2 ;;
  esac
else
  printf 'Install optional Config Split definitions? [y/N] '
  if ! read_response SPLITS; then SPLITS=n; fi
fi

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
run_drush_yes site:install minimal --account-name=superuser_1
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

case "$RESP" in
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

case "$SPLITS" in
  [yY]|[yY][eE][sS])
    mkdir -p config/_splits config/local config/staging
    run_composer require 'fourkitchens/sous-config-split:^2.0'
    ./devops/scripts/install-recipe.sh fourkitchens/sous-config-split
    ;;
esac

# Export the final installed composition as the generated site's complete config.
run_drush_yes config:export

# Verify the generated manifest/lock pair and login before success or cleanup.
run_composer validate --no-check-publish
run_drush uli --name=sous_chef

# Retire bootstrap metadata with the tools that own each manifest.
runtime_npm pkg delete scripts.sous-build
runtime_composer config --unset scripts.post-create-project-cmd

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
