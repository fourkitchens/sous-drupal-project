<?php

$option = getenv("SOUS_SMOKE_OPTION");
$recipes = preg_split('/[\s,]+/', getenv("SOUS_SMOKE_RECIPES"), -1, PREG_SPLIT_NO_EMPTY);
$theme = getenv("SOUS_SMOKE_THEME");
$page = \Drupal::entityTypeManager()->getStorage("node_type")->load("page");
$display = \Drupal::entityTypeManager()->getStorage("entity_view_display")->load("node.page.default");
$uid1 = \Drupal\user\Entity\User::load(1);
$chef = user_load_by_name("sous_chef");
$editor = \Drupal\user\Entity\Role::load("site_editor");
$media_types = \Drupal::entityTypeManager()->getStorage("media_type");
$checks = [
  // UID 1 stays blocked while the separate chef account can sign in as Superuser.
  "Blocked UID 1" => $uid1->isBlocked() && $uid1->getAccountName() === "superuser_1",
  "Chef login" => $chef && $chef->isActive() && $chef->hasRole("superuser") && \Drupal::service("password")->check(getenv("SOUS_SMOKE_PASSWORD"), $chef->getPassword()),
  // The generated project-named theme is the frontend default.
  "Custom theme" => \Drupal::config("system.theme")->get("default") === $theme,
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
  "Media types" => count($media_types->loadMultiple(["file", "image", "video"])) === 3 && count($media_types->loadByProperties(["source" => "video_file"])) === 1,
  "Site Admin" => $editor?->label() === "Site Admin" && !$editor->isAdmin() && $editor->hasPermission("administer users") && !$editor->hasPermission("administer site configuration") && !$editor->hasPermission("administer permissions") && !$editor->hasPermission("assign all roles") && !$editor->hasPermission("assign superuser role"),
];
$breakpoint_manager = \Drupal::service("breakpoint.manager");
$base_breakpoints = $breakpoint_manager->getBreakpointsByGroup("emulsify");
$theme_breakpoints = $breakpoint_manager->getBreakpointsByGroup($theme);
foreach (["xsmall", "small", "medium"] as $size) {
  $base_breakpoint = $base_breakpoints["emulsify.$size"] ?? NULL;
  $theme_breakpoint = $theme_breakpoints["$theme.$size"] ?? NULL;
  $checks["Teaser breakpoint: $size"] = $base_breakpoint && $theme_breakpoint && $base_breakpoint->getMediaQuery() === $theme_breakpoint->getMediaQuery();
}
// DDEV activates Local without persisting either split as enabled.
$baseline = ["field_ui", "syslog", "views_ui"];
$devTools = ["devel", "masquerade", "stage_file_proxy"];
$sync = \Drupal::service("config.storage.sync");
$export = $sync->read("core.extension");
foreach (["local", "staging"] as $split) {
  $name = "config_split.config_split.$split";
  $raw = \Drupal::service("config.storage")->read($name);
  $exported = $sync->read($name);
  $local = $split === "local";
  $expected = $local ? array_merge($baseline, $devTools) : $baseline;
  $expected = array_fill_keys($expected, 0);
  ksort($expected);
  ksort($raw["module"]);
  ksort($exported["module"]);
  $checks["Split module lists: $split"] = $raw["module"] === $expected && $exported["module"] === $expected;
  $checks["Split activation: $split"] = $raw["status"] === FALSE && $exported["status"] === FALSE && \Drupal::config($name)->get("status") === $local;
  $splitStorage = new \Drupal\Core\Config\FileStorage(DRUPAL_ROOT . "/../config/$split");
  $checks["Syslog defaults: $split"] = $splitStorage->exists("syslog.settings");
  foreach ($devTools as $module) {
    $checks["Split defaults: $split/$module"] = $splitStorage->exists("$module.settings") === $local;
  }
}
foreach (array_merge($baseline, $devTools) as $module) {
  $checks["Local tools: $module"] = \Drupal::moduleHandler()->moduleExists($module) && !isset($export["module"][$module]);
}
$checks["DropzoneJS module absent"] = !\Drupal::moduleHandler()->moduleExists("dropzonejs");
$checks["Login Destination absent"] = !\Drupal::moduleHandler()->moduleExists("login_destination");
$checks["Nonbreaking-space filter"] = \Drupal::moduleHandler()->moduleExists("no_nbsp");
foreach (["dashboard", "editoria11y", "gin_login", "twig_tweak", "role_delegation", "emulsify_tools", "menu_admin_per_menu", "menu_block"] as $module) {
  $checks["Default module: $module"] = \Drupal::moduleHandler()->moduleExists($module) && isset($export["module"][$module]);
}
// Dashboard selection follows the signed-in account's roles.
$account_switcher = \Drupal::service("account_switcher");
foreach (["Chef dashboard" => [$chef, "site_admin"], "Site Admin dashboard" => [new \Drupal\Core\Session\UserSession(["uid" => 1000, "roles" => ["authenticated", "site_editor"]]), "editor"]] as $name => [$account, $dashboard]) {
  $account_switcher->switchTo($account);
  try {
    $checks[$name] = \Drupal::service("dashboard.manager")->getDefaultDashboard()?->id() === $dashboard;
  }
  finally {
    $account_switcher->switchBack();
  }
}
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
  $teaser = \Drupal::entityTypeManager()->getStorage("entity_view_display")->load("node.$type.teaser");
  $checks["Landscape teaser: $type"] = $teaser?->getRenderer("field_teaser_image")?->getSetting("view_mode") === "content_teaser_landscape";
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
$content_list = $option !== "admin-only";
$config_storage = \Drupal::service("config.storage");
$checks["Content List paragraph"] = $config_storage->exists("paragraphs.paragraphs_type.content_list") === ($content_list && $option === "paragraphs");
$checks["Content List block"] = $config_storage->exists("block_content.type.content_list") === ($content_list && $option === "layout-builder");
if ($content_list && $option === "paragraphs") {
  foreach (["page" => TRUE, "event" => $events, "news_post" => $news] as $type => $installed) {
    if ($installed) {
      $field = \Drupal\field\Entity\FieldConfig::loadByName("node", $type, "field_content");
      $checks["Content List allowed on $type"] = isset($field?->getSetting("handler_settings")["target_bundles"]["content_list"]);
    }
  }
}
$gallery = $option !== "admin-only";
$checks["Gallery paragraph"] = $config_storage->exists("paragraphs.paragraphs_type.gallery") === ($gallery && $option === "paragraphs");
$checks["Gallery block"] = $config_storage->exists("block_content.type.gallery") === ($gallery && $option === "layout-builder");
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
$alerts = in_array("sous-alerts", $recipes, TRUE);
$alert = \Drupal\block\Entity\Block::load("sous_sitewide_alert");
$checks["Optional Alerts block"] = $alerts
  ? $alert && $alert->status() && $alert->getTheme() === $theme && $alert->getRegion() === "content_top"
  : !$alert;
$search = in_array("sous-search", $recipes, TRUE);
$checks["Optional Search view"] = $config_storage->exists("views.view.search") === $search;
foreach ([
  "Alerts" => [$alerts, ["sitewide_alert", "sitewide_alert_block"]],
  "Search" => [$search, ["search_api", "search_api_db", "facets", "facets_exposed_filters", "better_exposed_filters"]],
] as $name => [$selected, $modules]) {
  foreach ($modules as $module) {
    $checks["Optional $name module: $module"] = \Drupal::moduleHandler()->moduleExists($module) === $selected;
  }
}
foreach (["redirect", "simple_sitemap", "easy_breadcrumb", "google_tag", "schema_metatag"] as $module) {
  $checks["SEO module: $module"] = \Drupal::moduleHandler()->moduleExists($module);
}
foreach ($checks as $name => $passed) {
  if (!$passed) {
    throw new \RuntimeException("Unexpected composition: $name");
  }
}
echo "Selected composition matches.\n";
