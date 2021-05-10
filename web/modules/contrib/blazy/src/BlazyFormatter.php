<?php

namespace Drupal\blazy;

/**
 * Provides common field formatter-related methods: Blazy, Slick.
 */
class BlazyFormatter extends BlazyManager implements BlazyFormatterInterface {

  /**
   * Checks if image dimensions are set.
   *
   * @var array
   */
  private $isImageDimensionSet;

  /**
   * Returns available styles with crop in the effect name.
   *
   * @var array
   */
  protected $cropStyles;

  /**
   * Checks if the image style contains crop in the effect name.
   *
   * @var array
   */
  protected $isCrop;

  /**
   * {@inheritdoc}
   */
  public function buildSettings(array &$build, $items) {
    $settings = &$build['settings'];
    $this->getCommonSettings($settings);

    $count          = $items->count();
    $field          = $items->getFieldDefinition();
    $entity         = $items->getEntity();
    $entity_type_id = $entity->getEntityTypeId();
    $entity_id      = $entity->id();
    $bundle         = $entity->bundle();
    $field_name     = $field->getName();
    $field_clean    = str_replace("field_", '', $field_name);
    $view_mode      = empty($settings['current_view_mode']) ? '_custom' : $settings['current_view_mode'];
    $namespace      = $settings['namespace'];
    $id             = isset($settings['id']) ? $settings['id'] : '';
    $gallery_id     = "{$namespace}-{$entity_type_id}-{$bundle}-{$field_clean}-{$view_mode}";
    $id             = Blazy::getHtmlId("{$gallery_id}-{$entity_id}", $id);
    $internal_path  = $absolute_path = NULL;

    // Deals with UndefinedLinkTemplateException such as paragraphs type.
    // @see #2596385, or fetch the host entity.
    if (!$entity->isNew() && method_exists($entity, 'hasLinkTemplate')) {
      if ($entity->hasLinkTemplate('canonical')) {
        $url = $entity->toUrl();
        $internal_path = $url->getInternalPath();
        $absolute_path = $url->setAbsolute()->toString();
      }
    }

    $settings['bundle']         = $bundle;
    $settings['cache_metadata'] = ['keys' => [$id, $count]];
    $settings['cache_tags'][]   = $entity_type_id . ':' . $entity_id;
    $settings['caption']        = empty($settings['caption']) ? [] : array_filter($settings['caption']);
    $settings['content_url']    = $settings['absolute_path'] = $absolute_path;
    $settings['count']          = $count;
    $settings['entity_id']      = $entity_id;
    $settings['entity_type_id'] = $entity_type_id;
    $settings['gallery_id']     = str_replace('_', '-', $gallery_id . '-' . $settings['media_switch']);
    $settings['id']             = $id;
    $settings['internal_path']  = $internal_path;
    $settings['use_field']      = !$settings['lightbox'] && isset($settings['third_party'], $settings['third_party']['linked_field']) && !empty($settings['third_party']['linked_field']['linked']);

    // Bail out if Vanilla mode is requested.
    if (!empty($settings['vanilla'])) {
      $settings = array_filter($settings);
      return;
    }

    // Lazy load types: blazy, and slick: ondemand, anticipated, progressive.
    $settings['blazy'] = !empty($settings['blazy']) || !empty($settings['background']) || $settings['resimage'];
    $settings['lazy']  = $settings['blazy'] ? 'blazy' : (isset($settings['lazy']) ? $settings['lazy'] : '');
    $settings['lazy']  = empty($settings['is_preview']) ? $settings['lazy'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function preBuildElements(array &$build, $items, array $entities = []) {
    $this->buildSettings($build, $items);
    $settings = &$build['settings'];

    // Pass first item to optimize sizes this time.
    if (isset($items[0]) && $item = $items[0]) {
      $this->extractFirstItem($settings, $item, reset($entities));
    }

    // Sets dimensions once, if cropped, to reduce costs with ton of images.
    // This is less expensive than re-defining dimensions per image.
    if (!empty($settings['_uri'])) {
      if (empty($settings['resimage'])) {
        $this->setImageDimensions($settings);
      }
      elseif (!empty($settings['resimage']) && !empty($settings['ratio']) && $settings['ratio'] == 'fluid') {
        $this->setResponsiveImageDimensions($settings);
      }
    }

    // Allows altering the settings.
    $this->getModuleHandler()->alter('blazy_settings', $build, $items);
  }

  /**
   * {@inheritdoc}
   */
  public function postBuildElements(array &$build, $items, array $entities = []) {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function extractFirstItem(array &$settings, $item, $entity = NULL) {
    if ($settings['field_type'] == 'image') {
      $settings['_item'] = $item;
      $settings['_uri'] = ($file = $item->entity) && empty($item->uri) ? $file->getFileUri() : $item->uri;
    }
    elseif ($entity && $entity->hasField('thumbnail') && $image = $entity->get('thumbnail')->first()) {
      if (isset($image->entity) && $file = $image->entity) {
        $settings['_item'] = $image;
        $settings['_uri'] = $file->getFileUri();
      }
    }

    // The first image dimensions to differ from individual item dimensions.
    if (!empty($settings['_item'])) {
      BlazyUtil::imageDimensions($settings, $settings['_item'], TRUE);
    }
  }

  /**
   * Sets dimensions once to reduce method calls, if image style contains crop.
   *
   * @param array $settings
   *   The settings being modified.
   */
  protected function setImageDimensions(array &$settings = []) {
    if (!isset($this->isImageDimensionSet[md5($settings['id'])])) {
      // If image style contains crop, sets dimension once, and let all inherit.
      if (!empty($settings['image_style']) && ($style = $this->isCrop($settings['image_style']))) {
        $settings = array_merge($settings, BlazyUtil::transformDimensions($style, $settings, TRUE));

        // Informs individual images that dimensions are already set once.
        $settings['_dimensions'] = TRUE;
      }

      $this->isImageDimensionSet[md5($settings['id'])] = TRUE;
    }
  }

  /**
   * Returns available image styles with crop in the name.
   */
  private function cropStyles() {
    if (!isset($this->cropStyles)) {
      $this->cropStyles = [];
      foreach ($this->entityLoadMultiple('image_style') as $style) {
        foreach ($style->getEffects() as $effect) {
          if (strpos($effect->getPluginId(), 'crop') !== FALSE) {
            $this->cropStyles[$style->getName()] = $style;
            break;
          }
        }
      }
    }
    return $this->cropStyles;
  }

  /**
   * {@inheritdoc}
   */
  public function isCrop($style) {
    if (!isset($this->isCrop[$style])) {
      $this->isCrop[$style] = $this->cropStyles() && isset($this->cropStyles()[$style]) ? $this->cropStyles()[$style] : FALSE;
    }
    return $this->isCrop[$style];
  }

}
