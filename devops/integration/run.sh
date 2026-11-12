#!/usr/bin/env bash
# Check a generated site and remove its temporary DDEV project on exit.
# Run: bash devops/integration/run.sh [OPTIONS].
set -euo pipefail

# Describe the smoke test and its options without creating a site.
usage() {
  cat <<'EOF'
Usage: run.sh [OPTIONS]

Export committed HEAD with git archive, run the real Composer create-project
with that template (version 8.0.0) in a temporary DDEV site, check the site,
and delete it on exit. Commit first: uncommitted changes are not tested.

Options:
  --option admin-only|paragraphs|layout-builder
                        Content composition (default: admin-only).
  --recipes LIST        Add comma-separated Sous recipe names (default: empty).
  --ci circleci|github|gitlab|none
                        Site CI provider (default: github).
  --library none|ui-kit|URL
                        Component library dessert (default: none).
  --library-ref REF     Library branch or tag (default: the installer's
                        UI Kit ref or the custom repository's default branch).
  --packages PATH       Use PATH/packages/* at version 2.0.0, copied by Composer
                        and mounted read-only in DDEV at the same absolute path
                        (default: use the published Composer package index).
  --artifacts DIR       Write logs and result.json here
                        (default: .integration-results in the current directory).
  --keep                Keep the DDEV site and workspace (default: delete both).
  -h, --help            Print this help and exit.

Checks, in order:
  1. Local development settings stay disabled outside DDEV.
  2. Drupal bootstraps successfully.
  3. The front page and selected recipe routes return HTTP 200.
  4. Content and recipes match the choices; local tools stay in their split;
     UID 1 stays blocked, sous_chef can
     sign in, and the default custom theme has tools. A chosen component library
     loads through Emulsify and has compiled assets; none leaves an empty scaffold.
  5. Signing in through the login form opens Site admin with an Editor tab.
  6. Maintainer tooling, one-time theme setup files and assets/ci are absent;
     site agent guidance has working links and survives a site archive.
     Only the selected provider's CI files remain.
     The generated release configuration loads in Node. Applied Sous recipes,
     their setup repository and the creation hook are retired.
  7. Composer reinstalls from the unchanged lock without the Sous repository;
     configuration and notices survive. Configuration scripts work through DDEV and native tools;
     Drush deploy succeeds and config:status reports no changes.
  8. Run Composer CI for admin-only or a chosen library. For admin-only without
     a library, build a project-owned Sass entrypoint after the empty build.
  9. For admin-only, back up, restore with npm run setup and reset with npm run
     rebuild, checking bootstrap.

The artifacts directory keeps each step's log, config-status.stderr,
cleanup.log (unless --keep), and result.json with status, site, option,
exit_code and kept. A failing step's log is printed before cleanup.
With --keep, use the site path in result.json to find PROJECT and WORKSPACE:
  ddev delete -Oy PROJECT
  chmod -R u+w WORKSPACE
  rm -rf WORKSPACE

Requirements: git, Composer and Node from .nvmrc on your computer;
DDEV with Docker running, jq and curl.
EOF
}

# Read options before creating a workspace or registering cleanup traps.
parse_options() {
  option=admin-only
  recipes=''
  ci=github
  library=none
  library_ref=''
  packages=''
  keep=0
  artifacts="$PWD/.integration-results"

  while [ "$#" -gt 0 ]; do
    case "$1" in
      --option|--recipes|--ci|--library|--library-ref|--packages|--artifacts)
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
      --recipes)
        recipes=$2
        shift 2
        ;;
      --ci)
        ci=$2
        shift 2
        ;;
      --library)
        library=$2
        shift 2
        ;;
      --library-ref)
        library_ref=$2
        shift 2
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
    admin-only|paragraphs|layout-builder) ;;
    *)
      usage >&2
      exit 2
      ;;
  esac

  case "$ci" in
    circleci|github|gitlab|none) ;;
    *)
      usage >&2
      exit 2
      ;;
  esac

  case "$library" in
    none|ui-kit|https://*.git|http://*.git|ssh://*.git|git://*.git|file://*.git|*@*:*.git) ;;
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
  work_dir=$(mktemp -d "${TMPDIR:-/tmp}/site-smoke.XXXXXX")
  # DDEV mounts must match the real path rather than a symlink to it.
  work_dir=$(cd "$work_dir" && pwd -P)
  project_name=$(printf 'site-smoke-%s' "${work_dir##*.}" | tr '[:upper:]' '[:lower:]')
  site_dir="$work_dir/$project_name"
  result_file="$artifacts/result.json"
  checks_dir=.sous-smoke-checks

  export HUSKY=0 COMPOSER_NO_INTERACTION=1 SOUS_CONTENT="$option"
  export SOUS_RECIPES="$recipes" SOUS_CI="$ci"
  export SOUS_LIBRARY="$library" SOUS_LIBRARY_REF="$library_ref"
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
    rm -rf "${site_dir:?}/${checks_dir:?}" "${work_dir:?}/template/${checks_dir:?}" || exit_code=1
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

# Point Composer and the index preflight at the same local packages.
use_local_packages() {
  local package_versions
  jq -s '
    {packages: (map({key: .name, value: {"2.0.0": (. + {version: "2.0.0"})}}) | from_entries)}
  ' "$packages"/packages/*/composer.json > "$work_dir/packages.json"
  package_versions=$(jq '.packages | map_values(keys[0])' "$work_dir/packages.json")
  jq --arg url "$packages/packages/*" --argjson versions "$package_versions" '
    .repositories.sous =
      {type: "path", url: $url, options: {symlink: false, versions: $versions}}
  ' "$work_dir/template/composer.json" > "$work_dir/composer.json"
  mv "$work_dir/composer.json" "$work_dir/template/composer.json"
  export SOUS_INDEX="$work_dir/packages.json"
  jq -n --arg path "$packages" '
    {services: {web: {volumes: [
      {type: "bind", source: $path, target: $path, read_only: true}
    ]}}}
  ' > "$work_dir/template/.ddev/docker-compose.recipes.yaml"
}

# Run the installer in an empty directory to catch setup before refusal.
check_admin_rejects_content_list() {
  local status=0 reject_dir="$work_dir/rejected/$project_name"
  mkdir -p "$reject_dir"
  step reject-content-list env -C "$reject_dir" SOUS_CONTENT=admin-only \
    SOUS_RECIPES=sous-content-list sh "$work_dir/template/devops/scripts/sous-build.sh" </dev/null || status=$?
  test "$status" -eq 2
  expect_line reject-content-list 'Recipe sous-content-list requires a compatible main course selected with SOUS_CONTENT.'
  test -z "$(ls -A "$reject_dir")"
}

# Check that a missing library ref stops before setup creates files.
check_rejects_missing_library_ref() {
  local status=0 reject_dir="$work_dir/rejected-library/$project_name"
  mkdir -p "$reject_dir"
  step reject-library-ref env -C "$reject_dir" SOUS_CONTENT=admin-only SOUS_RECIPES='' SOUS_CI=none \
    SOUS_LIBRARY=ui-kit SOUS_LIBRARY_REF=sous-missing-ref \
    sh "$work_dir/template/devops/scripts/sous-build.sh" </dev/null || status=$?
  test "$status" -eq 2
  expect_line reject-library-ref 'Cannot resolve component library ref "sous-missing-ref" in https://github.com/emulsify-ds/emulsify-ui-kit.git.'
  expect_line reject-library-ref 'Set SOUS_LIBRARY_REF to an existing branch or tag.'
  test -z "$(ls -A "$reject_dir")"
}

# Run the real project installer against the exported template, then enter the site.
create_site() {
  step create-project composer create-project fourkitchens/sous-drupal-project \
    "$site_dir" \
    --repository='{"type":"path","url":"'"$work_dir/template"'","options":{"symlink":false,"versions":{"fourkitchens/sous-drupal-project":"8.0.0"}}}'
  cd "$site_dir"
}

# Check that a stray local settings file cannot enable development settings on a host.
check_host_settings() {
  step host-settings ddev exec --raw env -u IS_DDEV_PROJECT php "/var/www/html/$checks_dir/host-settings.php"
}

# Check Drupal bootstrap and the public routes for the selected recipes.
check_site_responds() {
  local http_url selected
  step bootstrap ddev drush status --field=bootstrap
  expect_line bootstrap Successful
  http_url=$(ddev describe -j | jq -er '.raw.httpurl')
  step front-page curl --silent --show-error --output /dev/null \
    --write-out '%{http_code}\n' "${http_url%/}/"
  expect_line front-page 200
  selected=",${recipes//[[:space:]]/,},"
  if [[ "$selected" == *,sous-alerts,* ]]; then
    step alerts-load curl --silent --show-error --output /dev/null \
      --write-out '%{http_code}\n' "${http_url%/}/sitewide_alert/load"
    expect_line alerts-load 200
  fi
  step sitemap-generate ddev drush simple-sitemap:generate
  step sitemap curl --silent --show-error --output /dev/null \
    --write-out '%{http_code}\n' "${http_url%/}/sitemap.xml"
  expect_line sitemap 200
}

# Check content, administrator accounts and the default custom theme in Drupal.
check_composition() {
  local chef_password
  local theme
  chef_password=$(awk '$1 == "|" && $2 == "Password" {print $3}' "$artifacts/create-project.log")
  theme=$(printf '%s' "$project_name" | tr -d '-')
  step composition ddev exec --raw env SOUS_SMOKE_OPTION="$option" \
    SOUS_SMOKE_RECIPES="$recipes" SOUS_SMOKE_THEME="$theme" \
    SOUS_SMOKE_PASSWORD="$chef_password" vendor/bin/drush php:script "/var/www/html/$checks_dir/composition.php"
}

# Sign in through the real form; one-time login links skip Dashboard's redirect.
check_dashboard_login() {
  local http_url chef_password form_build_id login_url
  http_url=$(ddev describe -j | jq -er '.raw.httpurl')
  chef_password=$(awk '$1 == "|" && $2 == "Password" {print $3}' "$artifacts/create-project.log")
  step dashboard-login-form curl --silent --show-error --fail --location \
    --cookie-jar "$work_dir/dashboard.cookies" --output "$work_dir/login.html" \
    "${http_url%/}/user/login"
  form_build_id=$(sed -n 's/.*name="form_build_id" value="\([^"]*\)".*/\1/p' "$work_dir/login.html")
  test -n "$form_build_id"
  step dashboard-login curl --silent --show-error --location \
    --cookie "$work_dir/dashboard.cookies" --cookie-jar "$work_dir/dashboard.cookies" \
    --data-urlencode name=sous_chef --data-urlencode pass@- \
    --data-urlencode "form_build_id=$form_build_id" --data-urlencode form_id=user_login_form \
    --data-urlencode 'op=Log in' --output "$work_dir/dashboard.html" \
    --write-out '%{http_code}\n%{url_effective}\n' "${http_url%/}/user/login" \
    < <(printf '%s' "$chef_password")
  expect_line dashboard-login 200
  login_url=$(tail -n 1 "$artifacts/dashboard-login.log")
  test "${login_url%%\?*}" = "${http_url%/}/admin/dashboard"
  step dashboard-site-admin grep -Fq 'Site admin' "$work_dir/dashboard.html"
  # BigPipe may stream the tabs as an escaped HTML fragment.
  step dashboard-editor-tab grep -Fq -e 'href="/admin/dashboard/editor"' \
    -e 'href=\u0022\/admin\/dashboard\/editor\u0022' "$work_dir/dashboard.html"
}

# Load chosen libraries through the real CLI and require actual compiled output.
check_component_library() {
  local theme_path
  local library_label
  theme_path="web/themes/custom/$(printf '%s' "$project_name" | tr -d '-')"
  case "$library" in
    none) library_label=None ;;
    ui-kit) library_label='Emulsify UI Kit' ;;
    *) library_label="Custom: $library" ;;
  esac
  step theme-tools test -d "$theme_path/node_modules/@emulsify/core"
  if [ "$library" = none ]; then
    step absent-component-library test ! -d "$theme_path/src/components"
    step absent-initial-theme-build test ! -d "$theme_path/dist"
  else
    # The CLI validates the installed repository, checkout and platform variant.
    # shellcheck disable=SC2016
    step component-library bash -e -c 'cd "$1"
      "$2" component list' \
      bash "$theme_path" "$PWD/node_modules/.bin/emulsify"
    step theme-assets find "$theme_path/dist" -type f -size +0c
    test -s "$artifacts/theme-assets.log"
  fi
  # Compare only boxed receipt rows so tool output cannot count as an order.
  # shellcheck disable=SC2016
  step dessert-order env SOUS_SMOKE_LIBRARY_LABEL="$library_label" awk '
    BEGIN { expected = ENVIRON["SOUS_SMOKE_LIBRARY_LABEL"] }
    $1 == "|" {
      line = $0
      sub(/^ *[|] */, "", line)
      sub(/ *[|] *$/, "", line)
      if (line ~ /^[0-9]+ +DESSERT$/) { dessert = 1; next }
      if (dessert) { prep += line == expected; dessert = 0 }
      if (line ~ /^Dessert +/) {
        sub(/^Dessert +/, "", line)
        pickup += line == expected
      }
    }
    END {
      printf "Prep ticket: %d matching dessert\nPickup ticket: %d matching dessert\n", prep, pickup
      exit(prep != 1 || pickup != 1)
    }
  ' "$artifacts/create-project.log"
  # Both receipts list each included package on its own line.
  # shellcheck disable=SC2016
  step pantry-order awk -v option="$option" '
    BEGIN {
      count = split("Emulsify,Gin administration,Media,Text formats,Dashboards,Config Split,publishing workflow,Dev Tools,SEO", labels, ",")
      if (option != "admin-only") {
        labels[++count] = "Content List"
        labels[++count] = "Gallery"
      }
    }
    $1 == "|" {
      line = $0
      sub(/^ *[|] */, "", line)
      sub(/ *[|] *$/, "", line)
      if (line == "PANTRY STAPLES (INCLUDED)") { pantry = 1; row = 0; next }
      if (!pantry) next
      if (line == "" && row == count) { done++; pantry = 0; next }
      if (line != "+ " labels[++row]) exit 1
    }
    END { if (done != 2) exit 1 }
  ' "$artifacts/create-project.log"
}

# Check that sites omit maintainer tooling and one-time theme setup files.
check_product_files_absent() {
  local path
  for path in devops/integration docs/maintainers assets/installer devops/scripts/sous-build.sh \
    devops/scripts/theme-setup.sh devops/scripts/theme-initialize.mjs; do
    step "absent-${path//\//-}" test ! -e "$path"
  done
}

# Check the installed site's guidance, navigation and archive contents.
check_agent_guidance() {
  local facts=docs/agents/workflow-facts.md
  local theme
  theme=$(printf '%s' "$project_name" | tr -d '-')
  step agent-heading grep -Fx "# $project_name agent instructions" AGENTS.md
  step agent-claude grep -Fx '@AGENTS.md' CLAUDE.md
  step facts-heading grep -Fx '# Workflow facts' "$facts"
  step facts-sections grep '^## ' "$facts"
  step agent-theme-path grep -F '`web/themes/custom/'"$theme"'`' AGENTS.md
  step agent-workflow-pointer grep -Fx -- \
    '- Workflow facts: Read [the workflow facts](docs/agents/workflow-facts.md) before debugging, implementing, reviewing, or deploying a change.' AGENTS.md
  step agent-site-uri ddev drush status --field=uri
  step agent-no-trailing-whitespace bash -c '! grep -nE " +$" "$@"' bash AGENTS.md CLAUDE.md "$facts"

  # Resolve the relative file links and Markdown heading anchors used here.
  # shellcheck disable=SC2016
  step agent-links env SOUS_SMOKE_URI="$(cat "$artifacts/agent-site-uri.log")" node -e '
    const fs = require("node:fs");
    const { pathToFileURL } = require("node:url");
    const documents = process.argv.slice(1);
    for (const document of documents) {
      const contents = fs.readFileSync(document, "utf8");
      if (/\b(?:PROJECT_NAME|PROJECT_THEME|LOCAL_URL|LOCAL_DATABASE|INSTALL_DATE|CI_FACTS)\b|^# Sous coding agent brief$/m.test(contents)) {
        throw new Error(`Unrendered starter guidance in ${document}`);
      }
      const base = pathToFileURL(document);
      for (const [, link] of contents.matchAll(/\]\(([^)\s]+)\)/g)) {
        if (link.startsWith("//")) continue;
        const target = new URL(link, base);
        if (target.protocol !== "file:") continue;
        fs.accessSync(target);
        if (target.hash) {
          const anchor = decodeURIComponent(target.hash.slice(1));
          const headings = fs.readFileSync(target, "utf8").matchAll(/^#{1,6} +(.+)$/gm);
          const found = [...headings].some(([, title]) =>
            title.trim().toLowerCase().replace(/[^\w -]/g, "").replace(/ /g, "-") === anchor);
          if (!found) {
            throw new Error(`${document}: missing heading for ${link}`);
          }
        }
      }
    }
    const sections = fs.readFileSync(documents[1], "utf8").split(/^## /m).slice(1);
    const expected = ["Sites", "Database refresh", "Test roles", "Public files", "Browser testing", "Git workflow", "Review and deploy"];
    if (JSON.stringify(sections.map(section => section.split("\n")[0])) !== JSON.stringify(expected)) {
      throw new Error("Workflow facts must contain the required sections in order");
    }
    if (sections.some(section => !section.includes("(source:"))) {
      throw new Error("Every workflow facts section must cite its source");
    }
    if (!sections[0].includes(`| default | ${process.env.SOUS_SMOKE_URI} |`)) {
      throw new Error("Workflow facts Sites URL does not match Drush");
    }
  ' AGENTS.md "$facts" CLAUDE.md README.md

  # Archive a staged tree to check export rules without committing to the site.
  # shellcheck disable=SC2016
  step agent-archive env GIT_INDEX_FILE="$work_dir/agent-archive.index" bash -e -o pipefail -c '
    git add -- .gitattributes "$@"
    git archive "$(git write-tree)" | tar -tf -
  ' bash AGENTS.md CLAUDE.md "$facts"
  expect_line agent-archive AGENTS.md
  expect_line agent-archive CLAUDE.md
  expect_line agent-archive "$facts"
}

# Check that only the chosen CI files remain after the installer removes its templates.
check_generated_files() {
  step release-config node -e "require('./release.config.js')"
  if [ "$option" = paragraphs ]; then
    step recipe-notice cat NOTICE
    expect_line recipe-notice 'MIT License'
    expect_line recipe-notice 'Copyright (c) 2023 Four Kitchens'
  fi
  step absent-assets-ci test ! -e assets/ci
  step scaffold-assets test -d assets/scaffold
  step reusable-recipe-command test -f devops/scripts/install-recipe.sh

  if [ "$ci" = github ]; then
    step ci-github-workflow test -f .github/workflows/build.yml
    step ci-github-files ls -A1 .github/workflows
    test "$(cat "$artifacts/ci-github-files.log")" = build.yml
  else
    step absent-github-workflows test ! -e .github/workflows
  fi

  if [ "$ci" = gitlab ]; then
    step ci-gitlab test -f .gitlab-ci.yml
    step ci-gitlab-template test -f .gitlab/merge_request_templates/Default.md
    step absent-github test ! -e .github
  else
    step absent-gitlab-ci test ! -e .gitlab-ci.yml
    step absent-gitlab-metadata test ! -e .gitlab
  fi

  if [ "$ci" = circleci ]; then
    step ci-circleci test -f .circleci/config.yml
  else
    step absent-circleci test ! -e .circleci
  fi
}

# Reinstall only Composer-managed dependencies and keep project files in place.
check_lock_reinstall() {
  step composer-repositories ddev composer repo list --no-ansi
  if grep -E '^\[(sous|dropzone)\]' "$artifacts/composer-repositories.log"; then return 1; fi
  step composer-scripts ddev composer run-script --list --no-ansi
  if grep -E '^[[:space:]]*post-create-project-cmd([[:space:]]|$)' "$artifacts/composer-scripts.log"; then return 1; fi

  if [ -n "$packages" ]; then
    step remove-package-mount rm .ddev/docker-compose.recipes.yaml
    step restart-without-packages ddev restart
    step absent-package-mount ddev exec --raw -- test ! -e "$packages/packages"
  fi
  step save-lock cp composer.lock "$work_dir/reinstall.lock"
  step save-config cp -R config "$work_dir/reinstall-config"
  if [ -f NOTICE ]; then step save-notice cp NOTICE "$work_dir/reinstall-notice"; fi
  # Remove files where Composer runs so Mutagen cannot race the reinstall.
  step remove-dependencies ddev exec --raw -- rm -rf vendor web/core web/modules/contrib web/themes/contrib \
    web/profiles/contrib web/libraries drush/contrib
  step reinstall ddev composer install --no-interaction
  step lock-preserved cmp "$work_dir/reinstall.lock" composer.lock
  step config-preserved diff -r "$work_dir/reinstall-config" config
  if [ -f "$work_dir/reinstall-notice" ]; then
    step notice-preserved cmp "$work_dir/reinstall-notice" NOTICE
  fi

  # Let the child shell expand the recipe paths in the generated checkout.
  # shellcheck disable=SC2016
  step absent-recipe-dirs bash -ec 'for recipe in web/recipes/sous-*; do test ! -e "$recipe"; done'
  step absent-dropzone-library test ! -e web/libraries/dropzone
  step locked-packages ddev composer show --locked --format=json --no-ansi
  # shellcheck disable=SC2016
  step locked-selection jq -e '
    (.locked | map(.name)) as $names
    | all($names[]; (test("^(fourkitchens/sous-|drupal/dropzonejs$|dropzone/dropzone$)") | not))
      and all(["drupal/devel", "drupal/masquerade", "drupal/stage_file_proxy"][];
        . as $name | $names | index($name) != null)
  ' "$artifacts/locked-packages.log"
}

# Deploy the site and require no remaining configuration changes.
check_clean_deploy() {
  step deploy npm run confim -- @self
  step native-deploy ddev exec --raw sh devops/scripts/confim.sh @self
  # Check the preserved export before exercising either export entry point.
  # shellcheck disable=SC2016
  step config-status bash -c \
    'ddev drush config:status --format=json 2> "$1"' \
    bash "$artifacts/config-status.stderr"
  jq -e 'length == 0' "$artifacts/config-status.log"
  step config-export npm run confex -- @self
  step native-config-export ddev exec --raw sh devops/scripts/confex.sh @self
  # CI can select its own PHP even in a configured DDEV checkout.
  step explicit-native-php env -u IS_DDEV_PROJECT SOUS_PHP_RUNTIME=native \
    sh devops/scripts/run-php.sh php -r 'echo getcwd(), PHP_EOL;'
  expect_line explicit-native-php "$(pwd -P)"
  # Exclude DDEV's container placeholder to exercise a host without DDEV.
  # shellcheck disable=SC2016
  step native-php ddev exec --raw env -u IS_DDEV_PROJECT PATH=/usr/bin:/bin \
    sh devops/scripts/run-php.sh php -r \
    'if ($argv[1] !== "argument with spaces") { exit(1); } echo "Native PHP works.\n";' \
    'argument with spaces'
  expect_line native-php 'Native PHP works.'
}

# Exercise backup, fresh-clone setup and quick branch rebuild, checking each reset.
check_backup_restore() {
  step backup npm run local-data-bak
  local database_dumps=(reference/db-*.sql.gz)
  step restore npm run setup -- "${database_dumps[0]}"
  step restored-bootstrap ddev drush status --field=bootstrap
  expect_line restored-bootstrap Successful
  step rebuild npm run rebuild -- "${database_dumps[0]}"
  step rebuilt-bootstrap ddev drush status --field=bootstrap
  expect_line rebuilt-bootstrap Successful
}

# Build a site-owned entrypoint on the scaffold without installing a library.
check_project_owned_asset() {
  local theme_path source
  theme_path="web/themes/custom/$(printf '%s' "$project_name" | tr -d '-')"
  source="$theme_path/src/base/site-smoke.scss"
  step empty-theme-ci test ! -d "$theme_path/dist"
  mkdir -p "$theme_path/src/base"
  cat > "$source" <<'SCSS'
$smoke-color: red;
.sous-smoke { color: $smoke-color; }
SCSS
  step project-theme-build npm run theme-build
  step project-theme-asset grep -F '.sous-smoke' "$theme_path/dist/global/base/site-smoke.css"
  rm "$source"
  rm -r "$theme_path/dist"
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
  step absent-starter-agent-brief test ! -e "$work_dir/template/AGENTS.md"
  if [ -n "$packages" ]; then
    use_local_packages
    if [ "$option" = admin-only ] && [ -f "$packages/packages/sous-content-list/composer.json" ]; then
      check_admin_rejects_content_list
      check_rejects_missing_library_ref
    fi
  fi
  # Let Composer copy the checks into the site before DDEV starts syncing it.
  step copy-checks cp -R "$source_root/devops/integration/checks" "$work_dir/template/$checks_dir"
  create_site
  check_host_settings
  check_site_responds
  check_composition
  check_dashboard_login
  step remove-checks rm -r "$checks_dir" "$work_dir/template/$checks_dir"
  check_component_library
  check_product_files_absent
  check_agent_guidance
  check_generated_files
  check_lock_reinstall
  check_clean_deploy
  # Chosen libraries must pass the same lint/build command as a site's own CI.
  if [ "$option" = admin-only ] || [ "$library" != none ]; then
    step ci composer ci
  fi
  if [ "$option" = admin-only ] && [ "$library" = none ]; then
    check_project_owned_asset
  fi
  # Resets are the same for every composition, so exercise them for admin-only.
  if [ "$option" = admin-only ]; then
    check_backup_restore
  fi
}

main "$@"
