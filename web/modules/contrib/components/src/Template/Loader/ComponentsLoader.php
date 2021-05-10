<?php

namespace Drupal\components\Template\Loader;

use Drupal\Core\Theme\ActiveTheme;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\components\Template\ComponentsInfo;
use Twig\Loader\FilesystemLoader;

/**
 * Loads templates from the filesystem.
 *
 * This loader adds module and theme components paths as namespaces to the Twig
 * filesystem loader so that templates can be referenced by namespace, like
 * \@mycomponents/box.html.twig or \@mythemeComponents/page.html.twig.
 */
class ComponentsLoader extends FilesystemLoader {

  /**
   * The components info service.
   *
   * @var \Drupal\components\Template\ComponentsInfo
   */
  protected $componentsInfo;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The active theme that the current namespaces are valid for.
   *
   * @var string
   */
  protected $activeTheme;

  /**
   * Cache of namespaces for any theme that was active during this request.
   *
   * @var array
   */
  protected $activeThemeNamespaces;

  /**
   * Cache of module namespaces that are valid for any active theme.
   *
   * @var array
   */
  protected $moduleNamespaces;

  /**
   * Constructs a new ComponentsLoader object.
   *
   * @param \Drupal\components\Template\ComponentsInfo $components_info
   *   The components info service.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager service.
   *
   * @throws \Twig\Error\LoaderError
   */
  public function __construct(
    ComponentsInfo $components_info,
    ThemeManagerInterface $theme_manager
  ) {
    parent::__construct();

    $this->componentsInfo = $components_info;
    $this->themeManager = $theme_manager;
  }

  /**
   * Activates the proper namespaces if the active theme has changed.
   *
   * @return string
   *   The name of the active theme.
   *
   * @throws \Twig\Error\LoaderError
   *
   * @internal
   */
  public function checkActiveTheme() {
    $active_theme = $this->themeManager->getActiveTheme();

    // Update our namespaces if the active theme has changed.
    if ($this->activeTheme !== $active_theme->getName()) {
      $this->setActiveTheme($active_theme);
    }

    return $this->activeTheme;
  }

  /**
   * Sets the namespaces based on the given active theme.
   *
   * @param \Drupal\Core\Theme\ActiveTheme $active_theme
   *   The active theme.
   *
   * @throws \Twig\Error\LoaderError
   */
  protected function setActiveTheme(ActiveTheme $active_theme) {
    $this->activeTheme = $active_theme->getName();

    // Invalidate the cache.
    $this->cache = $this->errorCache = [];

    // Use the active theme cache, if available.
    if (isset($this->activeThemeNamespaces[$this->activeTheme])) {
      $this->paths = $this->activeThemeNamespaces[$this->activeTheme];
      return;
    }

    // Gather info about the active theme's base themes.
    $active_themes = [$this->activeTheme];
    foreach ($active_theme->getBaseThemeExtensions() as $extension) {
      $active_themes[] = $extension->getName();
    }
    $theme_info = $this->componentsInfo->getAllThemeInfo();

    // Templates in namespaces should be loaded from paths in this priority:
    //
    //   1. active theme paths
    //   2. active theme's base themes paths
    //   3. module namespaces paths
    //
    // We accomplish this by loading default namespaces first (where the name of
    // of the namespace matches the name of the theme/module). And then prepend
    // paths in reverse order of the above priority.
    $this->paths = [];

    // Register module namespaces.
    if (!isset($this->moduleNamespaces)) {
      $this->moduleNamespaces = [];
      $module_info = $this->componentsInfo->getAllModuleInfo();

      // Find default namespaces first.
      foreach ($module_info as $extensionName => $info) {
        if (isset($info['namespaces']) && isset($info['namespaces'][$extensionName])) {
          $this->moduleNamespaces[$extensionName] = $info['namespaces'][$extensionName];
        }
      }

      // Find other module namespaces.
      foreach ($module_info as $moduleName => $info) {
        if (isset($info['namespaces'])) {
          foreach ($info['namespaces'] as $namespace => $paths) {
            // Skip protected namespaces and log a warning.
            if ($this->componentsInfo->isProtectedNamespace($namespace)) {
              $extensionInfo = $this->componentsInfo->getProtectedNamespaceExtensionInfo($namespace);
              $this->componentsInfo->logWarning(sprintf('The %s module attempted to alter the protected Twig namespace, %s, owned by the %s %s. See https://www.drupal.org/node/3190969#s-extending-a-default-twig-namespace to fix this error.', $moduleName, $namespace, $extensionInfo['name'], $extensionInfo['type']));
            }
            // Skip default namespaces.
            elseif ($namespace !== $moduleName) {
              if (!isset($this->moduleNamespaces[$namespace])) {
                $this->moduleNamespaces[$namespace] = [];
              }
              // Save paths in the same order specified in the .info.yml file.
              foreach (array_reverse($paths) as $path) {
                array_unshift($this->moduleNamespaces[$namespace], $path);
              }
            }
          }
        }
      }
    }
    foreach ($this->moduleNamespaces as $name => $paths) {
      $this->setPaths($paths, $name);
    }

    // Add theme namespaces, starting with the most-base base theme.
    foreach (array_reverse($active_themes) as $theme_name) {
      if (isset($theme_info[$theme_name]) && isset($theme_info[$theme_name]['namespaces'])) {
        foreach ($theme_info[$theme_name]['namespaces'] as $namespace => $paths) {
          // Skip protected namespaces and log a warning.
          if ($this->componentsInfo->isProtectedNamespace($namespace)) {
            $extensionInfo = $this->componentsInfo->getProtectedNamespaceExtensionInfo($namespace);
            $this->componentsInfo->logWarning(sprintf('The %s theme attempted to alter the protected Twig namespace, %s, owned by the %s %s. See https://www.drupal.org/node/3190969#s-extending-a-default-twig-namespace to fix this error.', $theme_name, $namespace, $extensionInfo['name'], $extensionInfo['type']));
          }
          else {
            // Save paths in the same order specified in the .info.yml file.
            foreach (array_reverse($paths) as $path) {
              $this->prependPath($path, $namespace);
            }
          }
        }
      }
    }

    // Suppress warnings until the theme registry cache is rebuilt.
    $this->componentsInfo->suppressWarnings();

    // Save the paths as a cache.
    $this->activeThemeNamespaces[$this->activeTheme] = $this->paths;
  }

  /**
   * {@inheritdoc}
   */
  public function addPath($path, $namespace = self::MAIN_NAMESPACE) {
    // Invalidate the cache.
    $this->cache = $this->errorCache = [];
    $this->paths[$namespace][] = rtrim($path, '/\\');
  }

  /**
   * {@inheritdoc}
   */
  public function prependPath($path, $namespace = self::MAIN_NAMESPACE) {
    // Invalidate the cache.
    $this->cache = $this->errorCache = [];

    $path = rtrim($path, '/\\');

    if (!isset($this->paths[$namespace])) {
      $this->paths[$namespace][] = $path;
    }
    else {
      array_unshift($this->paths[$namespace], $path);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Twig\Error\LoaderError
   */
  protected function findTemplate($name, $throw = TRUE) {
    // The active theme might change during the request, so we wait until the
    // last possible moment to check before delivering a template.
    $this->checkActiveTheme();

    return parent::findTemplate($name, $throw);
  }

}
