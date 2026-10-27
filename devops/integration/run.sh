#!/usr/bin/env bash
# Smoke-test the shipped fresh/restore entry points in one disposable DDEV site.
set -euo pipefail
source_root="$(CDPATH='' cd "$(dirname "$0")/../.." && pwd -P)"
option=default; split=0; packages=''; packages_ref=''; keep=0
artifacts="$PWD/.integration-results"
usage() { echo 'Usage: run.sh [--option default|layout-builder|paragraphs|admin-only] [--config-split] [--packages PATH | --packages-ref SHA] [--artifacts DIR] [--keep]'; }
while [ "$#" -gt 0 ]; do
  case "$1" in
    --option|--packages|--packages-ref|--artifacts)
      [ "$#" -ge 2 ] || { usage >&2; exit 2; }
      case "$1" in
        --option) option=$2 ;; --packages) packages=$2 ;;
        --packages-ref) packages_ref=$2 ;; --artifacts) artifacts=$2 ;;
      esac
      shift 2 ;;
    --config-split) split=1; shift ;;
    --keep) keep=1; shift ;;
    --help|-h) usage; exit 0 ;;
    *) usage >&2; exit 2 ;;
  esac
done
case "$option" in
  default|layout-builder|paragraphs|admin-only) ;;
  *) usage >&2; exit 2 ;;
esac
[ -z "$packages" ] || [ -z "$packages_ref" ] || { usage >&2; exit 2; }
[[ -z "$packages_ref" || "$packages_ref" =~ ^[0-9a-f]{40}$ ]] || { echo 'Use a full packages commit SHA.' >&2; exit 2; }
if [ -n "$packages" ]; then packages=$(cd "$packages" && pwd -P); fi
mkdir -p "$artifacts"
artifacts=$(cd "$artifacts" && pwd -P)
work=$(mktemp -d "${TMPDIR:-/tmp}/sous-smoke.XXXXXX")
project=$(printf 'sous-smoke-%s' "${work##*.}" | tr '[:upper:]' '[:lower:]')
site="$work/$project"
result="$artifacts/result.json"
created=0
export HUSKY=0 SOUS_RUNTIME=ddev SOUS_ENVIRONMENT=local COMPOSER_NO_INTERACTION=1
jq -n --arg option "$option" --arg project "$project" --arg workspace "$work" --argjson split "$split" \
  '{status:"running",option:$option,config_split:($split==1),project:$project,workspace:$workspace,steps:[]}' > "$result"

finish() {
  status=$?
  trap - EXIT
  set +e
  if [ "$keep" -eq 0 ]; then
    if [ "$created" -eq 1 ]; then
      (cd "$site" && ddev delete -Oy "$project") > "$artifacts/cleanup.log" 2>&1 || status=1
    fi
    chmod -R u+w "$work" 2>/dev/null
    rm -rf "$work" || status=1
  else
    echo "Keeping $project in $site"
  fi
  outcome=pass; [ "$status" -eq 0 ] || outcome=fail
  jq --arg status "$outcome" --argjson code "$status" --argjson keep "$keep" \
    '.status=$status | .exit_code=$code | .kept=($keep==1)' "$result" > "$artifacts/result.tmp"
  mv "$artifacts/result.tmp" "$result"
  echo "Smoke test: $outcome ($artifacts)"
  exit "$status"
}
trap finish EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

step() {
  local name=$1 code=0
  shift
  echo "Smoke test: $name"
  "$@" > "$artifacts/$name.log" 2>&1 || code=$?
  jq --arg name "$name" --argjson code "$code" \
    '.steps += [{name:$name,exit_code:$code,log:($name+".log")}]' "$result" > "$artifacts/result.tmp"
  mv "$artifacts/result.tmp" "$result"
  if [ "$code" -ne 0 ]; then cat "$artifacts/$name.log" >&2; return "$code"; fi
}
copy_checkout() {
  local file
  while IFS= read -r -d '' file; do
    [ -e "$source_root/$file" ] || continue
    mkdir -p "$site/$(dirname "$file")" && cp -p "$source_root/$file" "$site/$file" || return
  done < <(git -C "$source_root" ls-files -z)
}
use_packages() {
  mkdir -p .smoke-packages && cp -R "$packages/packages/." .smoke-packages/ || return
  local versions
  versions=$(jq -s 'map({key:.name,value:"2.0.0"}) | from_entries' .smoke-packages/*/composer.json) || return
  jq --argjson versions "$versions" '.repositories = [{type:"path",url:".smoke-packages/*",canonical:true,only:["fourkitchens/sous-*"],options:{symlink:false,versions:$versions}}] + .repositories' composer.json > composer.tmp && mv composer.tmp composer.json
}
fresh() {
  local answer=no
  [ "$split" -eq 0 ] || answer=yes
  SOUS_CONTENT="$option" SOUS_CONFIG_SPLIT="$answer" sh devops/scripts/setup.sh fresh
}
bootstrapped() { ddev drush status --field=bootstrap | grep -Fx Successful; }
front_page() {
  local code
  code=$(curl --silent --show-error --output /dev/null --write-out '%{http_code}' "http://$project.ddev.site/") || return
  echo "Front page HTTP $code"
  [ "$code" = 200 ]
}
login_url() { ddev drush uli --name=sous_chef | grep -E '^https?://'; }
clean_config() { ddev drush config:status --format=json | jq -e 'length == 0'; }
restore() {
  local dumps=(reference/db-*.sql.gz)
  [ -f "${dumps[0]}" ] || { echo 'No database dump was created.' >&2; return 1; }
  sh devops/scripts/setup.sh restore "${dumps[0]}"
}

mkdir -p "$site"
step copy-checkout copy_checkout
cd "$site"
step name-project sh devops/scripts/sous-setup.sh
if [ -n "$packages_ref" ]; then
  step packages-clone git clone --quiet --no-checkout https://github.com/fourkitchens/sous-drupal-packages.git "$work/packages"
  step packages-checkout git -C "$work/packages" checkout --quiet --detach "$packages_ref"
  packages="$work/packages"
fi
if [ -n "$packages" ]; then step packages-path use_packages; fi
created=1
step ddev-config ddev config --project-name "$project"
step fresh-install fresh
step environment-config ddev npm run check:environment -- config
step environment-runtime ddev npm run check:environment -- runtime
step bootstrap bootstrapped
step front-page front_page
step login-url login_url
# PHP evaluates these variables in Drupal, not in the invoking shell.
# shellcheck disable=SC2016
step composition ddev exec env SOUS_SMOKE_OPTION="$option" SOUS_SMOKE_SPLIT="$split" vendor/bin/drush php:eval '
$option = getenv("SOUS_SMOKE_OPTION");
$page = \Drupal::entityTypeManager()->getStorage("node_type")->load("page");
$display = \Drupal::entityTypeManager()->getStorage("entity_view_display")->load("node.page.default");
$checks = [
  "Page" => (bool) $page === ($option !== "admin-only"),
  "Paragraphs" => \Drupal::moduleHandler()->moduleExists("paragraphs") === ($option === "paragraphs"),
  "Layout Builder" => (bool) $display?->getThirdPartySetting("layout_builder", "enabled", FALSE) === ($option === "layout-builder"),
  "Config Split" => \Drupal::moduleHandler()->moduleExists("config_split") === (getenv("SOUS_SMOKE_SPLIT") === "1"),
];
foreach ($checks as $name => $passed) { if (!$passed) { throw new \RuntimeException("Unexpected composition: $name"); } }
echo "Selected composition matches.\n";
'
step project-lint ddev npm run lint:project
step config-export ddev npm run confex
step config-import ddev npm run confim
step config-clean clean_config
if [ "$option" = default ]; then
  step database-backup ddev npm run local-data-bak
  step restore restore
  step restored-bootstrap bootstrapped
  step composer-audit ddev composer audit --abandoned=report
  step npm-audit ddev npm audit --omit=dev --audit-level=high
fi
