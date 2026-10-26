<?php

declare(strict_types=1);

/**
 * Exercise an installed disposable Sous site using its versioned recipe checks.
 *
 * SOUS_DISPOSABLE_TEST=1 SOUS_INTEGRATION_COMBINATION=default \
 *   vendor/bin/drush --uri=https://SITE php:script devops/integration/verify-site.php
 * Other combinations: paragraphs, core-layout-builder, admin-only.
 */

use Drupal\Core\Config\DatabaseStorage;
use Drupal\Core\Site\Settings;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder\Section;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

if (getenv('SOUS_DISPOSABLE_TEST') !== '1') {
  throw new RuntimeException('Set SOUS_DISPOSABLE_TEST=1 only for a disposable installation.');
}
$combination = getenv('SOUS_INTEGRATION_COMBINATION') ?: 'default';
if (!in_array($combination, ['default', 'paragraphs', 'core-layout-builder', 'admin-only'], TRUE)) {
  throw new InvalidArgumentException('Unknown integration combination: ' . $combination);
}
$recipes_directory = getenv('SOUS_INTEGRATION_RECIPES_DIR') ?: DRUPAL_ROOT . '/recipes';
$recipes = $combination === 'admin-only'
  ? ['sous-admin']
  : ['sous-admin', 'sous-emulsify', 'sous-content-types', 'sous-formats', 'sous-media'];
if ($combination === 'paragraphs') {
  $recipes = array_merge($recipes, ['sous-paragraph-types', 'sous-paragraphs']);
}
if ($combination === 'core-layout-builder') {
  $recipes[] = 'sous-layout-builder';
}
$checked_recipes = [];
$verify_recipe = static function (string $name) use (&$verify_recipe, &$checked_recipes, $recipes_directory): void {
  if (isset($checked_recipes[$name])) {
    return;
  }
  $directory = str_contains($name, '/') ? DRUPAL_ROOT . '/' . $name : $recipes_directory . '/' . $name;
  $path = $directory . '/recipe.yml';
  if (!is_file($path)) {
    throw new RuntimeException('Missing applied recipe snapshot: ' . $path);
  }
  $recipe = Yaml::parseFile($path);
  $checked_recipes[$name] = TRUE;
  foreach ($recipe['recipes'] ?? [] as $prerequisite) {
    $verify_recipe($prerequisite);
  }
  foreach ($recipe['install'] ?? [] as $extension) {
    if (!\Drupal::moduleHandler()->moduleExists($extension) && !\Drupal::service('theme_handler')->themeExists($extension)) {
      throw new RuntimeException('Recipe extension is not installed: ' . $name . ' / ' . $extension);
    }
  }
};
foreach ($recipes as $name) {
  $verify_recipe($name);
}
if (\Drupal::config('system.theme')->get('admin') !== 'gin') {
  throw new RuntimeException('Sous Admin did not select Gin as the administration theme.');
}
if ($combination !== 'admin-only') {
  $theme = \Drupal::config('system.theme')->get('default');
  $active_theme = \Drupal::service('theme.initialization')->getActiveThemeByName($theme);
  if ($theme !== 'emulsify' && !isset($active_theme->getBaseThemeExtensions()['emulsify'])) {
    throw new RuntimeException('The frontend theme must be Emulsify or inherit from it.');
  }
  foreach (['file', 'image', 'video'] as $bundle) {
    if (!\Drupal::entityTypeManager()->getStorage('media_type')->load($bundle)) {
      throw new RuntimeException('Missing Sous Media bundle: ' . $bundle);
    }
  }
}
if ($combination === 'paragraphs' && !\Drupal::entityTypeManager()->getStorage('field_config')->load('node.page.field_content')) {
  throw new RuntimeException('Apply the public Sous Paragraphs host recipe before the Paragraphs check.');
}
if ($combination === 'core-layout-builder') {
  if (\Drupal::entityTypeManager()->getStorage('field_config')->load('node.page.field_content')) {
    throw new RuntimeException('Use a baseline without the Paragraphs host field for the core Layout Builder check.');
  }
  foreach (['layout_builder', 'layout_discovery'] as $extension) {
    if (!\Drupal::moduleHandler()->moduleExists($extension)) {
      throw new RuntimeException('Enable core Layout Builder and Layout Discovery before the composition check.');
    }
  }
}

$checks = [];
$run = static function (string $relative) use ($recipes_directory, &$checks): void {
  $path = $recipes_directory . '/' . $relative;
  if (!is_file($path)) {
    throw new RuntimeException('Missing versioned regression check: ' . $relative);
  }
  ob_start();
  try {
    (static function (string $file): void { require $file; })($path);
    $output = trim(ob_get_contents());
  }
  finally {
    ob_end_clean();
  }
  $checks[] = ['script' => $relative, 'output' => $output];
};
$run('sous-admin/tests/verify-navigation.php');
if ($combination !== 'admin-only') {
  $run('sous-formats/tests/verify-formats.php');
}
// The standalone Page check intentionally precedes composition: the public
// Paragraphs host can require field_content, which that check does not supply.
if ($combination === 'default') {
  $run('sous-content-types/tests/verify-page.php');
}
if ($combination === 'paragraphs') {
  $run('sous-content-types/tests/verify-composition.php');
}
if ($combination === 'paragraphs') {
  $run('sous-paragraph-types/tests/verify-paragraph-types.php');
}
if ($combination === 'core-layout-builder') {
  $assert = static function (bool $condition, string $message): void {
    if (!$condition) {
      throw new RuntimeException($message);
    }
  };
  $assert(\Drupal::service('plugin.manager.block')->hasDefinition('inline_block:basic'), 'The native Basic block must be available in the layout authoring UI.');
  $assert(\Drupal::service('plugin.manager.block')->hasDefinition('field_block:node:page:body'), 'The saved native Body field block is not registered after recipe application.');
  $display = \Drupal::entityTypeManager()->getStorage('entity_view_display')->load('node.page.default');
  $assert($display instanceof LayoutBuilderEntityViewDisplay && $display->isLayoutBuilderEnabled() && $display->isOverridable(), 'The offered Page layout must remain enabled and allow per-page overrides.');
  $sections = $display->getSections();
  $body = FALSE;
  foreach ($sections as $section) {
    foreach ($section->getComponents() as $component) {
      $configuration = $component->toArray()['configuration'];
      $body = $body || ($configuration['id'] === 'field_block:node:page:body' && ($configuration['formatter']['type'] ?? NULL) === 'text_default');
    }
  }
  $assert($body, 'The native default layout lost the visible Body field block.');
  $original_sections = array_map(static fn (Section $section): array => $section->toArray(), $sections);
  $page = NULL;
  $account_switcher = \Drupal::service('account_switcher');
  $account_switcher->switchTo(User::load(1));
  try {
    $marker = 'Native layout ' . bin2hex(random_bytes(6));
    $page = Node::create(['type' => 'page', 'title' => $marker, 'field_seo_title' => $marker, 'body' => ['value' => '<p>' . $marker . '</p>', 'format' => 'basic_html'], 'status' => 1, 'uid' => 1]);
    $form = \Drupal::service('entity.form_builder')->getForm($page);
    $assert(isset($form['body']['widget']) && $page->hasField('layout_builder__layout'), 'Page Body authoring or native layout override field is missing.');
    $assert(count($page->validate()) === 0, 'New Layout Builder Page failed validation.');
    $page->save();
    $render = static function (Node $node): string {
      $build = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node);
      return (string) \Drupal::service('renderer')->renderInIsolation($build);
    };
    $assert(str_contains($render($page), '<p>' . $marker . '</p>'), 'The offered default layout did not render saved Body content.');
    $overrides = array_map(static fn (array $section): Section => Section::fromArray($section), $original_sections);
    $overrides[0]->setLayoutSettings(['label' => $marker] + $overrides[0]->getLayoutSettings());
    $page->set('layout_builder__layout', array_map(static fn (Section $section): array => ['section' => $section], $overrides));
    $page->setTitle('Edited ' . $marker);
    $page->set('body', ['value' => '<p>Edited ' . $marker . '</p>', 'format' => 'basic_html']);
    $page->setNewRevision(TRUE);
    $assert(count($page->validate()) === 0, 'Edited per-page layout failed validation.');
    $page->save();
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$page->id()]);
    $page = Node::load($page->id());
    $assert($page->get('layout_builder__layout')->first()->section->getLayoutSettings()['label'] === $marker, 'Per-page layout customization did not survive saving.');
    $assert(str_contains($render($page), '<p>Edited ' . $marker . '</p>'), 'Edited Body did not render through the saved per-page layout.');
    $unchanged = \Drupal::entityTypeManager()->getStorage('entity_view_display')->loadUnchanged('node.page.default');
    $assert($unchanged->isLayoutBuilderEnabled() && $unchanged->isOverridable() && array_map(static fn (Section $section): array => $section->toArray(), $unchanged->getSections()) === $original_sections, 'Per-page customization changed the enabled default layout.');
    $checks[] = ['script' => 'native Layout Builder', 'output' => 'PASS: enabled default Body layout, Page form/create/edit/save/render, saved per-page customization, and unchanged default sections.'];
  }
  finally {
    if ($page && !$page->isNew()) {
      $page->delete();
    }
    $account_switcher->switchBack();
  }
}

// Compare every active collection across native Drush export/import. Mapping key
// order is irrelevant; ordered configuration lists must remain unchanged.
$normalize = static function (mixed $value) use (&$normalize): mixed {
  if (is_array($value)) {
    $value = array_map($normalize, $value);
    if (!array_is_list($value)) {
      ksort($value);
    }
  }
  return $value;
};
$snapshot = static function () use ($normalize): array {
  // Read uncached active storage after the separate Drush import process.
  $storage = new DatabaseStorage(\Drupal::database(), 'config');
  $result = [];
  foreach (array_merge([''], $storage->getAllCollectionNames()) as $collection) {
    $collection_storage = $storage->createCollection($collection);
    $result[$collection] = $normalize($collection_storage->readMultiple($collection_storage->listAll()));
  }
  ksort($result);
  return $result;
};
$before = $snapshot();
$directory = Settings::get('config_sync_directory');
if (!$directory) {
  throw new RuntimeException('The generated site has no project-owned configuration sync directory.');
}
$directory = str_starts_with($directory, '/') ? $directory : DRUPAL_ROOT . '/' . $directory;
$project_root = dirname(DRUPAL_ROOT);
if (realpath($directory) !== realpath($project_root . '/config/default')) {
  throw new RuntimeException('The fresh starter must use its tracked project-owned config/default directory.');
}
if (Settings::get('sous_integration_sentinel') !== 'preserved') {
  throw new RuntimeException('The preserved settings.local.php file must also be loaded.');
}
// Composer also installs a shell proxy named "drush"; PHP must use this proxy.
$drush = $project_root . '/vendor/bin/drush.php';
$uri = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBasePath();
foreach ([['config:export'], ['deploy']] as $arguments) {
  $process = new Process(array_merge([PHP_BINARY, $drush, '--root=' . DRUPAL_ROOT, '--uri=' . $uri], $arguments, ['--yes']), $project_root);
  $process->setTimeout(180);
  $process->mustRun();
  $checks[] = ['command' => $arguments[0], 'output' => trim($process->getOutput() . $process->getErrorOutput())];
  if ($arguments[0] === 'config:export') {
    if (!is_file($directory . '/system.site.yml') || !is_file($directory . '/core.extension.yml')) {
      throw new RuntimeException('Native configuration export did not create a complete site export.');
    }
    \Drupal::configFactory()->getEditable('system.site')->set('name', 'Sous import regression ' . bin2hex(random_bytes(8)))->save();
    if ($before === $snapshot()) {
      throw new RuntimeException('Temporary configuration change was not saved before import.');
    }
  }
}
if ($before !== $snapshot()) {
  throw new RuntimeException('Native configuration export/import changed active configuration.');
}
print json_encode([
  'result' => 'pass',
  'combination' => $combination,
  'drupal' => \Drupal::VERSION,
  'php' => PHP_VERSION,
  'site_uri' => $uri,
  'verified_recipes' => array_keys($checked_recipes),
  'configuration_collections' => count($before),
  'configuration_round_trip_unchanged' => TRUE,
  'configuration_import_restored_temporary_change' => TRUE,
  'project_owned_sync_directory' => realpath($directory),
  'native_drush_deploy_verified' => TRUE,
  'checks' => $checks,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
