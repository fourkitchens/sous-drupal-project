<?php

namespace Drupal\blazy\Dejavu;

use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\blazy\BlazyManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base for blazy views integration to have re-usable methods in one place.
 *
 * @see \Drupal\mason\Plugin\views\style\MasonViews
 * @see \Drupal\gridstack\Plugin\views\style\GridStackViews
 * @see \Drupal\slick_views\Plugin\views\style\SlickViews
 */
abstract class BlazyStylePluginBase extends StylePluginBase {

  use BlazyStyleBaseTrait;
  use BlazyStyleOptionsTrait;
  use BlazyStylePluginTrait;

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesGrouping = FALSE;

  /**
   * Constructs a GridStackManager object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlazyManagerInterface $blazy_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blazyManager = $blazy_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('blazy.manager'));
  }

  /**
   * Returns an individual row/element content.
   */
  public function buildElement(array &$element, $row, $index) {
    $settings = &$element['settings'];
    $item_id = empty($settings['item_id']) ? 'box' : $settings['item_id'];

    // Add main image fields if so configured.
    if (!empty($settings['image'])) {
      // Supports individual grid/box image style either inline IMG, or CSS.
      $image             = $this->getImageRenderable($settings, $row, $index);
      $element['item']   = $this->getImageItem($image);
      $element[$item_id] = empty($image['rendered']) ? [] : $image['rendered'];
    }

    // Add caption fields if so configured.
    $element['caption'] = $this->getCaption($index, $settings);

    // Add layout field, may be a list field, or builtin layout options.
    if (!empty($settings['layout'])) {
      $this->getLayout($settings, $index);
    }
  }

}
