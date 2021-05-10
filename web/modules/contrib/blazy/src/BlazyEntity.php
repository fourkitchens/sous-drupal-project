<?php

namespace Drupal\blazy;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides common entity utilities to work with field details.
 */
class BlazyEntity implements BlazyEntityInterface {

  /**
   * The blazy oembed service.
   *
   * @var object
   */
  protected $oembed;

  /**
   * The blazy manager service.
   *
   * @var object
   */
  protected $blazyManager;

  /**
   * Constructs a BlazyFormatter instance.
   */
  public function __construct(BlazyOEmbedInterface $oembed) {
    $this->oembed = $oembed;
    $this->blazyManager = $oembed->blazyManager();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('blazy.oembed')
    );
  }

  /**
   * Returns the blazy oembed service.
   */
  public function oembed() {
    return $this->oembed;
  }

  /**
   * Returns the blazy manager service.
   */
  public function blazyManager() {
    return $this->blazyManager;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array &$data, $entity, $fallback = '') {
    if (!$entity instanceof EntityInterface) {
      return [];
    }

    // Supports core Media via Drupal\blazy\BlazyOEmbed::getMediaItem().
    $data['settings'] = empty($data['settings']) ? [] : $data['settings'];
    $this->blazyManager->getCommonSettings($data['settings']);
    $this->oembed->getMediaItem($data, $entity);
    $settings = &$data['settings'];

    // Made Responsive image also available outside formatters here.
    if (!empty($settings['resimage']) && $settings['ratio'] == 'fluid') {
      $this->blazyManager->setResponsiveImageDimensions($settings, FALSE);
    }

    // Only pass to Blazy for known entities related to File or Media.
    if (in_array($entity->getEntityTypeId(), ['file', 'media'])) {
      /** @var Drupal\image\Plugin\Field\FieldType\ImageItem $item */
      if (empty($data['item'])) {
        $data['content'][] = $this->getEntityView($entity, $settings, $fallback);
      }

      // Pass it to Blazy for consistent markups.
      $build = $this->blazyManager->getBlazy($data);

      // Allows top level elements to load Blazy once rather than per field.
      // This is still here for non-supported Views style plugins, etc.
      if (empty($settings['_detached'])) {
        $load = $this->blazyManager->attach($settings);
        $build['#attached'] = empty($build['#attached']) ? $load : NestedArray::mergeDeep($build['#attached'], $load);
      }
    }
    else {
      $build = $this->getEntityView($entity, $settings, $fallback);
    }

    $this->blazyManager->getModuleHandler()->alter('blazy_build_entity', $build, $entity, $settings);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityView($entity, array $settings = [], $fallback = '') {
    if ($entity instanceof EntityInterface) {
      $entity_type_id = $entity->getEntityTypeId();
      $view_mode      = $settings['view_mode'] = empty($settings['view_mode']) ? 'default' : $settings['view_mode'];
      $langcode       = $entity->language()->getId();
      $fallback       = $fallback && is_string($fallback) ? ['#markup' => '<div class="is-fallback">' . $fallback . '</div>'] : $fallback;

      // If entity has view_builder handler.
      if ($this->blazyManager->getEntityTypeManager()->hasHandler($entity_type_id, 'view_builder')) {
        $build = $this->blazyManager->getEntityTypeManager()->getViewBuilder($entity_type_id)->view($entity, $view_mode, $langcode);

        // @todo figure out why video_file empty, this is blatant assumption.
        if ($entity_type_id == 'file') {
          try {
            $build = $this->getFileOrMedia($entity, $settings) ?: $build;
          }
          catch (\Exception $ignore) {
            // Do nothing, no need to be chatty in mischievous deeds.
          }
        }
        return $build ?: $fallback;
      }
      else {
        // If module implements own {entity_type}_view.
        // @todo remove due to being deprecated at D8.7.
        // See https://www.drupal.org/node/3033656
        $view_hook = $entity_type_id . '_view';
        if (is_callable($view_hook)) {
          return $view_hook($entity, $view_mode, $langcode);
        }
      }
    }
    return $fallback;
  }

  /**
   * Returns file view or media due to being empty returned by view builder.
   *
   * @todo make it usable for other file-related entities.
   */
  public function getFileOrMedia($file, array $settings, $use_file = TRUE) {
    list($type,) = explode('/', $file->getMimeType(), 2);
    if ($type == 'video') {
      // As long as you are not being too creative by renaming or changing
      // fields provided by core, this should be your good friend.
      $settings['media_source'] = 'video_file';
      $settings['source_field'] = 'field_media_video_file';
    }
    if (!empty($settings['source_field']) && isset($settings['media_source'])
      && $media = $this->blazyManager->getEntityTypeManager()->getStorage('media')->loadByProperties([$settings['source_field'] => ['fid' => $file->id()]])) {
      if ($media = reset($media)) {
        return $use_file ? BlazyMedia::build($media, $settings) : $media;
      }
    }
    return [];
  }

  /**
   * Returns the string value of the fields: link, or text.
   */
  public function getFieldValue($entity, $field_name, $langcode) {
    if ($entity->hasField($field_name)) {
      if ($entity->hasTranslation($langcode)) {
        // If the entity has translation, fetch the translated value.
        return $entity->getTranslation($langcode)->get($field_name)->getValue();
      }

      // Entity doesn't have translation, fetch original value.
      return $entity->get($field_name)->getValue();
    }
    return NULL;
  }

  /**
   * Returns the string value of the fields: link, or text.
   */
  public function getFieldString($entity, $field_name, $langcode, $clean = TRUE) {
    if ($entity->hasField($field_name)) {
      $values = $this->getFieldValue($entity, $field_name, $langcode);

      // Can be text, or link field.
      $string = isset($values[0]['uri']) ? $values[0]['uri'] : (isset($values[0]['value']) ? $values[0]['value'] : '');

      if ($string && is_string($string)) {
        $string = $clean ? strip_tags($string, '<a><strong><em><span><small>') : Xss::filter($string, BlazyDefault::TAGS);
        return trim($string);
      }
    }
    return '';
  }

  /**
   * Returns the formatted renderable array of the field.
   */
  public function getFieldRenderable($entity, $field_name, $view_mode, $multiple = TRUE) {
    if ($entity->hasField($field_name) && !empty($entity->{$field_name}->view($view_mode)[0])) {
      $view = $entity->get($field_name)->view($view_mode);

      // Prevents quickedit to operate here as otherwise JS error.
      // @see 2314185, 2284917, 2160321.
      // @see quickedit_preprocess_field().
      // @todo Remove when it respects plugin annotation.
      $view['#view_mode'] = '_custom';
      $weight = isset($view['#weight']) ? $view['#weight'] : 0;

      // Intentionally clean markups as this is not meant for vanilla.
      if ($multiple) {
        $items = [];
        foreach (Element::children($view) as $key) {
          $items[$key] = $entity->get($field_name)->view($view_mode)[$key];
        }

        $items['#weight'] = $weight;
        return $items;
      }
      return $view[0];
    }

    return [];
  }

  /**
   * Returns the text or link value of the fields: link, or text.
   */
  public function getFieldTextOrLink($entity, $field_name, $settings, $multiple = TRUE) {
    if ($entity->hasField($field_name)) {
      $langcode = $settings['langcode'];
      if ($text = $this->getFieldValue($entity, $field_name, $langcode)) {
        if (!empty($text[0]['value']) && !isset($text[0]['uri'])) {
          // Prevents HTML-filter-enabled text from having bad markups (h2 > p),
          // except for a few reasonable tags acceptable within H2 tag.
          $text = $this->getFieldString($entity, $field_name, $langcode, FALSE);
        }
        elseif (isset($text[0]['uri']) && !empty($text[0]['title'])) {
          $text = $this->getFieldRenderable($entity, $field_name, $settings['view_mode'], $multiple);
        }

        // Prevents HTML-filter-enabled text from having bad markups
        // (h2 > p), save for few reasonable tags acceptable within H2 tag.
        return is_string($text)
          ? ['#markup' => strip_tags($text, '<a><strong><em><span><small>')]
          : $text;
      }
    }
    return [];
  }

}
