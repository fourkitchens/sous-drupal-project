<?php

namespace Drupal\slick\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Trait common for slick formatters.
 */
trait SlickFormatterTrait {

  /**
   * The slick field formatter manager.
   *
   * @var \Drupal\slick\SlickManagerInterface
   */
  protected $manager;

  /**
   * The image factory service.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory = NULL;

  /**
   * Returns the image factory.
   *
   * @todo deprecated in blazy:8.x-2.0 and is removed from blazy:8.x-3.0. Use
   *   BlazyOEmbed::imageFactory() instead.
   * @see https://www.drupal.org/node/3103018
   */
  public function imageFactory() {
    if (is_null($this->imageFactory)) {
      $this->imageFactory = \Drupal::service('image.factory');
    }
    return $this->imageFactory;
  }

  /**
   * Returns the slick field formatter service.
   */
  public function formatter() {
    return $this->formatter;
  }

  /**
   * Returns the slick service.
   */
  public function manager() {
    return $this->manager;
  }

  /**
   * Returns the slick admin service shortcut.
   */
  public function admin() {
    return \Drupal::service('slick.admin');
  }

  /**
   * Injects DI services.
   */
  protected static function injectServices($instance, ContainerInterface $container, $type = '') {
    $instance->formatter = $instance->blazyManager = $container->get('slick.formatter');
    $instance->manager = $container->get('slick.manager');

    // Blazy:2.x+ might already set these, provides a failsafe.
    if ($type == 'image' || $type == 'entity') {
      $instance->imageFactory = isset($instance->imageFactory) ? $instance->imageFactory : $container->get('image.factory');
      if ($type == 'entity') {
        $instance->loggerFactory = isset($instance->loggerFactory) ? $instance->loggerFactory : $container->get('logger.factory');
        $instance->blazyEntity = isset($instance->blazyEntity) ? $instance->blazyEntity : $container->get('blazy.entity');
        $instance->blazyOembed = isset($instance->blazyOembed) ? $instance->blazyOembed : $instance->blazyEntity->oembed();
      }
    }

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return $this->admin()->getSettingsSummary($this->getScopedFormElements());
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getFieldStorageDefinition()->isMultiple();
  }

  /**
   * Builds the settings.
   */
  public function buildSettings() {
    $settings = array_merge($this->getCommonFieldDefinition(), $this->getSettings());
    $settings['third_party'] = $this->getThirdPartySettings();
    return $settings;
  }

  /**
   * Defines the common scope for both front and admin.
   */
  public function getCommonFieldDefinition() {
    $field = $this->fieldDefinition;
    return [
      'namespace'         => 'slick',
      'current_view_mode' => $this->viewMode,
      'field_name'        => $field->getName(),
      'field_type'        => $field->getType(),
      'entity_type'       => $field->getTargetEntityTypeId(),
      'plugin_id'         => $this->getPluginId(),
      'target_type'       => $this->getFieldSetting('target_type'),
    ];
  }

  /**
   * Defines the common scope for the form elements.
   */
  public function getCommonScopedFormElements() {
    return ['settings' => $this->getSettings()] + $this->getCommonFieldDefinition();
  }

}
