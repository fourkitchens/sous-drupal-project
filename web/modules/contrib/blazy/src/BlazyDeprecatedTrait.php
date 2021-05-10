<?php

namespace Drupal\blazy;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * A Trait common for deprecated methods for easy removal and declutter.
 *
 * @todo remove at blazy:8.x-2.1, or earlier.
 * @see https://www.drupal.org/node/3103018
 */
trait BlazyDeprecatedTrait {

  /**
   * Implements hook_field_formatter_info_alter().
   *
   * @todo remove from blazy:8.x-2.1 for
   *   \Drupal\blazy\Plugin\Field\FieldFormatter\BlazyMediaFormatter.
   * @see https://www.drupal.org/node/3103018
   */
  public static function fieldFormatterInfoAlter(array &$info) {
    // Supports optional Media Entity via VEM/VEF if available.
    $common = [
      'description' => new TranslatableMarkup('Displays lazyloaded images, or iframes, for VEF/ ME.'),
      'quickedit'   => ['editor' => 'disabled'],
      'provider'    => 'blazy',
    ];

    $info['blazy_file'] = $common + [
      'id'          => 'blazy_file',
      'label'       => new TranslatableMarkup('Blazy Image with VEF (deprecated)'),
      'class'       => 'Drupal\blazy\Plugin\Field\FieldFormatter\BlazyFileFormatter',
      'field_types' => ['entity_reference', 'image'],
    ];

    $info['blazy_video'] = $common + [
      'id'          => 'blazy_video',
      'label'       => new TranslatableMarkup('Blazy Video (deprecated)'),
      'class'       => 'Drupal\blazy\Plugin\Field\FieldFormatter\BlazyVideoFormatter',
      'field_types' => ['video_embed_field'],
    ];
  }

}
