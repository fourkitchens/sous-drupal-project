<?php

namespace Drupal\slick\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\blazy\Plugin\Field\FieldFormatter\BlazyFileFormatterBase;
use Drupal\slick\SlickDefault;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for slick image and file ER formatters.
 */
abstract class SlickFileFormatterBase extends BlazyFileFormatterBase {

  use SlickFormatterTrait;
  use SlickFormatterViewTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    return self::injectServices($instance, $container, 'image');
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return SlickDefault::imageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entities = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($entities)) {
      return [];
    }

    return $this->commonViewElements($items, $langcode, $entities);
  }

  /**
   * Build the slick carousel elements.
   */
  public function buildElements(array &$build, $files) {
    $settings   = &$build['settings'];
    $item_id    = $settings['item_id'];
    $tn_caption = empty($settings['thumbnail_caption']) ? NULL : $settings['thumbnail_caption'];

    foreach ($files as $delta => $file) {
      $settings['delta'] = $delta;
      $settings['type'] = 'image';

      /** @var Drupal\image\Plugin\Field\FieldType\ImageItem $item */
      $item = $file->_referringItem;

      $settings['file_tags'] = $file->getCacheTags();
      $settings['uri']       = $file->getFileUri();

      $element = ['item' => $item, 'settings' => $settings];

      // @todo remove, no longer file entity/VEF/M for pure Media.
      $this->buildElement($element, $file);
      $settings = $element['settings'];

      // Image with responsive image, lazyLoad, and lightbox supports.
      $element[$item_id] = $this->formatter->getBlazy($element);

      if (!empty($settings['caption'])) {
        foreach ($settings['caption'] as $caption) {
          $element['caption'][$caption] = empty($element['item']->{$caption}) ? [] : ['#markup' => Xss::filterAdmin($element['item']->{$caption})];
        }
      }

      // Build individual slick item.
      $build['items'][$delta] = $element;

      // Build individual slick thumbnail.
      if (!empty($settings['nav'])) {
        $thumb = ['settings' => $settings];

        // Thumbnail usages: asNavFor pagers, dot, arrows, photobox thumbnails.
        $thumb[$item_id] = empty($settings['thumbnail_style']) ? [] : $this->formatter->getThumbnail($settings, $element['item']);
        $thumb['caption'] = empty($element['item']->{$tn_caption}) ? [] : ['#markup' => Xss::filterAdmin($element['item']->{$tn_caption})];

        $build['thumb']['items'][$delta] = $thumb;
        unset($thumb);
      }

      unset($element);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getScopedFormElements() {
    return [
      'namespace'       => 'slick',
      'nav'             => TRUE,
      'thumb_captions'  => ['title' => $this->t('Title'), 'alt' => $this->t('Alt')],
      'thumb_positions' => TRUE,
    ] + parent::getScopedFormElements();
  }

}
