#!/usr/bin/env bash
# Execute the action's actual shell with disposable source and argument probes.
source "$(dirname "$0")/_test.sh"
ACTION="$SOURCE/.github/actions/integration/action.yml"
awk '/      run: \|/{inside=1;next} inside{if($0!~/^        /&&$0!~/^$/)exit;sub(/^        /,"");print}' "$ACTION" > "$TEST_ROOT/action.sh"
workspace="$TEST_ROOT/caller workspace with spaces"; tooling="$workspace/.sous-tooling"
mkdir -p "$tooling/devops/integration"
cp "$SOURCE/devops/integration/pr-refs.json" "$tooling/devops/integration/pr-refs.json"
cat > "$tooling/devops/integration/run.sh" <<'RUNNER'
#!/usr/bin/env bash
set -euo pipefail
subject=''; repository=''; ref=''; artifacts=''
while [ "$#" -gt 0 ]; do
  case "$1" in
    --subject) subject="$2";; --subject-repository) repository="$2";;
    --packages-ref) ref="$2";; --artifacts) artifacts="$2";; *) exit 2;;
  esac
  shift 2
done
jq -n --arg subject "$subject" --arg repository "$repository" --arg ref "$ref" '{subject:$subject,subject_repository:$repository,packages_ref:$ref}' > "$artifacts/result.json"
exit "$TEST_EXIT_CODE"
RUNNER
chmod +x "$tooling/devops/integration/run.sh"
git -C "$tooling" init --initial-branch=main --quiet
git -C "$tooling" add .
git -C "$tooling" -c user.name='Sous Test' -c user.email=sous-test@example.invalid commit --quiet -m 'Tooling fixture'
export SOUS_TOOLING_REF="$(git -C "$tooling" rev-parse HEAD)"
expected_ref="$SOUS_TOOLING_REF"
subject="$TEST_ROOT/subject \$(touch injected-subject)"; mkdir "$subject"
export GITHUB_OUTPUT="$TEST_ROOT/github-output" RUNNER_TEMP="$TEST_ROOT/runner temporary directory" SUBJECT="$subject" SUBJECT_REPOSITORY=fourkitchens/sous-drupal-project
export GITHUB_WORKSPACE="$workspace" SOURCE_BRANCH=unrelated-branch PACKAGES_REF=aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa
expected="$RUNNER_TEMP/sous-integration-results"
contains "$ACTION" 'uses: actions/checkout@3d3c42e5aac5ba805825da76410c181273ba90b1'
contains "$ACTION" 'SOUS_TOOLING_REPOSITORY: ${{ github.action_repository }}'
contains "$ACTION" 'SOUS_TOOLING_REF: ${{ github.action_ref }}'
contains "$ACTION" 'repository: ${{ env.SOUS_TOOLING_REPOSITORY }}'
contains "$ACTION" 'ref: ${{ env.SOUS_TOOLING_REF }}'
contains "$ACTION" 'persist-credentials: false'
for status in 0 42; do
  export TEST_EXIT_CODE="$status"
  expect_status "$status" bash -e -o pipefail "$TEST_ROOT/action.sh"
  contains "$GITHUB_OUTPUT" "result-path=$expected"
  jq -e --arg subject "$SUBJECT" --arg ref "$PACKAGES_REF" '.subject==$subject and .subject_repository=="fourkitchens/sous-drupal-project" and .packages_ref==$ref' "$expected/result.json" >/dev/null || fail 'action changed argument boundaries'
  [ -z "$(find "$TEST_ROOT" -name injected-subject)" ] || fail 'untrusted input was executed'
  pass "exact source checkout, literal arguments and runner exit $status"
done
export TEST_EXIT_CODE=0 PACKAGES_REF='' SOURCE_BRANCH=codex/release-starter-packages-2.0
expect_status 0 bash -e -o pipefail "$TEST_ROOT/action.sh"
expected_package_ref="$(jq -er '.packages_ref | select(test("^[0-9a-f]{40}$"))' "$SOURCE/devops/integration/pr-refs.json")"
jq -e --arg ref "$expected_package_ref" '.packages_ref==$ref' "$expected/result.json" >/dev/null || fail 'release branch did not use its reviewed candidate SHA'
pass 'coordinated release branch selects the exact reviewed monorepo candidate'
export PACKAGES_REF=bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb
expect_status 0 bash -e -o pipefail "$TEST_ROOT/action.sh"
jq -e --arg ref "$PACKAGES_REF" '.packages_ref==$ref' "$expected/result.json" >/dev/null || fail 'manual candidate SHA was replaced'
export PACKAGES_REF='' SOURCE_BRANCH=main
expect_status 0 bash -e -o pipefail "$TEST_ROOT/action.sh"
jq -e '.packages_ref==""' "$expected/result.json" >/dev/null || fail 'production main used a candidate fallback'
pass 'explicit SHA takes precedence; production main uses the public index'
for invalid in main refs/heads/main '$(touch injected-ref)'; do
  export PACKAGES_REF="$invalid"
  rm -f "$expected/result.json"
  expect_status 2 bash -e -o pipefail "$TEST_ROOT/action.sh"
  [ ! -e "$expected/result.json" ] || fail 'runner executed with an unreviewed candidate reference'
done
pass 'floating or executable candidate input is rejected before the runner'
export PACKAGES_REF=''
for incorrect_ref in '' 0000000000000000000000000000000000000000; do
  export SOUS_TOOLING_REF="$incorrect_ref"
  rm -f "$expected/result.json"
  expect_status 1 bash -e -o pipefail "$TEST_ROOT/action.sh"
  [ ! -f "$expected/result.json" ] || fail 'runner executed without resolved tooling revision'
done
export SOUS_TOOLING_REF="$expected_ref"
pass 'missing or mismatched tooling revision fails closed'
echo "PASS: $TEST_COUNT composite-action checks."
