<?php

namespace Drupal\blazy\Dejavu;

use Drupal\Component\Utility\Html;
use Drupal\views\Views;

/**
 * A Trait common for optional views style plugins.
 */
trait BlazyStyleOptionsTrait {

  /**
   * The Views as options.
   *
   * @var array
   */
  protected $viewsOptions;

  /**
   * Returns available fields for select options.
   */
  public function getDefinedFieldOptions($defined_options = []) {
    $field_names = $this->displayHandler->getFieldLabels();
    $definition = [];
    $stages = [
      'blazy_media',
      'block_field',
      'colorbox',
      'entity_reference_entity_view',
      'gridstack_file',
      'gridstack_media',
      'photobox',
      'video_embed_field_video',
      'youtube_video',
    ];

    // Formatter based fields.
    $options = [];
    foreach ($this->displayHandler->getOption('fields') as $field => $handler) {
      // This is formatter based type, not actual field type.
      if (isset($handler['type'])) {
        switch ($handler['type']) {
          // @todo recheck other reasonable image-related formatters.
          case 'blazy':
          case 'image':
          case 'media':
          case 'media_thumbnail':
          case 'intense':
          case 'responsive_image':
          case 'video_embed_field_thumbnail':
          case 'video_embed_field_colorbox':
          case 'youtube_thumbnail':
            $options['images'][$field] = $field_names[$field];
            $options['overlays'][$field] = $field_names[$field];
            $options['thumbnails'][$field] = $field_names[$field];
            break;

          case 'list_key':
            $options['layouts'][$field] = $field_names[$field];
            break;

          case 'entity_reference_label':
          case 'text':
          case 'string':
          case 'link':
            $options['links'][$field] = $field_names[$field];
            $options['titles'][$field] = $field_names[$field];
            if ($handler['type'] != 'link') {
              $options['thumb_captions'][$field] = $field_names[$field];
            }
            break;
        }

        $classes = ['list_key', 'entity_reference_label', 'text', 'string'];
        if (in_array($handler['type'], $classes)) {
          $options['classes'][$field] = $field_names[$field];
        }

        $slicks = strpos($handler['type'], 'slick') !== FALSE;
        if ($slicks || in_array($handler['type'], $stages)) {
          $options['overlays'][$field] = $field_names[$field];
        }

        // Allows advanced formatters/video as the main image replacement.
        // They are not reasonable for thumbnails, but main images.
        // Note: Certain Responsive image has no ID at Views, possibly a bug.
        if (in_array($handler['type'], $stages)) {
          $options['images'][$field] = $field_names[$field];
        }
      }

      // Content: title is not really a field, unless title.module installed.
      if (isset($handler['field'])) {
        if ($handler['field'] == 'title') {
          $options['classes'][$field] = $field_names[$field];
          $options['titles'][$field] = $field_names[$field];
          $options['thumb_captions'][$field] = $field_names[$field];
        }

        if (in_array($handler['field'], ['nid', 'nothing', 'view_node'])) {
          $options['links'][$field] = $field_names[$field];
          $options['titles'][$field] = $field_names[$field];
        }

        if (in_array($handler['field'], ['created'])) {
          $options['classes'][$field] = $field_names[$field];
        }

        $blazies = strpos($handler['field'], 'blazy_') !== FALSE;
        if ($blazies) {
          $options['images'][$field] = $field_names[$field];
          $options['overlays'][$field] = $field_names[$field];
          $options['thumbnails'][$field] = $field_names[$field];
        }
      }

      // Captions can be anything to get custom works going.
      $options['captions'][$field] = $field_names[$field];
    }

    $definition['plugin_id'] = $this->getPluginId();
    $definition['settings'] = $this->options;
    $definition['current_view_mode'] = $this->view->current_display;
    $definition['_views'] = TRUE;

    // Provides the requested fields based on available $options.
    foreach ($defined_options as $key) {
      $definition[$key] = isset($options[$key]) ? $options[$key] : [];
    }

    $contexts = [
      'handler' => $this->displayHandler,
      'view' => $this->view,
    ];
    $this->blazyManager->getModuleHandler()->alter('blazy_views_field_options', $definition, $contexts);

    return $definition;
  }

  /**
   * Returns an array of views for option list.
   *
   * Cannot use Views::getViewsAsOptions() as we need to limit to something.
   */
  protected function getViewsAsOptions($plugin = 'html_list') {
    if (!isset($this->viewsOptions[$plugin])) {
      $options = [];

      // Convert list of objects to options for the form.
      foreach (Views::getEnabledViews() as $view_name => $view) {
        foreach ($view->get('display') as $id => $display) {
          $valid = isset($display['display_options']['style']['type']) && $display['display_options']['style']['type'] == $plugin;
          if ($valid) {
            $options[$view_name . ':' . $id] = $view->label() . ' (' . $display['display_title'] . ')';
          }
        }
      }
      $this->viewsOptions[$plugin] = $options;
    }
    return $this->viewsOptions[$plugin];
  }

  /**
   * Returns the string values for the expected Title, ET label, List, Term.
   *
   * @todo re-check this, or if any consistent way to retrieve string values.
   */
  public function getFieldString($row, $field_name, $index, $clean = TRUE) {
    $values = [];

    // Content title/List/Text, either as link or plain text.
    if ($value = $this->getFieldValue($index, $field_name)) {
      $value = is_array($value) ? array_filter($value) : $value;

      // Entity reference label where the above $value can be term ID.
      if ($markup = $this->getField($index, $field_name)) {
        $value = is_object($markup) ? trim(strip_tags($markup->__toString())) : $value;
      }

      if (is_string($value)) {
        // Only respects tags with default CSV, just too much to worry about.
        if (strpos($value, ',') !== FALSE) {
          $tags = explode(',', $value);
          $rendered_tags = [];
          foreach ($tags as $tag) {
            $tag = trim($tag);
            $rendered_tags[] = $clean ? Html::cleanCssIdentifier(mb_strtolower($tag)) : $tag;
          }
          $values[$index] = implode(' ', $rendered_tags);
        }
        else {
          $values[$index] = $clean ? Html::cleanCssIdentifier(mb_strtolower($value)) : $value;
        }
      }
      else {
        $value = isset($value[0]['value']) && !empty($value[0]['value']) ? $value[0]['value'] : '';
        if ($value) {
          $values[$index] = $clean ? Html::cleanCssIdentifier(mb_strtolower($value)) : $value;
        }
      }
    }

    return $values;
  }

}
