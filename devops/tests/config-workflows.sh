#!/usr/bin/env bash
# Isolated runtime/refresh regressions. No provider, database or container runs.
# Usage: bash devops/tests/config-workflows.sh
source "$(dirname "$0")/_test.sh"
reset_project

# Export/deploy retain the selected site's arguments and the child's status.
for status in 0 42; do
  export TEST_COMMAND_STATUSES="{\"config-export\":$status}"
  expect_status "$status" helper confex.sh
  calls_assert '[.[]|select(.tool=="drush")|.command]==["export","config-export"]'
  reset_log
done
pass 'export uses project Drush and propagates failure'
for status in 0 42; do
  export TEST_COMMAND_STATUSES="{\"deploy\":$status}"
  expect_status "$status" helper confim.sh
  calls_assert '[.[]|select(.tool=="drush" or .tool=="hook-probe")|.command]==["import","deploy","cache:rebuild","post-config-import","active"]'
  reset_log
done
pass 'deployment always attempts target safety cleanup'
for mode in export import active; do
  export TEST_COMMAND_STATUSES="{\"$mode\":41}"
  case "$mode" in export) script=confex.sh;; *) script=confim.sh;; esac
  expect_status 41 helper "$script"
done
pass 'preflight and active guard failures propagate'
reset_project
expect_status 0 helper confex.sh '--destination=path with spaces' --uri https://example.invalid '--config=configuration path'
calls_assert '[.[]|select(.tool=="drush")][0].argv==["--uri=https://sous-test.invalid","--uri","https://example.invalid","--config=configuration path","php:script","devops/scripts/environment-check.php"]'
calls_assert '[.[]|select(.tool=="drush")][1].argv[-4:]==["--destination=path with spaces","--uri","https://example.invalid","--config=configuration path"]'
reset_log; export SOUS_RUNTIME=native
expect_status 0 helper confim.sh -lhttps://example.invalid -rweb '-cconfiguration path' -Doptions.uri=https://defined.invalid --local
calls_assert '[.[]|select(.command=="active")][0].argv[0:5]==["-lhttps://example.invalid","-rweb","-cconfiguration path","-Doptions.uri=https://defined.invalid","--local"]'
unset SOUS_RUNTIME
pass 'quoted global target options reach guard and original command'
for script in confex.sh confim.sh; do
  reset_log; expect_status 2 helper "$script" @remote.production
  [ ! -s "$COMMAND_LOG" ] || fail 'remote alias ran a command'
done
pass 'remote aliases fail before any target work'
reset_log; export SOUS_RUNTIME=ddev IS_DDEV_PROJECT=true
expect_status 0 helper confex.sh
calls_assert 'all(.[]; .tool!="ddev")'
pass 'actual DDEV container never recursively invokes DDEV'
reset_project; export CI=true
expect_status 0 helper confex.sh
calls_assert 'any(.[];.tool=="ddev")'
reset_log; export SOUS_RUNTIME=native
expect_status 0 helper confex.sh
calls_assert 'all(.[];.tool!="ddev")'
rm "$PROJECT/vendor/bin/drush"; reset_log
expect_failure helper confex.sh
[ ! -s "$COMMAND_LOG" ] || fail 'missing project Drush fell back globally'
pass 'native CI is explicit and never falls back to global Drush'
reset_project; export DDEV_PROJECT=host-context DDEV_SITENAME=host-context
expect_status 0 helper confex.sh
calls_assert 'any(.[];.tool=="ddev")'
pass 'host provider metadata does not infer native execution'
for value in other ''; do
  reset_project; export SOUS_ENVIRONMENT="$value"
  expect_status 2 helper confex.sh
  [ ! -s "$COMMAND_LOG" ] || fail 'invalid environment ran a command'
done
reset_project; export SOUS_RUNTIME=other
expect_status 2 helper confex.sh
pass 'invalid and explicitly empty runtime values are rejected'
reset_project; export SOUS_RUNTIME=native SOUS_ENVIRONMENT=production
expect_status 0 helper confim.sh
calls_assert 'all(.[];.tool!="hook-probe")'
for script in import-data.sh rebuild.sh local-data-bak.sh get-db.sh; do
  reset_log; expect_failure helper "$script"
  [ ! -s "$COMMAND_LOG" ] || fail 'production development workflow ran a command'
done
pass 'production permits configuration deployment but never local refresh hooks'
reset_project; export SOUS_RUNTIME=native
expect_failure helper get-db.sh
contains "$TEST_ROOT/output" 'requires local or nonproduction'
pass 'native development requires an explicit environment'
reset_project; mkdir -p "$PROJECT/web/themes/custom/a theme"
export SOUS_RUNTIME=native SOUS_ENVIRONMENT=local
for pair in 'theme-build.sh build' 'theme-watch.sh develop' 'theme-storybook.sh storybook-build'; do
  read -r script task <<< "$pair"
  reset_log; expect_status 0 helper "$script" "$PROJECT/web/themes/custom/a theme" --example 'two words'
  calls_assert 'any(.[];.tool=="npm" and .argv[-2:]==["--example","two words"])'
  export FAIL_MATCH="$task"; expect_status 42 helper "$script" "$PROJECT/web/themes/custom/a theme"; export FAIL_MATCH=''
done
pass 'theme paths, tool arguments and failure status survive routing'
reset_project
expect_status 0 helper install-recipe.sh vendor/example
calls_assert '[.[]|select(.tool=="drush")|.command]==["recipe","cr"]'
export FAIL_MATCH='recipe recipes/example'; reset_log
expect_status 42 helper install-recipe.sh vendor/example
calls_assert '[.[]|select(.tool=="drush")|.command]==["recipe"]'
pass 'canonical recipe application stops before cache rebuild on failure'

# These archives are all local fixtures, including deliberately malformed bytes.
reset_project; export SOUS_ENVIRONMENT=nonproduction
mkdir -p "$PROJECT/selected backups"
printf "SELECT 'selected backup';\n" > "$PROJECT/selected backups/site dump.sql"
gzip -c "$PROJECT/selected backups/site dump.sql" > "$PROJECT/selected backups/site dump.sql.gz"
cp "$PROJECT/selected backups/site dump.sql" "$TEST_ROOT/original.sql"
cp "$PROJECT/selected backups/site dump.sql.gz" "$TEST_ROOT/original.sql.gz"
chmod 444 "$PROJECT/selected backups/"*
for extension in sql sql.gz; do
  for runtime in ddev native; do
    export SOUS_RUNTIME="$runtime"; reset_log
    expect_status 0 helper import-data.sh "$PROJECT/selected backups/site dump.$extension"
    cmp "$PROJECT/selected backups/site dump.$extension" "$TEST_ROOT/original.$extension" || fail 'selected backup bytes changed'
    [ "$(php -r 'printf("%o",fileperms($argv[1])&0777);' "$PROJECT/selected backups/site dump.$extension")" = 444 ] || fail 'selected backup permissions changed'
    calls_assert '[.[]|select(.tool=="drush" or .tool=="hook-probe")|.command]|index("post-import")<index("sql-sanitize")'
    if [ "$runtime" = ddev ]; then
      calls_assert 'any(.[];.command=="import-db" and (.argv|index("--skip-hooks"))!=null and (.payload|contains("selected backup")) and .mode==384)'
      prepared="$(jq -r 'select(.command=="import-db")|.backup' "$COMMAND_LOG")"
      [ ! -e "$prepared" ] || fail 'private SQL snapshot leaked'
    else
      calls_assert 'all(.[];.tool!="ddev")'
      calls_assert 'any(.[];.command=="sql:cli" and (.payload|contains("selected backup")))'
    fi
  done
done
pass 'plain/gzip paths with spaces use private immutable snapshots in both runtimes'
reset_project; export SOUS_ENVIRONMENT=local
mkdir -p "$PROJECT/reference"; printf 'SELECT 1;\n' | gzip > "$PROJECT/reference/site-db.sql.gz"
for selected in '' 'missing dump.sql.gz'; do
  reset_log; expect_failure helper import-data.sh "$selected"; no_import
  calls_assert 'all(.[];.tool!="hook-probe")'
done
expect_failure helper get-db.sh ''
pass 'explicit missing/empty paths never select a valid default'
reset_project; export SOUS_ENVIRONMENT=nonproduction
: > "$PROJECT/empty.sql"; printf '' | gzip > "$PROJECT/empty.sql.gz"
printf 'SELECT 1;\n' > "$PROJECT/plain.sql.gz"
printf 'SELECT 1;\n' | gzip > "$PROJECT/compressed.sql"
printf '\037\213' > "$PROJECT/truncated.sql.gz"
printf 'PK\005\006\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000' > "$PROJECT/empty-zip.sql"
printf 'PK\003\004archive' > "$PROJECT/zip.sql"; printf 'PK\007\010archive' > "$PROJECT/spanned-zip.sql"
printf 'BZharchive' > "$PROJECT/bzip.sql"; printf '\3757zXZ\000archive' > "$PROJECT/xz.sql"
printf '\050\265\057\375archive' > "$PROJECT/zstd.sql"
printf 'SELECT 1;\n' > "$PROJECT/unsupported.zip"
# Truncate a real stream so its integrity, rather than only its signature, fails.
printf 'SELECT 1;\n' | gzip > "$TEST_ROOT/valid.gz"
dd if="$TEST_ROOT/valid.gz" of="$PROJECT/crc.sql.gz" bs=1 count=13 2>/dev/null
for backup in "$PROJECT"/*.sql "$PROJECT"/*.sql.gz "$PROJECT"/*.zip; do
  for runtime in ddev native; do
    export SOUS_RUNTIME="$runtime"; reset_log
    expect_failure helper import-data.sh "$backup"; no_import
  done
done
pass 'compression mismatch, corrupt streams, zero payload and unsupported archives fail before import'
mkdir "$PROJECT/directory.sql"; reset_log
expect_failure helper import-data.sh "$PROJECT/directory.sql"; no_import
printf 'SELECT 1;\n' > "$PROJECT/unreadable.sql"; chmod 000 "$PROJECT/unreadable.sql"
if [ ! -r "$PROJECT/unreadable.sql" ]; then
  expect_failure helper import-data.sh "$PROJECT/unreadable.sql"; no_import
else
  echo '# readability subcase skipped: this process bypasses file mode restrictions'
fi
chmod 600 "$PROJECT/unreadable.sql"
pass 'nonregular and unreadable backups are rejected when permissions are enforced'
reset_project; printf 'SELECT 1;\n' > "$PROJECT/backup.sql"
export SOUS_ENVIRONMENT=nonproduction
for pair in 'ddev import-db' 'native sql:drop' 'native sql:cli'; do
  read -r runtime command <<< "$pair"; export SOUS_RUNTIME="$runtime"
  export TEST_COMMAND_STATUSES="{\"$command\":47,\"cache:rebuild\":49,\"post-import\":51,\"active\":53}"
  reset_log; expect_status 47 helper import-data.sh "$PROJECT/backup.sql"
  calls_assert 'any(.[];.command=="cache:rebuild") and any(.[];.command=="post-import") and any(.[];.command=="active")'
  calls_assert 'all(.[];.command!="sql-sanitize" and .command!="uli")'
done
pass 'partial import attempts all cleanup and retains its original failure'
for command in cache:rebuild post-import active sql-sanitize; do
  export TEST_COMMAND_STATUSES="{\"$command\":41}"; reset_log
  expect_status 41 helper import-data.sh "$PROJECT/backup.sql"
  calls_assert 'any(.[];.command=="post-import") and any(.[];.command=="active") and all(.[];.command!="uli")'
  if [ "$command" != sql-sanitize ]; then calls_assert 'all(.[];.command!="sql-sanitize")'; fi
done
pass 'failed safeguards prevent sanitization and login'
export TEST_COMMAND_STATUSES='{}'; reset_log
expect_status 0 helper import-data.sh --defer-sanitize "$PROJECT/backup.sql"
calls_assert 'any(.[];.command=="post-import") and all(.[];.command!="sql-sanitize")'
pass 'whole-refresh import can defer sanitization after successful safeguards'
mkdir -p "$PROJECT/web/themes/custom/sous-project"
for command in none post-import post-config-import sql-sanitize; do
  export TEST_COMMAND_STATUSES='{}'; expected=0
  if [ "$command" != none ]; then export TEST_COMMAND_STATUSES="{\"$command\":41}"; expected=41; fi
  reset_log; expect_status "$expected" helper rebuild.sh "$PROJECT/backup.sql"
  if [ "$command" = none ]; then
    calls_assert '[.[]|select(.tool=="drush" or .tool=="hook-probe")|.command]|index("post-config-import")<index("sql-sanitize") and index("sql-sanitize")<index("uli")'
  else
    calls_assert 'all(.[];.command!="uli")'
    if [ "$command" != sql-sanitize ]; then calls_assert 'all(.[];.command!="sql-sanitize")'; fi
  fi
done
pass 'both refresh hooks gate final sanitizing and login'
for failures in '{}' '{"deploy":43}' '{"post-config-import":45}' '{"deploy":43,"cache:rebuild":44,"post-config-import":45,"active":46}'; do
  export TEST_COMMAND_STATUSES="$failures"; expected="$(jq '.deploy // .["post-config-import"] // 0' <<< "$failures")"
  reset_log; expect_status "$expected" helper confim.sh
  calls_assert '[.[]|select(.tool=="drush" or .tool=="hook-probe")|.command]==["import","deploy","cache:rebuild","post-config-import","active"]'
done
pass 'failed deployments still repeat safeguards and preserve the earlier error'
export TEST_COMMAND_STATUSES='{}'; reset_log
expect_status 0 helper confim.sh --uri https://example.invalid '--config=path with spaces' --verbose
calls_assert '[.[]|select(.tool=="hook-probe")][0].argv==["post-config-import","--uri","https://example.invalid","--config=path with spaces"]'
pass 'safety hooks receive global target context without deployment-only flags'
rm "$PROJECT/devops/hooks/post-import.sh"; reset_log
expect_failure helper import-data.sh "$PROJECT/backup.sql"
calls_assert 'all(.[];.command!="sql-sanitize" and .command!="uli")'
rm "$PROJECT/devops/hooks/post-config-import.sh"; reset_log
expect_failure helper confim.sh
pass 'required local safety hooks fail closed when absent'

reset_project; export SOUS_ENVIRONMENT=local
mkdir -p "$PROJECT/download directory"
printf 'SELECT 2;\n' > "$PROJECT/source.sql"; gzip -c "$PROJECT/source.sql" > "$PROJECT/source.sql.gz"
for name in 'ready backup.sql' 'ready backup.sql.gz' verified.sql; do
  case "$name" in *.gz) export TEST_DOWNLOAD_SOURCE="$PROJECT/source.sql.gz";; *) export TEST_DOWNLOAD_SOURCE="$PROJECT/source.sql";; esac
  destination="$PROJECT/download directory/$name"; printf 'old completed\n' > "$destination"
  reset_log; expect_status 0 helper get-db.sh "$destination"
  cmp "$destination" "$TEST_DOWNLOAD_SOURCE" || fail 'downloaded bytes differ'
  mode="$(php -r 'printf("%o",fileperms($argv[1])&0777);' "$destination")"
  [ "$mode" = 600 ] || fail 'download mode is not 0600'
  payload="$(jq -r 'select(.tool=="hook-probe")|.destination' "$COMMAND_LOG")"
  inode="$(php -r 'echo fileinode($argv[1]);' "$destination")"
  jq -se --argjson inode "$inode" 'any(.[];.tool=="hook-probe" and .payload_inode==$inode and .parent_mode==448)' "$COMMAND_LOG" >/dev/null || fail 'completion did not preserve the private payload inode'
  [ ! -e "$payload" ] || fail 'partial download remained'
  [ "$(find "$PROJECT/download directory" -type f | wc -l | tr -d ' ')" = 1 ] || fail 'download scratch remained'
  rm "$destination"
done
pass 'downloads are validated privately then atomically completed at mode 0600'
destination="$PROJECT/download directory/ready.sql.gz"; export TEST_DOWNLOAD_SOURCE="$PROJECT/source.sql.gz"
for kind in copy empty corrupt symlink; do
  export TEST_DOWNLOAD_KIND="$kind" TEST_COMMAND_STATUSES='{}'
  if [ "$kind" = copy ]; then export TEST_COMMAND_STATUSES='{"download-db":61}'; fi
  printf 'old completed\n' > "$destination"; reset_log
  expect_failure helper get-db.sh "$destination"
  if [ "$kind" = copy ]; then [ "$ACTUAL_STATUS" = 61 ] || fail 'producer failure changed'; fi
  contains "$destination" 'old completed'
  [ "$(find "$PROJECT/download directory" -type f | wc -l | tr -d ' ')" = 1 ] || fail 'failed download scratch remained'
done
pass 'failed, empty, corrupt and symlink downloads preserve the completed target'
ln -s "$SOURCE/devops/tests/_mock.sh" "$BIN/rm"
export TEST_DOWNLOAD_KIND=copy TEST_COMMAND_STATUSES='{"download-db":61,"rm":63}'
expect_status 61 helper get-db.sh "$destination"
rm -f "$BIN/rm"
pass 'download cleanup failure cannot replace the producer failure'
reset_project; export SOUS_ENVIRONMENT=local TEST_DOWNLOAD_SOURCE="$TEST_ROOT/approved.sql.gz"
printf 'SELECT 3;\n' | gzip > "$TEST_DOWNLOAD_SOURCE"
expect_status 0 helper import-data.sh
calls_assert '[.[]|select(.tool=="hook-probe")|.command]==["download-db","post-import"]'
pass 'only a missing omitted default invokes the configured download hook'
reset_project; export SOUS_ENVIRONMENT=local TEST_DOWNLOAD_SOURCE="$TEST_ROOT/approved.sql.gz" TEST_COMMAND_STATUSES='{"download-db":62}'
expect_status 62 helper import-data.sh; no_import
cp "$SOURCE/devops/hooks/download-db.sh" "$PROJECT/devops/hooks/download-db.sh"
export TEST_COMMAND_STATUSES='{}'; reset_log
expect_failure helper import-data.sh; no_import
pass 'failed and unconfigured default downloads never reach database work'
reset_project
expect_failure env PATH="$BIN:$PATH" bash "$PROJECT/devops/ci/drush-config-import"
[ ! -s "$COMMAND_LOG" ] || fail 'unconfigured remote adapter invoked a tool'
expect_status 0 env PATH="$BIN:$PATH" bash "$PROJECT/devops/ci/drush-config-import" example test
calls_assert 'any(.[];.tool=="terminus" and .argv==["-n","drush","example.test","--","deploy","--yes"])'
pass 'optional remote adapter uses explicit target and native deploy'
export TEST_COMMAND_STATUSES='{"terminus":42}'
expect_status 42 env PATH="$BIN:$PATH" bash "$PROJECT/devops/ci/drush-config-import" 'example; touch injected' test
[ ! -e "$PROJECT/injected" ] || fail 'remote arguments were evaluated'
pass 'remote adapter arguments and errors remain literal'
export TERMINUS_SITE=environment TERMINUS_ENV=dev TEST_COMMAND_STATUSES='{}'; reset_log
expect_status 0 env PATH="$BIN:$PATH" bash "$PROJECT/devops/ci/drush-config-import"
calls_assert 'any(.[];.tool=="terminus" and .argv[2]=="environment.dev")'
pass 'remote adapter explicitly configured fallback is retained'
[ -f "$SOURCE/config/default/.gitkeep" ] && [ -f "$SOURCE/config/default/README.md" ] || fail 'missing sync ownership markers'
[ -z "$(find "$SOURCE/config/default" -name '*.yml' -print)" ] || fail 'starter ships a partial site export'
excludes "$SOURCE/assets/scaffold/files/additions-example.settings.local.txt" config_split
pass 'starter contains no partial site export or implicit split activation'
printf 'PASS: %s runtime/refresh check groups.\n' "$TEST_COUNT"
