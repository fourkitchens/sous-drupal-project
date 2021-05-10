<?php

namespace Drupal\blazy;

/**
 * Provides grid utilities.
 */
class BlazyGrid {

  /**
   * Returns items wrapped by theme_item_list(), can be a grid, or plain list.
   *
   * @param array $items
   *   The grid items being modified.
   * @param array $settings
   *   The given settings.
   *
   * @return array
   *   The modified array of grid items.
   */
  public static function build(array $items = [], array $settings = []) {
    $settings  += BlazyDefault::htmlSettings();
    $style      = empty($settings['style']) ? '' : $settings['style'];
    $is_grid    = isset($settings['_grid']) ? $settings['_grid'] : (!empty($settings['style']) && !empty($settings['grid']));
    $class_item = $is_grid ? 'grid' : 'blazy__item';

    $contents = [];
    foreach ($items as $item) {
      // Support non-Blazy which normally uses item_id.
      $attributes    = isset($item['attributes']) ? $item['attributes'] : [];
      $item_settings = isset($item['settings']) ? $item['settings'] : $settings;
      $item_settings = isset($item['#build']) && isset($item['#build']['settings']) ? $item['#build']['settings'] : $item_settings;
      unset($item['settings'], $item['attributes'], $item['item']);

      // Good for Bootstrap .well/ .card class, must cast or BS will reset.
      $classes = empty($item_settings['grid_content_class']) ? [] : (array) $item_settings['grid_content_class'];

      // Supports both single formatter field and complex fields such as Views.
      $content['content'] = $is_grid ? [
        '#theme'      => 'container',
        '#children'   => $item,
        '#attributes' => ['class' => array_merge(['grid__content'], $classes)],
      ] : $item;

      if (!empty($item_settings['grid_item_class'])) {
        $attributes['class'][] = $item_settings['grid_item_class'];
      }

      $classes = isset($attributes['class']) ? $attributes['class'] : [];
      $attributes['class'] = array_merge([$class_item], $classes);
      $content['#wrapper_attributes'] = $attributes;

      $contents[] = $content;
    }

    $settings['count'] = empty($settings['count']) ? count($contents) : $settings['count'];
    $wrapper = ['item-list--blazy', 'item-list--blazy-' . $style];
    $wrapper = $style ? $wrapper : ['item-list--blazy'];
    $wrapper = array_merge(['item-list'], $wrapper);
    $element = [
      '#theme'              => 'item_list',
      '#items'              => $contents,
      '#context'            => ['settings' => $settings],
      '#attributes'         => [],
      '#wrapper_attributes' => ['class' => $wrapper],
    ];

    // Supports field label via Field UI, unless use_field takes place.
    if (empty($settings['use_field']) && isset($settings['label'], $settings['label_display']) && $settings['label_display'] != 'hidden') {
      $element['#title'] = $settings['label'];
    }

    self::attributes($element['#attributes'], $settings);

    return $element;
  }

  /**
   * Provides reusable container attributes.
   */
  public static function attributes(array &$attributes, array $settings = []) {
    $style      = empty($settings['style']) ? '' : $settings['style'];
    $is_gallery = !empty($settings['lightbox']) && !empty($settings['gallery_id']);
    $is_grid    = isset($settings['_grid']) ? $settings['_grid'] : (!empty($settings['style']) && !empty($settings['grid']));

    // Provides data-attributes to avoid conflict with original implementations.
    Blazy::containerAttributes($attributes, $settings);

    // Provides gallery ID, although Colorbox works without it, others may not.
    // Uniqueness is not crucial as a gallery needs to work across entities.
    if (!empty($settings['id'])) {
      $attributes['id'] = $is_gallery ? $settings['gallery_id'] : $settings['id'];
    }

    // Limit to grid only, so to be usable for plain list.
    if ($is_grid) {
      $attributes['class'][] = 'blazy--grid block-' . $style . ' block-count-' . $settings['count'];

      // Adds common grid attributes for CSS3 column, Foundation, etc.
      if ($settings['grid_large'] = $settings['grid']) {
        foreach (['small', 'medium', 'large'] as $grid) {
          if (!empty($settings['grid_' . $grid])) {
            $attributes['class'][] = $grid . '-block-' . $style . '-' . $settings['grid_' . $grid];
          }
        }
      }
    }
  }

}
