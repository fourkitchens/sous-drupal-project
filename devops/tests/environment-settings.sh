#!/usr/bin/env bash
# Exercise actual scaffold PHP in isolated processes; no Drupal database needed.
source "$(dirname "$0")/_test.sh"
WEB="$TEST_ROOT/web"; SITE="$WEB/sites/default"
mkdir -p "$SITE"
cp "$SOURCE/assets/scaffold/files/settings.sous.php" "$SITE/settings.sous.php"
cp "$SOURCE/assets/scaffold/files/"{development.services.yml,sous.production.services.yml} "$WEB/sites/"
{ printf '<?php\n'; cat "$SOURCE/assets/scaffold/files/additions-default.settings.txt"; } > "$SITE/settings.php"
cat > "$SITE/settings.ddev.php" <<'PHP'
<?php
$settings['ddev_loaded'] = TRUE;
$config['system.logging']['error_level'] = 'verbose';
PHP
cat > "$SITE/settings.local.php" <<'PHP'
<?php
$settings['local_loaded'] = TRUE;
$settings['rebuild_access'] = TRUE;
$settings['cache']['bins']['render'] = 'cache.backend.null';
$settings['container_yamls'][] = $app_root . '/sites/development.services.yml';
$config['config_split.config_split.sous_nonproduction']['status'] = TRUE;
$config['system.mail']['interface']['default'] = 'devel_mail_log';
PHP
cp "$SITE/settings.local.php" "$TEST_ROOT/local-original"
cat > "$TEST_ROOT/probe.php" <<'PHP'
<?php
$app_root = $argv[2]; $site_path = 'sites/default';
$seed = json_decode($argv[3], TRUE, 512, JSON_THROW_ON_ERROR);
$settings = $seed['settings'] ?? []; $config = $seed['config'] ?? [];
try {
  require $argv[1];
  print json_encode(['settings'=>$settings,'config'=>$config,'display_errors'=>ini_get('display_errors')], JSON_THROW_ON_ERROR);
}
catch (Throwable $error) {
  print json_encode(['error'=>get_class($error),'settings'=>$settings,'config'=>$config], JSON_THROW_ON_ERROR);
  exit(42);
}
PHP
probe() (
  unset SOUS_ENVIRONMENT IS_DDEV_PROJECT
  if [ "$1" != __unset__ ]; then export SOUS_ENVIRONMENT="$1"; fi
  if [ "$2" = true ]; then export IS_DDEV_PROJECT=true; fi
  seed="${3:-}"; [ -n "$seed" ] || seed='{}'
  php -d display_errors=1 "$TEST_ROOT/probe.php" "$SITE/settings.php" "$WEB" "$seed"
)
for row in '__unset__ false production' '__unset__ true local' 'production true production' 'nonproduction true nonproduction' 'local false local'; do
  read -r supplied ddev expected <<< "$row"
  expect_status 0 probe "$supplied" "$ddev"
  jq -e --arg expected "$expected" --argjson ddev "$ddev" '.settings.sous_environment==$expected and (.settings.local_loaded//false)==($expected=="local") and (.settings.ddev_loaded//false)==$ddev' "$TEST_ROOT/output" >/dev/null || fail 'environment precedence/local-file gate'
  cmp "$SITE/settings.local.php" "$TEST_ROOT/local-original" || fail 'local settings overwritten'
done
pass 'explicit environment precedence and local-file isolation'
for supplied in '' staging LOCAL ' production' 'local '; do
  expect_status 42 probe "$supplied" true
  json_assert "$TEST_ROOT/output" '.error=="InvalidArgumentException" and (.settings|has("local_loaded")|not)'
done
pass 'invalid values fail before loading local settings'
for supplied in local nonproduction production; do
  expect_status 0 probe "$supplied" false
  jq -e --arg expected "$supplied" '.display_errors==(if $expected=="local" then "1" else "0" end)' "$TEST_ROOT/output" >/dev/null || fail 'PHP error display policy'
done
pass 'nonlocal PHP error display is disabled'
seed="$(jq -cn --arg dev "$WEB/sites/development.services.yml" '{settings:{rebuild_access:true,cache:{default:"cache.backend.null",bins:{render:"cache.backend.null",discovery:"cache.backend.redis"}},container_yamls:[$dev,"/project/services.yml"]}}')"
for supplied in production nonproduction; do
  expect_status 0 probe "$supplied" true "$seed"
  jq -e --arg services "$WEB/sites/sous.production.services.yml" '.settings.rebuild_access==false and (.settings.cache|has("default")|not) and (.settings.cache.bins|has("render")|not) and .settings.cache.bins.discovery=="cache.backend.redis" and .settings.container_yamls==["/project/services.yml",$services] and .config["system.logging"].error_level=="hide" and .config["system.performance"].css.preprocess and .config["system.performance"].js.preprocess' "$TEST_ROOT/output" >/dev/null || fail 'nonlocal service/cache policy'
done
pass 'nonlocal policy wins over prior DDEV/development settings'
for supplied in local nonproduction; do
  expect_status 0 probe "$supplied" false
  json_assert "$TEST_ROOT/output" '.config["system.mail"].interface.default=="test_mail_collector" and .config["automated_cron.settings"].interval==0'
done
seed='{"config":{"system.mail":{"interface":{"default":"project_transport","project_module":"project_transport","old_development":"devel_mail_log"}},"automated_cron.settings":{"interval":10800}}}'
expect_status 0 probe production false "$seed"
json_assert "$TEST_ROOT/output" '.config["system.mail"].interface=={default:"project_transport",project_module:"project_transport"} and .config["automated_cron.settings"].interval==10800 and .config["stage_file_proxy.settings"].origin==""'
pass 'capture and automatic-cron policy are nonproduction-only'
for supplied in local nonproduction production; do
  expect_status 0 probe "$supplied" false
  jq -e --arg env "$supplied" '.config["config_split.config_split.sous_local"].status==($env=="local") and .config["config_split.config_split.sous_nonproduction"].status==($env=="nonproduction") and (.config|has("core.extension")|not) and (.settings|has("config_exclude_modules")|not)' "$TEST_ROOT/output" >/dev/null || fail 'split selection'
done
pass 'split overrides are exclusive and do not enable modules'
rm "$WEB/sites/sous.production.services.yml"
expect_status 42 probe production false
json_assert "$TEST_ROOT/output" '.error=="RuntimeException"'
json_assert "$SOURCE/composer.json" '.extra["drupal-scaffold"]["file-mapping"]["[web-root]/sites/default/settings.sous.php"].overwrite==false and .extra["drupal-scaffold"]["file-mapping"]["[web-root]/sites/sous.production.services.yml"].overwrite==false'
excludes "$SOURCE/assets/scaffold/files/additions-example.settings.local.txt" '\$databases'
excludes "$SOURCE/assets/scaffold/files/additions-example.settings.local.txt" "hash_salt"
pass 'required services fail closed and scaffold preserves owned files'
echo "PASS: $TEST_COUNT settings check groups."
