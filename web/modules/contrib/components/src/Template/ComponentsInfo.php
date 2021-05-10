<?php

namespace Drupal\components\Template;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ExtensionList;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Loads info about components defined in themes or modules.
 */
class ComponentsInfo {

  use LoggerChannelTrait;

  /**
   * Keep track of component info provided by modules.
   *
   * @var array
   */
  protected $moduleInfo = [];

  /**
   * Keep track of component info provided by themes.
   *
   * @var array
   */
  protected $themeInfo = [];

  /**
   * Module namespaces that cannot be overridden.
   *
   * @var array
   */
  protected $protectedNamespaces = [];

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The module extension list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The theme extension list service.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $themeExtensionList;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Stores whether the registry was already initialized.
   *
   * @var bool
   */
  protected $initialized = FALSE;

  /**
   * Constructs a new ComponentsInfo object.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list service.
   * @param \Drupal\Core\Extension\ThemeExtensionList $themeExtensionList
   *   The theme extension list service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The theme manager service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend for storing components info flags.
   */
  public function __construct(
    ModuleExtensionList $moduleExtensionList,
    ThemeExtensionList $themeExtensionList,
    ModuleHandlerInterface $moduleHandler,
    ThemeManagerInterface $themeManager,
    CacheBackendInterface $cache
  ) {
    $this->moduleExtensionList = $moduleExtensionList;
    $this->themeExtensionList = $themeExtensionList;
    $this->moduleHandler = $moduleHandler;
    $this->themeManager = $themeManager;
    $this->cache = $cache;
  }

  /**
   * Initializes the registry and loads the theme namespaces.
   */
  protected function init(): void {
    if ($this->initialized) {
      return;
    }
    $this->initialized = TRUE;

    $this->moduleInfo = $this->findComponentsInfo($this->moduleExtensionList);
    $this->themeInfo = $this->findComponentsInfo($this->themeExtensionList);

    // Run hook_protected_twig_namespaces_alter().
    $this->moduleHandler->alter('protected_twig_namespaces', $this->protectedNamespaces);
    $this->themeManager->alter('protected_twig_namespaces', $this->protectedNamespaces);
  }

  /**
   * Finds component info from the given extension list.
   *
   * @param \Drupal\Core\Extension\ExtensionList $extension_list
   *   The extension list to search.
   *
   * @return array
   *   The components info for all extensions in the extension list.
   */
  protected function findComponentsInfo(ExtensionList $extension_list) {
    $data = [];

    foreach ($extension_list->getAllInstalledInfo() as $name => $extension_info) {
      // Find the components info.
      $info = isset($extension_info['components']) && is_array($extension_info['components']) ? $extension_info['components'] : [];

      // Look for namespaces using 1.x API (backwards compatibility).
      if (!isset($info['namespaces']) && isset($extension_info['component-libraries'])) {
        $this->logWarning(sprintf('Components 8.x-1.x API is deprecated in components:8.x-2.0 and is removed from components:3.0.0. Update the %s.info.yml file to replace the component-libraries.[namespace].paths data with components.namespaces.[namespace]. See https://www.drupal.org/node/3082817', $name));
        if (is_array($extension_info['component-libraries'])) {
          foreach ($extension_info['component-libraries'] as $namespace => $namespace_data) {
            if (!empty($namespace_data['paths'])) {
              $info['namespaces'][$namespace] = $namespace_data['paths'];
            }
          }
        }
      }

      // Normalize namespace data.
      $extension_path = $extension_list->getPath($name);
      if (isset($info['namespaces'])) {
        foreach ($info['namespaces'] as $namespace => $paths) {
          // Allow paths to be an array or a string.
          if (!is_array($paths)) {
            $info['namespaces'][$namespace] = [];
            $paths = [$paths];
          }

          // Add the full path to the namespace paths.
          foreach ($paths as $key => $path) {
            // Determine if the given path is relative to the Drupal root or to
            // the extension.
            $parent_path = ($path[0] === '/')
              ? \Drupal::root()
              : $extension_path . '/';
            $info['namespaces'][$namespace][$key] = $parent_path . $path;
          }
        }
      }

      // Save the components info for the extension.
      if (!empty($info)) {
        $info['extensionPath'] = $extension_path;
        $data[$name] = $info;
      }

      // The following namespaces are protected because they did not opt-in.
      if ((!isset($info['namespaces']) || empty($info['namespaces'][$name])) && !isset($info['allow_default_namespace_reuse'])) {
        $this->setProtectedNamespace($name, $extension_info);
      }
    }

    return $data;
  }

  /**
   * Retrieves the components info for the given module.
   *
   * @param string $name
   *   The name of the module.
   *
   * @return array
   *   The components info.
   *
   * @internal
   */
  public function getModuleInfo($name) {
    $this->init();

    if (isset($this->moduleInfo[$name])) {
      return $this->moduleInfo[$name];
    }

    // No components info.
    return [];
  }

  /**
   * Retrieves the components info for all modules.
   *
   * @return array
   *   The components info, keyed by module name.
   *
   * @internal
   */
  public function getAllModuleInfo() {
    $this->init();

    return $this->moduleInfo;
  }

  /**
   * Retrieves the components info for the given theme.
   *
   * @param string $name
   *   The name of the theme.
   *
   * @return array
   *   The components info.
   *
   * @internal
   */
  public function getThemeInfo($name) {
    $this->init();

    if (isset($this->themeInfo[$name])) {
      return $this->themeInfo[$name];
    }

    // No components info.
    return [];
  }

  /**
   * Retrieves the components info for all themes.
   *
   * @return array
   *   The components info, keyed by theme name.
   *
   * @internal
   */
  public function getAllThemeInfo() {
    $this->init();

    return $this->themeInfo;
  }

  /**
   * Checks if the string is a default namespace that should not be overridden.
   *
   * Protected namespaces are default namespaces that are maintained by Drupal
   * core and owned by individual modules or themes. By default, default
   * namespaces cannot be overridden; a module or theme can opt-in to having
   * their namespace altered by using their name in the components.namespaces
   * key of their .info.yml or by setting the
   * components.allow_default_namespace_reuse key in their .info.yml.
   *
   * @param string $namespace
   *   The namespace to check.
   *
   * @return bool
   *   Whether the namespace is protected or not.
   *
   * @internal
   */
  public function isProtectedNamespace(string $namespace): bool {
    $this->init();

    return isset($this->protectedNamespaces[$namespace]);
  }

  /**
   * Marks a Twig namespace as protected and saves info about its extension.
   *
   * @param string $namespace
   *   The protected Twig namespace to save.
   * @param array $extensionInfo
   *   Information about the extension that owns the namespace.
   */
  protected function setProtectedNamespace(string $namespace, array $extensionInfo) {
    $this->protectedNamespaces[$namespace] = [
      'name' => $extensionInfo['name'],
      'type' => $extensionInfo['type'],
      'package' => isset($extensionInfo['package']) ? $extensionInfo['package'] : '',
    ];
  }

  /**
   * Get info about the module/theme that owns the protected Twig namespace.
   *
   * @param string $namespace
   *   The namespace to get the extension info about.
   *
   * @return array
   *   Information about the protected Twig namespace's extension, including:
   *   - name: The friendly-name of the module/theme that owns the namespace.
   *   - type: The extension type: module, theme, or profile.
   *   - package: The package name the module is listed under or an empty
   *     string.
   *
   * @internal
   */
  public function getProtectedNamespaceExtensionInfo(string $namespace) {
    $this->init();

    return isset($this->protectedNamespaces[$namespace])
      ? $this->protectedNamespaces[$namespace]
      : ['name' => '', 'type' => '', 'package' => ''];
  }

  /**
   * Returns a list of default namespaces that should not be overridden.
   *
   * The returned list is of default namespaces that are maintained by Drupal
   * core and owned by individual modules or themes. By default, default
   * namespaces cannot be overridden; a module or theme can opt-in to having
   * their namespace altered by using their name in the components.namespaces
   * key of their .info.yml or by setting the
   * components.allow_default_namespace_reuse key in their .info.yml.
   *
   * @return array
   *   List of protected namespaces.
   *
   * @internal
   */
  public function getProtectedNamespaces() {
    $this->init();

    return array_keys($this->protectedNamespaces);
  }

  /**
   * Logs exceptional occurrences that are not errors.
   *
   * Example: Use of deprecated APIs, poor use of an API, undesirable things
   * that are not necessarily wrong.
   *
   * @param string $message
   *   The warning to log.
   * @param mixed[] $context
   *   Any additional context to pass to the logger.
   *
   * @internal
   */
  public function logWarning($message, array $context = []) {
    if (!$this->cache->get('components:suppressWarnings')) {
      $this->getLogger('components')->warning($message, $context);
    }
  }

  /**
   * Suppress warnings until the theme registry cache is rebuilt.
   *
   * @internal
   */
  public function suppressWarnings() {
    $this->cache->set(
      'components:suppressWarnings',
      TRUE,
      Cache::PERMANENT,
      ['theme_registry']
    );
  }

}
