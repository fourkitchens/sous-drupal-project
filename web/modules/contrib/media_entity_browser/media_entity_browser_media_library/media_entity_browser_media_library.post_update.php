<?php

/**
 * @file
 * Post update file.
 */

use Drupal\views\Entity\View;

/**
 * Update the MEB Media library view with D8.7 Media Library row classes.
 */
function media_entity_browser_media_library_post_update_fix_views_classes() {
  /** @var \Drupal\views\Entity\View $view */
  if ($view = View::load('media_entity_browser_media_library')) {

    $display = &$view->getDisplay('default');

    if (!empty($display)) {
      // Merge classes.
      $core_media_library_classes = 'media-library-item media-library-item--grid js-media-library-item js-click-to-select';
      $core_classes_array = explode(' ', $core_media_library_classes);
      $view_classes = explode(' ', $display['display_options']['style']['options']['row_class']);

      $classes = array_unique(array_merge($view_classes, $core_classes_array));

      $display['display_options']['style']['options']['row_class'] = implode(' ', $classes);
      $view->trustData()->save();
    }
  }
}
