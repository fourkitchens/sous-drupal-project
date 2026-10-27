<?php

declare(strict_types=1);

/**
 * Check the bundled starter defaults without loading Drupal or changing a site.
 *
 * Uses the existing Composer YAML parser; SOUS_INTEGRATION_AUTOLOAD can select
 * an installed disposable fixture's vendor/autoload.php.
 */

use Symfony\Component\Yaml\Yaml;

$root = dirname(__DIR__, 2);
require getenv('SOUS_INTEGRATION_AUTOLOAD') ?: $root . '/vendor/autoload.php';
$check = static function (bool $condition, string $message): void {
  if (!$condition) {
    throw new RuntimeException($message);
  }
};
$read = static fn (string $file): array => Yaml::parseFile($root . '/web/recipes/' . $file);
$content = $read('sous-starter-content/recipe.yml');
$paragraphs = $read('sous-starter-paragraphs/recipe.yml');
$layout = $read('sous-layout-builder/recipe.yml');
$manifest = json_decode(file_get_contents($root . '/composer.json'), TRUE, 512, JSON_THROW_ON_ERROR);
$check(isset($manifest['require']['drupal/pathauto']) && $manifest['require']['drupal/pathauto'] !== '*', 'The starter must declare its bounded Pathauto dependency.');
$check(in_array('sous-content-types', $content['recipes'], TRUE), 'Starter content must compose the shared Page/media defaults.');
$check(!in_array('sous-admin', $content['recipes'], TRUE), 'Content composition must not reapply strict Gin imports after starter Admin setup.');
foreach (['block_content', 'login_history', 'menu_ui', 'path', 'pathauto', 'taxonomy', 'views'] as $module) {
  $check(in_array($module, $content['install'], TRUE), 'The starter content recipe must install ' . $module . '.');
}
$check($read('sous-starter-content/config/node.type.frontpage.yml')['type'] === 'frontpage', 'Frontpage must be a bundled starter content type.');
foreach (['body', 'field_metadata', 'field_seo_title', 'field_teaser_image', 'field_teaser_text'] as $field) {
  $definition = $read('sous-starter-content/config/field.field.node.frontpage.' . $field . '.yml');
  $check($definition['bundle'] === 'frontpage' && $definition['field_name'] === $field, 'Incorrect Frontpage field identity: ' . $field);
  $check(in_array('field.storage.node.' . $field, $definition['dependencies']['config'], TRUE), 'Frontpage must reuse the shared storage: ' . $field);
  $check(!is_file($root . '/web/recipes/sous-starter-content/config/field.storage.node.' . $field . '.yml'), 'The starter duplicated shared field storage: ' . $field);
}
$form = $read('sous-starter-content/config/core.entity_form_display.node.frontpage.default.yml');
$view = $read('sous-starter-content/config/core.entity_view_display.node.frontpage.default.yml');
$check($form['content']['body']['type'] === 'text_textarea_with_summary' && $view['content']['body']['type'] === 'text_default', 'Frontpage needs an editable, visible Body field.');
$check(in_array('body', $form['third_party_settings']['field_group']['group_content']['children'], TRUE), 'Frontpage Body must be reachable in its Content tab.');
$check($form['content']['path']['type'] === 'path', 'Frontpage must expose its native path widget.');
$check($content['config']['actions']['core.entity_form_display.node.page.default']['setComponent']['name'] === 'path', 'The starter must add Page URL authoring.');
foreach (['page', 'frontpage'] as $bundle) {
  $pattern = $read('sous-starter-content/config/pathauto.pattern.' . $bundle . '.yml');
  $criteria = $pattern['selection_criteria'];
  $id = array_key_first($criteria);
  $check($pattern['pattern'] === '[node:title]' && $criteria[$id]['uuid'] === $id && ($criteria[$id]['bundles'][$bundle] ?? NULL) === $bundle, 'Invalid portable alias pattern for ' . $bundle . '.');
}
$native_views = [
  'block_content' => ['views.view.block_content'],
  'node' => ['views.view.content', 'views.view.glossary'],
  'file' => ['views.view.files'],
  'media' => ['views.view.media'],
  'media_library' => ['views.view.media_library'],
  'taxonomy' => ['views.view.taxonomy_term'],
  'user' => ['views.view.who_s_new', 'views.view.who_s_online'],
  'login_history' => ['views.view.login_history'],
];
foreach ($native_views as $provider => $names) {
  foreach ($names as $name) {
    $check(in_array($name, $content['config']['import'][$provider] ?? [], TRUE), 'Missing native management View: ' . $provider . ' / ' . $name);
    $check(!is_file($root . '/web/recipes/sous-starter-content/config/' . $name . '.yml'), 'Native provider defaults must not be copied into the starter: ' . $name);
  }
}
$check($content['config']['strict'] === FALSE, 'Starter defaults must preserve existing project customizations.');
$check(!is_file($root . '/web/recipes/sous-starter-content/config/user.role.superuser.yml'), 'Superuser authorization must remain owned by Sous Admin.');
foreach (['sous-starter-content', 'sous-paragraphs'] as $prerequisite) {
  $check(in_array($prerequisite, $paragraphs['recipes'], TRUE), 'The bundled Paragraphs option must compose ' . $prerequisite . '.');
}
$field = $read('sous-starter-paragraphs/config/field.field.node.frontpage.field_content.yml');
$check(in_array('field.storage.node.field_content', $field['dependencies']['config'], TRUE) && $field['field_type'] === 'entity_reference_revisions', 'Frontpage Paragraphs must reuse the Page host storage.');
foreach (['page', 'frontpage'] as $bundle) {
  $form_action = $paragraphs['config']['actions']['core.entity_form_display.node.' . $bundle . '.default'];
  $check($form_action['simpleConfigUpdate']['third_party_settings.field_group.group_content.children'] === ['body', 'field_content'], 'Paragraphs must retain Body and components in the ' . $bundle . ' Content tab.');
  $actions = $layout['config']['actions']['core.entity_view_display.node.' . $bundle . '.default'];
  $check(array_key_exists('enableLayoutBuilder', $actions) && $actions['allowLayoutOverrides'] === TRUE, 'Native Layout Builder must support ' . $bundle . ' and per-node overrides.');
}
$frontpage = $paragraphs['config']['actions']['core.entity_view_display.node.frontpage.default']['setComponent'];
$check($frontpage['name'] === 'field_content' && $frontpage['options']['type'] === 'entity_reference_revisions_entity_view', 'Frontpage Paragraphs must have a visible component formatter.');
foreach (['sous-starter-content', 'sous-starter-paragraphs'] as $recipe) {
  foreach (glob($root . '/web/recipes/' . $recipe . '/config/*.yml') as $file) {
    $data = Yaml::parseFile($file);
    $check(!array_key_exists('uuid', $data) && !isset($data['_core']['default_config_hash']), 'Site export metadata must not leak into reusable starter defaults: ' . basename($file));
  }
}
print "PASS: bundled Page/Frontpage defaults, shared field ownership, aliases, native Views, Admin role ownership, Paragraphs composition and Layout Builder support; no site/database changed.\n";
