<?php

namespace Drupal\blazy\Dejavu;

/**
 * A Trait common for Media integration.
 *
 * @see Drupal\blazy\Plugin\views\field\BlazyViewsFieldPluginBase
 * @see Drupal\slick_browser\SlickBrowser::widgetEntityBrowserFileFormAlter()
 * @see Drupal\slick_browser\Plugin\EntityBrowser\FieldWidgetDisplay\...
 * @todo deprecated in blazy:8.x-2.0 and is removed from blazy:8.x-3.0. Use
 *   Drupal\blazy\BlazyOEmbed instead.
 * @see https://www.drupal.org/node/3103018
 */
trait BlazyVideoTrait {

  /**
   * The blazy oembed service.
   *
   * @var \Drupal\blazy\BlazyOEmbedInterface
   * @todo remove default null post Blazy 8.2.x full release.
   */
  protected $blazyOembed = NULL;

  /**
   * Core Media oEmbed url resolver.
   *
   * @var \Drupal\Core\Image\ImageFactory
   * @todo remove default null post Blazy 8.2.x full release.
   */
  protected $imageFactory = NULL;

  /**
   * Returns the blazy oEmbed service.
   *
   * @todo remove null check post Blazy 8.2.x full release.
   */
  public function blazyOembed() {
    if (is_null($this->blazyOembed)) {
      $this->blazyOembed = \Drupal::service('blazy.oembed');
    }
    return $this->blazyOembed;
  }

  /**
   * Returns the image factory.
   *
   * @todo remove null check post Blazy 8.2.x full release.
   */
  public function imageFactory() {
    if (is_null($this->imageFactory)) {
      $this->imageFactory = \Drupal::service('image.factory');
    }
    return $this->imageFactory;
  }

  /**
   * Gets the faked image item out of file entity, or ER, if applicable.
   *
   * @param object $file
   *   The expected file entity, or ER, to get image item from.
   *
   * @return array
   *   The array of image item and settings if a file image, else empty.
   *
   * @todo enable post RC before release release.
   * @todo deprecated in blazy:8.x-2.0 and is removed from blazy:8.x-3.0. Use
   *   BlazyOEmbed::getImageItem() instead.
   * @see https://www.drupal.org/node/3103018
   */
  public function getImageItem($file) {
    // @todo enable post release @trigger_error('getImageItem is deprecated in blazy:8.x-2.0 and is removed from blazy:8.x-3.0. Use \Drupal\blazy\BlazyOEmbed::getImageItem() instead. See https://www.drupal.org/node/3103018', E_USER_DEPRECATED);
    return $this->blazyOembed()->getImageItem($file);
  }

  /**
   * Gets the Media item thumbnail, or re-associate the file entity to ME.
   *
   * @param array $data
   *   An array of data containing settings, and potential video thumbnail item.
   * @param object $media
   *   The core Media entity.
   *
   * @todo remove post Blazy 8.2.x when blazy-plugins use core Media.
   *
   * @deprecated in blazy:8.x-2.0 and is removed from blazy:8.x-3.0. Use
   *   BlazyOEmbed::getMediaItem() instead.
   * @see https://www.drupal.org/node/3103018
   */
  public function getMediaItem(array &$data = [], $media = NULL) {
    @trigger_error('getMediaItem is deprecated in blazy:8.x-2.0 and is removed from blazy:8.x-3.0. Use \Drupal\blazy\BlazyOEmbed::getMediaItem() instead. See https://www.drupal.org/node/3103018', E_USER_DEPRECATED);
    $this->blazyOembed()->getMediaItem($data, $media);
  }

  /**
   * Builds relevant Media settings based on the given media url.
   *
   * @param array $settings
   *   An array of settings to be passed into theme_blazy().
   * @param string $external_url
   *   A video URL.
   *
   * @todo remove post Blazy 8.2.x full release. This is still kept to
   * allow changing from video_embed_field into media field without breaking it,
   * and to allow transition from blazy-related modules to depend on media.
   * Currently this is only required by deprecated SlickVideoFormatter.
   *
   * @deprecated in blazy:8.x-2.0 and is removed from blazy:8.x-3.0. Use
   *   BlazyOEmbed::build() instead.
   * @see https://www.drupal.org/node/3103018
   */
  public function buildVideo(array &$settings = [], $external_url = '') {
    @trigger_error('buildVideo is deprecated in blazy:8.x-2.0 and is removed from blazy:8.x-3.0. Use \Drupal\blazy\BlazyOEmbed::build() instead. See https://www.drupal.org/node/3103018', E_USER_DEPRECATED);
    $settings['input_url'] = empty($settings['input_url']) ? $external_url : $settings['input_url'];
    return $this->blazyOembed()->build($settings);
  }

}
