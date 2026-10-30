#!/usr/bin/env bash
# Exercise the shipped config/data scripts with isolated tools and SQL fixtures.
source "$(dirname "$0")/_test.sh"

for context in host container; do
  reset_project; [ "$context" != container ] || export IS_DDEV_PROJECT=true
  for status in 0 42; do
    export TEST_COMMAND_STATUSES="{\"config:export\":$status}"; reset_log
    expect_status "$status" helper confex.sh '--destination=path with spaces'
    calls_assert '[.[]|select(.tool=="drush")][0].argv==["config:export","--yes","--destination=path with spaces"]'
  done
done
pass 'configuration export routes directly and preserves arguments and status'
for context in host container; do
  for failures in '{}' '{"deploy":43}' '{"post-config-import":45}' '{"deploy":43,"post-config-import":45}'; do
    reset_project; [ "$context" != container ] || export IS_DDEV_PROJECT=true
    export TEST_COMMAND_STATUSES="$failures"
    expected=$(jq '.deploy // .["post-config-import"] // 0' <<< "$failures")
    expect_status "$expected" helper confim.sh
    calls_assert '[.[]|select(.tool=="drush" or .tool=="hook-probe")|.command]==["deploy","post-config-import"]'
  done
done
pass 'deployment runs an optional hook after success or failure and keeps the first error'
reset_project; rm "$PROJECT/devops/hooks/post-config-import.sh"
expect_status 0 helper confim.sh
calls_assert 'all(.[];.tool!="hook-probe")'
pass 'missing configuration hooks are skipped'

for context in host container; do
  reset_project; [ "$context" != container ] || export IS_DDEV_PROJECT=true
  expect_status 0 helper runtime-probe.sh runtime_exec hook-probe 'two words'
  expect_status 0 helper runtime-probe.sh runtime_composer install
  expect_status 0 helper runtime-probe.sh runtime_npm ci
  expect_status 0 helper runtime-probe.sh runtime_start
  if [ "$context" = host ]; then
    calls_assert '[.[]|select(.tool=="ddev")|.command]==["exec","composer","npm","start"]'
    calls_assert 'any(.[];.tool=="ddev" and .command=="exec" and (.argv|index("--dir=/var/www/html"))!=null)'
  else calls_assert 'all(.[];.tool!="ddev")'; fi
  calls_assert 'any(.[];.tool=="hook-probe" and .argv==["two words"])'
done
pass 'host adapters use DDEV commands and container adapters use project tools'

reset_project; export IS_DDEV_PROJECT=true
expect_status 0 helper confex.sh
calls_assert 'all(.[];.tool!="ddev")'
rm "$PROJECT/vendor/bin/drush"; reset_log
expect_failure helper confex.sh
[ ! -s "$COMMAND_LOG" ] || fail 'missing project Drush fell back to global Drush'
pass 'container commands avoid DDEV recursion and never fall back to global Drush'
for context in host container; do
  reset_project; [ "$context" != container ] || export IS_DDEV_PROJECT=true
  mkdir -p "$PROJECT/selected backups"
  printf "SELECT 'selected backup';\n" > "$PROJECT/selected backups/site dump.sql"
  gzip -c "$PROJECT/selected backups/site dump.sql" > "$PROJECT/selected backups/site dump.sql.gz"
  for extension in sql sql.gz; do
    reset_log; expect_status 0 helper import-data.sh "$PROJECT/selected backups/site dump.$extension"
    calls_assert '[.[]|select(.tool=="drush" or .tool=="hook-probe")|.command]|index("cache:rebuild")<index("post-import") and index("post-import")<index("sql:sanitize")'
    if [ "$context" = host ]; then
      calls_assert 'any(.[];.command=="import-db" and (.payload|contains("selected backup")))'
    else
      calls_assert 'all(.[];.tool!="ddev") and any(.[];.command=="sql:cli" and (.payload|contains("selected backup")))'
    fi
  done
  : > "$PROJECT/empty.sql"
  printf 'not gzip\n' > "$PROJECT/corrupt.sql.gz"
  printf '\037\213' > "$PROJECT/truncated.sql.gz"
  printf 'SELECT 1;\n' > "$PROJECT/unsupported.zip"
  for name in empty.sql corrupt.sql.gz truncated.sql.gz unsupported.zip missing.sql.gz; do
    reset_log; expect_failure helper import-data.sh "$PROJECT/$name"; no_import
  done
done
pass 'plain/gzip backups import in both DDEV contexts; invalid backups fail before database commands'
for pair in 'host import-db' 'container sql:drop' 'container sql:cli'; do
  reset_project; read -r context command <<< "$pair"
  [ "$context" != container ] || export IS_DDEV_PROJECT=true
  export TEST_COMMAND_STATUSES="{\"$command\":47,\"post-import\":51}"
  printf 'SELECT 1;\n' > "$PROJECT/backup.sql"
  expect_status 47 helper import-data.sh "$PROJECT/backup.sql"
  calls_assert 'any(.[];.command=="post-import") and all(.[];.command!="cache:rebuild" and .command!="sql:sanitize" and .command!="uli")'
done
pass 'import failures attempt the optional hook and preserve the import error'
for command in cache:rebuild post-import sql:sanitize; do
  reset_project; export TEST_COMMAND_STATUSES="{\"$command\":41}"
  printf 'SELECT 1;\n' > "$PROJECT/backup.sql"
  expect_status 41 helper import-data.sh "$PROJECT/backup.sql"
  calls_assert 'all(.[];.command!="uli")'
  if [ "$command" != sql:sanitize ]; then calls_assert 'all(.[];.command!="sql:sanitize")'; fi
done
pass 'cache, hook and sanitization failures propagate without a login link'
reset_project
printf 'SELECT 1;\n' > "$PROJECT/backup.sql"; rm "$PROJECT/devops/hooks/post-import.sh"
expect_status 0 helper import-data.sh "$PROJECT/backup.sql"
calls_assert 'any(.[];.command=="sql:sanitize") and all(.[];.tool!="hook-probe")'
pass 'missing post-import hook is optional'

reset_project
mkdir -p "$PROJECT/download directory"
printf 'SELECT 2;\n' > "$PROJECT/source.sql"
gzip -c "$PROJECT/source.sql" > "$PROJECT/source.sql.gz"
for extension in sql sql.gz; do
  export TEST_DOWNLOAD_SOURCE="$PROJECT/source.$extension"
  destination="$PROJECT/download directory/ready backup.$extension"
  printf 'old completed\n' > "$destination"
  expect_status 0 helper get-db.sh "$destination"
  cmp "$destination" "$TEST_DOWNLOAD_SOURCE" || fail 'downloaded bytes differ'
  [ ! -e "$destination.part" ] || fail 'completed download left a .part file'
  calls_assert 'any(.[];.command=="download-db" and (.destination|endswith(".part")))'
done
pass 'successful downloads replace the destination only after validation'
for kind in copy empty corrupt; do
  export TEST_DOWNLOAD_KIND="$kind" TEST_DOWNLOAD_SOURCE="$PROJECT/source.sql.gz" TEST_COMMAND_STATUSES='{}'
  if [ "$kind" = copy ]; then export TEST_COMMAND_STATUSES='{"download-db":61}'; fi
  destination="$PROJECT/download directory/ready.sql.gz"
  printf 'old completed\n' > "$destination"; reset_log
  expect_failure helper get-db.sh "$destination"
  if [ "$kind" = copy ]; then [ "$ACTUAL_STATUS" -eq 61 ] || fail 'producer failure changed'; fi
  contains "$destination" 'old completed'
  [ ! -e "$destination.part" ] || fail 'failed download left a .part file'
done
pass 'producer, empty-payload and gzip-validation failures preserve the destination and remove .part'
reset_project; export TEST_DOWNLOAD_SOURCE="$TEST_ROOT/approved.sql.gz"
printf 'SELECT 3;\n' | gzip > "$TEST_DOWNLOAD_SOURCE"
expect_status 0 helper import-data.sh
calls_assert '[.[]|select(.tool=="hook-probe")|.command]==["download-db","post-import"]'
reset_log; expect_failure helper import-data.sh "$PROJECT/missing.sql.gz"; no_import
calls_assert 'all(.[];.command!="download-db")'
pass 'only an omitted missing default triggers a download'
reset_project
rm "$PROJECT/devops/hooks/download-db.sh"
expect_failure helper get-db.sh
[ ! -s "$COMMAND_LOG" ] || fail 'missing optional download hook ran a tool'
pass 'missing download hook fails validation without invoking a tool'

echo "PASS: $TEST_COUNT runtime/config/data check groups."
