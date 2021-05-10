<?php

namespace Drupal\blazy;

use Drupal\Component\Utility\Xss;
use Drupal\Component\Serialization\Json;
use Drupal\image\Entity\ImageStyle;

/**
 * Provides lightbox utilities.
 */
class BlazyLightbox {

  /**
   * Gets media switch elements: all lightboxes, not content, nor iframe.
   *
   * @param array $element
   *   The element being modified.
   */
  public static function build(array &$element = []) {
    $item       = $element['#item'];
    $settings   = &$element['#settings'];
    $uri        = $settings['uri'];
    $switch     = $settings['media_switch'];
    $switch_css = str_replace('_', '-', $switch);
    $valid      = BlazyUtil::isValidUri($uri);

    // Provide relevant URL if it is a lightbox.
    $url_attributes = &$element['#url_attributes'];
    $url_attributes['class'][] = 'blazy__' . $switch_css . ' litebox';
    $url_attributes['data-' . $switch_css . '-trigger'] = TRUE;
    $element['#icon']['litebox']['#markup'] = '<span class="media__icon media__icon--litebox"></span>';

    // Gallery is determined by a view, or overriden by colorbox settings.
    $gallery_enabled = !empty($settings['view_name']);
    $gallery_default = $gallery_enabled ? $settings['view_name'] . '-' . $settings['current_view_mode'] : 'blazy-' . $switch_css;

    // Respects colorbox settings unless for an explicit view gallery.
    if (!$gallery_enabled && $switch === 'colorbox' && function_exists('colorbox_theme')) {
      $gallery_enabled = (bool) \Drupal::config('colorbox.settings')->get('custom.slideshow.slideshow');
    }

    // The gallery_id might be a formatter inside a view, not aware of its view.
    // The formatter might be duplicated on a page, although rare at production.
    $gallery_id             = empty($settings['gallery_id']) ? $gallery_default : $settings['gallery_id'] . '-' . $gallery_default;
    $settings['gallery_id'] = !$gallery_enabled ? NULL : str_replace('_', '-', $gallery_id);
    $settings['box_url']    = $valid ? BlazyUtil::transformRelative($uri) : $uri;
    $settings['box_width']  = empty($settings['width']) ? NULL : $settings['width'];
    $settings['box_height'] = empty($settings['height']) ? NULL : $settings['height'];

    $dimensions = [
      'width' => $settings['box_width'],
      'height' => $settings['box_height'],
      'uri' => $uri,
    ];
    if (!empty($settings['box_style']) && $valid) {
      if ($box_style = ImageStyle::load($settings['box_style'])) {
        $dimensions = array_merge($dimensions, BlazyUtil::transformDimensions($box_style, $dimensions));
        $settings['box_url'] = BlazyUtil::transformRelative($uri, $box_style);
      }
    }

    // Allows custom work to override this without image style, such as
    // a combo of image, video, Instagram, Facebook, etc.
    if (empty($settings['_box_width'])) {
      $settings['box_width'] = $dimensions['width'];
      $settings['box_height'] = $dimensions['height'];
    }

    $json = [
      'width'  => $settings['box_width'],
      'height' => $settings['box_height'],
    ];

    // Might not be present from BlazyFilter.
    foreach (['bundle', 'type'] as $key) {
      if (!empty($settings[$key])) {
        $json[$key] = $settings[$key];
      }
    }

    // This allows PhotoSwipe with videos still swipable.
    if (!empty($settings['box_media_style']) && $valid) {
      if ($box_media_style = ImageStyle::load($settings['box_media_style'])) {
        $dimensions = array_merge($dimensions, BlazyUtil::transformDimensions($box_media_style, $dimensions));
        $settings['box_media_url'] = BlazyUtil::transformRelative($uri, $box_media_style);
      }
    }

    $url = $settings['box_url'];
    $videos = ['remote_video', 'video'];
    if (isset($json['bundle']) && in_array($json['bundle'], $videos)) {
      $json['width']  = 640;
      $json['height'] = 360;

      // Force autoplay for media URL on lightboxes, saving another click.
      if (!empty($settings['embed_url'])) {
        $url = $settings['embed_url'];
        $url_attributes['data-oembed-url'] = $settings['embed_url'];

        // This allows PhotoSwipe with remote videos still swipable.
        if (!empty($settings['box_media_url'])) {
          $settings['box_url'] = $settings['box_media_url'];
        }

        if ($switch == 'photobox') {
          $url_attributes['rel'] = 'video';
        }
      }

      // Remote or local videos.
      if (!empty($settings['box_media_url'])) {
        $json['width'] = $settings['box_width']  = $dimensions['width'];
        $json['height'] = $settings['box_height'] = $dimensions['height'];
      }
    }

    if ($switch == 'colorbox') {
      // @todo make Blazy Grid without Blazy Views fields support multiple
      // fields and entities as a gallery group, likely via a class at Views UI.
      // Must use consistent key for multiple entities, hence cannot use id.
      // We do not have option for this like colorbox, as it is only limited
      // to the known Blazy formatters, or Blazy Views style plugins for now.
      // The hustle is Colorbox wants rel on individual item to group, unlike
      // other lightbox library which provides a way to just use a container.
      $json['rel'] = $settings['gallery_id'];
    }

    // @todo make is flexible for regular non-media HTML.
    if (!empty($element['#lightbox_html'])) {
      $pad = round((($json['height'] / $json['width']) * 100), 2);
      $content = [
        '#theme' => 'container',
        '#children' => $element['#lightbox_html'],
        '#attributes' => [
          'class' => ['media', 'media--ratio'],
          'style' => 'width:' . $json['width'] . 'px; padding-bottom: ' . $pad . '%;',
        ],
      ];
      $json['html'] = \blazy()->getRenderer()->renderPlain($content);
      unset($element['#lightbox_html']);
    }

    $url_attributes['data-media'] = Json::encode($json);

    if (!empty($settings['box_caption'])) {
      $element['#captions']['lightbox'] = self::buildCaptions($item, $settings);
    }

    $element['#url'] = $url;
  }

  /**
   * Builds lightbox captions.
   *
   * @param object|mixed $item
   *   The \Drupal\image\Plugin\Field\FieldType\ImageItem item.
   * @param array $settings
   *   The settings to work with.
   *
   * @return array
   *   The renderable array of caption, or empty array.
   */
  private static function buildCaptions($item, array $settings = []) {
    $title   = empty($item->title) ? '' : $item->title;
    $alt     = empty($item->alt) ? '' : $item->alt;
    $delta   = empty($settings['delta']) ? 0 : $settings['delta'];
    $caption = '';

    switch ($settings['box_caption']) {
      case 'auto':
        $caption = $alt ?: $title;
        break;

      case 'alt':
        $caption = $alt;
        break;

      case 'title':
        $caption = $title;
        break;

      case 'alt_title':
      case 'title_alt':
        $alt     = $alt ? '<p>' . $alt . '</p>' : '';
        $title   = $title ? '<h2>' . $title . '</h2>' : '';
        $caption = $settings['box_caption'] == 'alt_title' ? $alt . $title : $title . $alt;
        break;

      case 'entity_title':
        $caption = ($entity = $item->getEntity()) ? $entity->label() : '';
        break;

      case 'custom':
        $caption = '';
        if (!empty($settings['box_caption_custom']) && ($entity = $item->getEntity())) {
          $options = ['clear' => TRUE];
          $caption = \Drupal::token()->replace($settings['box_caption_custom'], [
            $entity->getEntityTypeId() => $entity,
            'file' => $item,
          ], $options);

          // Checks for multi-value text fields, and maps its delta to image.
          if (!empty($caption) && strpos($caption, ", <p>") !== FALSE) {
            $caption = str_replace(", <p>", '| <p>', $caption);
            $captions = explode("|", $caption);
            $caption = isset($captions[$delta]) ? $captions[$delta] : '';
          }
        }
        break;
    }

    return empty($caption)
      ? []
      : ['#markup' => Xss::filter($caption, BlazyDefault::TAGS)];
  }

}
