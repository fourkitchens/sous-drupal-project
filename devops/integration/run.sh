#!/usr/bin/env bash

# Check the selected Sous sources together in one owned disposable DDEV site.
# Host tools: Bash, Git, jq, curl, realpath and shasum; PHP/Node run in DDEV.
# Usage: bash devops/integration/run.sh [--sources PARENT] [--subject CHECKOUT]
#   [--subject-repository OWNER/REPO] [--refs-json JSON] [--artifacts DIRECTORY]
#   [--option all|default|paragraphs|layout-builder|admin-only] [--keep]
# --keep is for local diagnosis only. CI always removes its owned fixture.
# jq expands its --arg variables inside the literal filter strings below.
# shellcheck disable=SC2016
set -eEuo pipefail

harness="$(CDPATH='' cd "$(dirname "$0")/../.." && pwd -P)"
subject=$harness
subject_repository=
sources=
refs='{}'
artifacts="$PWD/.integration-results"
option=all
keep=0
while [ "$#" -gt 0 ]; do
  case "$1" in
    --subject|--subject-repository|--sources|--refs-json|--artifacts|--option)
      [ "$#" -ge 2 ] || { echo "Missing value for $1" >&2; exit 2; }
      case "$1" in
        --subject) subject=$2 ;; --subject-repository) subject_repository=$2 ;;
        --sources) sources=$2 ;; --refs-json) refs=$2 ;; --artifacts) artifacts=$2 ;;
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
[ -z "$sources" ] || sources=$(realpath "$sources")
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
owned=(sous-admin sous-content-types sous-emulsify sous-formats sous-media sous-paragraph-types)
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
dd() { must "$1" ddev "${@:2}"; }
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
  catalog="$harness/devops/integration/sources.json"
  jq -e --argjson refs "$refs" '
    . as $catalog | ($refs | type)=="object" and
    all($refs | to_entries[]; . as $entry | ($catalog | has($entry.key)) and ($entry.value | type)=="string" and ($entry.value | test("^[A-Za-z0-9][A-Za-z0-9._/-]{0,199}$")) and ($entry.value | contains("..") | not))
  ' "$catalog" > /dev/null || fail 'recipe_refs must map known repositories to valid reviewed Git references.'
  subject_name=$(jq -er '.name | sub("^fourkitchens/"; "")' "$subject/composer.json")
  case "$subject_name" in sous-drupal-project|sous-admin|sous-content-types|sous-emulsify|sous-formats|sous-media|sous-paragraph-types) ;; *) fail 'Unknown Sous subject.' ;; esac
  [ -z "$subject_repository" ] || [ "${subject_repository##*/}" = "$subject_name" ] || fail 'Subject checkout does not match caller repository.'
  jq -en --argjson refs "$refs" --arg subject "$subject_name" '$refs | has($subject) | not' > /dev/null || fail 'A companion ref cannot replace the checked-out subject.'
  while IFS= read -r name; do
    repository=$(jq -r --arg name "$name" '.[$name]' "$catalog")
    ref=$(jq -nr --argjson refs "$refs" --arg name "$name" '$refs[$name] // ""')
    if [ "$name" = "$subject_name" ]; then source_directory=$subject
    elif [ -n "$sources" ] && [ -d "$sources/$name" ] && [ -z "$ref" ]; then source_directory="$sources/$name"
    elif [ "$name" = sous-drupal-project ] && [ -z "$ref" ]; then source_directory=$harness
    else
      source_directory="$work/downloads/$name"
      mkdir -p "$source_directory"
      must "init-$name" git init --quiet "$source_directory"
      must "remote-$name" git -C "$source_directory" remote add origin "$repository"
      must "fetch-$name" git -C "$source_directory" fetch --depth=1 origin "${ref:-main}"
      must "checkout-$name" git -C "$source_directory" checkout --quiet --detach FETCH_HEAD
    fi
    snapshot "$source_directory" "$work/sources/$name"
    update_result --arg name "$name" --arg repository "$repository" --arg revision "$revision" --arg digest "$digest" \
      '.sources[$name]={repository:$repository,revision:$revision,source_digest_sha256:$digest}'
  done < <(jq -r 'keys[]' "$catalog")
  mkdir -p "$site/.integration/recipes"
  cp -pR "$work/sources/sous-drupal-project/." "$site/"
  cp -pR "$work/sources/sous-drupal-project" "$site/.integration/original-starter"
  # Canonical directory names let the documentation checker resolve links among
  # all source repositories while its PHP parser runs inside DDEV, not the host.
  cp -pR "$work/sources" "$site/.integration/sources"
  while IFS= read -r name; do
    [ "$name" = sous-drupal-project ] || cp -pR "$work/sources/$name" "$site/.integration/recipes/$name"
  done < <(jq -r 'keys[]' "$catalog")
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
  jq --argjson names '["sous-admin","sous-content-types","sous-emulsify","sous-formats","sous-media","sous-paragraph-types","sous-paragraphs"]' '
    ($names | map({key:("fourkitchens/"+.),value:"dev-main"}) | from_entries) as $versions |
    .repositories=([{type:"path",url:".integration/recipes/*",options:{symlink:false,versions:$versions}}]+.repositories) |
    .require += $versions
  ' composer.json > "$work/composer.json"
  mv "$work/composer.json" composer.json
  mkdir -p web/sites/default
  printf "<?php\n\$settings['sous_integration_sentinel'] = 'preserved';\n" > web/sites/default/settings.local.php
  cp web/sites/default/settings.local.php "$work/settings.local.php"
  dd composer-resolve-sources composer update --no-interaction --prefer-dist
  dd composer-unpack-recipes composer drupal:recipe-unpack
  for name in "${owned[@]}" sous-paragraphs; do
    cmp -s ".integration/recipes/$name/recipe.yml" "web/recipes/$name/recipe.yml" || fail "Installed recipe differs from source: $name"
  done
  jq '.repositories |= map(select(.type!="path"))' composer.json > "$work/composer.json"
  mv "$work/composer.json" composer.json
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
  cmp -s "$work/selected-packages.json" "$work/portable-packages.json" || fail 'Portable lock refresh changed selected packages.'
  jq -e 'all((.packages+(."packages-dev"//[]))[]; (.name | startswith("fourkitchens/sous-") | not) and .dist.type != "path")' composer.lock > /dev/null || fail 'A temporary recipe/path package remains in the portable lock.'
  lock_digest=$(sha256 composer.lock)
  rm -rf vendor web/core web/modules/contrib web/themes/contrib web/libraries
  dd composer-clean-install composer install --no-interaction --prefer-dist
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
  for name in "${owned[@]}"; do directories+=("/var/www/html/.integration/recipes/$name"); done
  directories+=("/var/www/html/.integration/recipes/sous-paragraphs")
  dd source-validation exec env SOUS_INTEGRATION_AUTOLOAD=/var/www/html/vendor/autoload.php php devops/integration/validate-recipes.php "${directories[@]}"
  dd starter-defaults exec -d "$original" env SOUS_INTEGRATION_AUTOLOAD=/var/www/html/vendor/autoload.php php devops/tests/starter-defaults.php
  dd documentation-validation exec bash devops/tests/documentation.sh --sources /var/www/html/.integration/sources
  for name in sous-drupal-project "${owned[@]}"; do
    if [ "$name" = sous-drupal-project ]; then local_source="$site/.integration/original-starter"; container=$original
    else local_source="$site/.integration/recipes/$name"; container="/var/www/html/.integration/recipes/$name"; fi
    dd "composer-validate-$name" exec -d "$container" composer validate --no-check-publish
    audit "npm-audit-$name" npm "$local_source/package-lock.json" ddev exec -d "$container" npm audit --include=dev --package-lock-only --json
  done
  dd php-lint exec npm run lint:php
  dd javascript-lint exec npm run lint:js
  for test in install-workflows config-workflows environment-settings lint-workflows integration-audit integration-action ci-templates artifact; do
    dd "$test" exec -d "$original" bash "devops/tests/$test.sh"
  done
  dd environment-service-isolation exec env SOUS_INTEGRATION_AUTOLOAD=/var/www/html/vendor/autoload.php php devops/tests/environment-services.php
  dd release-regressions exec -d "$original" npm run release:test
  dd cli-cache-regression exec -d "$original" node devops/tests/verify-cli-cache.mjs
  dd minimal-install drush --yes site:install minimal --account-name=integration_admin --account-pass=integration-disposable --site-name='Sous integration'
  authorization
  for recipe in sous-emulsify sous-admin; do dd "base-$recipe" install-recipe "fourkitchens/$recipe"; done
  must theme-generation-build-lint-storybook sh devops/integration/theme.sh integration
  audit theme-audit npm "$site/web/themes/custom/integration/package-lock.json" ddev exec -d /var/www/html/web/themes/custom/integration npm audit --include=dev --json
  dd generated-project-lint exec npm run lint:project
  dd baseline-snapshot snapshot --name=integration-base
  for manifest in composer.json composer.lock package.json package-lock.json; do cp "$manifest" "$artifacts/generated-$manifest"; done
}

auth_verify() { dd "authorization-$1" exec env SOUS_DISPOSABLE_TEST=1 "SOUS_AUTHORIZATION_CASE=$1" vendor/bin/drush php:script web/recipes/sous-admin/tests/verify-authorization.php; }
linkit_verify() { dd "linkit-ownership-$1" exec env SOUS_DISPOSABLE_TEST=1 "SOUS_LINKIT_CASE=$1" vendor/bin/drush php:script web/recipes/sous-formats/tests/verify-linkit-ownership.php; }
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
  dd authorization-legacy-export-deploy exec env SOUS_DISPOSABLE_TEST=1 SOUS_INTEGRATION_COMBINATION=admin-only vendor/bin/drush "--uri=https://$project.ddev.site" php:script devops/integration/verify-site.php
  auth_verify verify-legacy
  dd authorization-missing-role-reset snapshot restore authorization-minimal
  auth_verify migration
  dd authorization-final-reset snapshot restore authorization-minimal
  update_result '.authorization={theme_only_has_no_role:true,fresh_admin_access:true,legacy_role_and_memberships_preserved:true,explicit_migration_repeated_safely:true,legacy_export_deploy_preserved:true,shared_linkit_defaults_and_customization_preserved:true}'
}

option_case() {
    choice=$1
    dd "$choice-reset" snapshot restore integration-base || return 1
    case "$choice" in
      default) recipe=sous-starter-content; mode=default ;;
      paragraphs) recipe=sous-starter-paragraphs; mode=paragraphs ;;
      layout-builder)
        recipe=sous-layout-builder; mode=core-layout-builder ;;
      admin-only) recipe=; mode=admin-only ;;
    esac
    if [ -n "$recipe" ]; then dd "$choice-apply" install-recipe "fourkitchens/$recipe" || return 1; fi
    dd "$choice-runtime" exec env SOUS_DISPOSABLE_TEST=1 "SOUS_INTEGRATION_COMBINATION=$mode" SOUS_INTEGRATION_RECIPES_DIR=/var/www/html/web/recipes vendor/bin/drush "--uri=https://$project.ddev.site" php:script devops/integration/verify-site.php || return 1
    if [ "$choice" = default ]; then
      certificate=()
      if command -v mkcert > /dev/null; then
        must development-ca mkcert -CAROOT || return 1
        certificate_directory=$(cat "$last_stdout")
        [ ! -f "$certificate_directory/rootCA.pem" ] || certificate=(--ca-file "$certificate_directory/rootCA.pem")
      fi
      must page-http-authoring bash "$harness/devops/integration/verify-authoring.sh" "https://$project.ddev.site" ${certificate[@]+"${certificate[@]}"} || return 1
    fi
}

options() {
  choices=("$option")
  [ "$option" != all ] || choices=(default paragraphs layout-builder admin-only)
  failed_options=
  # Each case restores the same owned baseline. A failed case must not hide the
  # other offered choices, but the aggregate gate still fails before release.
  for choice in "${choices[@]}"; do
    if option_case "$choice"; then
      update_result --arg option "$choice" '.options[$option]={status:"pass"}'
    else
      update_result --arg option "$choice" '.options[$option]={status:"fail",error:.error}'
      failed_options="$failed_options $choice"
    fi
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
  for split_folder in _splits local staging; do mv "$folders/$split_folder" "$hidden/$split_folder"; done
  update_result '.environments={}'
  for split in plain split; do
    if [ "$split" = split ]; then
      for split_folder in _splits local staging; do mv "$hidden/$split_folder" "$folders/$split_folder"; done
      dd environment-optional-package composer require 'drupal/config_split:^2.0.2' --no-interaction
      dd environment-native-split-transforms exec php devops/tests/config-splits.php
      dd environment-optional-recipe install-recipe fourkitchens/sous-config-split
      environment_exec environment-split-defaults local env SOUS_DISPOSABLE_TEST=1 vendor/bin/drush php:script web/recipes/sous-config-split/tests/verify-defaults.php
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
