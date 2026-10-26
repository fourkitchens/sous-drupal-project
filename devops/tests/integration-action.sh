#!/usr/bin/env bash
# Execute the action's real shell when its downloaded archive omits the runner.
source "$(dirname "$0")/_test.sh"
ACTION="$SOURCE/.github/actions/integration/action.yml"
awk '/      run: \|/{inside=1;next} inside{if($0!~/^        /&&$0!~/^$/)exit;sub(/^        /,"");print}' "$ACTION" > "$TEST_ROOT/action.sh"
central="$TEST_ROOT/downloaded central action"; action="$central/.github/actions/integration"
workspace="$TEST_ROOT/caller workspace with spaces"; tooling="$workspace/.sous-tooling"
mkdir -p "$action" "$tooling/devops/integration" "$TEST_ROOT/working directory"
# Only the additional Git checkout contains maintainer files; this reproduces
# GitHub's export-ignore archive behavior without fetching a remote repository.
cat > "$tooling/devops/integration/run.sh" <<'SH'
#!/usr/bin/env bash
set -euo pipefail
subject=''; repository=''; refs=''; artifacts=''
while [ "$#" -gt 0 ]; do
  case "$1" in
    --subject) subject="$2";; --subject-repository) repository="$2";;
    --refs-json) refs="$2";; --artifacts) artifacts="$2";; *) exit 2;;
  esac
  shift 2
done
jq -n --arg subject "$subject" --arg repository "$repository" --arg refs "$refs" --arg artifacts "$artifacts" '{subject:$subject,subject_repository:$repository,refs_json:$refs,artifacts:$artifacts}' > "$artifacts/result.json"
exit "$TEST_EXIT_CODE"
SH
chmod +x "$tooling/devops/integration/run.sh"
git -C "$tooling" init --initial-branch=main --quiet
git -C "$tooling" add .
git -C "$tooling" -c user.name='Sous Test' -c user.email=sous-test@example.invalid commit --quiet -m 'Tooling fixture'
export SOUS_TOOLING_REF="$(git -C "$tooling" rev-parse HEAD)"
expected_ref="$SOUS_TOOLING_REF"
subject="$TEST_ROOT/subject \$(touch injected-subject)"; mkdir "$subject"
export GITHUB_ACTION_PATH="$action" GITHUB_OUTPUT="$TEST_ROOT/github-output" RUNNER_TEMP="$TEST_ROOT/runner temporary directory" SUBJECT="$subject" SUBJECT_REPOSITORY=fourkitchens/sous-formats
export GITHUB_WORKSPACE="$workspace"
export RECIPE_REFS='{"sous-formats":"$(touch injected-ref); echo untrusted"}'
# Checkout inputs must capture the parent context, keep credentials transient,
# and use the fixed path consumed by the shell below, with no moving-ref fallback.
contains "$ACTION" 'uses: actions/checkout@3d3c42e5aac5ba805825da76410c181273ba90b1'
contains "$ACTION" 'SOUS_TOOLING_REPOSITORY: ${{ github.action_repository }}'
contains "$ACTION" 'SOUS_TOOLING_REF: ${{ github.action_ref }}'
contains "$ACTION" 'repository: ${{ env.SOUS_TOOLING_REPOSITORY }}'
contains "$ACTION" 'ref: ${{ env.SOUS_TOOLING_REF }}'
contains "$ACTION" 'path: .sous-tooling'
contains "$ACTION" 'fetch-depth: 1'
contains "$ACTION" 'persist-credentials: false'
excludes "$ACTION" 'GITHUB_ACTION_PATH/../../../devops/integration'
for status in 0 42; do
  export TEST_EXIT_CODE="$status"
  expect_status "$status" bash -e -o pipefail "$TEST_ROOT/action.sh"
  expected="$RUNNER_TEMP/sous-integration-results"
  contains "$GITHUB_OUTPUT" "result-path=$expected"
  jq -e --arg subject "$SUBJECT" --arg refs "$RECIPE_REFS" --arg artifacts "$expected" '.subject==$subject and .subject_repository=="fourkitchens/sous-formats" and .refs_json==$refs and .artifacts==$artifacts' "$expected/result.json" >/dev/null || fail 'composite action changed argument boundaries'
  [ ! -d "$central/.git" ] || fail 'action required central Git checkout'
  [ ! -e "$central/devops/integration" ] || fail 'downloaded action fixture accidentally contained the runner'
  [ -z "$(find "$TEST_ROOT" -name injected-subject -o -name injected-ref)" ] || fail 'untrusted input was executed'
  pass "archive omission/full source checkout, literal arguments and runner exit $status"
done
for incorrect_ref in '' 0000000000000000000000000000000000000000; do
  export SOUS_TOOLING_REF="$incorrect_ref"
  rm -f "$expected/result.json"
  expect_status 1 bash -e -o pipefail "$TEST_ROOT/action.sh"
  [ ! -f "$expected/result.json" ] || fail 'runner executed without the resolved tooling revision'
done
export SOUS_TOOLING_REF="$expected_ref"
pass 'checkout captures the parent action SHA and refuses missing or different revisions'
echo "PASS: $TEST_COUNT composite-action checks."
