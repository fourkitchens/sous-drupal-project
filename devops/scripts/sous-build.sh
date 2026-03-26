#!/bin/sh

# Initialize the new project as a git repository.
echo "Initializing your project as a git repository and set the default branch to main"
git config --global init.defaultBranch main
git init

# Install PHP/JS dependencies in the project environment.
echo "Verify project dependencies are installed..."
ddev composer install
ddev npm install

# Boot DDEV and install base Drupal with core Sous recipes.
echo "Starting ddev"
ddev start
ddev drush site:install minimal --account-name=sous-project --account-name=superuser_1 -y
ddev install-recipe fourkitchens/sous-emulsify
ddev install-recipe fourkitchens/sous-admin

# Generate and enable the project theme, then set up initial admin users.
echo "Creating an Emulsify based theme..."
ddev drush emulsify sous-project
echo "Installing theme dependencies... This may take a minute..."
ddev npm --prefix ./web/themes/custom/sous-project install --silent
echo "Enable sous-project and set as the default theme..."
ddev drush theme:install sous-project
ddev drush config-set system.theme default sous-project -y
ddev drush user:block superuser_1
ddev drush user:create sous_chef --mail="sous_chef@fourkitchens.com"
ddev drush user:role:add 'superuser' superuser_1
ddev drush user:role:add 'superuser' sous_chef

# Prompt for optional feature recipe and install based on selection.
echo "Which version of Sous would you like to install?"
echo "[0] Default Sous (media and content types only)"
echo "[1] Sous with Layout Builder"
echo "[2] Sous with Paragraphs"
echo "[3] Just the admin"
read -p "Enter your selection " RESP
case $RESP in
  0)
    ddev composer require fourkitchens/sous-content-types && ddev drush cr && ddev install-recipe fourkitchens/sous-content-types
    ;;
  1)
    ddev composer require fourkitchens/sous-layout-builder && ddev drush cr && ddev install-recipe fourkitchens/sous-layout-builder
    ;;
  2)
    ddev composer require fourkitchens/sous-paragraphs && ddev drush cr && ddev install-recipe fourkitchens/sous-paragraphs
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
ddev drush uli --name=sous_chef

# Remove bootstrap setup artifacts so this install workflow only runs once.
echo "Running post-setup cleanup..."

# Remove nested JSON key (sous references) from a file while preserving formatted JSON output.
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
