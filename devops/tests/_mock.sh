#!/usr/bin/env bash
# Project-tool double shared by the workflow tests. Record quoted argv as JSON;
# run real shell helpers, but never call DDEV, a provider or a database.
set -euo pipefail
tool="${0##*/}"; arguments=("$@"); command="${1:-}"
if [ "$tool" = drush ]; then
  while [ "$#" -gt 0 ]; do
    case "$1" in
      --uri|--root|--config|--alias-path|--include|--define|-l|-r|-c|-D) shift; [ "$#" -eq 0 ] || shift ;;
      -*) shift ;;
      *) command="$1"; break ;;
    esac
  done
  if [ "$command" = php:script ]; then command="${SOUS_ENVIRONMENT_CHECK:-php:script}"; fi
fi
set -- "${arguments[@]}"
record="$(jq -cn --arg tool "$tool" --arg command "$command" --arg cwd "$PWD" --arg environment "${SOUS_ENVIRONMENT:-}" --arg check "${SOUS_ENVIRONMENT_CHECK:-}" --args '$ARGS.positional as $argv | {tool:$tool,command:$command,cwd:$cwd,environment:$environment,check:$check,argv:$argv}' -- "$@")"
if [ "$tool" = drush ] && [ "$command" = sql:cli ]; then
  payload="$(cat)"; record="$(printf '%s' "$record" | jq --arg payload "$payload" '.+{payload:$payload}')"
fi
if [ "$tool" = ddev ] && [ "$command" = import-db ]; then
  for argument in "$@"; do case "$argument" in --file=*) backup="${argument#--file=}";; esac; done
  mode="$("$REAL_PHP" -r 'echo fileperms($argv[1]) & 0777;' "$backup")"
  record="$(printf '%s' "$record" | jq --arg backup "$backup" --argjson mode "$mode" --rawfile payload "$backup" '.+{backup:$backup,payload:$payload,mode:$mode}')"
fi
if [ "$tool" = hook-probe ] && [ "$command" = download-db ]; then
  destination="$2"
  case "${TEST_DOWNLOAD_KIND:-copy}" in
    empty) : > "$destination" ;;
    corrupt) printf '\037\213' > "$destination" ;;
    symlink) ln -s "$TEST_DOWNLOAD_SOURCE" "$destination" ;;
    copy) [ -z "${TEST_DOWNLOAD_SOURCE:-}" ] || cp "$TEST_DOWNLOAD_SOURCE" "$destination" ;;
  esac
  if [ -f "$destination" ] && [ ! -L "$destination" ]; then
    inode="$("$REAL_PHP" -r 'echo fileinode($argv[1]);' "$destination")"
  else inode=0; fi
  parent_mode="$("$REAL_PHP" -r 'echo fileperms(dirname($argv[1])) & 0777;' "$destination")"
  record="$(printf '%s' "$record" | jq --arg destination "$destination" --argjson inode "$inode" --argjson parent_mode "$parent_mode" '.+{destination:$destination,payload_inode:$inode,parent_mode:$parent_mode}')"
fi
printf '%s\n' "$record" >> "$COMMAND_LOG"
statuses="${TEST_COMMAND_STATUSES:-}"
[ -n "$statuses" ] || statuses='{}'
status="$(jq -r --arg tool "$tool" --arg command "$command" '.[$tool] // .[$command] // 0' <<< "$statuses")"
if [ -n "${FAIL_MATCH:-}" ]; then case "$*" in *"$FAIL_MATCH"*) status=42;; esac; fi
[ "$status" -eq 0 ] || exit "$status"
case "$tool:$command" in
  ddev:exec)
    shift
    while [ "$#" -gt 0 ]; do case "$1" in --raw|--dir=*) shift;; *) break;; esac; done
    if [ "${1:-}" = env ]; then
      shift
      while [ "$#" -gt 0 ]; do case "$1" in *=*) export "$1"; shift;; *) break;; esac; done
    fi
    export IS_DDEV_PROJECT=true DDEV_PRIMARY_URL=https://sous-test.invalid
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
