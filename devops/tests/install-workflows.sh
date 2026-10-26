#!/usr/bin/env bash
# Run real bootstrap/scaffold scripts against isolated project/tool fixtures.
# Composer scaffold callbacks execute through real Composer/PHP, without plugins.
source "$(dirname "$0")/_test.sh"
REAL_COMPOSER="$(command -v composer)"
fixture() {
  reset_project
  for name in composer.json package.json package-lock.json .gitignore README.md PROJECT-README.md; do cp "$SOURCE/$name" "$PROJECT/$name"; done
  for name in docs .husky assets config/default config/splits; do mkdir -p "$PROJECT/$(dirname "$name")"; cp -R "$SOURCE/$name" "$PROJECT/$name"; done
  mkdir -p "$PROJECT/web/recipes" "$PROJECT/web/sites/default" "$PROJECT/web/themes/custom/sous-project"
  for recipe in sous-layout-builder sous-config-split; do cp -R "$SOURCE/web/recipes/$recipe" "$PROJECT/web/recipes/$recipe"; done
  cp "$SOURCE/.ddev/config.yaml" "$PROJECT/.ddev/config.yaml"
  printf '<?php\n// local defaults\n' > "$PROJECT/web/sites/example.settings.local.php"
  printf '<?php\n// core defaults and Sous additions\n' > "$PROJECT/web/sites/default/default.settings.php"
  export GIT_CONFIG_GLOBAL="$TEST_ROOT/gitconfig" GIT_CONFIG_NOSYSTEM=1
  export COMPOSER_HOME="$TEST_ROOT/composer" COMPOSER_CACHE_DIR="$TEST_ROOT/composer-cache" COMPOSER_DISABLE_NETWORK=1
  printf '[init]\n\tdefaultBranch = developer-preference\n' > "$GIT_CONFIG_GLOBAL"
  cat > "$BIN/php" <<'SH'
#!/usr/bin/env bash
set -euo pipefail
if [ "${FAIL_MATCH:-}" = metadata-write ] && [ "${3:-}" = composer.json ]; then exit 42; fi
exec "$REAL_PHP" "$@"
SH
  chmod +x "$BIN/php"
}
generate() { (cd "$PROJECT"; PATH="$BIN:$PATH" sh devops/scripts/sous-setup.sh && printf '%s\n' "$1" | PATH="$BIN:$PATH" sh devops/scripts/setup.sh fresh); }
scaffold() { (cd "$PROJECT"; PATH="$BIN:$PATH" "$REAL_COMPOSER" --no-plugins run-script post-drupal-scaffold-cmd); }
naming() { (cd "$PROJECT"; PATH="$BIN:$PATH" sh devops/scripts/sous-setup.sh); }
preserved() {
  excludes "$TEST_ROOT/output" 'Your Drupal site is ready'
  [ -f "$PROJECT/devops/scripts/sous-build.sh" ] && [ -f "$PROJECT/devops/scripts/sous-setup.sh" ] || fail 'failed bootstrap removed its helpers'
  json_assert "$PROJECT/composer.json" '.scripts|has("post-create-project-cmd")'
  json_assert "$PROJECT/package.json" '.scripts|has("sous-build")'
}
fixture
expect_status 0 generate 0
contains "$TEST_ROOT/output" 'Your Drupal site is ready'
[ "$(git -C "$PROJECT" branch --show-current)" = main ] || fail 'new project branch'
contains "$GIT_CONFIG_GLOBAL" 'developer-preference'
[ ! -f "$PROJECT/devops/scripts/sous-build.sh" ] || fail 'successful bootstrap retained installer'
json_assert "$PROJECT/composer.json" '.name=="project/project-with-spaces" and (.scripts|has("post-create-project-cmd")|not)'
json_assert "$PROJECT/package.json" '.private and (.scripts|has("sous-build")|not) and (.repository|not) and .scripts.lint=="npm run lint:project"'
json_assert "$PROJECT/package-lock.json" '.name=="project-with-spaces" and .packages[""].name=="project-with-spaces"'
mkdir -p "$PROJECT/web/core" "$PROJECT/web/modules/contrib/example" "$PROJECT/web/themes/contrib/example" "$PROJECT/web/storybook" "$PROJECT/web/themes/custom/projectwithspaces/node_modules" "$PROJECT/web/themes/custom/projectwithspaces/dist"
touch "$PROJECT/web/core/core.php" "$PROJECT/web/modules/contrib/example/example.php" "$PROJECT/web/themes/contrib/example/example.info.yml" "$PROJECT/web/storybook/index.html" "$PROJECT/web/themes/custom/projectwithspaces/node_modules/a.js" "$PROJECT/web/themes/custom/projectwithspaces/dist/a.js"
# Core 4's mirror is output for the generated theme; source and another custom
# theme's own components must still be tracked without broad folder exclusions.
mkdir -p "$PROJECT/web/themes/custom/projectwithspaces/components" "$PROJECT/web/themes/custom/projectwithspaces/src/components" "$PROJECT/web/themes/custom/handmade/components"
touch "$PROJECT/web/themes/custom/projectwithspaces/components/output.yml" "$PROJECT/web/themes/custom/projectwithspaces/src/components/source.yml" "$PROJECT/web/themes/custom/handmade/components/source.yml"
cp "$SOURCE/assets/scaffold/files/settings.sous.php" "$PROJECT/web/sites/default/settings.sous.php"
cp "$SOURCE/assets/scaffold/files/sous.production.services.yml" "$PROJECT/web/sites/sous.production.services.yml"
git -C "$PROJECT" add .
git -C "$PROJECT" ls-files > "$TEST_ROOT/tracked"
for name in composer.json composer.lock config/default/core.extension.yml config/default/system.site.yml web/sites/default/settings.sous.php web/sites/sous.production.services.yml devops/hooks/post-import.sh devops/hooks/post-config-import.sh web/recipes/sous-layout-builder/recipe.yml web/recipes/sous-config-split/recipe.yml web/themes/custom/projectwithspaces/src/index.js; do contains "$TEST_ROOT/tracked" "$name"; done
for pattern in web/core/ web/modules/contrib/ web/themes/contrib/ web/storybook/ /node_modules/ /dist/; do excludes "$TEST_ROOT/tracked" "$pattern"; done
excludes "$TEST_ROOT/tracked" 'web/themes/custom/projectwithspaces/components/'
contains "$TEST_ROOT/tracked" 'web/themes/custom/projectwithspaces/src/components/source.yml'
contains "$TEST_ROOT/tracked" 'web/themes/custom/handmade/components/source.yml'
pass 'generation tracks application sources, retires bootstrap and preserves global Git preferences'
fixture; git -C "$PROJECT" init -b contributor >/dev/null; printf 'unrelated\n' > "$PROJECT/unrelated.txt"
expect_status 0 generate 3
[ "$(git -C "$PROJECT" branch --show-current)" = contributor ] || fail 'contributor branch changed'
contains "$PROJECT/unrelated.txt" unrelated
pass 'contributor clones retain their branch and unrelated work'
fixture
expect_status 0 naming
printf '\nDeveloper additions.\n' >> "$PROJECT/README.md"; cp "$PROJECT/README.md" "$TEST_ROOT/readme"
expect_status 0 naming
cmp "$PROJECT/README.md" "$TEST_ROOT/readme" || fail 'naming retry overwrote README'
pass 'naming retries preserve a generated README'

# Each failure is injected at an actual child command; the real shell must stop.
for row in \
  '0|recipe recipes/sous-admin' \
  '0|require fourkitchens/sous-content-types' \
  '2|fourkitchens/sous-paragraph-types:dev-main' \
  '1|recipe recipes/sous-layout-builder' \
  '0|composer --no-ansi install' \
  '3|npm ci' \
  '0|recipe recipes/sous-content-types' \
  '3|uli --name=sous_chef' \
  '3|config:export' \
  '3|metadata-write'; do
  selection="${row%%|*}"; failed="${row#*|}"
  fixture; export FAIL_MATCH="$failed"
  expect_status 42 generate "$selection"; preserved
  if [ "$failed" != 'uli --name=sous_chef' ] && [ "$failed" != metadata-write ]; then
    calls_assert 'all(.[];.command!="uli")'
  fi
  pass "failed $failed stops dependent work and keeps bootstrap"
done
fixture; expect_status 0 generate 2
calls_assert 'any(.[];(.argv|join(" ")|contains("fourkitchens/sous-paragraph-types:dev-main"))) and any(.[];.command=="recipe" and (.argv|index("recipes/sous-paragraphs"))!=null)'
pass 'Paragraphs installs its declared component recipe'
fixture; expect_status 0 generate 1
calls_assert 'any(.[];.command=="recipe" and (.argv|index("recipes/sous-layout-builder"))!=null) and all(.[];(.argv|join(" ")|contains("require fourkitchens/sous-layout-builder")|not))'
pass 'Layout Builder applies bundled core source without Gin Layout Builder'
fixture; touch "$PROJECT/composer.lock"
expect_status 0 generate 3
calls_assert '[.[]|select(.tool=="composer")|.argv|join(" ")]|(map(contains("update --lock"))|index(true))<(map(test("(^| )install( |$)"))|index(true))'
pass 'existing lock metadata refresh precedes dependency installation'
fixture; expect_status 0 generate 3
calls_assert 'all(.[];(.argv|join(" ")|contains("update --lock")|not)) and any(.[];.tool=="composer" and (.argv|index("validate"))!=null)'
pass 'a clone without a lock resolves before lock validation'
for failed in 'composer --no-ansi update --lock' 'composer --no-ansi validate'; do
  fixture; touch "$PROJECT/composer.lock"; export FAIL_MATCH="$failed"
  expect_status 42 generate 3; preserved
done
pass 'lock refresh/validation failures never retire bootstrap'
# Keep this list limited to native setup work; removed theme rewrites are not prerequisites.
for failed in 'system install' 'pkg set' 'if [ -f package-lock.json ]' 'run build'; do
  fixture; export FAIL_MATCH="$failed"
  expect_status 42 generate 0; preserved
  calls_assert 'all(.[];.command!="theme:install" and .command!="user:create")'
done
pass 'theme setup failures stop before theme enabling and account creation'
fixture; expect_status 0 generate 3
calls_assert '[.[]|select(.tool=="drush")|.command]|index("config:export")<index("uli")'
[ -f "$PROJECT/config/default/core.extension.yml" ] && [ -f "$PROJECT/config/default/system.site.yml" ] || fail 'missing complete export'
pass 'complete configuration export precedes login and success'
for entry in direct ddev; do
  for failed in recipe cr; do
    fixture; export TEST_COMMAND_STATUSES="{\"$failed\":42}"
    if [ "$entry" = direct ]; then expect_status 42 helper install-recipe.sh fourkitchens/sous-admin
    else expect_status 42 env PATH="$BIN:$PATH" bash "$PROJECT/.ddev/commands/web/install-recipe" fourkitchens/sous-admin; fi
    if [ "$failed" = recipe ]; then calls_assert 'all(.[];.command!="cr")'; fi
  done
done
pass 'both recipe entry points propagate application/cache failures'
fixture
for selector in sous-admin vendor/.. vendor/name/extra; do
  reset_log; expect_status 2 helper install-recipe.sh "$selector"; [ ! -s "$COMMAND_LOG" ] || fail 'invalid selector ran commands'
done
expect_status 2 helper install-recipe.sh
expect_status 2 helper install-recipe.sh vendor/name extra
pass 'recipe argument validation happens before tool calls'
fixture
expect_status 2 helper setup.sh; expect_status 2 helper setup.sh unknown
printf '#!/bin/sh\nprintf "fresh\\n"\n' > "$PROJECT/devops/scripts/sous-build.sh"
printf '#!/bin/sh\nprintf "restore\\n"\n' > "$PROJECT/devops/scripts/rebuild.sh"
expect_status 0 helper setup.sh fresh; contains "$TEST_ROOT/output" fresh
expect_status 0 helper setup.sh restore; contains "$TEST_ROOT/output" restore
pass 'setup requires an explicit fresh/restore workflow'
fixture
expect_failure helper setup.sh restore
calls_assert 'all(.[];.command!="import-db" and .command!="deploy" and .command!="uli")'
pass 'missing restore backup stops before database/configuration/login'
for failed in import-db deploy 'run build'; do
  fixture; mkdir -p "$PROJECT/reference"; printf 'SELECT 1;\n' | gzip > "$PROJECT/reference/site-db.sql.gz"
  export FAIL_MATCH="$failed"; expect_status 42 helper setup.sh restore
  calls_assert 'all(.[];.command!="uli" and .command!="sql-sanitize")'
done
pass 'restore build/import/deployment failures prevent sanitizing and login'
fixture; mkdir -p "$PROJECT/reference"; printf 'SELECT 1;\n' | gzip > "$PROJECT/reference/site-db.sql.gz"
expect_status 0 helper setup.sh restore
calls_assert '[.[]|select(.tool=="drush" or .tool=="hook-probe")|.command]|index("post-config-import")<index("sql-sanitize") and index("sql-sanitize")<index("uli")'
calls_assert 'all(.[];.command!="site:install")'
pass 'restore uses the existing backup and both safeguards before final login'
fixture; export SOUS_ENVIRONMENT=production
for script in setup.sh rebuild.sh import-data.sh local-data-bak.sh get-db.sh; do
  reset_log
  if [ "$script" = setup.sh ]; then expect_failure helper "$script" fresh; else expect_failure helper "$script"; fi
  [ ! -s "$COMMAND_LOG" ] || fail 'production refresh invoked a tool'
done
pass 'production rejects destructive development workflows before work'

# Execute Composer's real scaffold callbacks, including preserved dangling links.
fixture; expect_status 0 scaffold
cmp "$PROJECT/web/sites/default/settings.local.php" "$PROJECT/web/sites/example.settings.local.php" || fail 'missing local initialization'
printf '<?php\n// developer settings\n' > "$PROJECT/web/sites/default/settings.local.php"
printf '<?php\n// project settings\n' > "$PROJECT/web/sites/default/settings.php"
expect_status 0 scaffold
contains "$PROJECT/web/sites/default/settings.local.php" 'developer settings'
contains "$PROJECT/web/sites/default/settings.php" 'project settings'
pass 'scaffold initializes once and preserves developer/project settings'
for name in settings.local.php settings.php; do
  rm "$PROJECT/web/sites/default/$name"; ln -s missing-owned-settings.php "$PROJECT/web/sites/default/$name"
done
expect_status 0 scaffold
[ -L "$PROJECT/web/sites/default/settings.local.php" ] && [ -L "$PROJECT/web/sites/default/settings.php" ] || fail 'scaffold replaced owned symlink'
pass 'scaffold preserves dangling project-owned settings symlinks'
for missing in web/sites/example.settings.local.php web/sites/default/default.settings.php; do
  fixture; rm "$PROJECT/$missing"; expect_failure scaffold
done
pass 'missing scaffold sources propagate failure'
echo "PASS: $TEST_COUNT installation check groups."
