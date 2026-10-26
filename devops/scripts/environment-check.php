<?php

declare(strict_types=1);

/**
 * @file
 * Check the native configuration storages used by Sous export/deployment.
 *
 * Called through project Drush by _runtime.sh; requires a bootstrapped Drupal
 * site and the project-owned settings.sous.php environment contract.
 * SOUS_ENVIRONMENT_CHECK selects export, import, active, or development mode.
 * A failed assertion throws so the shell caller can stop dependent work. This
 * script does not import, repair or enable configuration; native storage
 * transformations may have their normal Config Split filesystem side effects.
 */

use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Site\Settings;

$environment = Settings::get('sous_environment');
// Require adopted project settings rather than inferring safety from a hostname
// or a local file. The caller must also choose a mode explicitly.
if (!in_array($environment, ['local', 'nonproduction', 'production'], TRUE)) {
  throw new RuntimeException('Adopt the Sous environment include in this project before using its configuration helpers.');
}
$mode = getenv('SOUS_ENVIRONMENT_CHECK');
if (!in_array($mode, ['export', 'import', 'active', 'development'], TRUE)) {
  throw new InvalidArgumentException('Unknown Sous environment check mode.');
}
if ($mode === 'development') {
  // This narrow mode checks environment eligibility only, not stored config.
  if ($environment === 'production') {
    throw new RuntimeException('Database restoration and development setup are unavailable in production.');
  }
  print "PASS: Sous development environment.\n";
  return;
}

// This is a minimum safety list, not a classification of every contrib module.
// Projects add their own development-only modules in tracked settings.
$development_modules = array_unique(array_merge([
  'devel', 'devel_generate', 'webprofiler', 'stage_file_proxy',
  'reroute_email', 'devel_mail_log',
], Settings::get('sous_development_modules', [])));
$capture_backends = ['test_mail_collector', 'devel_mail_log'];
$excluded = Settings::get('config_exclude_modules', []);
// Core's exclusion behavior can retain an already-enabled module on import;
// it cannot serve as a production uninstall policy for development modules.
if ($environment === 'production' && array_intersect($excluded, $development_modules)) {
  throw new RuntimeException('Production cannot exclude development modules from import: core exclusions preserve active modules.');
}

$active = \Drupal::service('config.storage');
if (\Drupal::moduleHandler()->moduleExists('config_split')) {
  // Use effective definitions, including runtime status overrides. Inspect
  // folders before asking native transforms to create or read their storage.
  $assert_split = static function (ImmutableConfig $split) use ($excluded): void {
    if (array_intersect($excluded, array_keys($split->get('module') ?: []))) {
      throw new RuntimeException('A module cannot be owned by Config Split and core config_exclude_modules simultaneously.');
    }
    // Inactive optional splits do not require their folders in an artifact.
    if (!$split->get('status') || $split->get('storage') !== 'folder') {
      return;
    }
    $folder = (string) $split->get('folder');
    $directory = str_starts_with($folder, '/') ? $folder : DRUPAL_ROOT . '/' . $folder;
    $real = realpath($directory);
    if ($folder === '' || $real === FALSE || !is_dir($real)) {
      throw new RuntimeException('The active split directory must exist before import/export: ' . $split->get('id'));
    }
    if (str_starts_with($real . '/', realpath(DRUPAL_ROOT) . '/')) {
      throw new RuntimeException('Store active split configuration outside the public web root: ' . $split->get('id'));
    }
  };
  foreach ($active->listAll('config_split.config_split.') as $name) {
    $assert_split(\Drupal::config($name));
  }
  if ($mode === 'import') {
    // Use the same incoming definitions and runtime overrides as Config Split's
    // native importer, before its folder storage can create a missing path.
    // This uses the selected Config Split 2.x manager API; review it on upgrades.
    $manager = \Drupal::service('config_split.manager');
    $source = \Drupal::service('config.storage.sync');
    foreach ($manager->listAll($source) as $name) {
      $split = $manager->getSplitConfig($name, $source);
      if ($split !== NULL) {
        $assert_split($split);
      }
    }
  }
}

// A complete production-safe base is shared by all environments. Runtime
// overrides and active splits may differ; those differences must not leak into
// the base export. Accept a storage so each mode can inspect its native source.
$assert_base = static function (StorageInterface $storage) use ($development_modules, $capture_backends): void {
  $extensions = $storage->read('core.extension');
  if (!$extensions || !$storage->exists('system.site')) {
    throw new RuntimeException('Use a complete site export with core.extension and system.site.');
  }
  $unsafe_modules = array_intersect(array_keys($extensions['module'] ?? []), $development_modules);
  if ($unsafe_modules) {
    throw new RuntimeException('Development modules must not enter the production base: ' . implode(', ', $unsafe_modules));
  }
  foreach (array_merge([''], $storage->getAllCollectionNames()) as $collection) {
    // Include translated/other config collections, not just default-language
    // values, so development defaults cannot hide in a secondary collection.
    $data = $storage->createCollection($collection);
    $logging = $data->read('system.logging') ?: [];
    if (in_array($logging['error_level'] ?? NULL, ['all', 'verbose'], TRUE)) {
      throw new RuntimeException('Development error display belongs in runtime overrides, not the production base.');
    }
    $performance = $data->read('system.performance') ?: [];
    if (($performance['css']['preprocess'] ?? TRUE) === FALSE || ($performance['js']['preprocess'] ?? TRUE) === FALSE) {
      throw new RuntimeException('Disabled asset aggregation must not become a production base default.');
    }
    foreach (($data->read('system.mail') ?: [])['interface'] ?? [] as $backend) {
      if (in_array($backend, $capture_backends, TRUE)) {
        throw new RuntimeException('Mail capture belongs in nonproduction runtime overrides, not the production base.');
      }
    }
    $proxy = $data->read('stage_file_proxy.settings') ?: [];
    if (!empty($proxy['origin']) || !empty($proxy['hotlink'])) {
      throw new RuntimeException('File proxying must not be configured in the production base.');
    }
    foreach (['sous_local', 'sous_nonproduction'] as $id) {
      if (!empty(($data->read('config_split.config_split.' . $id) ?: [])['status'])) {
        throw new RuntimeException('Store optional split statuses as inactive; select them through the environment override.');
      }
    }
    foreach (($data->read('config_ignore.settings') ?: [])['ignored_config_entities'] ?? [] as $rule) {
      // Wildcards and whole-site ignores can hide drift from normal deployment.
      if (strpbrk((string) $rule, '*?') !== FALSE || in_array($rule, ['core.extension', 'system.site'], TRUE)) {
        throw new RuntimeException('Broad Config Ignore rules conceal deployment drift; use explicit runtime overrides or splits.');
      }
    }
  }
};

// Select the storage matching the operation: transformed export, sync input,
// or production's current active config. Nonproduction active state may contain
// intentionally split-owned modules, so its base is checked during export/import.
if ($mode === 'export') {
  // Native export transformations include Config Split when it is installed.
  $assert_base(\Drupal::service('config.storage.export'));
}
elseif ($mode === 'import') {
  $sync = \Drupal::service('config.storage.sync');
  $assert_base($sync);
  if ($environment === 'production') {
    // Validate the same transformed source the native importer will use.
    $assert_base(\Drupal::service('config.import_transformer')->transform($sync));
  }
}
elseif ($environment === 'production') {
  $assert_base($active);
}

// Stored config alone cannot prove runtime safety: inspect effective compiled
// services and settings too, especially after restoring a local database copy.
if ($environment !== 'local') {
  $twig = \Drupal::getContainer()->getParameter('twig.config');
  if (!empty($twig['debug']) || ($twig['cache'] ?? TRUE) === FALSE
    || \Drupal::getContainer()->getParameter('http.response.debug_cacheability_headers')
    || \Drupal::service('cache.render') instanceof \Drupal\Core\Cache\NullBackend
    || Settings::get('rebuild_access', FALSE)) {
    throw new RuntimeException('Non-local runtime still has development debugging/cache services enabled.');
  }
}
$interfaces = \Drupal::config('system.mail')->get('interface') ?: [];
// Check every native mail mapping: a per-module transport can override the
// default. Production retains real delivery; nonproduction must capture it.
if ($environment === 'production') {
  if (array_intersect($interfaces, $capture_backends)
    || \Drupal::config('stage_file_proxy.settings')->get('origin')) {
    throw new RuntimeException('Production must not capture mail or proxy files.');
  }
}
else {
  // Runtime overrides keep automatic cron disabled without exporting that local
  // choice into the shared production base, including when the module is absent.
  if (\Drupal::config('automated_cron.settings')->get('interval') !== 0) {
    throw new RuntimeException('Nonproduction automated cron must remain disabled through runtime settings.');
  }
  foreach ($interfaces as $backend) {
    if ($backend !== 'test_mail_collector') {
      throw new RuntimeException('Review specific mail transports: every nonproduction Drupal mail mapping must use the safe collector.');
    }
  }
}
print 'PASS: Sous ' . $environment . ' configuration ' . $mode . " check.\n";
