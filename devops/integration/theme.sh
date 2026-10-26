#!/bin/sh

set -eu

if [ "${SOUS_DISPOSABLE_TEST:-}" != 1 ]; then
  echo "Set SOUS_DISPOSABLE_TEST=1 only inside a disposable integration fixture." >&2
  exit 1
fi

# Run from the disposable generated project's root after its locked npm install.
theme="${1:-integration}"
case "$theme" in
  ''|[!a-z]*|*[!a-z0-9_]*) echo "Invalid Drupal theme machine name: $theme" >&2; exit 1 ;;
esac
directory="web/themes/custom/$theme"

ddev drush --yes emulsify "$theme"
sh devops/scripts/theme-setup.sh "$directory"
# Run the upstream checks separately: some starter releases join their lint
# scripts with semicolons, which can otherwise hide an earlier failing check.
ddev exec --raw -d "/var/www/html/$directory" npm run lint-js
ddev exec --raw -d "/var/www/html/$directory" npm run lint-styles
ddev exec --raw -d "/var/www/html/$directory" npm run prettier -- --check
ddev exec --raw sh devops/scripts/theme-storybook.sh "$directory"
ddev drush --yes theme:install "$theme"
ddev drush --yes config:set system.theme default "$theme"
ddev drush php:script devops/tests/verify-theme.php
