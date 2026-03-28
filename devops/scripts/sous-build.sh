#!/bin/sh

# Use a dedicated Drush wrapper to suppress terminal control-sequence noise.
# - TERM=dumb prevents TTY capability probes in some host/container terminal chains.
# - --no-ansi disables ANSI formatting and related escape output.
# - -y auto-confirms prompts for setup-safe mutating commands.
run_drush() {
  TERM=dumb ddev drush --no-ansi "$@"
}

run_drush_yes() {
  TERM=dumb ddev drush --no-ansi -y "$@"
}

# Use a dedicated Composer wrapper for cleaner setup logs.
# - TERM=dumb minimizes terminal capability probing noise in nested shells.
# - --no-ansi keeps output plain so escape/control sequences are not printed.
run_composer() {
  TERM=dumb ddev composer --no-ansi "$@"
}

# Initialize the new project as a git repository.
echo "Initializing your project as a git repository and set the default branch to main"
git config --global init.defaultBranch main
git init

# Install PHP/JS dependencies in the project environment.
echo "Verify project dependencies are installed..."
run_composer install
ddev npm install

# Boot DDEV and install base Drupal with core Sous recipes.
echo "Starting ddev"
ddev start
run_drush_yes site:install minimal --account-name=sous-project --account-name=superuser_1
ddev install-recipe fourkitchens/sous-emulsify
ddev install-recipe fourkitchens/sous-admin

# Generate and enable the project theme, then set up initial admin users.
echo "Creating an Emulsify based theme..."
run_drush_yes emulsify sous-project
echo "Installing theme dependencies... This may take a minute..."
ddev npm --prefix ./web/themes/custom/sous-project install --silent
echo "Enable sous-project and set as the default theme..."
run_drush_yes theme:install sous-project
run_drush_yes config-set system.theme default sous-project
run_drush_yes user:block superuser_1
run_drush_yes user:create sous_chef --mail="sous_chef@fourkitchens.com"
run_drush_yes user:role:add 'superuser' superuser_1
run_drush_yes user:role:add 'superuser' sous_chef

# Prompt for optional feature recipe and install based on selection.
echo "Which version of Sous would you like to install?"
echo "[0] Default Sous (media and content types only)"
echo "[1] Sous with Layout Builder"
echo "[2] Sous with Paragraphs"
echo "[3] Just the admin"
read -p "Enter your selection " RESP
case $RESP in
  0)
    run_composer require fourkitchens/sous-content-types && run_drush_yes cr && ddev install-recipe fourkitchens/sous-content-types
    ;;
  1)
    run_composer require fourkitchens/sous-layout-builder && run_drush_yes cr && ddev install-recipe fourkitchens/sous-layout-builder
    ;;
  2)
    run_composer require fourkitchens/sous-paragraphs && run_drush_yes cr && ddev install-recipe fourkitchens/sous-paragraphs
    ;;
  *)
    echo "No additional recipe required."
    ;;
esac

# Display setup banner and output one-time login URL for sous_chef.
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
run_drush uli --name=sous_chef

# Remove bootstrap setup artifacts so this install workflow only runs once.
remove_json_key() {
  file="$1"
  top_level_key="$2"
  nested_key="$3"

  if [ ! -f "$file" ]; then
    return 0
  fi

  php -r '
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
  file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
  ' "$file" "$top_level_key" "$nested_key"
}

remove_json_key "composer.json" "scripts" "post-create-project-cmd"
remove_json_key "package.json" "scripts" "sous-build"

# Delete this setup script and companion setup helper after successful run.
rm -f "devops/scripts/sous-setup.sh"
rm -f "devops/scripts/sous-build.sh"
