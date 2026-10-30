#!/usr/bin/env bash
# Small assertion/fixture helpers for the maintainer-only shell checks.
# Every fixture is below one private directory; none uses a developer database.
set -euo pipefail
SOURCE="$(CDPATH='' cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd -P)"
TEST_ROOT="$(mktemp -d "${TMPDIR:-/tmp}/sous-check.XXXXXX")"
trap 'rm -rf -- "$TEST_ROOT"' EXIT
TEST_COUNT=0
for required_tool in bash jq; do
  command -v "$required_tool" >/dev/null || { echo "Missing test tool: $required_tool" >&2; exit 1; }
done
fail() { echo "FAIL: $*" >&2; [ ! -f "$TEST_ROOT/output" ] || cat "$TEST_ROOT/output" >&2; exit 1; }
pass() { TEST_COUNT=$((TEST_COUNT + 1)); printf 'ok %s - %s\n' "$TEST_COUNT" "$*"; }
expect_status() {
  expected_status="$1"; shift
  if "$@" > "$TEST_ROOT/output" 2>&1; then ACTUAL_STATUS=0; else ACTUAL_STATUS=$?; fi
  [ "$ACTUAL_STATUS" -eq "$expected_status" ] || fail "Expected exit $expected_status, got $ACTUAL_STATUS: $*"
}
expect_failure() {
  if "$@" > "$TEST_ROOT/output" 2>&1; then fail "Expected failure: $*"; else ACTUAL_STATUS=$?; fi
}
contains() { grep -F -- "$2" "$1" >/dev/null || fail "Missing $2 in $1"; }
calls_assert() { jq -se "$1" "$COMMAND_LOG" >/dev/null || fail "Command assertion: $1"; }
reset_project() {
  PROJECT="$TEST_ROOT/project with spaces"
  rm -rf -- "$PROJECT"
  mkdir -p "$PROJECT/devops" "$PROJECT/vendor/bin" "$PROJECT/web" "$TEST_ROOT/bin"
  cp -R "$SOURCE/devops/scripts" "$PROJECT/devops/scripts"
  cp -R "$SOURCE/devops/hooks" "$PROJECT/devops/hooks"
  BIN="$TEST_ROOT/bin"; COMMAND_LOG="$TEST_ROOT/commands.jsonl"; : > "$COMMAND_LOG"
  export PROJECT COMMAND_LOG
  for tool in ddev drush composer npm hook-probe; do
    rm -f "$BIN/$tool"; ln -s "$SOURCE/devops/tests/_mock.sh" "$BIN/$tool"
  done
  ln -s "$SOURCE/devops/tests/_mock.sh" "$PROJECT/vendor/bin/drush"
  for hook in post-import post-config-import download-db; do
    printf '#!/bin/sh\nexec hook-probe %s "$@"\n' "$hook" > "$PROJECT/devops/hooks/$hook.sh"
  done
  cat > "$PROJECT/devops/scripts/runtime-probe.sh" <<'PROBE'
#!/bin/sh
set -eu
. "$(dirname "$0")/_runtime.sh"
"$@"
PROBE
  export TEST_COMMAND_STATUSES='{}' TEST_DOWNLOAD_SOURCE='' TEST_DOWNLOAD_KIND=copy
  unset IS_DDEV_PROJECT || :
}
# Run scripts by absolute path from a subdirectory to exercise root resolution.
helper() { (cd "$PROJECT/web"; PATH="$BIN:$PATH" bash "$PROJECT/devops/scripts/$1" "${@:2}"); }
reset_log() { : > "$COMMAND_LOG"; }
no_import() { calls_assert 'all(.[]; (.command|IN("import-db","sql:drop","sql:cli","sql:sanitize","post-import","post-config-import","uli"))|not)'; }
