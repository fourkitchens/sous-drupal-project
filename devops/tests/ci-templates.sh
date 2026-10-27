#!/usr/bin/env bash
# Execute both generated-site build bodies with a fake DDEV; no CI/DB runs.
source "$(dirname "$0")/_test.sh"
mkdir "$TEST_ROOT/bin"
export CI_DDEV_LOG="$TEST_ROOT/ddev.log" CI_DDEV_FAIL=''
cat > "$TEST_ROOT/bin/ddev" <<'SH'
#!/bin/sh
printf '%s\n' "$*" >> "$CI_DDEV_LOG"
if [ -n "$CI_DDEV_FAIL" ] && [ "$*" = "$CI_DDEV_FAIL" ]; then exit 42; fi
SH
chmod +x "$TEST_ROOT/bin/ddev"
for provider in circleci github-actions; do
  case "$provider" in circleci) file=config.yml;; *) file=build.yml;; esac
  template="$SOURCE/assets/ci/$provider/$file"
  # Select the named build block, independent of each provider's YAML nesting.
  awk '
    /name: Validate and build the generated site/ { selected=1; next }
    selected && /: \|$/ { match($0, /[^ ]/); indent=RSTART+1; inside=1; next }
    inside {
      if ($0 !~ /^ *$/ && match($0, /[^ ]/) && RSTART <= indent) exit
      if ($0 !~ /^ *$/) print substr($0, indent+1)
    }
  ' "$template" > "$TEST_ROOT/build.sh"
  [ -s "$TEST_ROOT/build.sh" ] || fail "Missing $provider build commands"
  excludes "$template" semantic-release
  excludes "$template" fourkitchens/sous-drupal-project
  excludes "$template" 'npm run release'
  : > "$CI_DDEV_LOG"
  expect_status 0 env PATH="$TEST_ROOT/bin:$PATH" bash -e -o pipefail "$TEST_ROOT/build.sh"
  contains "$CI_DDEV_LOG" 'composer install --no-interaction --prefer-dist'
  contains "$CI_DDEV_LOG" 'npm ci --include=dev'
  contains "$CI_DDEV_LOG" 'npm run lint'
  contains "$CI_DDEV_LOG" 'npm run theme-build'
  pass "$provider builds the generated site with its own locked dependencies"
  : > "$CI_DDEV_LOG"
  export CI_DDEV_FAIL='composer install --no-interaction --prefer-dist'
  expect_status 42 env PATH="$TEST_ROOT/bin:$PATH" bash -e -o pipefail "$TEST_ROOT/build.sh"
  excludes "$CI_DDEV_LOG" 'npm run theme-build'
  export CI_DDEV_FAIL=''
  pass "$provider stops after dependency installation failure"
done
echo "PASS: $TEST_COUNT generated CI template checks."
