<?php

namespace Drupal\blazy_test;

use Drupal\blazy\BlazyFormatter;

/**
 * Implements GridStackFormatterInterface.
 */
class BlazyFormatterTest extends BlazyFormatter implements BlazyFormatterTestInterface {

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
  public function getThumbnail(array $settings = [], $item = NULL) {
    $thumbnail = [];
    if (!empty($settings['uri'])) {
      $thumbnail = [
        '#theme'      => 'image_style',
        '#style_name' => isset($settings['thumbnail_style']) ? $settings['thumbnail_style'] : 'thumbnail',
        '#uri'        => $settings['uri'],
        '#item'       => $item,
      ];
    }
    return $thumbnail;
  }

}
