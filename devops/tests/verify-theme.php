<?php

declare(strict_types=1);

/**
 * @file
 * Check the generated stock theme and its native production build.
 *
 * Run through Drush after theme generation/build/enablement in a disposable
 * fixture. Component libraries, Twig slots and project-specific global assets
 * belong to the selected upstream system/site; this probe does not rewrite or
 * assume their structure.
 */

$theme = \Drupal::config('system.theme')->get('default');
$extension = \Drupal::service('theme_handler')->getTheme($theme);
$directory = DRUPAL_ROOT . '/' . $extension->getPath();
$metadata = json_decode(file_get_contents($directory . '/project.emulsify.json'), TRUE, 512, JSON_THROW_ON_ERROR);
if (($metadata['project']['machineName'] ?? NULL) !== $theme) {
  throw new RuntimeException('Generated theme metadata does not match the enabled theme.');
}

$assets = ['css' => 0, 'js' => 0];
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory . '/dist', FilesystemIterator::SKIP_DOTS));
foreach ($files as $file) {
  $type = $file->getExtension();
  if ($file->isFile() && array_key_exists($type, $assets)) {
    $assets[$type]++;
  }
}
if (!$assets['css'] || !$assets['js']) {
  throw new RuntimeException('The native theme build did not produce CSS and JavaScript assets.');
}

echo json_encode(['theme' => $theme, 'enabled' => TRUE, 'native_assets' => $assets], JSON_THROW_ON_ERROR) . PHP_EOL;
