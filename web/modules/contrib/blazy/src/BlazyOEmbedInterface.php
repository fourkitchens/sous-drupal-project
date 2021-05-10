<?php

namespace Drupal\blazy;

/**
 * Provides OEmbed integration.
 */
interface BlazyOEmbedInterface {

  /**
   * Returns the oEmbed Resource based on the given media input url.
   *
   * @param string $input_url
   *   The video url.
   *
   * @return Drupal\media\OEmbed\Resource[]
   *   The oEmbed resource.
   */
  public function getResource($input_url);

  /**
   * Builds media-related settings based on the given media input url.
   *
   * @param array $settings
   *   The settings array being modified.
   */
  public function build(array &$settings = []);

  /**
   * Provides the autoplay url suitable for lightboxes, or custom video trigger.
   *
   * @param string $url
   *   The embed URL, not input URL.
   *
   * @return array
   *   The settings array containing autoplay URL.
   */
  public function getAutoPlayUrl($url = '');

  /**
   * Gets the Media item thumbnail.
   *
   * @param array $data
   *   The modified array containing settings, and to be video thumbnail item.
   * @param object $media
   *   The core Media entity.
   */
  public function getMediaItem(array &$data, $media);

  /**
   * Gets the faked image item out of file entity, or ER, if applicable.
   *
   * @param object $file
   *   The expected file entity, or ER, to get image item from.
   *
   * @return array
   *   The array of image item and settings if a file image, else empty.
   *
   * @todo this is likely to be removed for anything Media, still kept for
   * BlazyFilter and few legacy file entity integrations such as Views file.
   */
  public function getImageItem($file);

}
