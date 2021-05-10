<?php

namespace Drupal\blazy;

/**
 * Provides common entity utilities to work with field details.
 *
 * This is alternative to Drupal\blazy\BlazyFormatter used outside
 * field managers, such as Views field, or Slick/Entity Browser displays, etc.
 *
 * @see Drupal\blazy\Dejavu\BlazyEntityReferenceBase
 * @see Drupal\blazy\Plugin\Field\FieldFormatter\BlazyMediaFormatterBase
 */
interface BlazyEntityInterface {

  /**
   * Build image/video preview either using theme_blazy(), or view builder.
   *
   * @param array $data
   *   An array of data containing settings, and image item.
   * @param object $entity
   *   The media entity, else file entity to be associated to media if any.
   * @param string $fallback
   *   The fallback string to display such as file name or entity label.
   *
   * @return array
   *   The renderable array of theme_blazy(), or view builder, else empty array.
   */
  public function build(array &$data, $entity, $fallback = '');

  /**
   * Returns the entity view, if available.
   *
   * @param object $entity
   *   The entity being rendered.
   * @param array $settings
   *   The settings containing view_mode.
   * @param string $fallback
   *   The fallback content when all fails, probably just entity label.
   *
   * @return array|bool
   *   The renderable array of the view builder, or false if not applicable.
   */
  public function getEntityView($entity, array $settings = [], $fallback = '');

}
