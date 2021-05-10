<?php

namespace Drupal\entity_browser_enhanced;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;

/**
 * Provides the default entity_browser_enhanced_plugin manager.
 */
class EntityBrowserEnhancedPluginManager extends DefaultPluginManager {

  /**
   * Provides default values for all entity_browser_enhanced_plugin plugins.
   *
   * @var array
   */
  protected $defaults = [
    'id' => '',
    'label' => '',
    'form_extra_class' => '',
    'libraray' => '',
  ];

  /**
   * Constructs a new EntityBrowserEnhancedPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
    $this->setCacheBackend($cache_backend, 'entity_browser_enhanced_plugin', ['entity_browser_enhanced_plugin']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery('enhancers', $this->moduleHandler->getModuleDirectories());
      $this->discovery->addTranslatableProperty('label', 'label_context');
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    if (empty($definition['id'])) {
      throw new PluginException(sprintf('Enhancer ID property (%s) definition "is" is required.', $plugin_id));
    }

    if (empty($definition['label'])) {
      throw new PluginException(sprintf('Enhancer Lable property (%s) definition "is" is required.', $plugin_id));
    }

    if (empty($definition['form_extra_class'])) {
      throw new PluginException(sprintf('Enhancer form extra CSS class selector property (%s) definition "is" is required.', $plugin_id));
    }

    if (empty($definition['libraray'])) {
      throw new PluginException(sprintf('Enhancer libraray (in a module or theme) property (%s) definition "is" is required.', $plugin_id));
    }

  }

  /**
   * Get Enhancer ID.
   *
   * @return string
   *   The ID of the Enhancer.
   */
  public function getId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * Get Enhancer Label.
   *
   * @return string
   *   The Label of the Enhancer.
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * Get Enhancer Form Extra CSS Class.
   *
   * @return string
   *   The selected form extra CSS class for the enhancer.
   */
  public function getFormExtraClass() {
    return $this->pluginDefinition['form_extra_class'];
  }

  /**
   * Get Enhancer styling library.
   *
   * @return string
   *   The styling library from modules or themes.
   */
  public function getLibraray() {
    return $this->pluginDefinition['libraray'];
  }

}
