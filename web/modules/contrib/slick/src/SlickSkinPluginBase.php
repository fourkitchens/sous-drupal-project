<?php

namespace Drupal\slick;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides base class for all slick skins.
 */
abstract class SlickSkinPluginBase extends PluginBase implements SlickSkinPluginInterface {

  use StringTranslationTrait;

  /**
   * The slick main/thumbnail skin definitions.
   *
   * @var array
   */
  protected $skins;

  /**
   * The slick arrow skin definitions.
   *
   * @var array
   */
  protected $arrows;

  /**
   * The slick dot skin definitions.
   *
   * @var array
   */
  protected $dots;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->skins = $this->setSkins();
    $this->arrows = $this->setArrows();
    $this->dots = $this->setDots();
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->configuration['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function skins() {
    return $this->skins;
  }

  /**
   * {@inheritdoc}
   */
  public function arrows() {
    return $this->arrows;
  }

  /**
   * {@inheritdoc}
   */
  public function dots() {
    return $this->dots;
  }

  /**
   * Sets the required plugin main/thumbnail skins.
   */
  abstract protected function setSkins();

  /**
   * Sets the optional/ empty plugin arrow skins.
   */
  protected function setArrows() {
    return [];
  }

  /**
   * Sets the optional/ empty plugin dot skins.
   */
  protected function setDots() {
    return [];
  }

}
