#!/usr/bin/env bash
# Exercise readiness and its real transition wrapper without retrying Composer.
source "$(dirname "$0")/_test.sh"
mkdir "$TEST_ROOT/bin"
export READY_STATE="$TEST_ROOT/state" READY_MODE=delayed
cat > "$TEST_ROOT/bin/supervisorctl" <<'MOCK'
#!/usr/bin/env bash
printf '%s\n' "$*" >> "$READY_STATE.calls"
if [ "$1" = start ]; then
  count=$(cat "$READY_STATE" 2>/dev/null || printf 0)
  count=$((count+1)); printf '%s\n' "$count" > "$READY_STATE"
  [ "$count" -ge 3 ] && [ "$READY_MODE" != failed ] || { echo 'supervisor socket not ready'; exit 7; }
fi
MOCK
cat > "$TEST_ROOT/bin/curl" <<'MOCK'
#!/usr/bin/env bash
[ "$(cat "$READY_STATE" 2>/dev/null || printf 0)" -ge 3 ] && [ "$READY_MODE" != failed ]
MOCK
printf '#!/bin/sh\nexit 0\n' > "$TEST_ROOT/bin/sleep"
chmod +x "$TEST_ROOT/bin/"*
export SOUS_DISPOSABLE_TEST=1
expect_status 0 env PATH="$TEST_ROOT/bin:$PATH" bash "$SOURCE/devops/integration/wait-for-index.sh"
[ "$(cat "$READY_STATE")" -eq 3 ] || fail 'readiness did not wait for the delayed supervisor/socket'
pass 'native readiness waits for delayed supervisor before declaring the index ready'
export READY_MODE=failed
printf '0\n' > "$READY_STATE"
expect_status 1 env PATH="$TEST_ROOT/bin:$PATH" bash "$SOURCE/devops/integration/wait-for-index.sh"
[ "$(cat "$READY_STATE")" -eq 30 ] || fail 'readiness retry count is not bounded'
contains "$TEST_ROOT/output" 'Candidate index did not become ready'
contains "$READY_STATE.calls" 'status webextradaemons:sous-package-index'
pass 'permanent failure is bounded and records only the owned daemon diagnostics'
awk '/^candidate_index_ready\(\) \{/{inside=1} /^sha256\(\)/{inside=0} inside{print}' "$SOURCE/devops/integration/run.sh" > "$TEST_ROOT/wrapper.sh"
source "$TEST_ROOT/wrapper.sh"
candidate=1; WRAPPER_FAILURE=0
must() {
  printf '%s\n' "$*" >> "$TEST_ROOT/wrapper.calls"
  if [[ "$1" = *-index-ready ]] && [ "$WRAPPER_FAILURE" = 1 ]; then return 42; fi
}
: > "$TEST_ROOT/wrapper.calls"
dd restore snapshot restore integration-base
contains "$TEST_ROOT/wrapper.calls" 'restore-index-ready ddev exec env SOUS_DISPOSABLE_TEST=1 timeout --kill-after=2 45 bash devops/integration/wait-for-index.sh'
[ "$(wc -l < "$TEST_ROOT/wrapper.calls" | tr -d ' ')" -eq 2 ] || fail 'DDEV transition was retried'
WRAPPER_FAILURE=1
if dd restore snapshot restore integration-base; then fail 'readiness failure was masked'; fi
candidate=0
: > "$TEST_ROOT/wrapper.calls"
dd production start
[ "$(wc -l < "$TEST_ROOT/wrapper.calls" | tr -d ' ')" -eq 1 ] || fail 'production run started a candidate server'
pass 'actual DDEV wrapper checks readiness once after transitions and propagates failure'
echo "PASS: $TEST_COUNT candidate readiness checks."
