<?php

namespace Drupal\slick\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\blazy\Dejavu\BlazyVideoTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Slick File' formatter to get ME within images.
 *
 * This is not 'Slick Media', instead a simple mix of image and optional video.
 *
 * @todo TBD; deprecate for core Media and remove post/ prior to 3.x release.
 * @todo deprecated in blazy:8.x-2.0 and is removed from blazy:8.x-3.0. Use
 *   \Drupal\slick\Plugin\Field\FieldFormatter\SlickMediaFormatter instead.
 */
class SlickFileFormatter extends SlickFileFormatterBase {

  // @@todo remove post blazy:2.x.
  use BlazyVideoTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    return self::injectServices($instance, $container, 'entity');
  }

  /**
   * {@inheritdoc}
   */
  public function buildElement(array &$build, $entity) {
    $settings = $build['settings'];
    $data = [];
    /** @var Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item */
    // EntityReferenceItem provides $item->entity Drupal\file\Entity\File.
    if (empty($build['item'])) {
      // @todo remove condition post blazy:2.x.
      if (method_exists($this->blazyOembed, 'getImageItem')) {
        $data = $this->blazyOembed->getImageItem($entity);
      }
      // @todo remove post blazy:2.x.
      elseif (method_exists($this, 'getImageItem')) {
        $data = $this->getImageItem($entity);
      }

      if ($data) {
        $build['item'] = $data['item'];
        $build['settings'] = array_merge($settings, $data['settings']);
      }
    }

    $this->blazyOembed->getMediaItem($build, $entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildSettings() {
    return ['blazy' => TRUE] + parent::getSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function getScopedFormElements() {
    return [
      'fieldable_form' => TRUE,
      'multimedia'     => TRUE,
      'view_mode'      => $this->viewMode,
    ] + $this->getCommonScopedFormElements() + parent::getScopedFormElements();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $storage = $field_definition->getFieldStorageDefinition();
    return $storage->isMultiple() && $storage->getSetting('target_type') === 'file';
  }

}
