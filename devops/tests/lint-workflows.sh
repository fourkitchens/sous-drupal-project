#!/usr/bin/env bash
# Check real lint tools in temporary projects, including theme delegation.
source "$(dirname "$0")/_test.sh"
for tool in node npm php; do command -v "$tool" >/dev/null || fail "Missing $tool"; done
[ -f "$SOURCE/node_modules/eslint/bin/eslint.js" ] || fail 'Run npm ci before lint regressions.'
PROJECT="$TEST_ROOT/lint project"; mkdir -p "$PROJECT/devops/scripts"
ln -s "$SOURCE/node_modules" "$PROJECT/node_modules"
cp "$SOURCE/eslint.config.js" "$PROJECT/eslint.config.js"
cp "$SOURCE/devops/scripts/lint-php.mjs" "$PROJECT/devops/scripts/lint-php.mjs"
jq '{private:true,scripts:.scripts}' "$SOURCE/package.json" > "$PROJECT/package.json"
export npm_config_cache="$TEST_ROOT/npm-cache"
printf 'module.exports = { enabled: true };\n' > "$PROJECT/example.config.js"
lint() { (cd "$PROJECT"; npm run "$1"); }
expect_status 0 lint lint
contains "$TEST_ROOT/output" 'no custom PHP files found'
excludes "$TEST_ROOT/output" MODULE_TYPELESS_PACKAGE_JSON
[ ! -d "$PROJECT/web" ] || fail 'starter lint required web/theme tree'
pass 'starter lint works without a generated theme'
printf 'module.exports = ;\n' > "$PROJECT/example.config.js"
expect_failure lint lint; contains "$TEST_ROOT/output" 'Parsing error'
printf 'module.exports = {};\n' > "$PROJECT/example.config.js"
pass 'invalid starter JavaScript fails lint'
mkdir -p "$PROJECT/web/modules/custom/example"
cat > "$PROJECT/web/modules/custom/example/example.js" <<'JS'
Drupal.behaviors.example = { attach(context) { once('example', '.example', context).forEach((element) => { element.textContent = drupalSettings.example; jQuery(element).addClass('ready'); }); } };
JS
expect_status 0 lint lint:js
printf 'Drupal.behaviors.example = missingHandler;\n' > "$PROJECT/web/modules/custom/example/example.js"
expect_failure lint lint:js; contains "$TEST_ROOT/output" no-undef
rm "$PROJECT/web/modules/custom/example/example.js"
pass 'Drupal globals are allowed while real JavaScript errors fail'
printf '<?php\nfunction broken( {\n' > "$PROJECT/devops/scripts/broken.php"
expect_failure lint lint
rm "$PROJECT/devops/scripts/broken.php"
pass 'invalid starter PHP helper fails lint'
mkdir -p "$PROJECT/web/modules/custom/a module"
printf '<?php\nfunction example(): bool { return true; }\n' > "$PROJECT/web/modules/custom/a module/a file.module"
expect_status 0 lint lint:php; rm "$PROJECT/web/modules/custom/a module/a file.module"
for extension in php inc module install theme; do
  printf '<?php\nfunction broken( {\n' > "$PROJECT/web/modules/custom/a module/a file.$extension"
  expect_failure lint lint
  rm "$PROJECT/web/modules/custom/a module/a file.$extension"
done
pass 'PHP-bearing Drupal extensions and spaced paths are linted'
for directory in web/core web/modules/contrib/example web/themes/custom/sous-project/src reference .sfdx; do
  mkdir -p "$PROJECT/$directory"; printf 'const = ;\n' > "$PROJECT/$directory/broken.js"
done
expect_status 0 lint lint:starter
pass 'starter scope excludes downloaded and generated theme sources'
expect_failure lint lint:project
contains "$TEST_ROOT/output" sous-project/package.json
pass 'project lint requires the generated theme'
theme="$PROJECT/web/themes/custom/sous-project"
mkdir -p "$theme/src"; printf 'const = ;\n' > "$theme/src/probe.js"
cat > "$theme/package.json" <<'JSON'
{"private":true,"scripts":{"lint":"npm run lint-js && npm run lint-styles","lint-js":"node --check src/probe.js","lint-styles":"node -e \"require('node:fs').writeFileSync('styles-ran','yes')\""}}
JSON
expect_failure lint lint:project
[ ! -f "$theme/styles-ran" ] || fail 'JavaScript failure did not stop style lint'
pass 'theme JavaScript failure stops dependent style checks'
cat > "$theme/package.json" <<'JSON'
{"private":true,"scripts":{"lint-styles":"node -e \"require('node:fs').writeFileSync('styles-invoked','yes'); process.exit(23)\"","prettier":"node -e \"require('node:fs').writeFileSync('format-invoked','yes'); process.exit(29)\" --"}}
JSON
expect_failure lint lint:styles; [ -f "$theme/styles-invoked" ] || fail 'theme style task was not invoked'
expect_failure lint prettier; [ -f "$theme/format-invoked" ] || fail 'theme format task was not invoked'
pass 'theme style and formatting failures reach root aliases'
echo "PASS: $TEST_COUNT lint check groups."
