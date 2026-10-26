<?php

/**
 * @file
 * Project-owned runtime environment defaults, applied after DDEV settings.
 */

$sous_environment = getenv('SOUS_ENVIRONMENT');
if ($sous_environment === FALSE) {
  $sous_environment = getenv('IS_DDEV_PROJECT') === 'true' ? 'local' : 'production';
}
if (!in_array($sous_environment, ['local', 'nonproduction', 'production'], TRUE)) {
  throw new InvalidArgumentException('SOUS_ENVIRONMENT must be local, nonproduction, or production.');
}

// File presence does not choose an environment. Existing local files remain
// project-owned, but cannot enable development settings in a non-local runtime.
if ($sous_environment === 'local' && is_readable($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}
$settings['sous_environment'] = $sous_environment;

// These overrides do not enable Config Split or create configuration. Optional
// split definitions and their directories remain an explicit site decision.
$config['config_split.config_split.sous_local']['status'] = $sous_environment === 'local';
$config['config_split.config_split.sous_nonproduction']['status'] = $sous_environment === 'nonproduction';

$config['system.performance']['css']['preprocess'] = $sous_environment !== 'local';
$config['system.performance']['js']['preprocess'] = $sous_environment !== 'local';
$config['system.logging']['error_level'] = $sous_environment === 'local' ? 'verbose' : 'hide';

if ($sous_environment !== 'production') {
  // Core's development mail collector is a sink, not a production transport.
  $config['system.mail']['interface']['default'] = 'test_mail_collector';
  $config['automated_cron.settings']['interval'] = 0;
}
else {
  // Let stored/project production transports take effect without retaining a
  // known development override. Never replace a real transport with php_mail.
  foreach ($config['system.mail']['interface'] ?? [] as $sous_mail_key => $sous_mail_backend) {
    if (in_array($sous_mail_backend, ['test_mail_collector', 'devel_mail_log'], TRUE)) {
      unset($config['system.mail']['interface'][$sous_mail_key]);
    }
  }
}

// No origin is shipped, inferred, or copied from another environment.
$config['stage_file_proxy.settings']['origin'] ??= '';
if ($sous_environment === 'production') {
  $config['stage_file_proxy.settings']['origin'] = '';
}

$sous_development_services = $app_root . '/sites/development.services.yml';
$sous_services = $sous_environment === 'local'
  ? $sous_development_services
  : $app_root . '/sites/sous.production.services.yml';

if ($sous_environment !== 'local') {
  ini_set('display_errors', '0');
  $settings['rebuild_access'] = FALSE;
  if (($settings['cache']['default'] ?? NULL) === 'cache.backend.null') {
    unset($settings['cache']['default']);
  }
  foreach ($settings['cache']['bins'] ?? [] as $sous_cache_bin => $sous_cache_backend) {
    if ($sous_cache_backend === 'cache.backend.null') {
      unset($settings['cache']['bins'][$sous_cache_bin]);
    }
  }
  $settings['container_yamls'] = array_values(array_filter(
    $settings['container_yamls'] ?? [],
    static fn ($sous_file) => $sous_file !== $sous_development_services && $sous_file !== $sous_services,
  ));
}
if (!is_readable($sous_services)) {
  throw new RuntimeException('Missing Sous environment services file: ' . $sous_services);
}
// Load the selected policy last so a prior development service cannot win.
$settings['container_yamls'][] = $sous_services;
$settings['container_yamls'] = array_values(array_unique($settings['container_yamls']));
