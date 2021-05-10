<?php

namespace Drupal\slick;

/**
 * Provides an interface defining Slick skins, and asset managements.
 */
interface SlickSkinManagerInterface {

  /**
   * Returns an instance of a plugin by given plugin id.
   *
   * @param string $id
   *   The plugin id.
   *
   * @return object
   *   Return instance of SlickSkin.
   */
  public function load($id);

}
