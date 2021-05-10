<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Utility\Xss;

/**
 * Plugin implementation of the `Blazy File` or `Blazy Image` for Blazy only.
 *
 * @see \Drupal\blazy\Plugin\Field\FieldFormatter\BlazyFileFormatter
 * @see \Drupal\blazy\Plugin\Field\FieldFormatter\BlazyImageFormatter
 */
class BlazyFormatterBlazy extends BlazyFileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return [];
    }

    return $this->commonViewElements($items, $langcode, $files);
  }

  /**
   * Build the Blazy elements.
   */
  public function buildElements(array &$build, $files) {
    $settings = $build['settings'];

    foreach ($files as $delta => $file) {
      /** @var Drupal\image\Plugin\Field\FieldType\ImageItem $item */
      $item = $file->_referringItem;

      $settings['delta']     = $delta;
      $settings['file_tags'] = $file->getCacheTags();
      $settings['type']      = 'image';
      $settings['uri']       = $file->getFileUri();
      $box['item']           = $item;
      $box['settings']       = $settings;

      // Build individual element.
      $this->buildElement($box, $file);

      // Build caption if so configured.
      if (!empty($settings['caption'])) {
        foreach ($settings['caption'] as $caption) {
          if ($caption_content = $box['item']->{$caption}) {
            $box['captions'][$caption] = ['#markup' => Xss::filterAdmin($caption_content)];
          }
        }
      }

      // Image with grid, responsive image, lazyLoad, and lightbox supports.
      $build[$delta] = $this->formatter->getBlazy($box);
      unset($box);
    }
  }

}
