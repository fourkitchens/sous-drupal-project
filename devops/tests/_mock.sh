#!/usr/bin/env bash
# Record project-tool calls without starting containers or touching a database.
set -euo pipefail
tool="${0##*/}"; command="${1:-}"
if [ "$tool" = drush ]; then
  for argument do
    case "$argument" in -*) ;; *) command="$argument"; break ;; esac
  done
fi
record=$(jq -cn --arg tool "$tool" --arg command "$command" --arg cwd "$PWD" --args \
  '{tool:$tool,command:$command,cwd:$cwd,argv:$ARGS.positional}' -- "$@")
if [ "$tool:$command" = drush:sql:cli ]; then
  payload=$(cat)
  record=$(printf '%s' "$record" | jq --arg payload "$payload" '.+{payload:$payload}')
fi
if [ "$tool:$command" = ddev:import-db ]; then
  for argument do case "$argument" in --file=*) backup="${argument#--file=}" ;; esac; done
  record=$(printf '%s' "$record" | jq --arg backup "$backup" --arg payload "$(gzip -dcf "$backup")" '.+{backup:$backup,payload:$payload}')
fi
if [ "$tool:$command" = hook-probe:download-db ]; then
  destination="$2"
  case "${TEST_DOWNLOAD_KIND:-copy}" in
    empty) : > "$destination" ;;
    corrupt) printf '\037\213' > "$destination" ;;
    copy) [ -z "${TEST_DOWNLOAD_SOURCE:-}" ] || cp "$TEST_DOWNLOAD_SOURCE" "$destination" ;;
  esac
  record=$(printf '%s' "$record" | jq --arg destination "$destination" '.+{destination:$destination}')
fi
printf '%s\n' "$record" >> "$COMMAND_LOG"
statuses="${TEST_COMMAND_STATUSES:-}"
[ -n "$statuses" ] || statuses='{}'
status=$(jq -r --arg tool "$tool" --arg command "$command" '.[$tool] // .[$command] // 0' <<< "$statuses")
if [ -n "${FAIL_MATCH:-}" ]; then case "$*" in *"$FAIL_MATCH"*) status=42 ;; esac; fi
[ "$status" -eq 0 ] || exit "$status"
case "$tool:$command" in
  ddev:drush) shift; exec "$PROJECT/vendor/bin/drush" "$@" ;;
  ddev:emulsify) shift; exec "$PROJECT/node_modules/.bin/emulsify" "$@" ;;
  ddev:composer|ddev:npm) shift; exec "$command" "$@" ;;
  ddev:exec)
    shift
    while [ "$#" -gt 0 ]; do case "$1" in --raw|--dir=*) shift ;; *) break ;; esac; done
    cd "$PROJECT"
    export IS_DDEV_PROJECT=true
    exec "$@" ;;
  ddev:install-recipe)
    shift; unset IS_DDEV_PROJECT
    exec bash .ddev/commands/web/install-recipe "$@" ;;
  composer:*) touch composer.lock ;;
  drush:emulsify)
    for theme do :; done
    mkdir -p "$PROJECT/web/themes/custom/$theme/src" "$PROJECT/web/themes/custom/$theme/dist"
    printf 'name: Test theme\n' > "$PROJECT/web/themes/custom/$theme/$theme.info.yml"
    : > "$PROJECT/web/themes/custom/$theme/src/index.js" ;;
  drush:config:export)
    mkdir -p "$PROJECT/config/default"
    printf 'uuid: generated-site-uuid\nname: Generated site\n' > "$PROJECT/config/default/system.site.yml"
    printf 'module:\n  system: 0\n  user: 0\n' > "$PROJECT/config/default/core.extension.yml" ;;
esac
