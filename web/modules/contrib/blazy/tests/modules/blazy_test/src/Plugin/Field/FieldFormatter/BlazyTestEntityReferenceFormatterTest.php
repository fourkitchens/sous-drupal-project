<?php

namespace Drupal\blazy_test\Plugin\Field\FieldFormatter;

use Drupal\blazy\BlazyDefault;
use Drupal\blazy\Dejavu\BlazyEntityReferenceBase;
use Drupal\blazy\Plugin\Field\FieldFormatter\BlazyFormatterTrait;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Blazy Entity Reference' formatter.
 *
 * @FieldFormatter(
 *   id = "blazy_entity_test",
 *   label = @Translation("Blazy Entity Reference Test"),
 *   field_types = {"entity_reference", "file"}
 * )
 */
class BlazyTestEntityReferenceFormatterTest extends BlazyEntityReferenceBase implements ContainerFactoryPluginInterface {

  use BlazyFormatterTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    return self::injectServices($instance, $container, 'entity');
  }

  /**
   * Returns the blazy_test admin service shortcut.
   */
  public function admin() {
    return \Drupal::service('blazy_test.admin');
  }

  /**
   * Returns the slick service.
   */
  public function blazyEntity() {
    return $this->blazyEntity;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return BlazyDefault::extendedSettings() + BlazyDefault::gridSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entities = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($entities)) {
      return [];
    }

    // Collects specific settings to this formatter.
    $settings = $this->buildSettings();
    $build = ['settings' => $settings];

    $this->formatter()->buildSettings($build, $items);

    // Build the elements.
    $this->buildElements($build, $entities, $langcode);

    // Pass to manager for easy updates to all Blazy formatters.
    return $this->formatter->build($build);
  }

  /**
   * Builds the settings.
   */
  public function buildSettings() {
    $settings              = $this->getSettings();
    $settings['blazy']     = TRUE;
    $settings['lazy']      = 'blazy';
    $settings['item_id']   = 'box';
    $settings['plugin_id'] = $this->getPluginId();

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getScopedFormElements() {
    $admin       = $this->admin();
    $target_type = $this->getFieldSetting('target_type');
    $views_ui    = $this->getFieldSetting('handler') == 'default';
    $bundles     = $views_ui ? [] : $this->getFieldSetting('handler_settings')['target_bundles'];
    $node        = $admin->getFieldOptions($bundles, ['entity_reference'], $target_type, 'node');
    $stages      = $admin->getFieldOptions($bundles, ['image'], $target_type);

    return [
      'namespace'  => 'blazy_test',
      'images'     => $stages,
      'overlays'   => $stages + $node,
      'thumbnails' => $stages,
      'optionsets' => ['default' => 'Default'],
    ] + parent::getScopedFormElements();
  }

}
