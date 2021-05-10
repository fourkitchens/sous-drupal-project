<?php

namespace Drupal\blazy_test;

use Drupal\Core\Render\Element\RenderCallbackInterface;

/**
 * Provides a render callback to sets blazy_test related URL attributes.
 *
 * @see blazy_test_blazy_alter()
 * @see blazy_photoswipe_blazy_alter()
 */
class BlazyTestAlter implements RenderCallbackInterface {

  /**
   * The #pre_render callback: Sets lightbox image URL.
   */
  public static function preRender($image) {
    $settings = isset($image['#settings']) ? $image['#settings'] : [];

    // Video's HREF points to external site, adds URL to local image.
    if (!empty($settings['box_url']) && !empty($settings['embed_url'])) {
      $image['#url_attributes']['data-box-url'] = $settings['box_url'];
    }

    return $image;
  }

}
