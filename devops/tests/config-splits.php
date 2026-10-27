<?php

declare(strict_types=1);

/**
 * Exercise native Config Split export/import using memory and private files.
 *
 * No Drupal bootstrap, application settings, or database is loaded. Use an
 * installed Config Split 2.0.2 source via SOUS_CONFIG_SPLIT_SOURCE if needed.
 */

use Drupal\config_split\ConfigSplitManager;
use Drupal\config_split\EventSubscriber\SplitImportExportSubscriber;
use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ConfigManager;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\StorageTransformEvent;
use Drupal\Core\Config\TypedConfigManager;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcher;

$root = dirname(__DIR__, 2);
$loader = require getenv('SOUS_INTEGRATION_AUTOLOAD') ?: $root . '/vendor/autoload.php';
$module = getenv('SOUS_CONFIG_SPLIT_SOURCE') ?: $root . '/web/modules/contrib/config_split';
$loader->addPsr4('Drupal\\config_split\\', $module . '/src');
$check = static function (bool $condition, string $message): void {
  if (!$condition) {
    throw new RuntimeException($message);
  }
};
$work = sys_get_temp_dir() . '/sous-native-splits-' . bin2hex(random_bytes(8));
$original = getcwd();
mkdir($work . '/web/sites/default', 0700, TRUE);
FileCacheFactory::setPrefix(basename($work));
try {
  foreach (['_splits', 'local', 'staging'] as $directory) {
    mkdir($work . '/config/' . $directory, 0700, TRUE);
  }
  chdir($work . '/web');
  // Typed-schema and dependency services are unused for these simple, empty
  // definitions. Keep storage/factory/subscriber/transforms native; the stub
  // reports no dependent entities instead of bootstrapping an application.
  $typed = (new ReflectionClass(TypedConfigManager::class))->newInstanceWithoutConstructor();
  $dependencies = new class extends ConfigManager {
    public function __construct() {}
    public function getConfigEntitiesToChangeOnDependencyRemoval($type, array $names, $dry_run = TRUE) {
      return ['delete' => [], 'update' => [], 'unchanged' => []];
    }
  };
  $active = new MemoryStorage();
  // Test the application's environment/bootstrap policy with native fixture
  // definitions. Package YAML/schema is checked during real recipe acceptance;
  // this source-only check must not require an optional recipe to be installed.
  foreach (['_splits', 'local', 'staging'] as $folder) {
    $bootstrap = $folder === '_splits';
    $id = $bootstrap ? '_splits' : 'sous_' . $folder;
    $active->write('config_split.config_split.' . $id, [
      'id' => $id, 'status' => $bootstrap, 'weight' => $bootstrap ? 100 : 0,
      'stackable' => FALSE, 'no_patching' => FALSE, 'storage' => 'folder',
      'folder' => $work . '/config/' . $folder, 'module' => [], 'theme' => [],
      'complete_list' => $bootstrap ? ['config_split.config_split.sous_local', 'config_split.config_split.sous_staging'] : [],
      'partial_list' => [],
    ]);
  }
  $active->write('core.extension', ['module' => ['config_split' => 0], 'theme' => []]);
  $active->write('system.site', ['name' => 'Native split fixture']);
  $make_manager = static function (MemoryStorage $active, MemoryStorage $sync, EventDispatcher $dispatcher) use ($typed, $dependencies): array {
    $factory = new ConfigFactory($active, $dispatcher, $typed);
    $manager = (new ReflectionClass(ConfigSplitManager::class))->newInstanceWithoutConstructor();
    foreach (['factory' => $factory, 'manager' => $dependencies, 'active' => $active, 'sync' => $sync, 'export' => $sync] as $name => $value) {
      (new ReflectionProperty($manager, $name))->setValue($manager, $value);
    }
    return [$manager, $factory];
  };
  $dispatcher = new EventDispatcher();
  [$manager, $factory] = $make_manager($active, new MemoryStorage(), $dispatcher);
  $export = new MemoryStorage();
  foreach ($active->listAll() as $name) {
    $export->write($name, $active->read($name));
  }
  $definitions = new MemoryStorage();
  $manager->splitPreview($factory->get('config_split.config_split._splits'), $export, $definitions);
  $check($export->exists('config_split.config_split._splits'), 'Export lost its bootstrap definition.');
  $files = new FileStorage($work . '/config/_splits');
  foreach (['local', 'staging'] as $environment) {
    $name = 'config_split.config_split.sous_' . $environment;
    $check(!$export->exists($name) && $definitions->exists($name), 'Definitions did not leave the base export.');
    $files->write($name, $definitions->read($name));
    (new FileStorage($work . '/config/' . $environment))->write('project.environment', ['name' => $environment]);
  }
  // Start every import without active split definitions. The explicit native
  // priority must discover _splits before the ordinary environment subscriber.
  copy($root . '/assets/scaffold/files/development.services.yml', $work . '/web/sites/development.services.yml');
  copy($root . '/assets/scaffold/files/sous.production.services.yml', $work . '/web/sites/sous.production.services.yml');
  foreach (['local', 'staging', 'production'] as $environment) {
    $settings = []; $config = []; $app_root = $work . '/web'; $site_path = 'sites/default';
    putenv('SOUS_ENVIRONMENT=' . $environment);
    require $root . '/assets/scaffold/files/settings.sous.php';
    $GLOBALS['config'] = $config;
    new Settings($settings);
    $incoming = new MemoryStorage();
    foreach ($export->listAll() as $name) {
      $incoming->write($name, $export->read($name));
    }
    $dispatcher = new EventDispatcher();
    [$manager, $factory] = $make_manager(new MemoryStorage(), $incoming, $dispatcher);
    $dispatcher->addSubscriber(new SplitImportExportSubscriber($manager, $factory));
    $dispatcher->dispatch(new StorageTransformEvent($incoming), 'config.transform.import');
    foreach (['local', 'staging'] as $name) {
      $check($incoming->exists('config_split.config_split.sous_' . $name), 'Fresh import did not discover environment definitions.');
    }
    $expected = $environment === 'production' ? FALSE : ['name' => $environment];
    $check($incoming->read('project.environment') === $expected, 'Fresh import selected the wrong environment values.');
  }
  // The real import guard must inspect nested definitions before transforms
  // can silently create missing folders. Only the module-availability query is
  // stubbed; factory, storages and the checked script are the production code.
  putenv('SOUS_ENVIRONMENT=local');
  $settings = []; $config = [];
  require $root . '/assets/scaffold/files/settings.sous.php';
  $GLOBALS['config'] = $config;
  new Settings($settings);
  [$manager, $factory] = $make_manager(new MemoryStorage(), $export, new EventDispatcher());
  $container = new ContainerBuilder();
  $container->set('config.storage', new MemoryStorage());
  $container->set('config.storage.sync', $export);
  $container->set('config.factory', $factory);
  $container->set('config_split.manager', $manager);
  $container->set('module_handler', new class extends ModuleHandler {
    public function __construct() {}
    public function moduleExists($module) { return $module === 'config_split'; }
  });
  Drupal::setContainer($container);
  putenv('SOUS_ENVIRONMENT_CHECK=import');
  $guard = static function () use ($root): void { require $root . '/devops/scripts/environment-check.php'; };
  $name = 'config_split.config_split.sous_local';
  $original_definition = $files->read($name);
  foreach ([$work . '/config/missing-incoming' => 'must exist', DRUPAL_ROOT => 'outside the public web root'] as $folder => $reason) {
    $definition = $original_definition;
    $definition['folder'] = $folder;
    $files->write($name, $definition);
    try {
      $guard();
      throw new RuntimeException('Unsafe nested split folder passed import preflight.');
    }
    catch (RuntimeException $error) {
      $check(str_contains($error->getMessage(), $reason), 'Unexpected nested split guard failure: ' . $error->getMessage());
    }
    $check(!is_dir($work . '/config/missing-incoming'), 'Preflight created the missing incoming folder.');
  }
  $files->write($name, $original_definition);
  $guard();
  // A runtime status override must not hide an invalid stored value. Refuse
  // export before resolving its transforming storage or writing split files.
  $name = 'config_split.config_split.sous_staging';
  $definition = $active->read($name);
  $definition['status'] = TRUE;
  $active->write($name, $definition);
  [$manager, $factory] = $make_manager($active, $export, new EventDispatcher());
  $check($factory->get($name)->get('status') === FALSE, 'The fixture did not mask the stored status with its local override.');
  $container->set('config.storage', $active);
  $container->set('config.factory', $factory);
  $container->set('config_split.manager', $manager);
  $export_factory = new class($export) {
    public int $calls = 0;
    public function __construct(private MemoryStorage $storage) {}
    public function create(): MemoryStorage { $this->calls++; return $this->storage; }
  };
  $container->set('sous.test.export_factory', $export_factory);
  $container->register('config.storage.export', MemoryStorage::class)
    ->setFactory([new Reference('sous.test.export_factory'), 'create']);
  putenv('SOUS_ENVIRONMENT_CHECK=export');
  try {
    $guard();
    throw new RuntimeException('A runtime override hid the invalid stored split status.');
  }
  catch (RuntimeException $error) {
    $check(str_contains($error->getMessage(), 'Store optional split statuses as inactive'), 'Unexpected stored split status failure: ' . $error->getMessage());
  }
  $check($export_factory->calls === 0, 'Export transformation ran before stored split status validation.');
  $definition['status'] = FALSE;
  $active->write($name, $definition);
  $guard();
  $check($export_factory->calls === 1, 'Valid stored statuses did not reach normal export validation.');
  print "PASS: native split-definition export, fresh imports, nested-folder preflight and raw-status export guard; no site/database changed.\n";
}
finally {
  chdir($original);
  putenv('SOUS_ENVIRONMENT');
  putenv('SOUS_ENVIRONMENT_CHECK');
  $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($work, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
  foreach ($iterator as $item) {
    $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
  }
  rmdir($work);
}
