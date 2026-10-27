#!/usr/bin/env bash

# Fast source checks: no Drupal install, application database or DDEV required.
set -euo pipefail
root="$(CDPATH='' cd "$(dirname "$0")/../.." && pwd -P)"
cd "$root"

npm run lint:starter
php devops/tests/starter-defaults.php
bash devops/tests/documentation.sh
for test in install-workflows config-workflows environment-settings lint-workflows integration-audit integration-action integration-clone integration-index ci-templates artifact; do
  bash "devops/tests/$test.sh"
done
npm run release:test
node devops/tests/verify-cli-cache.mjs
