<?php

declare(strict_types=1);

/**
 * Test native development-settings isolation without booting a site/database.
 *
 * SOUS_INTEGRATION_AUTOLOAD may select a disposable site's vendor/autoload.php.
 */

use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\DependencyInjection\Compiler\DevelopmentSettingsPass;
use Drupal\Core\KeyValueStore\KeyValueFactory;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Yaml\Yaml;

require getenv('SOUS_INTEGRATION_AUTOLOAD') ?: dirname(__DIR__, 2) . '/vendor/autoload.php';

$check = static function (bool $condition, string $message): void {
  if (!$condition) {
    throw new RuntimeException($message);
  }
};
$policy = Yaml::parseFile(dirname(__DIR__, 2) . '/assets/scaffold/files/sous.production.services.yml');
$flags = ['twig_debug' => TRUE, 'twig_cache_disable' => TRUE, 'disable_rendered_output_cache_bins' => TRUE];

foreach ([FALSE, TRUE] as $isolate) {
  // An in-memory stand-in stores the copied database's values. All routing and
  // compiler behavior below uses native Drupal factories and compiler passes.
  $database = new KeyValueMemoryFactory();
  $database->get('development_settings')->setMultiple($flags);
  $database->get('system.schema')->set('project_module', 1);
  $container = new ContainerBuilder();
  $container->set('keyvalue.database', $database);
  $container->setParameter('twig.config', $policy['parameters']['twig.config']);
  $options = $isolate ? $policy['parameters']['factory.keyvalue'] : ['default' => 'keyvalue.database'];
  foreach ($policy['services'] as $id => $definition) {
    $container->register($id, $definition['class']);
  }
  $container->register('keyvalue', KeyValueFactory::class)
    ->setArguments([new Reference('service_container'), $options]);
  foreach (['page', 'dynamic_page_cache', 'render'] as $bin) {
    $container->register('cache.' . $bin, MemoryBackend::class)
      ->addTag('cache.bin', ['default_backend' => 'cache.backend.database']);
  }
  (new DevelopmentSettingsPass())->process($container);
  $twig = $container->getParameter('twig.config');
  $check($twig['debug'] === !$isolate && $twig['cache'] === $isolate, 'Core UI flags were not isolated as expected.');
  foreach (['page', 'dynamic_page_cache', 'render'] as $bin) {
    $backend = $container->getDefinition('cache.' . $bin)->getTag('cache.bin')[0]['default_backend'];
    $check($backend === ($isolate ? 'cache.backend.database' : 'cache.backend.null'), 'Rendered-output cache policy changed.');
  }
  $check($database->get('development_settings')->getAll() === $flags, 'Runtime policy rewrote stored development flags.');
  $check($container->get('keyvalue')->get('system.schema')->get('project_module') === 1, 'Other key/value collections lost their database routing.');
}
print "PASS: native Core development UI flags affect local routing, are isolated by the non-local service policy, and stored flags/other collections remain unchanged.\n";
