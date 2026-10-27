<?php

declare(strict_types=1);

/** Explicit production-base or effective-runtime checks for a bootstrapped site. */

use Drupal\Core\Cache\NullBackend;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Site\Settings;

$mode = $extra[0] ?? 'config';
if (!in_array($mode, ['config', 'runtime'], TRUE)) {
  print "FAIL: Unknown environment check mode; use config or runtime.\n";
  exit(1);
}
$environment = Settings::get('sous_environment');
if (!in_array($environment, ['local', 'staging', 'production'], TRUE)) {
  print "FAIL: Adopt settings.sous.php; the sous_environment setting is missing or invalid.\n";
  exit(1);
}
$failed = FALSE;
$check = static function (bool $passed, string $rule) use (&$failed): void {
  print ($passed ? 'PASS: ' : 'FAIL: ') . $rule . "\n";
  $failed = $failed || !$passed;
};
$capture_backends = ['test_mail_collector', 'devel_mail_log'];

if ($mode === 'config') {
  $storage = new FileStorage(dirname(DRUPAL_ROOT) . '/config/default');
  $extensions = $storage->read('core.extension') ?: [];
  $modules = $extensions['module'] ?? NULL;
  $development_modules = array_merge([
    'devel', 'devel_generate', 'webprofiler', 'stage_file_proxy',
    'reroute_email', 'devel_mail_log',
  ], Settings::get('sous_development_modules', []));
  $logging = $storage->read('system.logging') ?: [];
  $mail = $storage->read('system.mail') ?: [];
  $performance = $storage->read('system.performance') ?: [];
  $check(is_array($modules) && !array_intersect(array_keys($modules), $development_modules), 'Production base has no development modules.');
  $check(!in_array($logging['error_level'] ?? NULL, ['all', 'verbose'], TRUE), 'Production base hides development error output.');
  $check(!array_intersect((array) ($mail['interface'] ?? []), $capture_backends), 'Production base has no capture mail backend.');
  $check((bool) ($performance['css']['preprocess'] ?? TRUE), 'Production base keeps CSS aggregation enabled.');
  $check((bool) ($performance['js']['preprocess'] ?? TRUE), 'Production base keeps JavaScript aggregation enabled.');
}
else {
  if ($environment !== 'local') {
    $twig = \Drupal::getContainer()->getParameter('twig.config');
    $check(empty($twig['debug']), 'Twig debugging is disabled outside local.');
    $check(!empty($twig['cache']), 'Twig caching is enabled outside local.');
    $check(!(\Drupal::service('cache.render') instanceof NullBackend), 'Render caching is enabled outside local.');
    $check(!Settings::get('rebuild_access', FALSE), 'Rebuild access is disabled outside local.');
  }
  $interfaces = \Drupal::config('system.mail')->get('interface') ?: [];
  if ($environment === 'production') {
    $check(!array_intersect($interfaces, $capture_backends), 'Production has no capture mail backend.');
  }
  else {
    $check(($interfaces['default'] ?? NULL) === 'test_mail_collector', 'Local/staging default mail uses test_mail_collector.');
    $check(\Drupal::config('automated_cron.settings')->get('interval') === 0, 'Local/staging automated cron is disabled.');
  }
}
exit($failed ? 1 : 0);
