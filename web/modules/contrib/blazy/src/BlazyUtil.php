<?php

namespace Drupal\blazy;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Site\Settings;
use Drupal\image\Entity\ImageStyle;

/**
 * Provides Blazy utilities.
 */
class BlazyUtil {

  /**
   * Generates an SVG Placeholder.
   *
   * @param string $width
   *   The image width.
   * @param string $height
   *   The image height.
   *
   * @return string
   *   Returns a string containing an SVG.
   */
  public static function generatePlaceholder($width, $height): string {
    return 'data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D\'http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg\'%20viewBox%3D\'0%200%20' . $width . '%20' . $height . '\'%2F%3E';
  }

  /**
   * Returns the sanitized attributes for user-defined (UGC Blazy Filter).
   *
   * When IMG and IFRAME are allowed for untrusted users, trojan horses are
   * welcome. Hence sanitize attributes relevant for BlazyFilter. The rest
   * should be taken care of by HTML filters after Blazy.
   *
   * @param array $attributes
   *   The given attributes to sanitize.
   *
   * @return array
   *   The sanitized $attributes suitable for UGC, such as Blazy filter.
   */
  public static function sanitize(array $attributes = []) {
    $clean_attributes = [];
    $tags = ['href', 'poster', 'src', 'about', 'data', 'action', 'formaction'];
    foreach ($attributes as $key => $value) {
      if (is_array($value)) {
        // Respects array item containing space delimited classes: aaa bbb ccc.
        $value = implode(' ', $value);
        $clean_attributes[$key] = array_map('\Drupal\Component\Utility\Html::cleanCssIdentifier', explode(' ', $value));
      }
      else {
        // Since Blazy is lazyloading known URLs, sanitize attributes which
        // make no sense to stick around within IMG or IFRAME tags.
        $kid = mb_substr($key, 0, 2) === 'on' || in_array($key, $tags);
        $key = $kid ? 'data-' . $key : $key;
        $clean_attributes[$key] = $kid ? Html::cleanCssIdentifier($value) : Html::escape($value);
      }
    }
    return $clean_attributes;
  }

  /**
   * Returns the URI from the given image URL, relevant for unmanaged files.
   */
  public static function buildUri($image_url) {
    if (!UrlHelper::isExternal($image_url) && $normal_path = UrlHelper::parse($image_url)['path']) {
      $public_path = Settings::get('file_public_path', 'sites/default/files');

      // Only concerns for the correct URI, not image URL which is already being
      // displayed via SRC attribute. Don't bother language prefixes for IMG.
      if ($public_path && strpos($normal_path, $public_path) !== FALSE) {
        $rel_path = str_replace($public_path, '', $normal_path);
        return file_build_uri($rel_path);
      }
    }
    return FALSE;
  }

  /**
   * Determines whether the URI has a valid scheme for file API operations.
   *
   * @param string $uri
   *   The URI to be tested.
   *
   * @return bool
   *   TRUE if the URI is valid.
   */
  public static function isValidUri($uri) {
    // Adds a check to pass the tests due to non-DI.
    return Blazy::streamWrapperManager() ? Blazy::streamWrapperManager()->isValidUri($uri) : FALSE;
  }

  /**
   * Provides image url based on the given settings.
   */
  public static function imageUrl(array &$settings) {
    // Provides image_url, not URI, expected by lazyload.
    $uri = $settings['uri'];
    $valid = self::isValidUri($uri);
    $styled = $valid && empty($settings['unstyled']);

    // Image style modifier can be multi-style images such as GridStack.
    if ($valid && !empty($settings['image_style']) && ($style = ImageStyle::load($settings['image_style']))) {
      $settings['image_url'] = self::transformRelative($uri, ($styled ? $style : NULL));
      $settings['cache_tags'] = $style->getCacheTags();

      // Only re-calculate dimensions if not cropped, nor already set.
      if (empty($settings['_dimensions']) && empty($settings['responsive_image_style'])) {
        $settings = array_merge($settings, self::transformDimensions($style, $settings));
      }
    }
    else {
      $image_url = $valid ? self::transformRelative($uri) : $uri;
      $settings['image_url'] = empty($settings['image_url']) ? $image_url : $settings['image_url'];
    }

    // Just in case, an attempted kidding gets in the way, relevant for UGC.
    $data_uri = mb_substr($settings['image_url'], 0, 10) === 'data:image';
    if (!empty($settings['_check_protocol']) && !$data_uri) {
      $settings['image_url'] = UrlHelper::stripDangerousProtocols($settings['image_url']);
    }
  }

  /**
   * Provides original unstyled image dimensions based on the given image item.
   */
  public static function imageDimensions(array &$settings, $item = NULL, $initial = FALSE) {
    $width = $initial ? '_width' : 'width';
    $height = $initial ? '_height' : 'height';
    $uri = $initial ? '_uri' : 'uri';

    if (empty($settings[$width])) {
      $settings[$width] = $item && isset($item->width) ? $item->width : NULL;
      $settings[$height] = $item && isset($item->height) ? $item->height : NULL;
    }
    // Only applies when Image style is empty, no file API, no $item,
    // with unmanaged VEF/ WYSIWG/ filter image without image_style.
    // Prevents 404 warning when video thumbnail missing for a reason.
    if (empty($settings['image_style']) && empty($settings[$width]) && !empty($settings[$uri])) {
      $abs = empty($settings['uri_root']) ? $settings[$uri] : $settings['uri_root'];
      if ($data = @getimagesize($abs)) {
        list($settings[$width], $settings[$height]) = $data;
      }
    }

    // Sometimes they are string, cast them integer to reduce JS logic.
    $settings[$width] = empty($settings[$width]) ? NULL : (int) $settings[$width];
    $settings[$height] = empty($settings[$height]) ? NULL : (int) $settings[$height];
  }

  /**
   * A wrapper for ImageStyle::transformDimensions().
   *
   * @param object $style
   *   The given image style.
   * @param array $data
   *   The data settings: _width, _height, _uri, width, height, and uri.
   * @param bool $initial
   *   Whether particularly transforms once for all, or individually.
   */
  public static function transformDimensions($style, array $data, $initial = FALSE) {
    $width  = $initial ? '_width' : 'width';
    $height = $initial ? '_height' : 'height';
    $uri    = $initial ? '_uri' : 'uri';
    $width  = isset($data[$width]) ? $data[$width] : NULL;
    $height = isset($data[$height]) ? $data[$height] : NULL;
    $dim    = ['width' => $width, 'height' => $height];

    // Funnily $uri is ignored at all core image effects.
    $style->transformDimensions($dim, $data[$uri]);

    // Sometimes they are string, cast them integer to reduce JS logic.
    if ($dim['width'] != NULL) {
      $dim['width'] = (int) $dim['width'];
    }
    if ($dim['height'] != NULL) {
      $dim['height'] = (int) $dim['height'];
    }
    return ['width' => $dim['width'], 'height' => $dim['height']];
  }

  /**
   * A wrapper for file_url_transform_relative() to pass tests anywhere else.
   */
  public static function transformRelative($uri, $style = NULL) {
    $url = $style ? $style->buildUrl($uri) : file_create_url($uri);
    return file_url_transform_relative($url);
  }

  /**
   * Checks if extension should not use image style: apng svg gif, etc.
   */
  public static function unstyled(array $settings) {
    $extensions = ['svg'];
    if (isset($settings['unstyled_extensions']) && $unstyled = $settings['unstyled_extensions']) {
      $extensions = array_merge($extensions, array_map('trim', explode(' ', mb_strtolower($unstyled))));
      $extensions = array_unique($extensions);
    }
    return isset($settings['extension']) && in_array($settings['extension'], $extensions);
  }

}
