<?php

namespace Drupal\blazy;

/**
 * Defines common field formatter-related methods: Blazy, Slick.
 */
interface BlazyFormatterInterface extends BlazyManagerInterface {

  /**
   * Modifies the field formatter settings inherited by child elements.
   *
   * @param array $build
   *   The array containing: settings, or potential optionset for extensions.
   * @param object $items
   *   The Drupal\Core\Field\FieldItemListInterface items.
   */
  public function buildSettings(array &$build, $items);

  /**
   * Modifies the field formatter settings inherited by child elements.
   *
   * @param array $build
   *   The array containing: settings, or potential optionset for extensions.
   * @param object $items
   *   The Drupal\Core\Field\FieldItemListInterface items.
   * @param array $entities
   *   The optional entities array, not available for non-entities: text, image.
   */
  public function preBuildElements(array &$build, $items, array $entities = []);

  /**
   * Modifies the field formatter settings not inherited by child elements.
   *
   * @param array $build
   *   The array containing: items, settings, or a potential optionset.
   * @param object $items
   *   The Drupal\Core\Field\FieldItemListInterface items.
   * @param array $entities
   *   The optional entities array, not available for non-entities: text, image.
   */
  public function postBuildElements(array &$build, $items, array $entities = []);

  /**
   * Extract the first image item to build colorbox/zoom-like gallery.
   *
   * @param array $settings
   *   The $settings array being modified.
   * @param object $item
   *   The Drupal\image\Plugin\Field\FieldType\ImageItem item.
   * @param object $entity
   *   The optional media entity.
   */
  public function extractFirstItem(array &$settings, $item, $entity = NULL);

  /**
   * Checks if an image style contains crop effect.
   *
   * @param string $style
   *   The image style to check for.
   *
   * @return object|bool
   *   Returns the image style instance if it contains crop effect, else FALSE.
   */
  public function isCrop($style);

}
