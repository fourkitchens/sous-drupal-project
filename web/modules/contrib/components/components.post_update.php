<?php

/**
 * @file
 * Post update functions for Components.
 */

/**
 * Clear caches to allow alter hooks used by components.info service.
 */
function components_post_update_components_info_alter() {
  // Empty post-update hook. @see https://www.drupal.org/node/2960601
}

/**
 * Clear caches to allow caching of data by components.info service.
 */
function components_post_update_components_info_cache() {
  // Empty post-update hook. @see https://www.drupal.org/node/2960601
}
