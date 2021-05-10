<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Trait common for all blazy formatters.
 */
trait BlazyFormatterTrait {

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyFormatterManager
   */
  protected $formatter;

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $blazyManager;

  /**
   * Returns the blazy formatter manager.
   */
  public function formatter() {
    return $this->formatter;
  }

  /**
   * Returns the blazy manager.
   */
  public function blazyManager() {
    return $this->blazyManager;
  }

  /**
   * Returns the blazy admin service.
   */
  public function admin() {
    return \Drupal::service('blazy.admin.formatter');
  }

  /**
   * Injects DI services.
   */
  protected static function injectServices($instance, ContainerInterface $container, $type = '') {
    $instance->formatter = $instance->blazyManager = $container->get('blazy.formatter');

    // Provides optional services.
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
   * Builds the settings.
   */
  public function buildSettings() {
    $settings = array_merge($this->getCommonFieldDefinition(), $this->getSettings());
    $settings['blazy'] = TRUE;
    $settings['item_id'] = $settings['lazy'] = 'blazy';
    $settings['_grid'] = !empty($settings['style']) && !empty($settings['grid']);
    $settings['third_party'] = $this->getThirdPartySettings();

    // Exposes few basic formatter settings w/o use_field.
    $settings['label'] = $this->fieldDefinition->getLabel();
    $settings['label_display'] = $this->label;
    return $settings;
  }

  /**
   * Defines the common scope for both front and admin.
   */
  public function getCommonFieldDefinition() {
    $field = $this->fieldDefinition;
    return [
      'namespace'        => 'blazy',
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
