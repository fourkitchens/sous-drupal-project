<?php

declare(strict_types=1);

/**
 * Exercise native environment behavior in the disposable integration fixture.
 *
 * Run seed, rebuild caches, verify, snapshot, confex, mutate, confim, roundtrip.
 * With the optional recipe, run split-prepare once before the shared database
 * baseline, split-configure in local/staging, and split-verify after
 * export/import. The integration runner owns database resets and file copies.
 */

use Drupal\Core\Config\DatabaseStorage;
use Drupal\Core\Mail\Plugin\Mail\TestMailCollector;
use Drupal\Core\Site\Settings;
use Symfony\Component\Yaml\Yaml;

if (getenv('SOUS_DISPOSABLE_TEST') !== '1') {
  throw new RuntimeException('Set SOUS_DISPOSABLE_TEST=1 only for a disposable installation.');
}
$environment = getenv('SOUS_ENVIRONMENT');
if (!in_array($environment, ['local', 'staging', 'production'], TRUE)) {
  throw new InvalidArgumentException('Select a supported SOUS_ENVIRONMENT for this test.');
}
$stage = getenv('SOUS_ENVIRONMENT_TEST');
if (!in_array($stage, ['seed', 'verify', 'snapshot', 'mutate', 'roundtrip', 'split-prepare', 'split-configure', 'split-verify'], TRUE)) {
  throw new InvalidArgumentException('Unknown SOUS_ENVIRONMENT_TEST stage.');
}
$check = static function (bool $condition, string $message): void {
  if (!$condition) {
    throw new RuntimeException($message);
  }
};
$check(Settings::get('sous_environment') === $environment, 'The effective environment differs from the explicit selection.');
$flags = ['twig_debug' => TRUE, 'twig_cache_disable' => TRUE, 'disable_rendered_output_cache_bins' => TRUE];
$raw_flags = \Drupal::service('keyvalue.database')->get('development_settings');
$storage = new DatabaseStorage(\Drupal::database(), 'config');
$state = \Drupal::state();
$normalize = static function (mixed $value) use (&$normalize): mixed {
  if (is_array($value)) {
    $value = array_map($normalize, $value);
    if (!array_is_list($value)) {
      ksort($value);
    }
  }
  return $value;
};
$snapshot = static function () use ($storage, $normalize): array {
  $result = [];
  foreach (array_merge([''], $storage->getAllCollectionNames()) as $collection) {
    $data = $storage->createCollection($collection);
    $result[$collection] = $normalize($data->readMultiple($data->listAll()));
  }
  ksort($result);
  return $result;
};

if ($stage === 'seed') {
  // Seed the real database factory, even when runtime collection routing uses
  // memory. A copied database must retain these flags without enabling them.
  $raw_flags->setMultiple($flags);
  // These fixture-only stored defaults make override contamination observable;
  // production helpers validate unsafe site defaults rather than repairing them.
  \Drupal::configFactory()->getEditable('system.performance')->set('css.preprocess', TRUE)->set('js.preprocess', TRUE)->save();
  \Drupal::configFactory()->getEditable('system.logging')->set('error_level', 'hide')->save();
  \Drupal::configFactory()->getEditable('system.mail')->set('interface', ['default' => 'php_mail'])->save();
}
elseif ($stage === 'verify') {
  $local = $environment === 'local';
  $check(Settings::get('sous_integration_sentinel') === ($local ? 'preserved' : NULL), 'The local settings sentinel loaded outside local, or failed to load locally.');
  $twig = \Drupal::service('twig');
  $check($twig->isDebug() === $local && $twig->isAutoReload() === $local, 'Effective Twig debugging or auto reload differs from the environment.');
  $check(($twig->getCache() === FALSE) === $local, 'Effective Twig caching differs from the environment.');
  $check(\Drupal::getContainer()->getParameter('http.response.debug_cacheability_headers') === $local, 'Debug cacheability headers differ from the environment.');
  foreach (['page', 'dynamic_page_cache', 'render'] as $bin) {
    $cache = \Drupal::service('cache.' . $bin);
    $cid = 'sous-environment-test:' . bin2hex(random_bytes(8));
    try {
      $cache->set($cid, $cid);
      $cached = $cache->get($cid);
      $check($local ? $cached === FALSE : ($cached !== FALSE && $cached->data === $cid), 'The ' . $bin . ' cache does not follow the environment policy.');
    }
    finally {
      $cache->delete($cid);
    }
  }
  foreach ($flags as $key => $value) {
    $check($raw_flags->get($key) === $value, 'Runtime policy rewrote the copied development setting: ' . $key);
  }
  $performance = \Drupal::config('system.performance');
  $check($performance->get('css.preprocess') === !$local && $performance->get('js.preprocess') === !$local, 'Runtime asset aggregation differs from the environment.');
  $check(\Drupal::config('system.logging')->get('error_level') === ($local ? 'verbose' : 'hide'), 'Runtime error display differs from the environment.');
  $raw_performance = $storage->read('system.performance');
  $check($raw_performance['css']['preprocess'] === TRUE && $raw_performance['js']['preprocess'] === TRUE, 'Runtime overrides contaminated stored aggregation defaults.');
  $check(($storage->read('system.logging')['error_level'] ?? NULL) === 'hide', 'Runtime overrides contaminated stored error display.');
  $check(($storage->read('system.mail')['interface'] ?? NULL) === ['default' => 'php_mail'], 'Runtime overrides contaminated the stored production mail transport.');
  $backend = \Drupal::service('plugin.manager.mail')->getInstance(['module' => 'sous_environment_test', 'key' => 'probe']);
  if ($environment === 'production') {
    // Never exercise a real production transport by sending a test message.
    $check(!($backend instanceof TestMailCollector) && \Drupal::config('system.mail')->get('interface.default') === 'php_mail', 'Production selected a capture transport instead of its stored transport.');
    $check(\Drupal::config('stage_file_proxy.settings')->get('origin') === '', 'Production retained a file proxy origin.');
    $check(Settings::get('rebuild_access', FALSE) === FALSE, 'Production permits the development rebuild endpoint.');
  }
  else {
    $check($backend instanceof TestMailCollector, 'Local/staging did not select the native mail collector.');
    $previous = $state->get('system.test_mail_collector');
    $marker = 'Sous environment ' . bin2hex(random_bytes(8));
    try {
      $check($backend->mail(['id' => $marker, 'to' => 'sous-environment-test@example.invalid', 'subject' => $marker, 'body' => [$marker]]) === TRUE, 'The native mail collector failed.');
      $messages = $state->get('system.test_mail_collector', []);
      $captured = end($messages);
      $check(is_array($captured) && ($captured['id'] ?? NULL) === $marker, 'The test message was not captured.');
    }
    finally {
      if ($previous === NULL) {
        $state->delete('system.test_mail_collector');
      }
      else {
        $state->set('system.test_mail_collector', $previous);
      }
    }
  }
  if (\Drupal::moduleHandler()->moduleExists('config_split')) {
    foreach (['local', 'staging'] as $name) {
      $split = 'config_split.config_split.sous_' . $name;
      $check(\Drupal::config($split)->get('status') === ($environment === $name), 'The optional split did not follow the explicit environment.');
      $check(($storage->read($split)['status'] ?? NULL) === FALSE, 'An environment override changed a stored split status.');
    }
  }
}
elseif ($stage === 'snapshot') {
  $state->set('sous.environment.config_snapshot', $snapshot());
}
elseif ($stage === 'mutate') {
  $before = $state->get('sous.environment.config_snapshot');
  $check(is_array($before), 'Take a configuration snapshot before mutating the fixture.');
  \Drupal::configFactory()->getEditable('system.site')->set('name', 'Sous roundtrip ' . bin2hex(random_bytes(8)))->save();
  $check($snapshot() !== $before, 'The deliberate site-name mutation was not saved.');
}
elseif ($stage === 'roundtrip') {
  $before = $state->get('sous.environment.config_snapshot');
  $check(is_array($before) && $snapshot() === $before, 'The configuration helpers did not restore every active configuration collection.');
}
elseif ($stage === 'split-prepare') {
  $check(\Drupal::moduleHandler()->moduleExists('config_split') && !\Drupal::moduleHandler()->moduleExists('contact'), 'Prepare the optional fixture from a baseline with Config Split and without Contact.');
  // Module ownership is project configuration, never a runtime override. Both
  // definitions remain identical across every environment and database copy.
  foreach (['local', 'staging'] as $name) {
    \Drupal::configFactory()->getEditable('config_split.config_split.sous_' . $name)->set('module', ['contact' => 0])->save();
  }
  $state->delete('sous.environment.split_fixture');
}
elseif ($stage === 'split-configure') {
  $check($environment !== 'production' && \Drupal::moduleHandler()->moduleExists('config_split'), 'Configure the optional split fixture only in local/staging with Config Split installed.');
  $check(!\Drupal::moduleHandler()->moduleExists('contact'), 'The Contact fixture requires a baseline without Contact.');
  foreach (['local', 'staging'] as $name) {
    $check(($storage->read('config_split.config_split.sous_' . $name)['module'] ?? NULL) === ['contact' => 0], 'Prepare stable split module lists before restoring the per-environment database baseline.');
  }
  \Drupal::service('module_installer')->install(['contact']);
  \Drupal::configFactory()->getEditable('contact.form.personal')->set('label', 'Sous ' . $environment . ' fixture')->save();
  $state->set('sous.environment.split_fixture', $environment);
}
elseif ($stage === 'split-verify') {
  $fixture = $state->get('sous.environment.split_fixture');
  $check($environment === 'production' || in_array($fixture, ['local', 'staging'], TRUE), 'Configure and export the optional split fixture first.');
  foreach (['local', 'staging'] as $name) {
    $check(($storage->read('config_split.config_split.sous_' . $name)['module'] ?? NULL) === ['contact' => 0], 'Export/import changed a stored split module list.');
  }
  $enabled = $environment === $fixture;
  $check(\Drupal::moduleHandler()->moduleExists('contact') === $enabled, 'The Contact module did not follow its environment split.');
  foreach (['contact.settings', 'contact.form.personal'] as $name) {
    $check($storage->exists($name) === $enabled, 'Module-owned configuration did not follow its split: ' . $name);
  }
  if ($enabled) {
    $check(\Drupal::config('contact.form.personal')->get('label') === 'Sous ' . $fixture . ' fixture', 'The distinct split form configuration was not restored.');
  }
  $export = \Drupal::service('config.storage.export');
  $check(!isset($export->read('core.extension')['module']['contact']) && !$export->listAll('contact.'), 'Contact or its module-owned configuration leaked into the transformed production base.');
  $check($export->exists('config_split.config_split._splits'), 'The complete export lost its split-definition bootstrap.');
  $definitions = DRUPAL_ROOT . '/../config/_splits';
  foreach (['local', 'staging'] as $name) {
    $filename = 'config_split.config_split.sous_' . $name;
    $check(!$export->exists($filename) && is_file($definitions . '/' . $filename . '.yml'), 'An environment split definition was not routed to config/_splits.');
  }
  if (in_array($fixture, ['local', 'staging'], TRUE)) {
    $directory = DRUPAL_ROOT . '/' . $storage->read('config_split.config_split.sous_' . $fixture)['folder'];
    $check(is_file($directory . '/contact.settings.yml') && is_file($directory . '/contact.form.personal.yml'), 'The active split export did not preserve module-owned configuration.');
    $form = Yaml::parseFile($directory . '/contact.form.personal.yml');
    $check(($form['label'] ?? NULL) === 'Sous ' . $fixture . ' fixture', 'The exported split lost its environment-specific form label.');
  }
}
print 'PASS: Sous environment ' . $environment . ' / ' . $stage . ".\n";
