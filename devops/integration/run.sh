#!/usr/bin/env bash
set -euo pipefail
source_root="$(CDPATH='' cd "$(dirname "$0")/../.." && pwd -P)"
option=default; split=0; packages=''; keep=0; artifacts="$PWD/.integration-results"
usage() { echo 'Usage: run.sh [--option default|layout-builder|paragraphs|admin-only] [--config-split] [--packages PATH] [--artifacts DIR] [--keep]'; }
while [ "$#" -gt 0 ]; do
  case "$1" in --option|--packages|--artifacts) [ "$#" -ge 2 ] || { usage >&2; exit 2; } ;; esac
  case "$1" in
    --option) option=$2; shift 2 ;;
    --config-split) split=1; shift ;;
    --packages) packages=$(cd "$2" && pwd -P); shift 2 ;;
    --artifacts) artifacts=$2; shift 2 ;;
    --keep) keep=1; shift ;;
    --help|-h) usage; exit 0 ;;
    *) usage >&2; exit 2 ;;
  esac
done
case "$option" in default|layout-builder|paragraphs|admin-only) ;; *) usage >&2; exit 2 ;; esac
mkdir -p "$artifacts"
artifacts=$(cd "$artifacts" && pwd -P)
work=$(mktemp -d "${TMPDIR:-/tmp}/sous-smoke.XXXXXX"); work=$(cd "$work" && pwd -P)
project=$(printf 'sous-smoke-%s' "${work##*.}" | tr '[:upper:]' '[:lower:]'); site="$work/$project"
result="$artifacts/result.json"; log="$artifacts/export.log"
export HUSKY=0 COMPOSER_NO_INTERACTION=1 SOUS_CONTENT="$option" SOUS_CONFIG_SPLIT=no
[ "$split" -eq 0 ] || export SOUS_CONFIG_SPLIT=yes
jq -n --arg site "$site" --arg option "$option" '{status:"running",site:$site,option:$option}' > "$result"
# Record the outcome on exit; --keep retains the site for inspection.
finish() {
  status=$?; trap - EXIT; set +e
  [ "$status" -eq 0 ] || cat "$log"
  if [ "$keep" -eq 0 ]; then
    ddev delete -Oy "$project" > "$artifacts/cleanup.log" 2>&1 || status=1
    chmod -R u+w "$work"; rm -rf "$work" || status=1
  else echo "Keeping $site"; fi
  jq --argjson code "$status" --argjson keep "$keep" '.status=(if $code==0 then "pass" else "fail" end)|.exit_code=$code|.kept=($keep==1)' "$result" > "$result.tmp"
  mv "$result.tmp" "$result"; echo "Smoke test: $(jq -r .status "$result") ($artifacts)"
  exit "$status"
}
trap finish EXIT
trap 'exit 130' INT; trap 'exit 143' TERM
# Log commands without catching failures so the first error reaches the exit trap.
run() { local name=$1; shift; log="$artifacts/$name.log"; echo "Smoke test: $name"; "$@" > "$log" 2>&1; }
mkdir -p "$work/template"
# Test committed source with the same distribution exclusions as a downloaded project.
git -C "$source_root" archive HEAD | tar -x -C "$work/template" 2> "$log"
if [ -n "$packages" ]; then
  versions=$(jq -s 'map({key:.name,value:"2.0.0"})|from_entries' "$packages"/packages/*/composer.json)
  jq --arg url "$packages/packages/*" --argjson versions "$versions" '.repositories=[{type:"path",url:$url,options:{symlink:false,versions:$versions}}]+[.repositories[]|select(.url!="https://fourkitchens.github.io/sous-drupal-packages/")]' "$work/template/composer.json" > "$work/composer.json"
  mv "$work/composer.json" "$work/template/composer.json"
  # Host Composer and DDEV must be able to read the same local recipe path.
  jq -n --arg path "$packages" '{services:{web:{volumes:[{type:"bind",source:$path,target:$path,read_only:true}]}}}' > "$work/template/.ddev/docker-compose.recipes.yaml"
fi
run create-project composer create-project fourkitchens/sous-drupal-project "$site" --repository='{"type":"path","url":"'"$work/template"'","options":{"symlink":false,"versions":{"fourkitchens/sous-drupal-project":"8.0.0"}}}'
cd "$site"
run bootstrap ddev drush status --field=bootstrap
grep -Fx Successful "$log"
run front-page curl --silent --show-error --output /dev/null --write-out '%{http_code}\n' "http://$project.ddev.site/"
grep -Fx 200 "$log"
# PHP evaluates these variables in Drupal, not in the invoking shell.
# shellcheck disable=SC2016
run composition ddev exec --raw env SOUS_SMOKE_OPTION="$option" SOUS_SMOKE_SPLIT="$split" vendor/bin/drush php:eval '
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
for path in devops/integration docs/maintainers .github/workflows; do
  run "absent-${path//\//-}" test ! -e "$path"
done
run deploy ddev drush deploy --yes
log="$artifacts/config-status.log"
# Keep Drush notices out of the JSON used to check for configuration changes.
ddev drush config:status --format=json > "$log" 2> "$artifacts/config-status.stderr"
jq -e 'length == 0' "$log"
# Restore is shared by all compositions, so exercise it once with the default site.
if [ "$option" = default ]; then
  run backup ddev npm run local-data-bak
  dumps=(reference/db-*.sql.gz)
  run restore sh devops/scripts/setup.sh "${dumps[0]}"
  run restored-bootstrap ddev drush status --field=bootstrap
  grep -Fx Successful "$log"
fi
