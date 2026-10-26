<?php

declare(strict_types=1);

/**
 * Validate checked-out source snapshots, not Composer's temporary path overrides.
 *
 * php devops/integration/validate-recipes.php /snapshots/sous-*
 * SOUS_INTEGRATION_AUTOLOAD may select a disposable project's vendor/autoload.php.
 */

use Composer\Semver\VersionParser;
use Symfony\Component\Yaml\Yaml;

$autoload = getenv('SOUS_INTEGRATION_AUTOLOAD') ?: dirname(__DIR__, 2) . '/vendor/autoload.php';
if (!is_file($autoload)) {
  throw new RuntimeException('Install Composer dependencies or set SOUS_INTEGRATION_AUTOLOAD.');
}
require $autoload;

$check = static function (bool $condition, string $message): void {
  if (!$condition) {
    throw new RuntimeException($message);
  }
};
$directories = [];
foreach (array_slice($argv, 1) as $argument) {
  $directory = realpath($argument);
  $check($directory !== FALSE && is_dir($directory), 'Missing snapshot directory: ' . $argument);
  if (is_file($directory . '/composer.json')) {
    $directories[] = $directory;
  }
  else {
    foreach (glob($directory . '/*/composer.json') as $manifest) {
      $directories[] = dirname($manifest);
    }
  }
}
$directories = array_values(array_unique($directories));
$check($directories !== [], 'Pass repository directories or a parent containing their snapshots.');

// These identifiers belong to plugin instances and embedded assets. Check their
// relationships without treating every nested UUID as exported site metadata.
$inspect_identifiers = static function (array $data, string $source) use (&$inspect_identifiers, $check): void {
  foreach ($data as $key => $value) {
    if ($key === 'matchers' && is_array($value)) {
      foreach ($value as $id => $matcher) {
        $check(is_array($matcher) && ($matcher['uuid'] ?? NULL) === $id, 'Invalid Linkit matcher identifier in ' . $source);
      }
    }
    if (is_string($key) && str_starts_with($key, 'matchers.') && is_array($value)) {
      $check(($value['uuid'] ?? NULL) === substr($key, 9), 'Invalid recipe Linkit matcher identifier in ' . $source);
    }
    if (is_array($value)) {
      $inspect_identifiers($value, $source);
    }
  }
  if (isset($data['icon_default']) && $data['icon_default'] !== '') {
    $uuid = $data['icon_uuid'] ?? '';
    $check(is_string($uuid) && preg_match('/^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$/i', $uuid) === 1, 'Missing portable icon UUID in ' . $source);
    $check(in_array('file:file:' . $uuid, $data['dependencies']['content'] ?? [], TRUE), 'Missing portable icon file dependency in ' . $source);
    $prefix = 'data:image/png;base64,';
    $check(is_string($data['icon_default']) && str_starts_with($data['icon_default'], $prefix), 'Unsupported embedded icon in ' . $source);
    $bytes = base64_decode(substr($data['icon_default'], strlen($prefix)), TRUE);
    $check($bytes !== FALSE && str_starts_with($bytes, "\x89PNG\r\n\x1a\n"), 'Invalid embedded PNG in ' . $source);
  }
};

$inspect_recipe = static function (string $directory) use ($check, $inspect_identifiers): array {
  $recipe_file = $directory . '/recipe.yml';
  $check(is_file($recipe_file), 'Missing recipe.yml: ' . $directory);
  $recipe = Yaml::parseFile($recipe_file);
  $check(is_array($recipe) && is_string($recipe['name'] ?? NULL) && is_string($recipe['type'] ?? NULL), 'Invalid recipe metadata: ' . $directory);
  foreach (['recipes', 'install'] as $key) {
    $values = $recipe[$key] ?? [];
    $check(is_array($values) && array_is_list($values) && count(array_filter($values, 'is_string')) === count($values), 'Invalid recipe list ' . $key . ': ' . $directory);
  }
  $inspect_identifiers($recipe, $recipe_file);
  $files = glob($directory . '/config/*.yml');
  foreach ($files as $file) {
    $data = Yaml::parseFile($file);
    $check(is_array($data), 'Invalid configuration mapping: ' . $file);
    $check(!array_key_exists('uuid', $data) && !isset($data['_core']['default_config_hash']), 'Exported site metadata in ' . $file);
    $inspect_identifiers($data, $file);
  }
  return [$recipe, count($files)];
};

$parser = new VersionParser();
$recipes = $results = [];
foreach ($directories as $directory) {
  $manifest = json_decode(file_get_contents($directory . '/composer.json'), TRUE, 512, JSON_THROW_ON_ERROR);
  $name = $manifest['name'] ?? '';
  $check(is_string($name) && $name !== '', 'Missing Composer package name: ' . $directory);
  $check(($manifest['minimum-stability'] ?? 'stable') === 'stable', 'Broad development stability in source package: ' . $name);
  foreach ($manifest['repositories'] ?? [] as $repository) {
    $check(($repository['type'] ?? NULL) !== 'path', 'Temporary path repository leaked into source: ' . $name);
  }
  foreach (array_merge($manifest['require'] ?? [], $manifest['require-dev'] ?? []) as $package => $constraint) {
    $check(is_string($constraint) && $constraint !== '' && $constraint !== '*' && !preg_match('/^>\s*=?\s*[^\s]+$/', $constraint), 'Unbounded direct requirement: ' . $name . ' / ' . $package);
    $parser->parseConstraints($constraint);
  }
  if (($manifest['type'] ?? NULL) !== 'drupal-recipe') {
    $check(($manifest['type'] ?? NULL) === 'project', 'Unexpected Composer package type: ' . $name);
    $count = 0;
    $bundled = ['sous-layout-builder', 'sous-config-split'];
    foreach ($bundled as $bundle) {
      [$recipe, $files] = $inspect_recipe($directory . '/web/recipes/' . $bundle);
      $recipes[$bundle] = $recipe;
      $count += $files;
    }
    $results[$name] = ['type' => 'project', 'configuration_files' => $count, 'bundled_recipes' => $bundled];
    continue;
  }
  [$recipe, $count] = $inspect_recipe($directory);
  $recipes[basename($directory)] = $recipe;
  $bundled = [];
  foreach (array_merge(glob($directory . '/recipes/*/recipe.yml'), glob($directory . '/migrations/*/recipe.yml')) as $file) {
    $relative = substr(dirname($file), strlen($directory) + 1);
    $key = 'recipes/' . basename($directory) . '/' . $relative;
    [$child, $child_count] = $inspect_recipe(dirname($file));
    $recipes[$key] = $child;
    $bundled[] = $key;
    $count += $child_count;
  }
  $results[$name] = ['type' => 'drupal-recipe', 'configuration_files' => $count, 'prerequisite_recipes' => $recipe['recipes'] ?? [], 'bundled_recipes' => $bundled];
}
foreach ($recipes as $name => $recipe) {
  foreach ($recipe['recipes'] ?? [] as $prerequisite) {
    $check(str_starts_with($prerequisite, 'core/recipes/') || isset($recipes[$prerequisite]), 'Missing prerequisite snapshot: ' . $name . ' requires ' . $prerequisite);
  }
}
ksort($results);
print json_encode(['result' => 'pass', 'repositories' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
