<?php

namespace Drupal\slick;

use Drupal\blazy\BlazyFormatterInterface;

/**
 * Defines re-usable services and functions for slick field plugins.
 */
interface SlickFormatterInterface extends BlazyFormatterInterface {

  /**
   * Gets the thumbnail image using theme_image_style().
   *
   * @param array $settings
   *   The array containing: thumbnail_style, etc.
   * @param object $item
   *   The \Drupal\image\Plugin\Field\FieldType\ImageItem object.
   *
   * @return array
   *   The renderable array of thumbnail image.
   */
  public function getThumbnail(array $settings = [], $item = NULL);

}
