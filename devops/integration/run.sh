#!/usr/bin/env bash

# Check the starter against independently installed recipe ZIPs in one owned DDEV site.
# Host tools: Bash, Git, jq, curl, realpath and shasum; PHP/Node run in DDEV.
# Usage: bash devops/integration/run.sh [--sources PARENT] [--subject CHECKOUT]
#   [--subject-repository OWNER/REPO] [--packages CHECKOUT|--packages-ref SHA] [--artifacts DIRECTORY]
#   [--option all|default|paragraphs|layout-builder|admin-only] [--keep]
# --keep is for local diagnosis only. CI always removes its owned fixture.
# jq expands its --arg variables inside the literal filter strings below.
# shellcheck disable=SC2016
set -eEuo pipefail

harness="$(CDPATH='' cd "$(dirname "$0")/../.." && pwd -P)"
subject=$harness
subject_repository=
sources=
packages=
packages_ref=
candidate=0
artifacts="$PWD/.integration-results"
option=all
keep=0
while [ "$#" -gt 0 ]; do
  case "$1" in
    --subject|--subject-repository|--sources|--packages|--packages-ref|--artifacts|--option)
      [ "$#" -ge 2 ] || { echo "Missing value for $1" >&2; exit 2; }
      case "$1" in
        --subject) subject=$2 ;; --subject-repository) subject_repository=$2 ;;
        --sources) sources=$2 ;; --packages) packages=$2 ;; --packages-ref) packages_ref=$2 ;; --artifacts) artifacts=$2 ;;
        --option) option=$2 ;;
      esac
      shift 2 ;;
    --keep) keep=1; shift ;;
    --help|-h) sed -n '3,8p' "$0"; exit 0 ;;
    *) echo "Unknown integration option: $1" >&2; exit 2 ;;
  esac
done
case "$option" in all|default|paragraphs|layout-builder|admin-only) ;; *) echo 'Unknown installation option.' >&2; exit 2 ;; esac
for tool in git jq curl realpath shasum ddev; do
  command -v "$tool" > /dev/null || { echo "Required integration tool is unavailable: $tool" >&2; exit 127; }
done
subject=$(realpath "$subject")
[ -z "$sources" ] || packages="$(realpath "$sources")/sous-drupal-packages"
[ -z "$packages" ] || packages=$(realpath "$packages")
[ -z "$packages_ref" ] || [[ "$packages_ref" =~ ^[0-9a-f]{40}$ ]] || { echo "Use a full reviewed packages commit SHA." >&2; exit 2; }
[ -z "$packages" ] || [ -z "$packages_ref" ] || { echo "Choose --packages or --packages-ref." >&2; exit 2; }
if [ -n "$packages$packages_ref" ]; then candidate=1; fi
mkdir -p "$artifacts"
artifacts=$(CDPATH='' cd "$artifacts" && pwd -P)
umask 077
work=$(mktemp -d "${TMPDIR:-/tmp}/sous-integration.XXXXXX")
site="$work/integration"
project="sous-ci-$(printf '%s' "${work##*/}" | shasum -a 256 | cut -c 1-10)"
result="$artifacts/result.json"
count=0
started=0
start_attempted=0
owned=(sous-admin sous-config-split sous-content-types sous-core-layout-builder sous-emulsify sous-formats sous-media sous-paragraph-types sous-paragraphs)
export HUSKY=0 SOUS_DISPOSABLE_TEST=1 SOUS_RUNTIME=ddev SOUS_ENVIRONMENT=local COMPOSER_NO_INTERACTION=1
jq -n --arg workspace "$work" --arg project "$project" \
  '{status:"running",sources:{},commands:[],options:{},workspace:$workspace,ddev_project:$project}' > "$result"

update_result() {
  jq "$@" "$result" > "$artifacts/.result.tmp"
  mv "$artifacts/.result.tmp" "$result"
}
fail() { echo "$*" >&2; update_result --arg error "$*" '.status="fail" | .error=$error'; return 1; }
unexpected_failure() {
  status=$?
  trap - ERR
  echo "Integration failed at shell line $1 (status $status)." >&2
  update_result --arg error "Integration failed at shell line $1 (status $status)." '.status="fail" | .error //= $error'
  exit "$status"
}
trap 'unexpected_failure "$LINENO"' ERR

# Only delete a registered project after verifying its actual approot. Failure
# to establish ownership preserves the workspace for manual recovery.
finish() {
  status=$?
  trap - EXIT ERR
  set +e
  if [ "$status" -ne 0 ]; then update_result '.status="fail"'; fi
  cleanup_failed=0
  if [ "$start_attempted" -eq 1 ] && [ "$keep" -eq 0 ]; then
    if (cd "$site" && ddev describe --json-output "$project") > "$work/description.json" 2> "$artifacts/cleanup-description.stderr"; then
      location=$(jq -er '.raw.approot' "$work/description.json")
      if [ -n "$location" ] && [ "$(realpath "$location")" = "$(realpath "$site")" ]; then
        if (cd "$site" && ddev delete --omit-snapshot --clean-containers=false --yes "$project") > "$artifacts/cleanup.stdout" 2> "$artifacts/cleanup.stderr"; then
          update_result '.cleanup="removed owned disposable DDEV project"'
        else cleanup_failed=1; fi
      else cleanup_failed=1; fi
    elif [ "$started" -eq 1 ]; then cleanup_failed=1
    else update_result '.cleanup="no registered DDEV project was created"'; fi
  fi
  if [ "$cleanup_failed" -eq 1 ]; then
    update_result '.status="fail" | .cleanup_error="Cannot remove disposable resources safely; inspect cleanup logs and workspace ownership."'
    status=1
  elif [ "$keep" -eq 0 ]; then
    # Drupal protects sites/default after install. Restore write permission on
    # these owned fixture directories only before host-side recursive removal.
    for directory in "$site/web/sites/default" "$site/.integration/clean-clone/web/sites/default"; do
      if [ -d "$directory" ]; then
        if ! chmod u+w "$directory"; then update_result '.status="fail" | .cleanup_error="Could not restore owned fixture directory permissions."'; status=1; fi
      fi
    done
    if ! rm -rf -- "$work"; then update_result '.status="fail" | .cleanup_error="Could not remove owned workspace."'; status=1; fi
  fi
  echo "Sous integration result: $(jq -r '.status' "$result") ($artifacts)"
  exit "$status"
}
trap finish EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

# Record every child status separately from stdout/stderr. Expected negative
# cases use record directly; must stops dependent work after an unexpected exit.
record() {
  local label prefix begin elapsed
  label=$1
  shift
  count=$((count + 1))
  prefix=$(printf '%02d-%s' "$count" "$label")
  echo "Sous integration: $label"
  begin=$(date +%s)
  if "$@" > "$work/command.stdout" 2> "$work/command.stderr"; then last_status=0; else last_status=$?; fi
  last_stdout="$artifacts/$prefix.stdout"
  last_stderr="$artifacts/$prefix.stderr"
  sed 's/integration-disposable/[disposable credential omitted]/g' "$work/command.stdout" > "$last_stdout"
  sed 's/integration-disposable/[disposable credential omitted]/g' "$work/command.stderr" > "$last_stderr"
  elapsed=$(( $(date +%s) - begin ))
  update_result --arg step "$label" --argjson code "$last_status" --argjson seconds "$elapsed" \
    --arg stdout "$prefix.stdout" --arg stderr "$prefix.stderr" \
    '.commands += [{step:$step,exit_code:$code,seconds:$seconds,stdout:$stdout,stderr:$stderr}]'
}
must() {
  record "$@"
  [ "$last_status" -eq 0 ] || fail "$1 failed ($last_status); inspect $last_stderr."
}
candidate_index_ready() {
  [ "$candidate" -eq 1 ] || return 0
  must "$1-index-ready" ddev exec env SOUS_DISPOSABLE_TEST=1 timeout --kill-after=2 45 bash devops/integration/wait-for-index.sh
}
dd() {
  local label=$1
  shift
  must "$label" ddev "$@" || return 1
  case "$1:${2:-}" in
    start:*|restart:*|snapshot:restore) candidate_index_ready "$label" ;;
  esac
}
sha256() { shasum -a 256 "$@" | awk '{print $1}'; }

# Use Git's exact tracked/nonignored inventory where available, including local
# working changes. Archive checkouts have no Git metadata and use file discovery.
# Hash the copied bytes, not a later reread of a changing developer checkout.
snapshot() {
  source_directory=$(realpath "$1")
  destination=$2
  mkdir -p "$destination"
  checkout=$(git -C "$source_directory" rev-parse --show-toplevel 2> /dev/null || true)
  if [ "$checkout" = "$source_directory" ]; then
    git -C "$source_directory" ls-files -z --cached --others --exclude-standard > "$work/inventory.raw"
    revision=$(git -C "$source_directory" rev-parse HEAD)
  else
    : > "$work/inventory.raw"
    while IFS= read -r -d '' filename; do printf '%s\0' "${filename#"$source_directory/"}" >> "$work/inventory.raw"; done < <(find "$source_directory" \( -type f -o -type l \) -print0)
    revision=workflow-source-archive
  fi
  jq -Rs 'split("\u0000") | map(select(length>0)) | sort | unique' "$work/inventory.raw" > "$work/inventory.json"
  : > "$work/copied.raw"
  while IFS= read -r -d '' relative; do
    case "/$relative/" in
      */.git/*|*/node_modules/*|*/vendor/*|*/reference/*|*/.sfdx/*|*/.DS_Store/*|*/.integration-results/*|*/.integration/*|*/__pycache__/*) continue ;;
    esac
    [ -f "$source_directory/$relative" ] || continue
    resolved=$(realpath "$source_directory/$relative")
    case "$resolved" in "$source_directory"/*) ;; *) fail "Source symlink leaves its checkout: $relative" ;; esac
    mkdir -p "$(dirname "$destination/$relative")"
    cp -p "$resolved" "$destination/$relative"
    printf '%s\0' "$relative" >> "$work/copied.raw"
  done < <(jq -j '.[] | ., "\u0000"' "$work/inventory.json")
  digest=$(
    while IFS= read -r -d '' relative; do printf '%s\0' "$relative"; cat "$destination/$relative"; done < "$work/copied.raw" | sha256
  )
}

acquire() {
  packages_repository=$(jq -er '.["sous-drupal-packages"]' "$harness/devops/integration/sources.json")
  subject_name=$(jq -er '.name' "$subject/composer.json")
  [ "$subject_name" = fourkitchens/sous-drupal-project ] || fail 'The starter checkout must be the integration subject.'
  [ -z "$subject_repository" ] || [ "$subject_repository" = fourkitchens/sous-drupal-project ] || [[ "$subject_repository" = */sous-drupal-project ]] || fail 'Unknown starter subject repository.'
  snapshot "$subject" "$work/sources/sous-drupal-project"
  update_result --arg revision "$revision" --arg digest "$digest" --argjson candidate "$candidate" \
    '.sources["sous-drupal-project"]={revision:$revision,source_digest_sha256:$digest} | .candidate_packages=($candidate==1)'
  if [ "$candidate" -eq 0 ]; then
    index=$(jq -er '.repositories[] | select(.type=="composer" and ((.only//[]) | index("fourkitchens/sous-admin"))) | .url' "$subject/composer.json")
    must published-index curl --fail --silent --show-error --location "${index%/}/packages.json" --output "$work/published-index.json"
    packages_ref=$(jq -er '.packages["fourkitchens/sous-admin"] | to_entries | map(select(.key|test("^2\\.[0-9]+\\.[0-9]+$"))) | sort_by(.key|split(".")|map(tonumber)) | last.value.dist.reference | select(test("^[0-9a-f]{40}$"))' "$work/published-index.json")
  fi
  if [ -z "$packages" ]; then
    packages="$work/downloads/sous-drupal-packages"
    must packages-init git init --quiet "$packages"
    must packages-fetch git -C "$packages" fetch --depth=1 "$packages_repository" "$packages_ref"
    must packages-checkout git -C "$packages" checkout --quiet --detach FETCH_HEAD
  fi
  snapshot "$packages" "$work/sources/sous-drupal-packages"
  packages_revision=$revision
  [[ "$packages_revision" =~ ^[0-9a-f]{40}$ ]] || fail 'Packages must be an exact committed checkout.'
  update_result --arg repository "$packages_repository" --arg revision "$revision" --arg digest "$digest" \
    '.sources["sous-drupal-packages"]={repository:$repository,revision:$revision,source_digest_sha256:$digest}'
  mkdir -p "$site"
  cp -pR "$work/sources/sous-drupal-project/." "$site/"
  mkdir -p "$site/.integration"
  cp -pR "$work/sources/sous-drupal-project" "$site/.integration/original-starter"
  cp -pR "$work/sources/sous-drupal-packages" "$site/.integration/packages"
  mkdir -p "$site/.integration/sources"
  cp -pR "$work/sources/." "$site/.integration/sources/"
  if [ "$candidate" -eq 1 ]; then
    mkdir -p "$site/.integration/publication"
    # Supervised by DDEV so settings restarts and database snapshot restores
    # cannot silently kill the cold-cache ZIP server.
    cat > "$site/.ddev/config.integration.yaml" <<'YAML'
web_extra_daemons:
  - name: sous-package-index
    command: "php -S 127.0.0.1:18737 -t /var/www/html/.integration/publication"
    directory: /var/www/html
YAML
  fi
  mkdir -p "$site/devops/integration"
  cp -pR "$harness/devops/integration/." "$site/devops/integration/"
  jq '.sources' "$result" > "$artifacts/sources.json"
  cd "$site"
}

audit() {
  audit_label=$1
  audit_kind=$2
  audit_lock=$3
  shift 3
  record "$audit_label" "$@"
  native_status=$last_status
  audit_report=$last_stdout
  must "$audit_label-policy" bash "$harness/devops/integration/audit.sh" "$audit_kind" "$audit_report" "$audit_lock" --exit-code "$native_status"
}

provision() {
  dd ddev-config config "--project-name=$project" --performance-mode=none
  start_attempted=1
  dd ddev-start start
  started=1
  # DDEV had to start before Composer; remove only its premature settings file
  # so the normal first scaffold supplies the source-owned environment include.
  rm -f web/sites/default/settings.php
  dd runtime exec sh -c 'php -r "echo PHP_VERSION, PHP_EOL;" && node --version && npm --version && composer --version --no-ansi'
  php_version=$(sed -n '1p' "$last_stdout")
  node_version=$(sed -n '2p' "$last_stdout")
  npm_version=$(sed -n '3p' "$last_stdout")
  composer_version=$(sed -n '4p' "$last_stdout")
  expected_php=$(jq -r '.config.platform.php | split(".")[0:2] | join(".")' composer.json)
  expected_node=$(tr -d '\r\n' < .nvmrc)
  expected_npm=$(jq -r '.packageManager | split("@")[1]' package.json)
  expected_composer=$(awk '/^composer_version:/ {gsub(/["\047]/, "", $2); print $2}' .ddev/config.yaml)
  case "$php_version" in "$expected_php".*) ;; *) fail 'Unexpected PHP runtime.' ;; esac
  [ "$node_version" = "v$expected_node" ] && [ "$npm_version" = "$expected_npm" ] || fail 'Unexpected Node/npm runtime.'
  case "$composer_version" in "Composer version $expected_composer "*) ;; *) fail 'Unexpected Composer runtime.' ;; esac
  update_result --arg php "$php_version" --arg node "$node_version" --arg npm "$npm_version" --arg composer "$expected_composer" '.runtime={php:$php,node:$node,npm:$npm,composer:$composer}'
  if [ "$candidate" -eq 1 ]; then
    dd build-candidate-zips exec node .integration/packages/tools/build.mjs --version 2.0.0 --reference "$packages_revision" --output .integration/publication --dist-base-url http://127.0.0.1:18737/dist/
    cp .integration/publication/release-manifest.json "$artifacts/candidate-package-manifest.json"
    dd candidate-index-ready exec curl --retry 5 --retry-connrefused --retry-delay 1 --fail http://127.0.0.1:18737/site/packages.json
    jq '.repositories=([{type:"composer",url:"http://127.0.0.1:18737/site",canonical:true,only:["fourkitchens/sous-admin","fourkitchens/sous-config-split","fourkitchens/sous-content-types","fourkitchens/sous-core-layout-builder","fourkitchens/sous-emulsify","fourkitchens/sous-formats","fourkitchens/sous-media","fourkitchens/sous-paragraph-types","fourkitchens/sous-paragraphs"]}]+[.repositories[] | select(((.only//[]) | index("fourkitchens/sous-admin")) | not)]) | .config["secure-http"]=false' composer.json > "$work/composer.json"
    mv "$work/composer.json" composer.json
  fi
  mkdir -p web/sites/default
  printf "<?php\n\$settings['sous_integration_sentinel'] = 'preserved';\n" > web/sites/default/settings.local.php
  cp web/sites/default/settings.local.php "$work/settings.local.php"
  dd composer-resolve-base exec env COMPOSER_HOME=/var/www/html/.integration/composer-home COMPOSER_CACHE_DIR=/var/www/html/.integration/cache-base composer update --no-interaction --prefer-dist
  mkdir -p "$work/bin"
  printf '#!/bin/sh\nexec ddev exec --dir=/var/www/html --raw php "$@"\n' > "$work/bin/php"
  chmod 755 "$work/bin/php"
  PATH="$work/bin:$PATH" must generate-project-metadata sh devops/scripts/sous-setup.sh
  # Source-generation cleanup deliberately removes maintainer tooling. Restore
  # it here only in this disposable CI fixture; real generated sites omit it.
  mkdir -p devops/integration devops/tests
  cp -pR "$harness/devops/integration/." devops/integration/
  cp -pR "$work/sources/sous-drupal-project/devops/tests/." devops/tests/
  jq -c '(.packages+(."packages-dev"//[])) | map([.name,.version,.source.reference,.dist.reference]) | sort' composer.lock > "$work/selected-packages.json"
  dd composer-portable-lock composer update --lock --no-install --no-interaction
  jq -c '(.packages+(."packages-dev"//[])) | map([.name,.version,.source.reference,.dist.reference]) | sort' composer.lock > "$work/portable-packages.json"
  cmp -s "$work/selected-packages.json" "$work/portable-packages.json" || fail 'Lock metadata refresh changed selected packages.'
  # The generated candidate keeps dist-only recipe records. Its temporary HTTP
  # index is validation-only; production root manifests never contain it.
  jq -e 'all((.packages+(."packages-dev"//[]))[]; .dist.type != "path" and (if (.name|startswith("fourkitchens/sous-")) then .dist.type=="zip" and (has("source")|not) else true end))' composer.lock > /dev/null || fail 'Non-ZIP recipe or path distribution in the generated lock.'
  lock_digest=$(sha256 composer.lock)
  rm -rf vendor web/core web/modules/contrib web/themes/contrib web/libraries web/recipes
  dd composer-clean-install exec env COMPOSER_HOME=/var/www/html/.integration/clone-home COMPOSER_CACHE_DIR=/var/www/html/.integration/cache-clean composer install --no-interaction --prefer-dist
  cmp -s web/sites/default/settings.local.php "$work/settings.local.php" && [ "$(sha256 composer.lock)" = "$lock_digest" ] || fail 'Clean install changed local settings or lock bytes.'
  dd generated-composer-validate composer validate --no-check-publish
  dd generated-ddev-settings start
  update_result --arg digest "$lock_digest" '.generated_lock={sha256:$digest,selected_packages_unchanged:true,clean_install_unchanged:true,settings_local_preserved:true}'
  dd composer-platform composer check-platform-reqs
  audit composer-audit composer "$site/composer.lock" ddev exec composer audit --format=json
  dd npm-ci exec env HUSKY=0 npm ci --include=dev
  original=/var/www/html/.integration/original-starter
  ln -s ../../node_modules .integration/original-starter/node_modules
  directories=("$original")
  for name in "${owned[@]}"; do directories+=("/var/www/html/.integration/packages/packages/$name"); done
  dd source-validation exec env SOUS_INTEGRATION_AUTOLOAD=/var/www/html/vendor/autoload.php php devops/integration/validate-recipes.php "${directories[@]}"
  dd starter-defaults exec -d "$original" env SOUS_INTEGRATION_AUTOLOAD=/var/www/html/vendor/autoload.php php devops/tests/starter-defaults.php
  dd documentation-validation exec bash devops/tests/documentation.sh --sources /var/www/html/.integration/sources
  dd composer-validate-starter exec -d "$original" composer validate --no-check-publish
  audit npm-audit-starter npm "$site/.integration/original-starter/package-lock.json" ddev exec -d "$original" npm audit --include=dev --package-lock-only --json
  for name in "${owned[@]}"; do
    dd "composer-validate-$name" exec -d "/var/www/html/.integration/packages/packages/$name" composer validate --strict --no-check-publish
  done
  dd php-lint exec npm run lint:php
  dd javascript-lint exec npm run lint:js
  for test in install-workflows config-workflows environment-settings lint-workflows integration-audit integration-action integration-clone integration-index ci-templates artifact; do
    dd "$test" exec -d "$original" bash "devops/tests/$test.sh"
  done
  dd environment-service-isolation exec env SOUS_INTEGRATION_AUTOLOAD=/var/www/html/vendor/autoload.php php devops/tests/environment-services.php
  dd release-regressions exec -d "$original" npm run release:test
  dd cli-cache-regression exec -d "$original" node devops/tests/verify-cli-cache.mjs
  dd minimal-install drush --yes site:install minimal --account-name=integration_admin --account-pass=integration-disposable --site-name='Sous integration'
  authorization
  for recipe in sous-emulsify sous-admin; do dd "base-$recipe" install-recipe "fourkitchens/$recipe"; done
  must theme-generation-build-lint-storybook sh devops/integration/theme.sh integration
  dd theme-source-lint-regressions exec env SOUS_DISPOSABLE_TEST=1 node devops/tests/theme-initialize.mjs web/themes/custom/integration
  audit theme-audit npm "$site/web/themes/custom/integration/package-lock.json" ddev exec -d /var/www/html/web/themes/custom/integration npm audit --include=dev --json
  dd generated-project-lint exec npm run lint:project
  dd baseline-snapshot snapshot --name=integration-base
  dd baseline-config-export drush config:export --yes
  cp -pR config "$work/baseline-config"
  cp composer.json "$work/baseline-composer.json"
  cp composer.lock "$work/baseline-composer.lock"
  for manifest in composer.json composer.lock package.json package-lock.json; do cp "$manifest" "$artifacts/generated-$manifest"; done
}

auth_verify() { dd "authorization-$1" exec env SOUS_DISPOSABLE_TEST=1 "SOUS_AUTHORIZATION_CASE=$1" vendor/bin/drush php:script .integration/packages/tests/recipes/sous-admin/verify-authorization.php; }
linkit_verify() { dd "linkit-ownership-$1" exec env SOUS_DISPOSABLE_TEST=1 "SOUS_LINKIT_CASE=$1" vendor/bin/drush php:script .integration/packages/tests/recipes/sous-formats/verify-linkit-ownership.php; }
authorization() {
  dd authorization-minimal-snapshot snapshot --name=authorization-minimal
  dd authorization-theme-only install-recipe fourkitchens/sous-emulsify
  auth_verify theme-only
  dd ownership-formats-before-admin install-recipe fourkitchens/sous-formats
  dd authorization-fresh-admin install-recipe fourkitchens/sous-admin
  auth_verify fresh
  linkit_verify fresh
  dd authorization-legacy-reset snapshot restore authorization-minimal
  auth_verify prepare-legacy
  dd ownership-shared-linkit drush recipe /var/www/html/web/recipes/sous-formats/recipes/linkit
  linkit_verify prepare-custom
  for recipe in sous-emulsify sous-admin; do dd "authorization-legacy-$recipe" install-recipe "fourkitchens/$recipe"; done
  dd ownership-formats-after-admin install-recipe fourkitchens/sous-formats
  linkit_verify custom
  auth_verify verify-legacy
  for repeat in 1 2; do dd "authorization-explicit-migration-$repeat" drush recipe /var/www/html/web/recipes/sous-admin/migrations/2026-10-superuser-role; done
  auth_verify verify-legacy
  dd authorization-legacy-export-deploy exec env SOUS_DISPOSABLE_TEST=1 SOUS_INTEGRATION_COMBINATION=admin-only SOUS_INTEGRATION_PROBES_DIR=/var/www/html/.integration/packages/tests/recipes vendor/bin/drush "--uri=https://$project.ddev.site" php:script devops/integration/verify-site.php
  auth_verify verify-legacy
  dd authorization-missing-role-reset snapshot restore authorization-minimal
  auth_verify migration
  dd authorization-final-reset snapshot restore authorization-minimal
  update_result '.authorization={theme_only_has_no_role:true,fresh_admin_access:true,legacy_role_and_memberships_preserved:true,explicit_migration_repeated_safely:true,legacy_export_deploy_preserved:true,shared_linkit_defaults_and_customization_preserved:true}'
}

verify_selected() {
  selection=$1
  split=$2
  must "$selection-$split-package-boundaries" ddev exec env SOUS_DISPOSABLE_TEST=1 "SOUS_INTEGRATION_SELECTION=$selection" "SOUS_INTEGRATION_SPLIT=$split" php devops/integration/verify-packages.php
}

cold_clone() {
  label=$1
  # Commit only generated application source, never downloaded code or probes.
  if [ ! -d .git ]; then
    must clone-source-init git init --quiet || return 1
    printf '/.integration/\n/.ddev/config.integration.yaml\n/devops/integration/\n/devops/tests/\n' >> .git/info/exclude || return 1
  fi
  must "$label-stage-source" git add --all || return 1
  must "$label-stage-lock" git add --force composer.json composer.lock patches.lock.json || return 1
  must "$label-source-commit" git -c user.name='Sous integration' -c user.email=sous-tests@example.invalid commit --quiet --allow-empty -m 'Disposable application source' || return 1
  clone="$site/.integration/clean-clone"
  rm -rf "$clone" || return 1
  must "$label-clone" git clone --quiet --no-hardlinks "$site" "$clone" || return 1
  [ ! -e "$clone/vendor" ] && [ ! -e "$clone/web/recipes" ] || { fail 'Composer-managed files entered the source clone.'; return 1; }
  dd "$label-clone-install" exec -d /var/www/html/.integration/clean-clone env COMPOSER_HOME=/var/www/html/.integration/clone-home COMPOSER_CACHE_DIR="/var/www/html/.integration/cache-$label" composer install --no-interaction --prefer-dist || return 1
  cmp -s composer.lock "$clone/composer.lock" || { fail 'Clone install changed lock bytes.'; return 1; }
  clone_recipes=()
  jq -r '.packages[] | select(.name | startswith("fourkitchens/sous-")) | .name' composer.lock > "$work/clone-recipes.txt" || return 1
  while IFS= read -r name; do clone_recipes+=("$name"); done < "$work/clone-recipes.txt"
  [ "${#clone_recipes[@]}" -gt 0 ] || { fail 'Clone lock lost its selected recipe records.'; return 1; }
  dd "$label-clone-prefer-source" exec -d /var/www/html/.integration/clean-clone env COMPOSER_HOME=/var/www/html/.integration/clone-home COMPOSER_CACHE_DIR="/var/www/html/.integration/source-cache-$label" composer reinstall "${clone_recipes[@]}" --prefer-source --no-interaction || return 1
  must "$label-clone-boundaries" ddev exec -d /var/www/html/.integration/clean-clone env SOUS_DISPOSABLE_TEST=1 "SOUS_INTEGRATION_SELECTION=$choice" "SOUS_INTEGRATION_SPLIT=$split" php /var/www/html/devops/integration/verify-packages.php || return 1
}

option_case() {
    choice=$1
    split=$2
    label="$choice-$split"
    dd "$label-reset" snapshot restore integration-base || return 1
    rm -rf config || return 1
    cp -pR "$work/baseline-config" config || return 1
    cp "$work/baseline-composer.json" composer.json || return 1
    cp "$work/baseline-composer.lock" composer.lock || return 1
    dd "$label-base-packages" composer install --no-interaction --prefer-dist || return 1
    case "$choice" in
      default) recipe=sous-content-types; mode=default ;;
      paragraphs) recipe=sous-paragraphs; mode=paragraphs ;;
      layout-builder) recipe=sous-core-layout-builder; mode=core-layout-builder ;;
      admin-only) recipe=; mode=admin-only ;;
    esac
    if [ -n "$recipe" ]; then
      dd "$label-package" composer require "fourkitchens/$recipe:^2.0" --no-interaction || return 1
      dd "$label-apply" install-recipe "fourkitchens/$recipe" || return 1
    fi
    if [ "$split" = split ]; then
      mkdir -p config/_splits config/local config/staging || return 1
      dd "$label-split-package" composer require 'fourkitchens/sous-config-split:^2.0' --no-interaction || return 1
      dd "$label-split-apply" install-recipe fourkitchens/sous-config-split || return 1
    fi
    verify_selected "$choice" "$split" || return 1
    dd "$label-runtime" exec env SOUS_DISPOSABLE_TEST=1 "SOUS_INTEGRATION_COMBINATION=$mode" SOUS_INTEGRATION_RECIPES_DIR=/var/www/html/web/recipes SOUS_INTEGRATION_PROBES_DIR=/var/www/html/.integration/packages/tests/recipes vendor/bin/drush "--uri=https://$project.ddev.site" php:script devops/integration/verify-site.php || return 1
    if [ "$choice" = default ] && [ "$split" = plain ]; then
      certificate=()
      if command -v mkcert > /dev/null; then
        must development-ca mkcert -CAROOT || return 1
        certificate_directory=$(cat "$last_stdout")
        [ ! -f "$certificate_directory/rootCA.pem" ] || certificate=(--ca-file "$certificate_directory/rootCA.pem")
      fi
      must page-http-authoring bash "$harness/devops/integration/verify-authoring.sh" "https://$project.ddev.site" ${certificate[@]+"${certificate[@]}"} || return 1
    fi
    cold_clone "$label" || return 1
}

options() {
  choices=("$option")
  [ "$option" != all ] || choices=(default paragraphs layout-builder admin-only)
  failed_options=
  for choice in "${choices[@]}"; do
    for split in plain split; do
      if option_case "$choice" "$split"; then
        update_result --arg option "$choice-$split" '.options[$option]={status:"pass"}'
      else
        update_result --arg option "$choice-$split" '.options[$option]={status:"fail",error:.error}'
        failed_options="$failed_options $choice-$split"
      fi
    done
  done
  [ -z "$failed_options" ] || fail "Installation option(s) failed:$failed_options"
}

environment_exec() {
  environment_label=$1
  environment_name=$2
  shift 2
  dd "$environment_label" exec env "SOUS_ENVIRONMENT=$environment_name" SOUS_RUNTIME=native "$@"
}
environment_record() {
  environment_label=$1
  environment_name=$2
  shift 2
  record "$environment_label" ddev exec env "SOUS_ENVIRONMENT=$environment_name" SOUS_RUNTIME=native "$@"
}
probe() { environment_exec "environment-$2-$1" "$2" env SOUS_DISPOSABLE_TEST=1 "SOUS_ENVIRONMENT_TEST=$1" vendor/bin/drush php:script devops/tests/verify-environment.php; }
helper() { environment_exec "environment-$2-$1" "$2" sh "devops/scripts/$1.sh"; }
negative_helper() {
  environment_record "environment-$2-$1-rejected" "$2" sh "devops/scripts/$1.sh"
  if [ "$last_status" -eq 0 ] || ! grep -Fq "$3" "$last_stdout" "$last_stderr"; then
    fail "Unsafe configuration was not rejected before $1."
  fi
}

environment_negatives() {
  # Save original bytes, including the possibility that optional config is absent.
  for safety_case in development-module capture-mail debug-errors disabled-aggregation broad-ignore; do
    case "$safety_case" in
      development-module) filename=core.extension.yml; reason='Development modules' ;;
      capture-mail) filename=system.mail.yml; reason='Mail capture' ;;
      debug-errors) filename=system.logging.yml; reason='error display' ;;
      disabled-aggregation) filename=system.performance.yml; reason=aggregation ;;
      broad-ignore) filename=config_ignore.settings.yml; reason='Config Ignore' ;;
    esac
    path="$site/config/default/$filename"
    existed=0
    if [ -f "$path" ]; then cp "$path" "$work/negative-original"; existed=1; fi
    probe snapshot production
    case "$safety_case" in
      development-module) sed '/^module:$/a\
  devel: 0
' "$path" > "$work/negative-source"; cp "$work/negative-source" "$path" ;;
      capture-mail) printf 'interface:\n  default: test_mail_collector\n' > "$path" ;;
      debug-errors) printf 'error_level: verbose\n' > "$path" ;;
      disabled-aggregation) printf 'css:\n  preprocess: false\n' > "$path" ;;
      broad-ignore) printf "ignored_config_entities:\n  - '*'\n" > "$path" ;;
    esac
    negative_helper confim production "$reason"
    probe roundtrip production
    if [ "$existed" -eq 1 ]; then cp "$work/negative-original" "$path"; else rm -f "$path"; fi
  done
  environment_record environment-production-restore-rejected production sh devops/scripts/import-data.sh
  if [ "$last_status" -eq 0 ] || ! grep -iq production "$last_stdout" "$last_stderr"; then
    fail 'Production accepted database restoration.'
  fi
  update_result '.environment_safety={unsafe_defaults_rejected:5,production_restore_rejected:true}'
}

environments() {
  dd environment-page-reset snapshot restore integration-base
  cp "$work/baseline-composer.json" composer.json
  cp "$work/baseline-composer.lock" composer.lock
  dd environment-base-packages composer install --no-interaction --prefer-dist
  dd environment-content-package composer require 'fourkitchens/sous-content-types:^2.0' --no-interaction
  dd environment-page-apply install-recipe fourkitchens/sous-content-types
  probe seed local
  environment_exec environment-local-cache local vendor/bin/drush cache:rebuild
  helper confex local
  dd environment-base-snapshot snapshot --name=environment-base
  sync="$site/config/default"
  base="$work/environment-base-config"
  cp -pR "$sync" "$base"
  folders="$site/config"
  hidden="$work/optional-folders-not-installed"
  mkdir -p "$hidden"
  for split_folder in _splits local staging; do
    [ ! -d "$folders/$split_folder" ] || mv "$folders/$split_folder" "$hidden/$split_folder"
  done
  update_result '.environments={}'
  for split in plain split; do
    if [ "$split" = split ]; then
      for split_folder in _splits local staging; do
        if [ -d "$hidden/$split_folder" ]; then mv "$hidden/$split_folder" "$folders/$split_folder"; else mkdir -p "$folders/$split_folder"; fi
      done
      dd environment-optional-package composer require 'fourkitchens/sous-config-split:^2.0' --no-interaction
      dd environment-native-split-transforms exec php devops/tests/config-splits.php
      dd environment-optional-recipe install-recipe fourkitchens/sous-config-split
      environment_exec environment-split-defaults local env SOUS_DISPOSABLE_TEST=1 vendor/bin/drush php:script .integration/packages/tests/recipes/sous-config-split/verify-defaults.php
      probe split-prepare local
      helper confex local
      dd environment-split-base-snapshot snapshot --name=environment-split-base
      rm -rf "$base"
      cp -pR "$sync" "$base"
      snapshot_name=environment-split-base
    else snapshot_name=environment-base; fi
    for environment_name in local staging production; do
      key="$environment_name-$split"
      dd "environment-$key-reset" snapshot restore "$snapshot_name"
      rm -rf "$sync"
      cp -pR "$base" "$sync"
      probe seed "$environment_name"
      if [ "$split" = split ] && [ "$environment_name" != production ]; then probe split-configure "$environment_name"; fi
      environment_exec "environment-$environment_name-cache" "$environment_name" vendor/bin/drush cache:rebuild
      probe verify "$environment_name"
      probe snapshot "$environment_name"
      helper confex "$environment_name"
      if [ "$split" = split ]; then
        probe split-verify "$environment_name"
        if [ "$environment_name" != production ]; then
          definition="$folders/_splits/config_split.config_split.sous_$environment_name.yml"
          cp "$definition" "$work/split-original"
          [ "$(grep -c '^folder:' "$definition")" -eq 1 ] || fail 'Expected one split folder setting.'
          for unsafe_folder in ../config/missing-incoming sites/default; do
            if [ "$unsafe_folder" = sites/default ]; then reason='outside the public web root'; else reason='active split directory must exist'; fi
            sed "s|^folder:.*|folder: $unsafe_folder|" "$work/split-original" > "$definition"
            negative_helper confim "$environment_name" "$reason"
            probe roundtrip "$environment_name"
          done
          cp "$work/split-original" "$definition"
          mv "$folders/$environment_name" "$work/missing-active-split"
          for helper_name in confex confim; do negative_helper "$helper_name" "$environment_name" 'active split directory must exist'; done
          probe roundtrip "$environment_name"
          mv "$work/missing-active-split" "$folders/$environment_name"
        fi
      fi
      probe mutate "$environment_name"
      helper confim "$environment_name"
      probe roundtrip "$environment_name"
      probe verify "$environment_name"
      if [ "$split" = split ]; then
        probe split-verify "$environment_name"
        if [ "$environment_name" != production ]; then
          environment_exec environment-production-cache production vendor/bin/drush cache:rebuild
          helper confim production
          probe split-verify production
          probe verify production
        fi
      fi
      update_result --arg key "$key" '.environments[$key]={status:"pass"}'
    done
    if [ "$split" = plain ]; then environment_negatives; fi
  done
  audit environment-composer-audit composer "$site/composer.lock" ddev exec composer audit --format=json
  dd environment-native-ci-build exec env IS_DDEV_PROJECT= SOUS_RUNTIME=native SOUS_ENVIRONMENT=staging sh devops/scripts/theme-build.sh
}

cd "$work"
acquire
provision
options
if [ "$option" = all ]; then environments; fi
update_result '.status="pass"'
