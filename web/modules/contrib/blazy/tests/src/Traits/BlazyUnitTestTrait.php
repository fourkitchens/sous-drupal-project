<?php

namespace Drupal\Tests\blazy\Traits;

use Drupal\Core\Cache\Cache;
use Drupal\blazy\BlazyDefault;

/**
 * A Trait common for Blazy Unit tests.
 */
trait BlazyUnitTestTrait {

  use BlazyPropertiesTestTrait;

  /**
   * The formatter settings.
   *
   * @var array
   */
  protected $formatterSettings = [];

  /**
   * Returns sensible formatter settings for testing purposes.
   *
   * @return array
   *   The formatter settings.
   */
  protected function getFormatterSettings() {
    $defaults = [
      'box_caption'     => 'custom',
      'box_style'       => 'large',
      'cache'           => 0,
      'image_style'     => 'blazy_crop',
      'media_switch'    => 'blazy_test',
      'thumbnail_style' => 'thumbnail',
      'ratio'           => 'fluid',
      'caption'         => ['alt' => 'alt', 'title' => 'title'],
      'sizes'           => '100w',
    ] + BlazyDefault::extendedSettings() + BlazyDefault::itemSettings() + $this->getDefaultFieldDefinition();

    return empty($this->formatterSettings) ? $defaults : array_merge($defaults, $this->formatterSettings);
  }

  /**
   * Sets formatter settings.
   *
   * @param array $settings
   *   The given settings.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  protected function setFormatterSettings(array $settings = []) {
    $this->formatterSettings = array_merge($this->getFormatterSettings(), $settings);
    return $this;
  }

  /**
   * Sets formatter setting.
   *
   * @param string $setting
   *   The given setting.
   * @param mixed|bool|string $value
   *   The given value.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  protected function setFormatterSetting($setting, $value) {
    $this->formatterSettings[$setting] = $value;
    return $this;
  }

  /**
   * Returns the default field definition.
   *
   * @return array
   *   The default field definition.
   */
  protected function getDefaultFieldDefinition() {
    return [
      'bundle'            => isset($this->bundle) ? $this->bundle : 'bundle_test',
      'current_view_mode' => 'default',
      'entity_type'       => $this->entityType,
      'field_name'        => $this->testFieldName,
      'field_type'        => 'image',
    ];
  }

  /**
   * Returns the default field formatter definition.
   *
   * @return array
   *   The default field formatter settings.
   */
  protected function getDefaultFormatterDefinition() {
    // @todo: Will be replaced by `form` array below.
    $deprecated = [
      'grid_form'         => TRUE,
      'image_style_form'  => TRUE,
      'fieldable_form'    => TRUE,
      'media_switch_form' => TRUE,
    ];

    return [
      'background'        => TRUE,
      'box_captions'      => TRUE,
      'captions'          => ['alt' => 'Alt', 'title' => 'Title'],
      'classes'           => ['field_class' => 'Classes'],
      'multimedia'        => TRUE,
      'images'            => [$this->testFieldName => $this->testFieldName],
      'layouts'           => ['top' => 'Top'],
      'links'             => ['field_link' => 'Link'],
      'namespace'         => 'blazy',
      'responsive_image'  => TRUE,
      'thumbnail_style'   => TRUE,
      'skins'             => ['classic' => 'Classic'],
      'style'             => 'grid',
      'target_type'       => 'file',
      'titles'            => ['field_text' => 'Text'],
      'view_mode'         => 'default',
      'settings'          => $this->getFormatterSettings(),
      'form'              => [
        'fieldable',
        'grid',
        'image_style',
        'media_switch',
      ],
    ] + $deprecated + $this->getDefaultFieldDefinition();
  }

  /**
   * Returns the default field formatter definition.
   *
   * @return array
   *   The default field formatter settings.
   */
  protected function getDefaulEntityFormatterDefinition() {
    return [
      'nav'              => TRUE,
      'overlays'         => ['field_image' => 'Image'],
      'vanilla'          => TRUE,
      'optionsets'       => ['default' => 'Default'],
      'thumbnails'       => TRUE,
      'thumbnail_effect' => ['grid', 'hover'],
      'thumbnail_style'  => TRUE,
      'thumb_captions'   => ['field_text' => 'Text'],
      'thumb_positions'  => TRUE,
      'caches'           => TRUE,
    ];
  }

  /**
   * Returns the field formatter definition along with settings.
   *
   * @return array
   *   The field formatter settings.
   */
  protected function getFormatterDefinition() {
    $defaults = $this->getDefaultFormatterDefinition();
    return empty($this->formatterDefinition) ? $defaults : array_merge($defaults, $this->formatterDefinition);
  }

  /**
   * Sets the field formatter definition.
   *
   * @param string $definition
   *   The key definition defining scope for form elements.
   * @param mixed|string|bool $value
   *   The defined value.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  protected function setFormatterDefinition($definition, $value) {
    $this->formatterDefinition[$definition] = $value;
    return $this;
  }

  /**
   * Return dummy cache metadata.
   */
  protected function getCacheMetaData() {
    $build = [];
    $suffixes[] = 3;
    foreach (['contexts', 'keys', 'tags'] as $key) {
      if ($key == 'contexts') {
        $cache = ['languages'];
      }
      elseif ($key == 'keys') {
        $cache = ['blazy_image'];
      }
      elseif ($key == 'tags') {
        $cache = Cache::buildTags('file:123', $suffixes, '.');
      }

      $build['cache_' . $key] = $cache;
    }
    return $build;
  }

  /**
   * Pre render Blazy image.
   *
   * @param array $build
   *   The data containing: settings and image item.
   *
   * @return array
   *   The pre_render element.
   */
  protected function doPreRenderImage(array $build = []) {
    $image = $this->blazyManager->getBlazy($build);

    $image['#build']['settings'] = array_merge($this->getCacheMetaData(), $build['settings']);
    $image['#build']['item'] = $build['item'];
    return $this->blazyManager->preRenderBlazy($image);
  }

  /**
   * Returns dummy fields for an entity reference.
   *
   * @return array
   *   A common field array for Blazy related entity reference formatter.
   */
  protected function getDefaultFields($select = FALSE) {
    $fields = [
      'field_class'  => 'text',
      'field_id'     => 'text',
      'field_image'  => 'image',
      'field_layout' => 'list_string',
      'field_link'   => 'link',
      'field_title'  => 'text',
      'field_teaser' => 'text',
    ];

    $options = [];
    foreach (array_keys($fields) as $key) {
      if (in_array($key, ['field_id', 'field_teaser'])) {
        continue;
      }
      $option = str_replace('field_', '', $key);
      $options[$option] = $key;
    }

    return $select ? $options : $fields;
  }

  /**
   * Set up Blazy variables.
   */
  protected function setUpVariables() {
    $this->entityType    = 'node';
    $this->bundle        = 'bundle_test';
    $this->testFieldName = 'field_image_multiple';
    $this->testFieldType = 'image';
    $this->testPluginId  = 'blazy';
    $this->maxItems      = 3;
    $this->maxParagraphs = 30;
  }

  /**
   * Setup the unit images.
   */
  protected function setUpUnitImages() {
    $item = new \stdClass();
    $item->uri = 'public://example.jpg';
    $item->entity = NULL;
    $item->alt = $this->randomMachineName();
    $item->title = $this->randomMachineName();

    $settings = $this->getFormatterSettings();

    $this->uri = $settings['uri'] = $item->uri;

    $this->data = [
      'settings' => $settings,
      'item' => $item,
    ];

    $this->testItem = $item;
  }

  /**
   * Setup the unit images.
   */
  protected function setUpMockImage() {
    $entity = $this->createMock('\Drupal\Core\Entity\ContentEntityInterface');
    $entity->expects($this->any())
      ->method('label')
      ->willReturn($this->randomMachineName());
    $entity->expects($this->any())
      ->method('getEntityTypeId')
      ->will($this->returnValue('node'));

    $item = $this->createMock('\Drupal\Core\Field\FieldItemListInterface');
    $item->expects($this->any())
      ->method('getEntity')
      ->willReturn($entity);

    $this->setUpUnitImages();

    $this->testItem = $item;
    $this->data['item'] = $item;
    $item->entity = $entity;
  }

}

namespace Drupal\blazy;

if (!function_exists('blazy_alterable_settings')) {

  /**
   * Dummy function.
   */
  function blazy_alterable_settings() {
    // Empty block to satisfy coder.
  }

}

if (!function_exists('file_create_url')) {

  /**
   * Dummy function.
   */
  function file_create_url() {
    // Empty block to satisfy coder.
  }

}

if (!function_exists('file_url_transform_relative')) {

  /**
   * Dummy function.
   */
  function file_url_transform_relative() {
    // Empty block to satisfy coder.
  }

}

if (!function_exists('file_valid_uri')) {

  /**
   * Dummy function.
   */
  function file_valid_uri() {
    // Empty block to satisfy coder.
  }

}


if (!function_exists('blazy')) {

  /**
   * Dummy function.
   */
  function blazy() {
    // Empty block to satisfy coder.
  }

}
