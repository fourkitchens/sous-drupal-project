<?php

/**
 * @file
 * Hooks provided by this module.
 *
 * @author Jim Berry ("solotandem", http://drupal.org/user/240748)
 */

use Drupal\google_tag\Entity\Container;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the state of snippet insertion on the current page response.
 *
 * This hook allows other modules to alter the state of snippet insertion based
 * on custom conditions that cannot be defined by the status, path, and role
 * conditions provided by this module.
 *
 * @param bool $satisfied
 *   The snippet insertion state.
 * @param \Drupal\google_tag\Entity\Container $container
 *   The associated container object.
 */
function hook_google_tag_insert_alter(&$satisfied, Container $container) {
  // Do something to the state.
  $state = !$state;
}

/**
 * Alter the snippets to be inserted on a page response.
 *
 * This hook allows other modules to alter the snippets to be inserted based on
 * custom settings not defined by this module.
 *
 * @param array $snippets
 *   Associative array of snippets keyed by type: script, noscript and
 *   data_layer.
 * @param \Drupal\google_tag\Entity\Container $container
 *   The associated container object.
 */
function hook_google_tag_snippets_alter(array &$snippets, Container $container) {
  // Do something to the script snippet.
  $snippets['script'] = str_replace('insertBefore', 'insertAfter', $snippets['script']);
}
