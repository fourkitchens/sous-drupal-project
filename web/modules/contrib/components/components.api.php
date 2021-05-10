<?php

/**
 * @file
 * Hooks related to the Components module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the list of protected Twig namespaces.
 *
 * @param array $protectedNamespaces
 *   The array of protected Twig namespaces, keyed on the machine name of the
 *   namespace. Within each array entry, the following pieces of data are
 *   available:
 *   - name: While the array key is the default Twig namespace (which is also
 *     the machine name of the module/theme that owns it), this "name" is the
 *     friendly name of the module/theme used in Drupal's admin lists.
 *   - type: The extension type: module, theme, or profile.
 *   - package: The package name the module is listed under or an empty string.
 *
 * @see https://www.drupal.org/node/3190969
 */
function hook_protected_twig_namespaces_alter(array &$protectedNamespaces) {
  // Allow the "block" Twig namespace to be altered.
  unset($protectedNamespaces['block']);

  // Allow alteration of any Twig namespace for a "Core" module.
  foreach ($protectedNamespaces as $name => $info) {
    if ($info['package'] === 'Core') {
      unset($protectedNamespaces[$name]);
    }
  }

  // Allow alteration of any Twig namespace for any theme.
  foreach ($protectedNamespaces as $name => $info) {
    if ($info['type'] === 'theme') {
      unset($protectedNamespaces[$name]);
    }
  }

  // Allow alteration of all Twig namespaces.
  $protectedNamespaces = [];
}

/**
 * @} End of "addtogroup hooks".
 */
