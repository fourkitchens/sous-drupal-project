#!/usr/bin/env bash
# remove xdebug to make php execute faster
phpenv config-rm xdebug.ini

# globally require drupal coder for code tests
composer global require "symfony/yaml:^3.4" "drupal/coder"

# run phpcs
phpcs --config-set installed_paths ~/.composer/vendor/drupal/coder/coder_sniffer
phpcs --standard=Drupal --ignore=README.md -p .
phpcs --standard=DrupalPractice -p .

# JS ESLint checking
set -x
source ~/.nvm/nvm.sh
set +x
nvm install 6
npm install -g eslint
eslint .
