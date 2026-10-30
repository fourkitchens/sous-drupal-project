#!/usr/bin/env bash
set -euo pipefail

# Describe the smoke test and its options without creating a site.
usage() {
  cat <<'EOF'
Usage: run.sh [OPTIONS]

Export committed HEAD with git archive, run the real Composer create-project
with that template (version 8.0.0) in a temporary DDEV site, check the site,
and delete it on exit. Commit first: uncommitted changes are not tested.

Options:
  --option default|layout-builder|paragraphs|admin-only
                        Content composition (default: default).
  --config-split        Include Config Split (default: off).
  --packages PATH       Use PATH/packages/* at version 2.0.0, copied by Composer
                        and mounted read-only in DDEV at the same absolute path
                        (default: use the published Composer package index).
  --artifacts DIR       Write logs and result.json here
                        (default: .integration-results in the current directory).
  --keep                Keep the DDEV site and workspace (default: delete both).
  -h, --help            Print this help and exit.

Checks, in order:
  1. Drupal bootstraps successfully.
  2. The front page returns HTTP 200.
  3. Page, Paragraphs, Layout Builder and Config Split match the chosen options.
  4. Product-only devops/integration, docs/maintainers and .github/workflows
     are absent from the generated site.
  5. Drush deploy succeeds and config:status reports no changes.
  6. For the default option, back up and restore the database, then bootstrap.

The artifacts directory keeps each step's log, config-status.stderr,
cleanup.log (unless --keep), and result.json with status, site, option,
exit_code and kept. A failing step's log is printed before cleanup.
With --keep, use the site path in result.json to find PROJECT and WORKSPACE:
  ddev delete -Oy PROJECT
  chmod -R u+w WORKSPACE
  rm -rf WORKSPACE

Requirements: git, Composer, DDEV with Docker running, jq and curl.
EOF
}

# Read options before creating a workspace or registering cleanup traps.
parse_options() {
  option=default
  split=0
  packages=''
  keep=0
  artifacts="$PWD/.integration-results"

  while [ "$#" -gt 0 ]; do
    case "$1" in
      --option|--packages|--artifacts)
        if [ "$#" -lt 2 ]; then
          usage >&2
          exit 2
        fi
        ;;
    esac

    case "$1" in
      --option)
        option=$2
        shift 2
        ;;
      --config-split)
        split=1
        shift
        ;;
      --packages)
        packages=$(cd "$2" && pwd -P)
        shift 2
        ;;
      --artifacts)
        artifacts=$2
        shift 2
        ;;
      --keep)
        keep=1
        shift
        ;;
      --help|-h)
        usage
        exit 0
        ;;
      *)
        usage >&2
        exit 2
        ;;
    esac
  done

  case "$option" in
    default|layout-builder|paragraphs|admin-only) ;;
    *)
      usage >&2
      exit 2
      ;;
  esac
}

# Create the temporary site workspace and record the running result.
prepare_workspace() {
  source_root="$(CDPATH='' cd "$(dirname "$0")/../.." && pwd -P)"
  mkdir -p "$artifacts"
  artifacts=$(cd "$artifacts" && pwd -P)
  work_dir=$(mktemp -d "${TMPDIR:-/tmp}/sous-smoke.XXXXXX")
  # DDEV mounts must match the real path rather than a symlink to it.
  work_dir=$(cd "$work_dir" && pwd -P)
  project_name=$(printf 'sous-smoke-%s' "${work_dir##*.}" | tr '[:upper:]' '[:lower:]')
  site_dir="$work_dir/$project_name"
  result_file="$artifacts/result.json"

  export HUSKY=0 COMPOSER_NO_INTERACTION=1 SOUS_CONTENT="$option" SOUS_CONFIG_SPLIT=no
  if [ "$split" -ne 0 ]; then
    export SOUS_CONFIG_SPLIT=yes
  fi
  jq -n --arg site "$site_dir" --arg option "$option" \
    '{status: "running", site: $site, option: $option}' > "$result_file"
}

# Record the exit status and remove the site unless --keep retains it for inspection.
finish() {
  local exit_code=$?
  trap - EXIT
  set +e

  if [ "$exit_code" -ne 0 ] && [ -n "${current_log:-}" ]; then
    cat "$current_log"
  fi
  if [ "$keep" -eq 0 ]; then
    ddev delete -Oy "$project_name" > "$artifacts/cleanup.log" 2>&1 || exit_code=1
    # Drupal makes sites/default read-only, so allow removal before deleting it.
    chmod -R u+w "$work_dir"
    rm -rf "$work_dir" || exit_code=1
  else
    echo "Keeping $site_dir"
  fi

  jq --argjson code "$exit_code" --argjson keep "$keep" '
    .status = (if $code == 0 then "pass" else "fail" end)
    | .exit_code = $code
    | .kept = ($keep == 1)
  ' "$result_file" > "$result_file.tmp"
  mv "$result_file.tmp" "$result_file"
  echo "Smoke test: $(jq -r .status "$result_file") ($artifacts)"
  exit "$exit_code"
}

# Log one command and remember its log so the EXIT trap can show a failure.
step() {
  local name=$1
  shift
  current_log="$artifacts/$name.log"
  echo "Smoke test: $name"
  "$@" > "$current_log" 2>&1
}

# Require an exact output line from the named step without changing the failing log.
expect_line() {
  local name=$1
  local expected_text=$2
  grep -Fx "$expected_text" "$artifacts/$name.log"
}

# Export committed source with the same exclusions as a downloaded project.
export_template() {
  mkdir -p "$work_dir/template"
  # The child Bash expands the positional arguments and checks both pipeline commands.
  # shellcheck disable=SC2016
  step export bash -o pipefail -c \
    'git -C "$1" archive HEAD | tar -x -C "$2"' \
    bash "$source_root" "$work_dir/template"
}

# Replace the published index with copied local packages visible to Composer and DDEV.
use_local_packages() {
  local package_versions
  package_versions=$(jq -s '
    map({key: .name, value: "2.0.0"}) | from_entries
  ' "$packages"/packages/*/composer.json)
  jq --arg url "$packages/packages/*" --argjson versions "$package_versions" '
    .repositories = [
      {type: "path", url: $url, options: {symlink: false, versions: $versions}}
    ] + [
      .repositories[]
      | select(.url != "https://fourkitchens.github.io/sous-drupal-packages/")
    ]
  ' "$work_dir/template/composer.json" > "$work_dir/composer.json"
  mv "$work_dir/composer.json" "$work_dir/template/composer.json"
  jq -n --arg path "$packages" '
    {services: {web: {volumes: [
      {type: "bind", source: $path, target: $path, read_only: true}
    ]}}}
  ' > "$work_dir/template/.ddev/docker-compose.recipes.yaml"
}

# Run the real project installer against the exported template, then enter the site.
create_site() {
  step create-project composer create-project fourkitchens/sous-drupal-project \
    "$site_dir" \
    --repository='{"type":"path","url":"'"$work_dir/template"'","options":{"symlink":false,"versions":{"fourkitchens/sous-drupal-project":"8.0.0"}}}'
  cd "$site_dir"
}

# Check Drupal bootstrap before requesting the site's front page.
check_site_responds() {
  step bootstrap ddev drush status --field=bootstrap
  expect_line bootstrap Successful
  step front-page curl --silent --show-error --output /dev/null \
    --write-out '%{http_code}\n' "http://$project_name.ddev.site/"
  expect_line front-page 200
}

# Check the selected content composition and independent Config Split option in Drupal.
check_composition() {
  local composition_checks
  composition_checks=$(cat <<'PHP'
$option = getenv("SOUS_SMOKE_OPTION");
$page = \Drupal::entityTypeManager()->getStorage("node_type")->load("page");
$display = \Drupal::entityTypeManager()->getStorage("entity_view_display")->load("node.page.default");
$checks = [
  // Page is included in every composition except admin-only.
  "Page" => (bool) $page === ($option !== "admin-only"),
  // Paragraphs is enabled only for its content composition.
  "Paragraphs" => \Drupal::moduleHandler()->moduleExists("paragraphs") === ($option === "paragraphs"),
  // Layout Builder is enabled on the Page display only when selected.
  "Layout Builder" => (bool) $display?->getThirdPartySetting("layout_builder", "enabled", FALSE) === ($option === "layout-builder"),
  // Config Split follows its own flag rather than the content composition.
  "Config Split" => \Drupal::moduleHandler()->moduleExists("config_split") === (getenv("SOUS_SMOKE_SPLIT") === "1"),
];
foreach ($checks as $name => $passed) {
  if (!$passed) {
    throw new \RuntimeException("Unexpected composition: $name");
  }
}
echo "Selected composition matches.\n";
PHP
  )
  step composition ddev exec --raw env SOUS_SMOKE_OPTION="$option" \
    SOUS_SMOKE_SPLIT="$split" vendor/bin/drush php:eval "$composition_checks"
}

# Check that archive exclusions kept maintainer tooling out of the generated site.
check_product_files_absent() {
  local path
  for path in devops/integration docs/maintainers .github/workflows; do
    step "absent-${path//\//-}" test ! -e "$path"
  done
}

# Deploy the site and require no remaining configuration changes.
check_clean_deploy() {
  step deploy ddev drush deploy --yes
  # Keep Drush notices in their own stderr file so config-status.log parses as JSON.
  # shellcheck disable=SC2016
  step config-status bash -c \
    'ddev drush config:status --format=json 2> "$1"' \
    bash "$artifacts/config-status.stderr"
  jq -e 'length == 0' "$artifacts/config-status.log"
}

# Back up and restore the database, then confirm Drupal still bootstraps.
check_backup_restore() {
  step backup ddev npm run local-data-bak
  local database_dumps=(reference/db-*.sql.gz)
  step restore sh devops/scripts/setup.sh "${database_dumps[0]}"
  step restored-bootstrap ddev drush status --field=bootstrap
  expect_line restored-bootstrap Successful
}

# Run each stage as a plain command so set -e stops at the first failure.
main() {
  parse_options "$@"
  prepare_workspace
  trap finish EXIT
  # Exit on signals so the EXIT trap records their status and cleans up.
  trap 'exit 130' INT
  trap 'exit 143' TERM

  export_template
  if [ -n "$packages" ]; then
    use_local_packages
  fi
  create_site
  check_site_responds
  check_composition
  check_product_files_absent
  check_clean_deploy
  # Restore is the same for every composition, so exercise it only for default.
  if [ "$option" = default ]; then
    check_backup_restore
  fi
}

main "$@"
