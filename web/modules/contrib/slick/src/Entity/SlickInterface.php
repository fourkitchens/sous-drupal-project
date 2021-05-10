<?php

namespace Drupal\slick\Entity;

/**
 * Provides an interface defining a Slick entity.
 */
interface SlickInterface extends SlickBaseInterface {

  /**
   * Returns the number of breakpoints.
   *
   * @return int
   *   The number of the provided breakpoints.
   */
  public function getBreakpoints();

  /**
   * Returns the Slick skin.
   *
   * @return string
   *   The name of the Slick skin.
   */
  public function getSkin();

  /**
   * Returns the group this optioset instance belongs to for easy selections.
   *
   * @return string
   *   The name of the optionset group.
   */
  public function getGroup();

  /**
   * Returns whether to optimize the stored options, or not.
   *
   * @return bool
   *   If true, the stored options will be cleaned out from defaults.
   */
  public function optimized();

}
