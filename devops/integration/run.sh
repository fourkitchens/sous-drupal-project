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
  3. The front page returns HTTP 200.
  4. Content and recipes match the choices; local tools stay in their split;
     UID 1 stays blocked, sous_chef can
     sign in, and the default custom theme has tools. A chosen component library
     loads through Emulsify and has compiled assets; none leaves an empty scaffold.
  5. Maintainer tooling, one-time theme setup files and assets/ci are absent;
     site agent guidance has working links and survives a site archive.
     Only the selected provider's CI files remain.
     The generated release configuration loads in Node. Applied Sous recipes,
     their setup repository and the creation hook are retired.
  6. Composer reinstalls from the unchanged lock without the Sous repository;
     configuration and notices survive. Configuration scripts work through DDEV and native tools;
     Drush deploy succeeds and config:status reports no changes.
  7. Run Composer CI for admin-only or a chosen library. For admin-only without
     a library, build a project-owned Sass entrypoint after the empty build.
  8. For admin-only, back up, restore with npm run setup and reset with npm run
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
    .repositories.sous =
      {type: "path", url: $url, options: {symlink: false, versions: $versions}}
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

# Check that a stray local settings file cannot enable development settings on a host.
check_host_settings() {
  local settings_check
  settings_check=$(cat <<'PHP'
$app_root = getcwd() . "/web";
$site_path = "sites/default";
define("DRUPAL_ROOT", $app_root);
include $app_root . "/" . $site_path . "/settings.php";
if (($settings["rebuild_access"] ?? FALSE) === TRUE) {
  throw new \RuntimeException("Local development settings loaded outside DDEV.");
}
echo "Local development settings stay in DDEV.\n";
PHP
  )
  step host-settings ddev exec --raw env -u IS_DDEV_PROJECT php -r "$settings_check"
}

# Check Drupal bootstrap before requesting the site's front page.
check_site_responds() {
  local http_url
  step bootstrap ddev drush status --field=bootstrap
  expect_line bootstrap Successful
  http_url=$(ddev describe -j | jq -er '.raw.httpurl')
  step front-page curl --silent --show-error --output /dev/null \
    --write-out '%{http_code}\n' "${http_url%/}/"
  expect_line front-page 200
}

# Check content, administrator accounts and the default custom theme in Drupal.
check_composition() {
  local composition_checks
  local chef_password
  local theme
  chef_password=$(awk '$1 == "|" && $2 == "Password" {print $3}' "$artifacts/create-project.log")
  theme=$(printf '%s' "$project_name" | tr -d '-')
  composition_checks=$(cat <<'PHP'
$option = getenv("SOUS_SMOKE_OPTION");
$recipes = preg_split('/[\s,]+/', getenv("SOUS_SMOKE_RECIPES"), -1, PREG_SPLIT_NO_EMPTY);
$page = \Drupal::entityTypeManager()->getStorage("node_type")->load("page");
$display = \Drupal::entityTypeManager()->getStorage("entity_view_display")->load("node.page.default");
$uid1 = \Drupal\user\Entity\User::load(1);
$chef = user_load_by_name("sous_chef");
$checks = [
  // UID 1 stays blocked while the separate chef account can sign in as Superuser.
  "Blocked UID 1" => $uid1->isBlocked() && $uid1->getAccountName() === "superuser_1",
  "Chef login" => $chef && $chef->isActive() && $chef->hasRole("superuser") && \Drupal::service("password")->check(getenv("SOUS_SMOKE_PASSWORD"), $chef->getPassword()),
  // The generated project-named theme is the frontend default.
  "Custom theme" => \Drupal::config("system.theme")->get("default") === getenv("SOUS_SMOKE_THEME"),
  // Page is included in every composition except admin-only.
  "Page" => (bool) $page === ($option !== "admin-only"),
  // Paragraphs is enabled only for its content composition.
  "Paragraphs" => \Drupal::moduleHandler()->moduleExists("paragraphs") === ($option === "paragraphs"),
  "Paragraph types" => \Drupal::service("config.storage")->exists("paragraphs.paragraphs_type.text") === ($option === "paragraphs"),
  // Layout Builder is enabled on the Page display only when selected.
  "Layout Builder" => (bool) $display?->getThirdPartySetting("layout_builder", "enabled", FALSE) === ($option === "layout-builder"),
  // Media and Config Split are applied to every main course.
  "Config Split" => \Drupal::moduleHandler()->moduleExists("config_split"),
  "Split definitions" => \Drupal::service("config.storage")->exists("config_split.config_split.local") && \Drupal::service("config.storage")->exists("config_split.config_split.staging"),
  "Media types" => count(\Drupal::entityTypeManager()->getStorage("media_type")->loadMultiple(["file", "image", "video"])) === 3,
];
// DDEV activates Local without persisting either split as enabled.
$baseline = ["field_ui", "syslog", "views_ui"];
$optional = ["devel", "masquerade", "stage_file_proxy"];
$devTools = in_array("sous-dev-tools", $recipes, TRUE);
$sync = \Drupal::service("config.storage.sync");
$export = $sync->read("core.extension");
foreach (["local", "staging"] as $split) {
  $name = "config_split.config_split.$split";
  $raw = \Drupal::service("config.storage")->read($name);
  $exported = $sync->read($name);
  $local = $split === "local";
  $expected = $local && $devTools ? array_merge($baseline, $optional) : $baseline;
  $expected = array_fill_keys($expected, 0);
  ksort($expected);
  ksort($raw["module"]);
  ksort($exported["module"]);
  $checks["Split module lists: $split"] = $raw["module"] === $expected && $exported["module"] === $expected;
  $checks["Split activation: $split"] = $raw["status"] === FALSE && $exported["status"] === FALSE && \Drupal::config($name)->get("status") === $local;
  $splitStorage = new \Drupal\Core\Config\FileStorage(DRUPAL_ROOT . "/../config/$split");
  $checks["Syslog defaults: $split"] = $splitStorage->exists("syslog.settings");
  foreach ($optional as $module) {
    $checks["Split defaults: $split/$module"] = $splitStorage->exists("$module.settings") === ($local && $devTools);
  }
}
foreach (array_merge($baseline, $optional) as $module) {
  $enabled = in_array($module, $baseline, TRUE) || $devTools;
  $checks["Local tools: $module"] = \Drupal::moduleHandler()->moduleExists($module) === $enabled && !isset($export["module"][$module]);
}
$checks["DropzoneJS module absent"] = !\Drupal::moduleHandler()->moduleExists("dropzonejs");
$checks["Nonbreaking-space filter"] = \Drupal::moduleHandler()->moduleExists("no_nbsp");
// Every installed content type shares the publishing workflow and scheduling sidebar.
$workflow = \Drupal\workflows\Entity\Workflow::load("editorial");
$workflow_type = $workflow->getTypePlugin();
$checks["Editorial states"] = array_map(static fn($state) => $state->label(), $workflow_type->getStates()) === ["draft" => "Draft", "published" => "Published", "archived" => "Archived"];
$checks["Editorial default"] = $workflow->get("type_settings")["default_moderation_state"] === "draft";
foreach (["workflows", "content_moderation", "scheduler", "scheduler_content_moderation_integration"] as $module) {
  $checks["Publishing module: $module"] = \Drupal::moduleHandler()->moduleExists($module);
}
$node_types = \Drupal::entityTypeManager()->getStorage("node_type")->loadMultiple();
$moderated_types = $workflow_type->getBundlesForEntityType("node");
$installed_types = array_keys($node_types);
sort($moderated_types);
sort($installed_types);
$checks["Moderated content types"] = $moderated_types === $installed_types;
foreach ($node_types as $type => $node_type) {
  $checks["Scheduling settings: $type"] = $node_type->getThirdPartySetting("scheduler", "publish_enable") && $node_type->getThirdPartySetting("scheduler", "unpublish_enable") && $node_type->getThirdPartySetting("scheduler", "fields_display_mode") === "vertical_tab";
  $form = \Drupal::entityTypeManager()->getStorage("entity_form_display")->load("node.$type.default");
  $checks["New content draft: $type"] = \Drupal\node\Entity\Node::create(["type" => $type])->moderation_state->value === "draft";
  $checks["Published on hidden: $type"] = $form->getComponent("published_at") === NULL;
  $checks["Metadata sidebar: $type"] = $form->getRenderer("field_metadata")->getPluginId() === "metatag_firehose" && $form->getRenderer("field_metadata")->getSetting("sidebar");
  if (in_array($type, ["event", "news_post"], TRUE)) {
    $checks["Menu settings: $type"] = $node_type->getThirdPartySetting("menu_ui", "available_menus") === [];
    $taxonomy_field = $type === "event" ? "field_event_type" : "field_news_categories";
    $groups = $form->getThirdPartySettings("field_group");
    $checks["Organization tab: $type"] = $groups["group_organization"]["children"] === [$taxonomy_field] && !in_array($taxonomy_field, $groups["group_content"]["children"], TRUE);
  }
  foreach (["moderation_state" => "moderation_state_default", "publish_on" => "datetime_timestamp_no_default", "unpublish_on" => "datetime_timestamp_no_default", "publish_state" => "scheduler_moderation", "unpublish_state" => "scheduler_moderation"] as $field => $widget) {
    $checks["Scheduling widget: $type.$field"] = $form->getRenderer($field)->getPluginId() === $widget;
  }
}
$events = in_array("sous-events", $recipes, TRUE);
$checks["Optional Events"] = (bool) \Drupal::entityTypeManager()->getStorage("node_type")->load("event") === $events;
if ($events) {
  // Events uses the selected composition without bringing in another one.
  $event_display = \Drupal::entityTypeManager()->getStorage("entity_view_display")->load("node.event.default");
  $checks["Event layouts"] = (bool) $event_display->getThirdPartySetting("layout_builder", "enabled", FALSE) === ($option === "layout-builder");
  $event_content = \Drupal\field\Entity\FieldConfig::loadByName("node", "event", "field_content");
  $checks["Event paragraphs"] = (bool) $event_content === ($option === "paragraphs");
  $checks["Event dates"] = \Drupal::moduleHandler()->moduleExists("smart_date_recur");
  $event_view = \Drupal\views\Entity\View::load("events");
  $checks["Event embeds"] = isset($event_view->get("display")["embed_upcoming"], $event_view->get("display")["embed_past"]);
}
$news = in_array("sous-news-post", $recipes, TRUE);
$checks["Optional News"] = (bool) \Drupal::entityTypeManager()->getStorage("node_type")->load("news_post") === $news;
if ($news) {
  // News follows the same editor choice while keeping its own fields and listing.
  $news_display = \Drupal::entityTypeManager()->getStorage("entity_view_display")->load("node.news_post.default");
  $checks["News layouts"] = (bool) $news_display->getThirdPartySetting("layout_builder", "enabled", FALSE) === ($option === "layout-builder");
  $news_content = \Drupal\field\Entity\FieldConfig::loadByName("node", "news_post", "field_content");
  $checks["News paragraphs"] = (bool) $news_content === ($option === "paragraphs");
  $news_view = \Drupal\views\Entity\View::load("news");
  $checks["Recent News embed"] = isset($news_view->get("display")["embed_recent"]);
}
$profile = in_array("sous-profile", $recipes, TRUE);
$checks["Optional Profile"] = (bool) \Drupal::entityTypeManager()->getStorage("node_type")->load("profile") === $profile;
if ($profile) {
  // Use the real form and save hooks to verify the generated name and hidden title.
  $account_switcher = \Drupal::service("account_switcher");
  $account_switcher->switchTo($chef);
  $person = \Drupal\node\Entity\Node::create(["type" => "profile", "field_first_name" => "Ada", "field_last_name" => "Lovelace"]);
  try {
    $profile_form = \Drupal::service("entity.form_builder")->getForm($person);
    $checks["Profile hidden title"] = $profile_form["title"]["widget"][0]["value"]["#type"] === "hidden" && $profile_form["title"]["widget"][0]["value"]["#required"] === FALSE;
    $checks["Profile names required"] = $person->getFieldDefinition("field_first_name")->isRequired() && $person->getFieldDefinition("field_last_name")->isRequired();
    $person->save();
    $checks["Profile automatic title"] = $person->label() === "Ada Lovelace";
    $person->set("field_last_name", "Byron")->save();
    $checks["Profile title updates"] = $person->label() === "Ada Byron";
    $profile_form_display = \Drupal::entityTypeManager()->getStorage("entity_form_display")->load("node.profile.default");
    $checks["Profile image"] = $person->getFieldDefinition("field_image")->getFieldStorageDefinition()->getCardinality() === 1 && $profile_form_display->getRenderer("field_image")->getPluginId() === "media_library_widget";
    $checks["Profile Bio"] = $person->getFieldDefinition("field_description")->getType() === "text_long" && $person->getFieldDefinition("field_description")->getSetting("allowed_formats") === ["basic_html"];
    $person->set("field_teaser_text", "Profile smoke teaser")->set("moderation_state", "published")->save();
    $profile_view = \Drupal\views\Views::getView("profiles");
    $checks["Profiles embed display"] = $profile_view->setDisplay("embed_profiles") && $profile_view->display_handler->getPluginId() === "embed";
    $profile_view->setExposedInput(["profile_search" => "Byron"]);
    $profile_view->execute();
    $checks["Profiles searchable embed"] = count($profile_view->result) === 1 && (int) $profile_view->result[0]->_entity->id() === (int) $person->id();
    $profile_output = $profile_view->render();
    $checks["Profiles teaser rendering"] = str_contains((string) \Drupal::service("renderer")->renderInIsolation($profile_output), "Profile smoke teaser");
  }
  finally {
    if (!$person->isNew()) {
      $person->delete();
    }
    $account_switcher->switchBack();
  }
}
foreach ($checks as $name => $passed) {
  if (!$passed) {
    throw new \RuntimeException("Unexpected composition: $name");
  }
}
echo "Selected composition matches.\n";
PHP
  )
  step composition ddev exec --raw env SOUS_SMOKE_OPTION="$option" \
    SOUS_SMOKE_RECIPES="$recipes" SOUS_SMOKE_THEME="$theme" \
    SOUS_SMOKE_PASSWORD="$chef_password" vendor/bin/drush php:eval "$composition_checks"
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
  local theme heading
  theme=$(printf '%s' "$project_name" | tr -d '-')
  step agent-heading grep -Fx "# $project_name agent instructions" AGENTS.md
  step facts-heading grep -Fx '# Workflow facts' "$facts"
  step facts-sections grep '^## ' "$facts"
  for heading in Sites 'Database refresh' 'Test roles' 'Public files' 'Git workflow' 'Review and deploy'; do
    expect_line facts-sections "## $heading"
  done
  step agent-theme-link grep -F "(web/themes/custom/$theme/AGENTS.md)" AGENTS.md
  step agent-package-link grep -F '(package.json)' AGENTS.md
  step agent-workflow-pointer grep -Fx -- \
    '- Workflow facts: Read [the workflow facts](docs/agents/workflow-facts.md) before debugging, implementing, reviewing, or deploying a change.' AGENTS.md
  step agent-project-discovery grep -F 'ddev describe' "$facts"
  step agent-site-facts grep -F 'ddev drush status --fields=uri,db-name' "$facts"

  # Resolve the relative file links and Markdown heading anchors used here.
  # shellcheck disable=SC2016
  step agent-links node -e '
    const fs = require("node:fs");
    const { pathToFileURL } = require("node:url");
    for (const document of process.argv.slice(1)) {
      const contents = fs.readFileSync(document, "utf8");
      if (/\b(?:PROJECT_NAME|PROJECT_THEME|LOCAL_URL|LOCAL_DATABASE)\b|^# Sous coding agent brief$/m.test(contents)) {
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
  ' AGENTS.md "$facts"

  # Archive a staged tree to check export rules without committing to the site.
  # shellcheck disable=SC2016
  step agent-archive env GIT_INDEX_FILE="$work_dir/agent-archive.index" bash -e -o pipefail -c '
    git add -- .gitattributes "$@"
    git archive "$(git write-tree)" | tar -tf -
  ' bash AGENTS.md "$facts"
  expect_line agent-archive AGENTS.md
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

  # The fixture is mounted during setup only, so it cannot satisfy this install.
  if [ -n "$packages" ]; then
    step remove-package-mount rm .ddev/docker-compose.recipes.yaml
    step restart-without-packages ddev restart
    step absent-package-mount ddev exec --raw -- test ! -e "$packages/packages"
  fi
  step save-lock cp composer.lock "$work_dir/reinstall.lock"
  step save-config cp -R config "$work_dir/reinstall-config"
  if [ -f NOTICE ]; then step save-notice cp NOTICE "$work_dir/reinstall-notice"; fi
  step remove-dependencies rm -rf vendor web/core web/modules/contrib web/themes/contrib \
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
  step locked-selection jq -e --arg recipes "$recipes" '
    (.locked | map(.name)) as $names
    | ($recipes | test("(^|[[:space:],])sous-dev-tools($|[[:space:],])")) as $dev_tools
    | all($names[]; test("^(fourkitchens/sous-|drupal/dropzonejs$|dropzone/dropzone$)") | not)
      and all(["drupal/devel", "drupal/masquerade", "drupal/stage_file_proxy"][];
        . as $name | ($names | index($name) != null) == $dev_tools)
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
  fi
  create_site
  check_host_settings
  check_site_responds
  check_composition
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
