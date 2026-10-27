<?php

declare(strict_types=1);

/** Assert Composer and on-disk boundaries before loading disposable Drupal. */

if (getenv('SOUS_DISPOSABLE_TEST') !== '1') {
  throw new RuntimeException('Only run in a disposable consumer fixture.');
}
$selection = getenv('SOUS_INTEGRATION_SELECTION');
$split = getenv('SOUS_INTEGRATION_SPLIT') === 'split';
$check = static function (bool $condition, string $message): void {
  if (!$condition) {
    throw new RuntimeException($message);
  }
};
$names = ['sous-admin', 'sous-config-split', 'sous-content-types', 'sous-core-layout-builder', 'sous-emulsify', 'sous-formats', 'sous-media', 'sous-paragraph-types', 'sous-paragraphs'];
$ids = array_map(static fn (string $name): string => 'fourkitchens/' . $name, $names);
$manifest = json_decode(file_get_contents('composer.json'), TRUE, 512, JSON_THROW_ON_ERROR);
$lock = json_decode(file_get_contents('composer.lock'), TRUE, 512, JSON_THROW_ON_ERROR);
$expected = ['fourkitchens/sous-admin', 'fourkitchens/sous-emulsify'];
$composition = match ($selection) {
  'default' => 'sous-content-types',
  'paragraphs' => 'sous-paragraphs',
  'layout-builder' => 'sous-core-layout-builder',
  'admin-only' => NULL,
  default => throw new InvalidArgumentException('Unknown composition.'),
};
if ($composition) {
  $expected[] = 'fourkitchens/' . $composition;
}
if ($split) {
  $expected[] = 'fourkitchens/sous-config-split';
}
foreach ($expected as $name) {
  $check(isset($manifest['require'][$name]), 'Native recipe unpacking lost selected root requirement: ' . $name);
}
$source = dirname(__DIR__, 2) . '/.integration/packages/packages/';
for ($index = 0; $index < count($expected); $index++) {
  $dependency = json_decode(file_get_contents($source . basename($expected[$index]) . '/composer.json'), TRUE, 512, JSON_THROW_ON_ERROR);
  foreach (array_keys($dependency['require'] ?? []) as $name) {
    if (in_array($name, $ids, TRUE) && !in_array($name, $expected, TRUE)) {
      $expected[] = $name;
    }
  }
}
$actual = [];
foreach ($lock['packages'] as $package) {
  if (!in_array($package['name'], $ids, TRUE)) {
    continue;
  }
  $actual[] = $package['name'];
  $check(preg_match('/^2\.[0-9]+\.[0-9]+$/', $package['version']) === 1 && ($package['dist']['type'] ?? '') === 'zip' && !isset($package['source']), 'Recipes must retain stable dist-only lock records: ' . $package['name']);
  $check(is_file('web/recipes/' . basename($package['name']) . '/recipe.yml'), 'Missing Composer-managed recipe files: ' . $package['name']);
  $check(!is_dir('web/recipes/' . basename($package['name']) . '/tests'), 'Maintainer probes leaked into consumer ZIP.');
}
sort($expected);
sort($actual);
$check($actual === $expected, 'Composer installed an unselected recipe or lost a required recipe.');
$directories = array_map('basename', glob('web/recipes/*', GLOB_ONLYDIR));
$expected_directories = array_map('basename', $expected);
sort($directories);
sort($expected_directories);
$check($directories === $expected_directories, 'An unselected recipe directory was copied into the consumer.');
$ignored = $manifest['extra']['drupal-recipe-unpack']['ignore'] ?? [];
sort($ignored);
sort($ids);
$check($ignored === $ids, 'Keep the exact nine native unpack exclusions, without requiring every package.');
if ($selection !== 'paragraphs') {
  foreach ($lock['packages'] as $package) {
    $check(!preg_match('@^drupal/paragraphs(?:_|$)@', $package['name']), 'Unselected Paragraphs module resolved.');
  }
  foreach (['paragraphs', 'paragraphs_ee', 'paragraphs_features', 'paragraphs_collapsible'] as $module) {
    $check(!is_dir('web/modules/contrib/' . $module), 'Unselected Paragraphs module directory exists: ' . $module);
  }
}
$has_split = in_array('drupal/config_split', array_column($lock['packages'], 'name'), TRUE);
$check($has_split === $split && is_dir('web/modules/contrib/config_split') === $split, 'Config Split must remain independently optional.');
print json_encode(['result' => 'pass', 'selection' => $selection, 'config_split' => $split, 'retained_recipes' => $actual, 'dist_only' => TRUE], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
