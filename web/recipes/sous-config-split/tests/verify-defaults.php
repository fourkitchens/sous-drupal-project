<?php

declare(strict_types=1);

/**
 * Verify freshly applied optional split defaults in a disposable installation.
 */

use Symfony\Component\Yaml\Yaml;

if (getenv('SOUS_DISPOSABLE_TEST') !== '1') {
  throw new RuntimeException('Set SOUS_DISPOSABLE_TEST=1 only for a disposable installation.');
}
if (!\Drupal::moduleHandler()->moduleExists('config_split')) {
  throw new RuntimeException('Apply the optional Config Split recipe first.');
}
foreach (['_splits', 'sous_local', 'sous_staging'] as $id) {
  $name = 'config_split.config_split.' . $id;
  $expected = Yaml::parseFile(dirname(__DIR__) . '/config/' . $name . '.yml');
  $actual = \Drupal::configFactory()->getEditable($name)->getRawData();
  foreach ($expected as $key => $value) {
    if (($actual[$key] ?? NULL) !== $value) {
      throw new RuntimeException('Fresh split default differs: ' . $name . ' / ' . $key);
    }
  }
  $violations = \Drupal::service('config.typed')->createFromNameAndData($name, $actual)->validate();
  if (count($violations) !== 0) {
    throw new RuntimeException('Invalid native split configuration schema: ' . $name . ': ' . (string) $violations);
  }
  if (!is_dir(DRUPAL_ROOT . '/' . $actual['folder'])) {
    throw new RuntimeException('The project-owned split directory is missing: ' . $id);
  }
}
print "PASS: optional definition/local/staging split defaults, native schema, and tracked directories.\n";
