#!/usr/bin/env bash
# The real clone function must fail inside the option aggregator's `if` context.
source "$(dirname "$0")/_test.sh"
awk '/^cold_clone\(\) \{/{inside=1} /^option_case\(\)/{inside=0} inside{print}' "$SOURCE/devops/integration/run.sh" > "$TEST_ROOT/clone-function.sh"
source "$TEST_ROOT/clone-function.sh"
site="$TEST_ROOT/consumer"; work="$TEST_ROOT"; choice=default; split=plain
mkdir -p "$site"
cd "$site"
printf '%s\n' '{"packages":[{"name":"fourkitchens/sous-admin"}]}' > composer.lock
export FAIL_STEP='' CORRUPT_LOCK=0
fail() { echo "FAIL: $*" >&2; return 1; }
must() {
  step=$1; shift
  printf '%s\n' "$step" >> "$TEST_ROOT/calls"
  [ "$step" != "$FAIL_STEP" ] || return 42
  if [ "$step" = clone-source-init ]; then mkdir -p .git/info; fi
  if [ "$step" = case-clone ]; then
    mkdir -p "$clone"
    cp composer.lock "$clone/composer.lock"
    [ "$CORRUPT_LOCK" = 0 ] || printf 'changed\n' > "$clone/composer.lock"
  fi
}
dd() { must "$@"; }
for failed in clone-source-init case-stage-source case-stage-lock case-source-commit case-clone case-clone-install case-clone-prefer-source case-clone-boundaries; do
  rm -rf .git "$site/.integration"
  if [ "$failed" != clone-source-init ]; then mkdir -p .git/info; fi
  : > "$TEST_ROOT/calls"
  FAIL_STEP=$failed
  if cold_clone case > "$TEST_ROOT/output" 2>&1; then echo "FAIL: clone accepted $failed failure" >&2; exit 1; fi
  [ "$(tail -n 1 "$TEST_ROOT/calls")" = "$failed" ] || { echo "FAIL: clone continued after $failed" >&2; cat "$TEST_ROOT/calls"; exit 1; }
  pass "clone stops at $failed even inside an if condition"
done
FAIL_STEP=''; CORRUPT_LOCK=1
: > "$TEST_ROOT/calls"
if cold_clone case > "$TEST_ROOT/output" 2>&1; then echo 'FAIL: changed clone lock was accepted' >&2; exit 1; fi
excludes "$TEST_ROOT/calls" case-clone-prefer-source
pass 'changed clone lock fails before reinstall/boundary success can mask it'
echo "PASS: $TEST_COUNT clone failure-propagation checks."
