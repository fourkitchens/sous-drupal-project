<?php

namespace Drupal\blazy;

/**
 * Defines re-usable services and functions for blazy plugins.
 */
interface BlazyManagerInterface {

  /**
   * Returns array of needed assets suitable for #attached property.
   *
   * @param array $attach
   *   The settings which determine what library to attach.
   *
   * @return array
   *   The supported libraries.
   */
  public function attach(array $attach = []);

  /**
   * Returns drupalSettings for IO.
   *
   * @param array $attach
   *   The settings which determine what library to attach.
   *
   * @return array
   *   The supported IO drupalSettings.
   */
  public function getIoSettings(array $attach = []);

  /**
   * Gets the supported lightboxes.
   *
   * @return array
   *   The supported lightboxes.
   */
  public function getLightboxes();

  /**
   * Returns the supported image effects.
   *
   * @return array
   *   The supported image effects.
   */
  public function getImageEffects();

  /**
   * Checks for Blazy formatter such as from within a Views style plugin.
   *
   * Ensures the settings traverse up to the container where Blazy is clueless.
   * The supported plugins can add [data-blazy] attribute into its container
   * containing $settings['blazy_data'] converted into [data-blazy] JSON.
   * This allows Blazy Grid, or other Views styles, lacking of UI, to have
   * additional settings extracted from the first Blazy formatter found.
   * Such as media switch/ lightbox. This way the container can add relevant
   * attributes to its container, etc. Also applies to entity references where
   * Blazy is not the main formatter, instead embedded as part of the parent's.
   *
   * This fairly complex logic is intended to reduce similarly complex logic at
   * individual item. But rather than at individual item, it is executed once
   * at the container level. If you have 100 images, this method is executed
   * once, not 100x, as long as you have all image styles cropped, not scaled.
   *
   * This still needs improvements and a little more simplified version.
   *
   * @param array $settings
   *   The settings being modified.
   * @param array $item
   *   The first item containing settings or item keys.
   *
   * @see \Drupal\blazy\BlazyManager::prepareBuild()
   * @see \Drupal\blazy\Dejavu\BlazyEntityBase::buildElements()
   */
  public function isBlazy(array &$settings, array $item = []);

}
